<?php

declare(strict_types=1);

use App\Auth\Interface\Web\Login\LoginForm;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var LoginForm $form
 * @var string $csrfToken
 */

$this->setTitle('Sign in');
?>
<style>
    body { font-family: sans-serif; margin: 2rem; max-width: 420px; }
    label { display: block; margin: 0.75rem 0 0.25rem; }
    input[type="email"], input[type="password"] { width: 100%; padding: 0.5rem; }
    .error { color: #b00020; margin: 0.75rem 0; }
</style>

<h1>Sign in</h1>

<?php if ($form->errorMessage() !== ''): ?>
    <div class="error"><?= htmlspecialchars($form->errorMessage(), ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="/login">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <label for="email">Email</label>
    <input id="email" type="email" name="email" required value="<?= htmlspecialchars($form->email(), ENT_QUOTES, 'UTF-8') ?>">

    <label for="password">Password</label>
    <input id="password" type="password" name="password" required>

    <label>
        <input type="checkbox" name="remember" value="1" <?= $form->remember() ? 'checked' : '' ?>>
        Remember me
    </label>

    <button type="submit">Login</button>
</form>
