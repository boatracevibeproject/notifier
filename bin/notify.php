<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BVP\Notifier\Channel\DiscordChannel;
use BVP\Notifier\LadyRacerRepository;
use BVP\Notifier\Notifier;
use BVP\Notifier\RaceProgramFetcher;
use BVP\Notifier\RaceProgramFormatter;
use Carbon\CarbonImmutable as Carbon;
use Carbon\Exceptions\InvalidFormatException;
use GuzzleHttp\Client;

$discordWebhookUrl = getenv('DISCORD_WEBHOOK_URL');

if ($discordWebhookUrl === false || $discordWebhookUrl === '') {
    fwrite(STDERR, "Environment variable DISCORD_WEBHOOK_URL is not set.\n");
    exit(1);
}

$date = null;

if (isset($argv[1])) {
    try {
        $date = Carbon::parse($argv[1]);
    } catch (InvalidFormatException $e) {
        fwrite(STDERR, "Invalid date: {$argv[1]} ({$e->getMessage()})\n");
        exit(1);
    }
}

$client = new Client([
    'timeout' => 10.0,
]);

$notifier = new Notifier(
    ladyRacerRepository: new LadyRacerRepository(),
    raceProgramFetcher: new RaceProgramFetcher($client),
    raceProgramFormatter: new RaceProgramFormatter(),
    channel: new DiscordChannel($client, webhookUrl: $discordWebhookUrl),
    date: $date,
);

$notifier->notify();
