<?php

declare(strict_types=1);

namespace W4\OS\Support;

final class JsonPrinter
{
    /**
     * @param array<string, mixed> $data
     */
    public static function print(array $data): void
    {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}
