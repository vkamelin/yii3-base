<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var array<string, string> $form
 * @var array<string, list<string>> $errors
 */

$this->setTitle('Create User');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="page-title">Create User</h2>
    <a class="btn btn-outline-secondary" href="/dashboard/users">Back to list</a>
</div>

<?php
$action = '/dashboard/users/create';
$submitLabel = 'Create';
require __DIR__ . '/form.php';
?>

