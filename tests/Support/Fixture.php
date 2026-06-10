<?php

namespace Tests\Support;

use RuntimeException;

final class Fixture
{
    public static function getTestFixture(string $filename): array
    {
        $path = __DIR__.'/..'.'/fixtures/'.ltrim($filename, '/');

        if (! file_exists($path)) {
            throw new RuntimeException("Fixture not found: {$path}");
        }

        $contents = file_get_contents($path);

        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Invalid JSON fixture: '.json_last_error_msg()
            );
        }

        return $data;
    }
}
