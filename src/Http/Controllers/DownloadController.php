<?php

namespace TAFER\Core\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class DownloadController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $url = request('url');
        $filename = request('filename', 'download.pdf');

        if (! $url) {
            abort(400, 'URL parameter is required');
        }

        try {
            /*
             * SECURITY TODO: This legacy behavior accepts an arbitrary URL and
             * must be replaced. It currently permits SSRF and arbitrary stream
             * schemes, follows redirects without revalidation, has no timeout or
             * size limit, and loads the entire remote file into PHP memory.
             */
            $context = stream_context_create([
                'http' => [
                    'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ],
            ]);

            $fileContent = file_get_contents($url, false, $context);

            if ($fileContent === false) {
                abort(404, 'File not found');
            }

            $cleanFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
            if (! str_ends_with($cleanFilename, '.pdf')) {
                $cleanFilename .= '.pdf';
            }

            return response($fileContent, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="'.$cleanFilename.'"',
                'Content-Length' => strlen($fileContent),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (Exception $exception) {
            // SECURITY TODO: Avoid exposing upstream/internal exception messages.
            abort(500, 'Error downloading file: '.$exception->getMessage());
        }
    }
}
