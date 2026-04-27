<?php

declare(strict_types=1);

use App\Rbac\Application\DTO\RoleView;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<RoleView> $roles
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var string $search
 * @var string $isSystem
 */

$this->setTitle('Roles');

$headers = ['Code', 'Name', 'Description', 'System', 'Created'];
$rows = [];
foreach ($roles as $role) {
    $rows[] = [
        $role->code,
        $role->name,
        $role->description ?? '',
        $role->isSystem ? 'yes' : 'no',
        $role->createdAt,
    ];
}
?>
<h2 class="page-title mb-3">Roles</h2>

<?php
$action = '/dashboard/roles';
$fields = [
    ['name' => 'search', 'label' => 'Search', 'value' => $search, 'placeholder' => 'code or name'],
    [
        'name' => 'is_system',
        'label' => 'System',
        'value' => $isSystem,
        'type' => 'select',
        'options' => ['1' => 'Yes', '0' => 'No'],
    ],
    ['name' => 'per_page', 'label' => 'Per page', 'value' => (string) $perPage, 'type' => 'select', 'options' => [
        '10' => '10',
        '20' => '20',
        '50' => '50',
    ]],
];
require __DIR__ . '/../Layout/Partial/filter-form.php';

require __DIR__ . '/../Layout/Partial/table.php';

$path = '/dashboard/roles';
$query = ['search' => $search, 'is_system' => $isSystem, 'per_page' => $perPage];
require __DIR__ . '/../Layout/Partial/pagination.php';
