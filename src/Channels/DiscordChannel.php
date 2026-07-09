<?php

declare(strict_types=1);

namespace BVP\Notifier\Channels;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use RuntimeException;

/**
 * @author shimomo
 */
final class DiscordChannel implements NotificationChannel
{
    /**
     * @param \GuzzleHttp\ClientInterface $client
     * @param string $webhookUrl
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly string $webhookUrl,
    ) {
        if ($this->webhookUrl === '') {
            throw new InvalidArgumentException('Discord webhook URL must not be empty.');
        }
    }

    /**
     * @param string $message
     * @return void
     */
    #[\Override]
    public function send(string $message): void
    {
        try {
            $this->client->request('POST', $this->webhookUrl, [
                'json' => ['content' => $message],
            ]);
        } catch (GuzzleException $exception) {
            throw new RuntimeException(
                "Failed to send Discord notification: {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }

    /**
     * @param list<\BVP\Notifier\Channels\Embed> $embeds
     * @return void
     */
    #[\Override]
    public function sendEmbeds(array $embeds): void
    {
        if (empty($embeds)) {
            return;
        }

        $payload = array_map(fn(Embed $embed): array => $embed->toArray(), $embeds);

        try {
            $this->client->request('POST', $this->webhookUrl, [
                'json' => ['embeds' => $payload],
            ]);
        } catch (GuzzleException $exception) {
            throw new RuntimeException(
                "Failed to send Discord embed notification: {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }
}
