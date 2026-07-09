<?php

declare(strict_types=1);

namespace BVP\Notifier;

use BVP\Notifier\Channels\Embed;
use Carbon\CarbonImmutable as Carbon;

/**
 * @author shimomo
 */
final class Formatter
{
    private const EMBED_COLOR = 0xFF6F91;

    /**
     * @param \Carbon\CarbonImmutable $date
     * @param int<1, 24> $stadiumNumber
     * @param int<1, 12> $raceNumber
     * @param \Carbon\CarbonImmutable $closedAt
     * @param array<int<1, 6>, array{
     *   entry_number: int<1, 6>,
     *   name: ?string,
     *   rank_number: ?int<1, 4>,
     *   branch_number: ?int<1, 47>,
     *   age: ?int,
     *   weight: ?float,
     *   national_win_rate: ?float
     * }> $racers
     */
    public function format(
        Carbon $date,
        int $stadiumNumber,
        int $raceNumber,
        Carbon $closedAt,
        array $racers,
    ): string {
        $lines = [];

        $lines[] = "【{$date->format('Y-m-d')} {$stadiumNumber} {$raceNumber}R】{$closedAt->format('H:i')}";
        $lines[] = '```';

        foreach ($racers as $racer) {
            $lines[] = implode(' | ', [
                sprintf('%01d 号艇', $racer['entry_number'] ?? '-'),
                sprintf('%s', $racer['name'] ?? '-'),
                sprintf('%s 級', $racer['rank_number'] ?? '-'),
                sprintf('%s', $racer['branch_number'] ?? '-'),
                sprintf('%02d 歳', $racer['age'] ?? '-'),
                sprintf('%.1f kg', $racer['weight'] ?? '-'),
                sprintf('勝率 %.2f', $racer['national_win_rate'] ?? '-'),
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
        $fields = array_map(fn(array $row): array => [
            'inline' => false,
            'name' => "{$row['entry_number']} 号艇 {$row['name']}",
            'value' => implode("\n", [
                implode(' / ', [
                    "{$row['rank_number']} 級",
                    "{$row['branch_number']}",
                    "{$row['age']} 歳",
                    sprintf('%.1f kg', $row['weight'] ?? '-'),
                    "F{$row['flying_count']}",
                    "L{$row['late_count']}",
                    sprintf('平均ST %.2f', $row['average_start_timing'] ?? '-'),
                ]),
                sprintf(
                    '全国: 勝率 %.2f / 2連対 %.2f%% / 3連対 %.2f%%',
                    $row['national_win_rate'] ?? '-',
                    $row['national_top_2_percent'] ?? '-',
                    $row['national_top_3_percent'] ?? '-',
                ),
                sprintf(
                    '当地: 勝率 %.2f / 2連対 %.2f%% / 3連対 %.2f%%',
                    $row['local_win_rate'] ?? '-',
                    $row['local_top_2_percent'] ?? '-',
                    $row['local_top_3_percent'] ?? '-',
                ),
                sprintf(
                    'モーター: %d / 2連対 %.2f%% / 3連対 %.2f%%',
                    $row['motor_number'] ?? '-',
                    $row['motor_top_2_percent'] ?? '-',
                    $row['motor_top_3_percent'] ?? '-',
                ),
                sprintf(
                    'ボート: %d / 2連対 %.2f%% / 3連対 %.2f%%',
                    $row['boat_number'] ?? '-',
                    $row['boat_top_2_percent'] ?? '-',
                    $row['boat_top_3_percent'] ?? '-',
                ),
            ]),
        ], $racers);

        return new Embed(
            title: "【{$date->format('Y-m-d')} {$stadiumNumber} {$raceNumber} R】",
            description: "締切時刻: {$closedAt->format('H:i')}",
            fields: $fields,
            color: $color,
        );
    }
}
