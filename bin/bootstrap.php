<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BVP\Notifier\Channels\DiscordChannel;
use BVP\Notifier\Chunkers\EmbedChunker;
use BVP\Notifier\Fetcher;
use BVP\Notifier\Formatter;
use BVP\Notifier\Notifier;
use BVP\Notifier\Repositories\LadyRacerRepository;
use BVP\Notifier\Repositories\SentReminderRepository;
use Carbon\CarbonImmutable as Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Dotenv\Dotenv;
use GuzzleHttp\Client;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

/**
 * @param ?string $dateArgument
 * @return \BVP\Notifier\Notifier
 */
function bvp_notifier_bootstrap(?string $dateArgument = null): Notifier
{
    $discordWebhookUrl = $_ENV['DISCORD_WEBHOOK_URL'] ?? false;

    if ($discordWebhookUrl === false || $discordWebhookUrl === '') {
        fwrite(STDERR, "Environment variable DISCORD_WEBHOOK_URL is not set.\n");
        exit(1);
    }

    $date = null;

    if ($dateArgument !== null) {
        try {
            $date = Carbon::parse($dateArgument, 'Asia/Tokyo');
        } catch (InvalidFormatException $exception) {
            fwrite(STDERR, "Invalid date: {$dateArgument} ({$exception->getMessage()})\n");
            exit(1);
        }
    }

    $client = new Client([
        'timeout' => 10.0,
    ]);

    return new Notifier(
        fetcher: new Fetcher($client),
        formatter: new Formatter(),
        ladyRacerRepository: new LadyRacerRepository(),
        sentReminderRepository: new SentReminderRepository(__DIR__ . '/../storage/sent_reminders.json'),
        embedChunker: new EmbedChunker(),
        channel: new DiscordChannel($client, webhookUrl: $discordWebhookUrl),
        date: $date,
    );
}
