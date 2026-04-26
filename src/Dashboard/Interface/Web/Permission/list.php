<?php

declare(strict_types=1);

use App\Rbac\Application\DTO\PermissionView;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<PermissionView> $permissions
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var string $search
 * @var string $groupCode
 * @var string $isSystem
 */

$this->setTitle('Permissions');

$headers = ['Code', 'Name', 'Group', 'Description', 'System', 'Created'];
$rows = [];
foreach ($permissions as $permission) {
    $rows[] = [
        $permission->code,
        $permission->name,
        $permission->groupCode,
        $permission->description ?? '',
        $permission->isSystem ? 'yes' : 'no',
        $permission->createdAt,
    ];
}
?>
<h2 class="page-title mb-3">Permissions</h2>

<?php
$action = '/dashboard/permissions';
$fields = [
    ['name' => 'search', 'label' => 'Search', 'value' => $search, 'placeholder' => 'code or name'],
    ['name' => 'group_code', 'label' => 'Group', 'value' => $groupCode, 'placeholder' => 'group code'],
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

$path = '/dashboard/permissions';
$query = ['search' => $search, 'group_code' => $groupCode, 'is_system' => $isSystem, 'per_page' => $perPage];
require __DIR__ . '/../Layout/Partial/pagination.php';
?>

