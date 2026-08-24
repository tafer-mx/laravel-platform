<?php

declare(strict_types=1);

const PACKAGE_NAME = 'tafer-mx/laravel-platform';
const LOCAL_COMPOSER_FILE = 'composer.local.json';
const LOCAL_LOCK_FILE = 'composer.local.lock';

function fail(string $message): never
{
    fwrite(STDERR, "Error: {$message}".PHP_EOL);
    exit(1);
}

/** @return array<string, mixed> */
function readJson(string $path): array
{
    if (! is_file($path)) {
        fail("No existe {$path}.");
    }

    try {
        $contents = file_get_contents($path);

        if ($contents === false) {
            fail("No se pudo leer {$path}.");
        }

        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fail("JSON inválido en {$path}: {$exception->getMessage()}");
    }

    if (! is_array($decoded)) {
        fail("{$path} no contiene un objeto JSON.");
    }

    return $decoded;
}

function writeJson(string $path, array $contents): void
{
    try {
        $encoded = json_encode(
            $contents,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    } catch (JsonException $exception) {
        fail("No se pudo serializar {$path}: {$exception->getMessage()}");
    }

    if (file_put_contents($path, $encoded.PHP_EOL) === false) {
        fail("No se pudo escribir {$path}.");
    }
}

function syntheticVersion(string $constraint): string
{
    if (preg_match('/^\^(\d+)\.(\d+)(?:\.\d+)?$/', trim($constraint), $matches) !== 1) {
        fail(
            "El constraint productivo '{$constraint}' no es compatible con el flujo local. "
            .'Se esperaba un constraint como ^0.4.',
        );
    }

    return "{$matches[1]}.{$matches[2]}.999";
}

function repositoryPath(string $consumerRoot, string $platformRoot): string
{
    if (dirname($consumerRoot) === dirname($platformRoot)) {
        return '../'.basename($platformRoot);
    }

    return $platformRoot;
}

function prepare(string $consumerRoot, string $platformRoot): void
{
    $composerPath = $consumerRoot.'/composer.json';
    $lockPath = $consumerRoot.'/composer.lock';
    $composer = readJson($composerPath);
    $platformComposer = readJson($platformRoot.'/composer.json');

    if (($platformComposer['name'] ?? null) !== PACKAGE_NAME) {
        fail("{$platformRoot} no es un checkout de ".PACKAGE_NAME.'.');
    }

    $constraint = $composer['require'][PACKAGE_NAME] ?? null;

    if (! is_string($constraint) || $constraint === '') {
        fail('El composer.json productivo no requiere '.PACKAGE_NAME.'.');
    }

    if (! is_file($lockPath)) {
        fail('Falta composer.lock; el flujo local necesita el lock productivo como base.');
    }

    $localRepository = [
        'type' => 'path',
        'url' => repositoryPath($consumerRoot, $platformRoot),
        'canonical' => true,
        'options' => [
            'symlink' => true,
            'versions' => [
                PACKAGE_NAME => syntheticVersion($constraint),
            ],
            'reference' => 'none',
        ],
    ];

    $repositories = $composer['repositories'] ?? [];

    if (! is_array($repositories)) {
        fail('La propiedad repositories de composer.json debe ser un arreglo.');
    }

    $composer['repositories'] = array_values(array_filter(
        $repositories,
        static fn (mixed $repository): bool => ! (
            is_array($repository)
            && ($repository['type'] ?? null) === 'path'
            && str_contains((string) ($repository['url'] ?? ''), 'laravel-platform')
        ),
    ));
    array_unshift($composer['repositories'], $localRepository);

    writeJson($consumerRoot.'/'.LOCAL_COMPOSER_FILE, $composer);

    if (! copy($lockPath, $consumerRoot.'/'.LOCAL_LOCK_FILE)) {
        fail('No se pudo crear '.LOCAL_LOCK_FILE.'.');
    }

    fwrite(STDOUT, sprintf(
        'Configuración local preparada para %s (%s).%s',
        PACKAGE_NAME,
        $localRepository['options']['versions'][PACKAGE_NAME],
        PHP_EOL,
    ));
}

function status(string $consumerRoot, string $platformRoot): void
{
    $installedPath = $consumerRoot.'/vendor/'.PACKAGE_NAME;
    $installedRealPath = realpath($installedPath);
    $platformRealPath = realpath($platformRoot);

    if ($installedRealPath !== false && $platformRealPath !== false && $installedRealPath === $platformRealPath) {
        fwrite(STDOUT, 'LOCAL: vendor/'.PACKAGE_NAME." enlaza a {$platformRealPath}.".PHP_EOL);

        return;
    }

    $version = 'desconocida';
    $lockPath = $consumerRoot.'/composer.lock';

    if (is_file($lockPath)) {
        $lock = readJson($lockPath);

        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
            if (is_array($package) && ($package['name'] ?? null) === PACKAGE_NAME) {
                $version = (string) ($package['version'] ?? $version);
                break;
            }
        }
    }

    if ($installedRealPath === false) {
        fwrite(STDOUT, 'NO INSTALADO: ejecuta composer install o composer platform:local.'.PHP_EOL);

        return;
    }

    fwrite(STDOUT, 'PUBLICADO: vendor/'.PACKAGE_NAME." usa {$version} desde Composer.".PHP_EOL);
}

function clean(string $consumerRoot): void
{
    foreach ([LOCAL_COMPOSER_FILE, LOCAL_LOCK_FILE] as $filename) {
        $path = $consumerRoot.'/'.$filename;

        if (is_file($path) && ! unlink($path)) {
            fail("No se pudo eliminar {$path}.");
        }
    }

    fwrite(STDOUT, 'Configuración local eliminada; Composer vuelve a usar composer.json y composer.lock.'.PHP_EOL);
}

$action = $argv[1] ?? '';
$consumerArgument = $argv[2] ?? getcwd();
$consumerRoot = is_string($consumerArgument) ? realpath($consumerArgument) : false;
$platformRoot = realpath(dirname(__DIR__));

if ($consumerRoot === false || ! is_dir($consumerRoot)) {
    fail('No se pudo resolver la raíz del proyecto consumidor.');
}

if ($platformRoot === false) {
    fail('No se pudo resolver la raíz de laravel-platform.');
}

match ($action) {
    'prepare' => prepare($consumerRoot, $platformRoot),
    'status' => status($consumerRoot, $platformRoot),
    'clean' => clean($consumerRoot),
    default => fail('Uso: php local-platform.php prepare|status|clean [consumer-root]'),
};
