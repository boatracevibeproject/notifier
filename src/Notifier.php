<?php

declare(strict_types=1);

namespace BVP\Notifier;

use BVP\Notifier\Channel\NotificationChannel;
use Carbon\CarbonImmutable as Carbon;

/**
 * @author shimomo
 */
final class Notifier
{
    /**
     * @param \BVP\Notifier\LadyRacerRepository $ladyRacerRepository
     * @param \BVP\Notifier\RaceProgramFetcher $raceProgramFetcher
     * @param \BVP\Notifier\RaceProgramFormatter $raceProgramFormatter
     * @param \BVP\Notifier\Channel\NotificationChannel $channel
     * @param ?\Carbon\CarbonImmutable $date
     */
    public function __construct(
        private readonly LadyRacerRepository $ladyRacerRepository,
        private readonly RaceProgramFetcher $raceProgramFetcher,
        private readonly RaceProgramFormatter $raceProgramFormatter,
        private readonly NotificationChannel $channel,
        private readonly ?Carbon $date = null,
    ) {
        //
    }

    /**
     * @return void
     */
    public function notify(): void
    {
        $messages = [];

        foreach ($this->findLadyOnlyRaces() as [$date, $stadiumNumber, $raceNumber, $racers]) {
            $messages[] = $this->raceProgramFormatter->format($date, $stadiumNumber, $raceNumber, $racers);
        }

        if (!empty($messages)) {
            $this->channel->send(implode("\n\n", $messages));
        }
    }

    /**
     * @return \Generator<int, array{
     *   0: \Carbon\CarbonImmutable,
     *   1: int<1, 24>,
     *   2: int<1, 12>,
     *   3: array<int<1, 6>, array<non-empty-string, int|float|string>>
     * }>
     */
    public function findLadyOnlyRaces(): \Generator
    {
        $date = $this->date ?? Carbon::today();

        $ladyRacerNumbers = $this->ladyRacerRepository->findNumbers($date);
        $stadiums = $this->raceProgramFetcher->fetchStadiums($date);

        foreach ($stadiums as $stadiumNumber => $stadium) {
            foreach ($stadium['races'] as $raceNumber => $race) {
                if ($this->isAllLadyRace($race['racers'], $ladyRacerNumbers)) {
                    yield [$date, $stadiumNumber, $raceNumber, $race['racers']];
                }
            }
        }
    }

    /**
     * @param array<int, array{number: int}> $racers
     * @param list<int|string> $ladyRacerNumbers
     */
    private function isAllLadyRace(array $racers, array $ladyRacerNumbers): bool
    {
        $racerNumbers = array_column($racers, 'number');

        return empty(array_diff($racerNumbers, $ladyRacerNumbers));
    }
}
