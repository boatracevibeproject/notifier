<?php

declare(strict_types=1);

namespace BVP\Notifier;

use Carbon\CarbonImmutable as Carbon;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

/**
 * @author shimomo
 */
final class Fetcher
{
    private const URL_FORMAT = 'https://boatraceopenapi.github.io/api-mirror/v1/%s/%s.json';

    /**
     * @param \GuzzleHttp\ClientInterface $client
     */
    public function __construct(
        private readonly ClientInterface $client,
    ) {
        //
    }

    /**
     * @param \Carbon\CarbonImmutable $date
     * @return array{
     *   stadiums: array<int<1, 24>, array{
     *     races: array<int<1, 12>, array{
     *       date: string,
     *       stadium_number: int<1, 24>,
     *       race_number: int<1, 12>,
     *       closed_at: string,
     *       racers: array<int<1, 6>, array{
     *         number: int,
     *       }>,
     *     }>,
     *   }>,
     * }
     * @throws \RuntimeException
     */
    public function fetchPrograms(Carbon $date): array
    {
        $url = sprintf(self::URL_FORMAT, $date->format('Y'), $date->format('Ymd'));

        try {
            $response = $this->client->request('GET', $url);
        } catch (GuzzleException $exception) {
            throw new RuntimeException(
                "Failed to fetch race program from: {$url}: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        /**
         * @var array{
         *   programs: array{
         *     stadiums: array<int<1, 24>, array{
         *       races: array<int<1, 12>, array{
         *         date: string,
         *         stadium_number: int<1, 24>,
         *         race_number: int<1, 12>,
         *         closed_at: string,
         *         racers: array<int<1, 6>, array{
         *           number: int,
         *         }>,
         *       }>,
         *     }>,
         *   },
         * }
         */
        $payload = JsonDecoder::decode((string) $response->getBody(), $url);

        return $payload['programs'];
    }
}
