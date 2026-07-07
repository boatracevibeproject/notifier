<?php

declare(strict_types=1);

namespace BVP\Notifier;

use RuntimeException;

/**
 * @author shimomo
 */
final class JsonDecoder
{
    /**
     * @param string $json
     * @param string $source
     * @return array
     * @throws \RuntimeException
     */
    public static function decode(string $json, string $source): array
    {
        $payload = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                "Failed to decode JSON from {$source}: " . json_last_error_msg()
            );
        }

        if (!is_array($payload)) {
            throw new RuntimeException(
                "Failed to decode JSON from {$source}: payload is not an array"
            );
        }

        return $payload;
    }
}
