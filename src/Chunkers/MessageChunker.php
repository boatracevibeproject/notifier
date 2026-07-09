<?php

declare(strict_types=1);

namespace BVP\Notifier\Chunkers;

/**
 * @author shimomo
 */
final class MessageChunker
{
    private const MAX_LENGTH = 2000;

    /**
     * @param list<string> $fragments
     * @param string $separator
     * @return list<string>
     */
    public function chunk(array $fragments, string $separator = "\n"): array
    {
        $chunks = [];
        $current = '';

        foreach ($fragments as $fragment) {
            $candidate = $current === '' ? $fragment : $current . $separator . $fragment;

            if (mb_strlen($candidate) > self::MAX_LENGTH) {
                if ($current !== '') {
                    $chunks[] = $current;
                }

                $current = $fragment;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
