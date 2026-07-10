<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$notifier = bvp_notifier_bootstrap($argv[1] ?? null);

$notifier->notifySummary();
