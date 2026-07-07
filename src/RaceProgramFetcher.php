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
final class RaceProgramFetcher
{
    /**
     * @var string
     */
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
     * @return array<int|string, array{races: array}>
     * @throws \RuntimeException
     */
    public function fetchStadiums(Carbon $date): array
    {
        $url = sprintf(self::URL_FORMAT, $date->format('Y'), $date->format('Ymd'));

        try {
            $response = $this->client->request('GET', $url);
        } catch (GuzzleException $e) {
            throw new RuntimeException(
                "Failed to fetch race program from: {$url}: {$e->getMessage()}",
                previous: $e,
            );
        }

        $payload = JsonDecoder::decode((string) $response->getBody(), $url);

        return $payload['programs']['stadiums'] ?? [];
    }
}
