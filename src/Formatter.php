<?php

declare(strict_types=1);

namespace BVP\Notifier;

use BVP\Notifier\Channels\Embed;
use BVP\Stadium\Stadium;
use Carbon\CarbonImmutable as Carbon;

/**
 * @author shimomo
 */
final class Formatter
{
    private const BASE_URL = 'https://www.boatrace.jp/owpc/pc/race/racelist?hd=%s&jcd=%02d&rno=%d';
    private const EMBED_COLOR = 0xFF6F91;

    /**
     * @param \Carbon\CarbonImmutable $date
     * @param int<1, 24> $stadiumNumber
     * @param int<1, 12> $raceNumber
     * @param \Carbon\CarbonImmutable $closedAt
     * @param array<int<1, 6>, array<string, int|float|string|null>> $racers
     * @return string
     */
    public function format(
        Carbon $date,
        int $stadiumNumber,
        int $raceNumber,
        Carbon $closedAt,
        array $racers,
    ): string {
        $stadiumName = Stadium::fromNumber($stadiumNumber)?->shortName();

        $title = "{$date->format('Y-m-d')} {$stadiumName} {$raceNumber} R";
        $description = "締切時刻: {$closedAt->format('H:i')}";

        $lines = [];

        $lines[] = "{$title}\n{$description}";
        $lines[] = '```';

        foreach ($racers as $racer) {
                $lines[] = implode(' | ', [
                sprintf('%d', $racer['entry_number'] ?? '-'),
                sprintf('%s', $racer['name'] ?? '-'),
                sprintf('%s級', match ($racer['rank_number'] ?? '') {
                    1 => 'A1',
                    2 => 'A2',
                    3 => 'B1',
                    4 => 'B2',
                    default => '-',
                }),
                sprintf('F%d', $racer['flying_count'] ?? '-'),
                sprintf('L%d', $racer['late_count'] ?? '-'),
            ]);
        }

        $lines[] = '```';

        return implode("\n", $lines);
    }

    /**
     * @param \Carbon\CarbonImmutable $date
     * @param int<1, 24> $stadiumNumber
     * @param int<1, 12> $raceNumber
     * @param \Carbon\CarbonImmutable $closedAt
     * @param array<int<1, 6>, array<string, int|float|string|null>> $racers
     * @param int $color
     * @return \BVP\Notifier\Channels\Embed
     */
    public function formatEmbed(
        Carbon $date,
        int $stadiumNumber,
        int $raceNumber,
        Carbon $closedAt,
        array $racers,
        int $color = self::EMBED_COLOR,
    ): Embed {
        $values = array_map(fn(array $racer): string => implode(' | ', [
            sprintf('%d', $racer['entry_number'] ?? '-'),
            sprintf('%s', $racer['name'] ?? '-'),
            sprintf('%s級', match ($racer['rank_number'] ?? '') {
                1 => 'A1',
                2 => 'A2',
                3 => 'B1',
                4 => 'B2',
                default => '-',
            }),
            sprintf('F%d/L%d', $racer['flying_count'] ?? '-', $racer['late_count'] ?? '-'),
        ]), $racers);

        $stadiumName = Stadium::fromNumber($stadiumNumber)?->shortName();

        $title = "{$date->format('Y-m-d')} {$stadiumName} {$raceNumber} R";
        $url = sprintf(self::BASE_URL, $date->format('Ymd'), $stadiumNumber, $raceNumber);
        $description = "締切時刻: {$closedAt->format('H:i')}";

        $name = '出走表';
        $value = "```\n" . implode("\n", $values) . "\n```";

        return new Embed(
            title: $title,
            url: $url,
            description: $description,
            fields: [[
                'inline' => false,
                'name' => $name,
                'value' => $value,
            ]],
            color: $color,
        );
    }
}
