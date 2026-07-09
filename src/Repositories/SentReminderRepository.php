<?php

declare(strict_types=1);

namespace BVP\Notifier\Repositories;

use Carbon\CarbonImmutable as Carbon;
use RuntimeException;

/**
 * @author shimomo
 */
final class SentReminderRepository
{
    /**
     * @param string $path
     */
    public function __construct(
        private readonly string $path,
    ) {
        //
    }

    /**
     * @param \Carbon\CarbonImmutable $date
     * @param int<1, 24> $stadiumNumber
     * @param int<1, 12> $raceNumber
     * @return bool
     * @throws \RuntimeException
     */
    public function markAsSentUnlessAlreadySent(Carbon $date, int $stadiumNumber, int $raceNumber): bool
    {
        $this->ensureDirectoryExists();

        $handle = fopen($this->path, 'c+');

        if ($handle === false) {
            throw new RuntimeException("Failed to open file: {$this->path}");
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException("Failed to acquire lock: {$this->path}");
            }

            $sentKeys = $this->readSentKeys($handle);

            $key = $this->buildKey($date, $stadiumNumber, $raceNumber);

            if (in_array($key, $sentKeys, true)) {
                return true;
            }

            $todayPrefix = $date->format('Y-m-d') . '_';
            $sentKeys = array_values(array_filter(
                $sentKeys,
                static fn (string $existingKey): bool => str_starts_with($existingKey, $todayPrefix),
            ));

            $sentKeys[] = $key;

            $this->writeSentKeys($handle, $sentKeys);

            return false;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @return list<string>
     */
    private function readSentKeys($handle): array
    {
        rewind($handle);

        $contents = stream_get_contents($handle);

        if ($contents === false) {
            throw new RuntimeException("Failed to read file: {$this->path}");
        }

        if (trim($contents) === '') {
            return [];
        }

        $payload = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            return [];
        }

        return array_values(array_filter($payload, 'is_string'));
    }

    /**
     * @param resource $handle
     * @param list<string> $sentKeys
     */
    private function writeSentKeys($handle, array $sentKeys): void
    {
        $json = json_encode($sentKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException("Failed to encode JSON for file: {$this->path}");
        }

        rewind($handle);

        if (!ftruncate($handle, 0)) {
            throw new RuntimeException("Failed to truncate file: {$this->path}");
        }

        if (fwrite($handle, $json) === false) {
            throw new RuntimeException("Failed to write file: {$this->path}");
        }

        fflush($handle);
    }

    /**
     * @param int<1, 24> $stadiumNumber
     * @param int<1, 12> $raceNumber
     * @return string
     */
    private function buildKey(Carbon $date, int $stadiumNumber, int $raceNumber): string
    {
        return sprintf('%s_%d_%d', $date->format('Y-m-d'), $stadiumNumber, $raceNumber);
    }

    /**
     * @return void
     */
    private function ensureDirectoryExists(): void
    {
        $directory = dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException("Failed to create directory: {$directory}");
        }
    }
}
