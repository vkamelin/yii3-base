<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $modalId
 * @var string $title
 * @var string $message
 * @var string $confirmLabel
 */
?>
<div class="modal modal-blur fade" id="<?= Html::encode($modalId) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= Html::encode($title) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><?= Html::encode($message) ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger"><?= Html::encode($confirmLabel) ?></button>
            </div>
        </div>
    </div>
</div>

