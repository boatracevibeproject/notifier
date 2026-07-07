<?php

declare(strict_types=1);

namespace BVP\Notifier;

use Carbon\CarbonImmutable as Carbon;

/**
 * @author shimomo
 */
final class RaceProgramFormatter
{
    /**
     * @var string
     */
    private const RACE_LIST_URL_FORMAT = 'https://www.boatrace.jp/owpc/pc/race/racelist?rno=%d&jcd=%02d&hd=%s';

    /**
     * @param \Carbon\CarbonImmutable $date
     * @param int<1, 24> $stadiumNumber
     * @param int<1, 12> $raceNumber
     * @param array<int, array<non-empty-string, int|float|string>> $racers
     */
    public function format(Carbon $date, int $stadiumNumber, int $raceNumber, array $racers): string
    {
        $rows = $this->buildRows($racers);
        $url = $this->buildRaceListUrl($date, (int) $stadiumNumber, (int) $raceNumber);

        $lines = [];
        $lines[] = "【{$stadiumNumber} {$raceNumber}R】{$url}";
        $lines[] = '```';

        foreach ($rows as $row) {
            $lines[] = $this->formatDataRow($row);
        }

        $lines[] = '```';

        return implode("\n", $lines);
    }

    /**
     * @param \Carbon\CarbonImmutable $date
     * @param int<1, 24> $stadiumNumber
     * @param int<1, 12> $raceNumber
     * @return string
     */
    private function buildRaceListUrl(Carbon $date, int $stadiumNumber, int $raceNumber): string
    {
        return sprintf(self::RACE_LIST_URL_FORMAT, $raceNumber, $stadiumNumber, $date->format('Ymd'));
    }

    /**
     * @param array{
     *   entry_number: int,
     *   name: string,
     *   rank_number_source: string,
     *   branch_number_source: string,
     *   age: int,
     *   weight: float,
     *   national_win_rate: float
     * } $row
     */
    private function formatDataRow(array $row): string
    {
        return implode(' | ', [
            "{$row['entry_number']}枠",
            $row['name'],
            $row['rank_number_source'],
            $row['branch_number_source'],
            "{$row['age']}歳",
            sprintf('%.1fkg', $row['weight']),
            sprintf('勝率%.2f', $row['national_win_rate']),
        ]);
    }

    /**
     * @param array<int, array<non-empty-string, int|float|string>> $racers
     * @return list<array{
     *   entry_number: int,
     *   name: string,
     *   rank_number_source: string,
     *   branch_number_source: string,
     *   age: int,
     *   weight: float,
     *   national_win_rate: float
     * }>
     */
    private function buildRows(array $racers): array
    {
        $rows = array_map(
            static fn (array $racer): array => [
                'entry_number' => (int) $racer['entry_number'],
                'name' => (string) $racer['name'],
                'rank_number_source' => (string) $racer['rank_number_source'],
                'branch_number_source' => (string) $racer['branch_number_source'],
                'age' => (int) $racer['age'],
                'weight' => (float) $racer['weight'],
                'national_win_rate' => (float) $racer['national_win_rate'],
            ],
            array_values($racers),
        );

        usort($rows, static fn (array $a, array $b): int => $a['entry_number'] <=> $b['entry_number']);

        return $rows;
    }
}
