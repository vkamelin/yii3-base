<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $action
 * @var string $submitLabel
 * @var array<string, string> $form
 * @var array<string, list<string>> $errors
 * @var string|null $csrf
 */
?>
<div class="card">
    <div class="card-body">
        <?php foreach (($errors['common'] ?? []) as $message): ?>
            <div class="alert alert-danger mb-3"><?= Html::encode($message) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= Html::encode($action) ?>">
            <input type="hidden" name="_csrf" value="<?= Html::encode((string) $csrf) ?>">

            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input
                    id="email"
                    class="form-control"
                    type="email"
                    name="email"
                    value="<?= Html::encode($form['email'] ?? '') ?>"
                    required
                >
                <?php foreach (($errors['email'] ?? []) as $message): ?>
                    <div class="text-danger small mt-1"><?= Html::encode($message) ?></div>
                <?php endforeach; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="name">Name</label>
                <input
                    id="name"
                    class="form-control"
                    type="text"
                    name="name"
                    value="<?= Html::encode($form['name'] ?? '') ?>"
                    required
                >
                <?php foreach (($errors['name'] ?? []) as $message): ?>
                    <div class="text-danger small mt-1"><?= Html::encode($message) ?></div>
                <?php endforeach; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="status">Status</label>
                <select id="status" class="form-select" name="status">
                    <?php
                    $status = $form['status'] ?? 'active';
                    $statuses = ['active' => 'Active', 'blocked' => 'Blocked', 'pending' => 'Pending'];
                    ?>
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= Html::encode($value) ?>" <?= $status === $value ? 'selected' : '' ?>>
                            <?= Html::encode($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php foreach (($errors['status'] ?? []) as $message): ?>
                    <div class="text-danger small mt-1"><?= Html::encode($message) ?></div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary"><?= Html::encode($submitLabel) ?></button>
        </form>
    </div>
</div>

