<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $action
 * @var list<array{
 *     name: string,
 *     label: string,
 *     value: string,
 *     type?: string,
 *     placeholder?: string,
 *     options?: array<string, string>
 * }> $fields
 */
?>
<form method="get" action="<?= Html::encode($action) ?>" class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <?php foreach ($fields as $field): ?>
                <div class="col-md-3">
                    <label class="form-label"><?= Html::encode($field['label']) ?></label>
                    <?php if (($field['type'] ?? 'text') === 'select'): ?>
                        <select class="form-select" name="<?= Html::encode($field['name']) ?>">
                            <option value="">All</option>
                            <?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                                <option
                                    value="<?= Html::encode($optionValue) ?>"
                                    <?= $field['value'] === $optionValue ? 'selected' : '' ?>
                                >
                                    <?= Html::encode($optionLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input
                            class="form-control"
                            type="<?= Html::encode($field['type'] ?? 'text') ?>"
                            name="<?= Html::encode($field['name']) ?>"
                            value="<?= Html::encode($field['value']) ?>"
                            placeholder="<?= Html::encode($field['placeholder'] ?? '') ?>"
                        >
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="<?= Html::encode($action) ?>" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

