<?php

declare(strict_types=1);

namespace BVP\Notifier\Chunkers;

/**
 * @author shimomo
 */
final class EmbedChunker
{
    private const MAX_EMBEDS_PER_MESSAGE = 10;
    private const MAX_TOTAL_LENGTH = 6000;

    /**
     * @param list<\BVP\Notifier\Channels\Embed> $embeds
     * @return list<list<\BVP\Notifier\Channels\Embed>>
     */
    public function chunk(array $embeds): array
    {
        $chunks = [];
        $current = [];
        $currentLength = 0;

        foreach ($embeds as $embed) {
            $embedLength = $embed->totalLength();
            $wouldExceedCount = count($current) >= self::MAX_EMBEDS_PER_MESSAGE;
            $wouldExceedLength = $currentLength + $embedLength > self::MAX_TOTAL_LENGTH;

            if ($current !== [] && ($wouldExceedCount || $wouldExceedLength)) {
                $chunks[] = $current;
                $current = [];
                $currentLength = 0;
            }

            $current[] = $embed;
            $currentLength += $embedLength;
        }

        if ($current !== []) {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
