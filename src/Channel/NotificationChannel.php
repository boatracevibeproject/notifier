<?php

declare(strict_types=1);

namespace BVP\Notifier\Channel;

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
}
