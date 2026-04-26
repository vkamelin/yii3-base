<?php

declare(strict_types=1);

use App\Dashboard\Infrastructure\Asset\DatatablesAsset;
use App\User\Application\DTO\UserListItem;
use Yiisoft\Assets\AssetManager;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<UserListItem> $users
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var string $search
 * @var string $status
 * @var AssetManager $assetManager
 */

$this->setTitle('Users');

$assetManager->register(DatatablesAsset::class);

$headers = ['Email', 'Name', 'Status', 'Created', 'Actions'];
$rows = [];
foreach ($users as $user) {
    $rows[] = [
        $user->email,
        $user->name,
        $user->status,
        $user->createdAt,
        [
            'html' => '<a class="btn btn-sm btn-outline-primary" href="/dashboard/users/'
                . rawurlencode($user->id)
                . '/edit">Edit</a>',
        ],
    ];
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="page-title">Users</h2>
    <a class="btn btn-primary" href="/dashboard/users/create">Create User</a>
</div>

<?php
$action = '/dashboard/users';
$fields = [
    ['name' => 'search', 'label' => 'Search', 'value' => $search, 'placeholder' => 'email or name'],
    [
        'name' => 'status',
        'label' => 'Status',
        'value' => $status,
        'type' => 'select',
        'options' => ['active' => 'Active', 'blocked' => 'Blocked', 'pending' => 'Pending'],
    ],
    ['name' => 'per_page', 'label' => 'Per page', 'value' => (string) $perPage, 'type' => 'select', 'options' => [
        '10' => '10',
        '20' => '20',
        '50' => '50',
    ]],
];
require __DIR__ . '/../Layout/Partial/filter-form.php';

require __DIR__ . '/../Layout/Partial/table.php';

$path = '/dashboard/users';
$query = ['search' => $search, 'status' => $status, 'per_page' => $perPage];
require __DIR__ . '/../Layout/Partial/pagination.php';

$modalId = 'confirmDelete';
$title = 'Delete user';
$message = 'Delete action is not connected yet.';
$confirmLabel = 'Delete';
require __DIR__ . '/../Layout/Partial/confirm-delete-modal.php';
?>

