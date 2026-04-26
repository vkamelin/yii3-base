<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Session\Flash\FlashInterface;

/** @var FlashInterface $flash */

$flashes = $flash->getAll();
if ($flashes === []) {
    return;
}

$classByKey = [
    'success' => 'alert-success',
    'error' => 'alert-danger',
    'warning' => 'alert-warning',
    'info' => 'alert-info',
];
?>
<?php foreach ($flashes as $key => $message): ?>
    <?php
    $messages = is_array($message) ? $message : [$message];
    $alertClass = $classByKey[(string) $key] ?? 'alert-info';
    ?>
    <?php foreach ($messages as $item): ?>
        <div class="alert <?= Html::encode($alertClass) ?> mb-3" role="alert">
            <?= Html::encode((string) $item) ?>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>
