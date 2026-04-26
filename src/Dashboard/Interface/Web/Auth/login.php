<?php

declare(strict_types=1);

use App\Auth\Interface\Web\Login\LoginForm;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var LoginForm $form
 * @var string $csrfToken
 */

$this->setTitle('Dashboard Login');
?>
<div class="card card-md">
    <div class="card-body">
        <h2 class="h2 text-center mb-4">Dashboard Sign In</h2>

        <?php if ($form->errorMessage() !== ''): ?>
            <div class="alert alert-danger mb-3"><?= Html::encode($form->errorMessage()) ?></div>
        <?php endif; ?>

        <form method="post" action="/dashboard/login" autocomplete="off" novalidate>
            <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">

            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input
                    id="email"
                    class="form-control"
                    type="email"
                    name="email"
                    required
                    value="<?= Html::encode($form->email()) ?>"
                >
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input id="password" class="form-control" type="password" name="password" required>
            </div>

            <label class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" value="1" <?= $form->remember() ? 'checked' : '' ?>>
                <span class="form-check-label">Remember me</span>
            </label>

            <button class="btn btn-primary w-100" type="submit">Sign In</button>
        </form>
    </div>
</div>

