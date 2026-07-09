<?php

declare(strict_types=1);

namespace BVP\Notifier\Channels;

/**
 * @author shimomo
 */
interface NotificationChannel
{
    /**
     * @param string $message
     * @return void
     */
    public function send(string $message): void;

    /**
     * @param list<\BVP\Notifier\Channels\Embed> $embeds
     * @return void
     */
    public function sendEmbeds(array $embeds): void;
}
