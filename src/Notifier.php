<?php

declare(strict_types=1);

namespace BVP\Notifier;

use BVP\Notifier\Channels\NotificationChannel;
use BVP\Notifier\Chunkers\EmbedChunker;
use BVP\Notifier\Repositories\LadyRacerRepository;
use BVP\Notifier\Repositories\SentReminderRepository;
use Carbon\CarbonImmutable as Carbon;
use Generator;

/**
 * @author shimomo
 */
final class Notifier
{
    /**
     * @var non-empty-string
     */
    private const string TIMEZONE = 'Asia/Tokyo';

    /**
     * @param \BVP\Notifier\Fetcher $fetcher
     * @param \BVP\Notifier\Formatter $formatter
     * @param \BVP\Notifier\Repositories\LadyRacerRepository $ladyRacerRepository
     * @param \BVP\Notifier\Repositories\SentReminderRepository $sentReminderRepository
     * @param \BVP\Notifier\Chunkers\EmbedChunker $embedChunker
     * @param \BVP\Notifier\Channels\NotificationChannel $channel
     * @param ?\Carbon\CarbonImmutable $date
     */
    public function __construct(
        private readonly Fetcher $fetcher,
        private readonly Formatter $formatter,
        private readonly LadyRacerRepository $ladyRacerRepository,
        private readonly SentReminderRepository $sentReminderRepository,
        private readonly EmbedChunker $embedChunker,
        private readonly NotificationChannel $channel,
        private readonly ?Carbon $date = null,
    ) {
        //
    }

    /**
     * @return void
     */
    public function notifySummary(): void
    {
        $embeds = [];

        foreach ($this->findLadyOnlyRaces() as [$date, $stadiumNumber, $raceNumber, $closedAt, $racers]) {
            $embeds[] = $this->formatter->formatEmbed($date, $stadiumNumber, $raceNumber, $closedAt, $racers);
        }

        if (empty($embeds)) {
            return;
        }

        foreach ($this->embedChunker->chunk($embeds) as $chunk) {
            $this->channel->sendEmbeds($chunk);
        }
    }

    /**
     * @return void
     */
    public function notifyReminder(): void
    {
        foreach ($this->findLadyOnlyRaces() as [$date, $stadiumNumber, $raceNumber, $closedAt, $racers]) {
            if (!Carbon::parse($closedAt, self::TIMEZONE)->between(
                Carbon::now(self::TIMEZONE)->subMinutes(20),
                Carbon::now(self::TIMEZONE),
            )) {
                continue;
            }

            $alreadySent = $this->sentReminderRepository->markAsSentUnlessAlreadySent(
                $date,
                $stadiumNumber,
                $raceNumber,
            );

            if ($alreadySent) {
                continue;
            }

            $embed = $this->formatter->formatEmbed($date, $stadiumNumber, $raceNumber, $closedAt, $racers);

            $this->channel->sendEmbeds([$embed]);
        }
    }

    /**
     * @return \Generator<int, array{
     *   0: \Carbon\CarbonImmutable,
     *   1: int<1, 24>,
     *   2: int<1, 12>,
     *   3: \Carbon\CarbonImmutable,
     *   4: array<int<1, 6>, array<string, int|float|string|null>>
     * }>
     */
    public function findLadyOnlyRaces(): Generator
    {
        $date = $this->date ?? Carbon::today(self::TIMEZONE);

        $ladyRacerNumbers = $this->ladyRacerRepository->findNumbers($date);

        foreach ($this->fetcher->fetchPrograms($date)['stadiums'] as $stadium) {
            foreach ($stadium['races'] as $race) {
                $racerNumbers = array_column($race['racers'], 'number');

                if ($this->isAllLadyRace($racerNumbers, $ladyRacerNumbers)) {
                    yield [
                        Carbon::parse($race['date'], self::TIMEZONE),
                        $race['stadium_number'],
                        $race['race_number'],
                        Carbon::parse($race['closed_at'], self::TIMEZONE),
                        $race['racers'],
                    ];
                }
            }
        }
    }

    /**
     * @param list<int> $racerNumbers
     * @param list<int> $ladyRacerNumbers
     * @return bool
     */
    private function isAllLadyRace(array $racerNumbers, array $ladyRacerNumbers): bool
    {
        return empty(array_diff($racerNumbers, $ladyRacerNumbers));
    }
}
