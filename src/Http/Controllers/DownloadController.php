<?php

namespace TAFER\Core\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('throttle:30,1'),
        ];
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $url = (string) $request->query('url');
        $filename = (string) $request->query('filename', 'download.pdf');

        if (trim($url) === '') {
            abort(400, 'URL parameter is required');
        }

        // Validación de esquema y host (Anti-SSRF)
        $parsed = parse_url($url);
        if ($parsed === false || ! isset($parsed['scheme'], $parsed['host'])) {
            abort(404, 'Invalid URL');
        }

        if (strtolower($parsed['scheme']) !== 'https') {
            abort(404, 'Only HTTPS is allowed');
        }

        $host = strtolower($parsed['host']);
        if (! $this->isAllowedHost($host)) {
            abort(404, 'Host not permitted');
        }

        // Sanitizacion del nombre del Archivo
        $cleanFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        if (! str_ends_with(strtolower($cleanFilename), '.pdf')) {
            $cleanFilename .= '.pdf';
        }

        $maxBytes = config('tafer.download.max_size_bytes', 25 * 1024 * 1024);
        $timeout = config('tafer.download.timeout_seconds', 8);
        $connectTimeout = config('tafer.download.connect_timeout_seconds', 3);

        try {
            // Inspeccion previa de cabeceras
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])
                ->timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withOptions(['stream' => true])
                ->get($url);

            if (! $response->successful()) {
                abort(404, 'File not found');
            }

            $contentLength = $response->header('Content-Length');
            if ($contentLength !== null && is_numeric($contentLength) && (int) $contentLength > $maxBytes) {
                abort(413, 'File size exceeds maximum permitted limit');
            }

            // 4. Descargar en Streaming (Anti-DoS y control de RAM)
            $psrBody = $response->toPsrResponse()->getBody();

            return response()->streamDownload(
                callback: function () use ($psrBody, $maxBytes) {
                    $bytesStreamed = 0;
                    try {
                        while (! $psrBody->eof()) {
                            $chunk = $psrBody->read(1024 * 64); // bloques de 64kb
                            $bytesStreamed += strlen($chunk);

                            if ($bytesStreamed > $maxBytes) {
                                break; // Corta si el CDN envía más datos de los permitidos
                            }

                            echo $chunk;

                            // vaciado seguro
                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();

                        }
                    } finally {
                        $psrBody->close();
                    }
                },
                name: $cleanFilename,
                headers: [
                    'Content-Type' => 'application/pdf',
                    'cache-control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    'X-Content-Type-Options' => 'nosniff',
                ]
            );
        } catch (Exception $exception) {
            Log::warning('DownloadController: failed to fetch remote resource', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            abort(404, 'Unable to download file');
        }
    }

    /**
     * Valida que el host sea exactamente iguala uno autorizado o termine en un sufijo permitido
     */
    private function isAllowedHost(string $host): bool
    {
        $allowedHosts = (array) config('tafer.download.allowed_hosts', ['a.storyblok.com']);
        if (in_array($host, $allowedHosts, true)) {
            return true;
        }

        $allowedSuffixes = (array) config('tafer.download.allowed_host_suffixes', ['.storyblok.com']);
        foreach ($allowedSuffixes as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
