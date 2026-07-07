<?php

declare(strict_types=1);

namespace BVP\Notifier;

use Carbon\CarbonImmutable as Carbon;
use RuntimeException;

/**
 * @author shimomo
 */
final class LadyRacerRepository
{
    /**
     * @var string
     */
    private const PATH_FORMAT = __DIR__ . '/../docs/%s/%s.json';

    /**
     * @param \Carbon\CarbonImmutable $date
     * @return array
     * @throws \RuntimeException
     */
    public function findNumbers(Carbon $date): array
    {
        $path = sprintf(self::PATH_FORMAT, $date->format('Y'), $date->format('Ymd'));

        if (!file_exists($path)) {
            throw new RuntimeException("Failed to load JSON: file not found: {$path}");
        }

        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("Failed to load JSON: could not read file: {$path}");
        }

        $payload = JsonDecoder::decode($json, $path);

        $ladyRacers = $payload['lady_racers'] ?? [];

        return array_column($ladyRacers, 'number');
    }
}
