<?php

declare(strict_types=1);

namespace BVP\Notifier\Channel;

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
    public function send(string $message): void
    {
        try {
            $this->client->request('POST', $this->webhookUrl, [
                'json' => ['content' => $message],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException(
                "Failed to send Discord notification: {$e->getMessage()}",
                previous: $e,
            );
        }
    }
}
