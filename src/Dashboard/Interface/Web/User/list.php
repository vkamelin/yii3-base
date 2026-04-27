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

$this->setTitle('Пользователи');

$assetManager->register(DatatablesAsset::class);

$headers = ['Email', 'Имя', 'Статус', 'Создан', 'Действия'];
$rows = [];
foreach ($users as $user) {
    $rows[] = [
        $user->email,
        $user->name,
        $user->status,
        $user->createdAt,
        [
            'html' => '<div class="btn-actions justify-content-end"><a class="btn btn-action" href="/dashboard/users/'
                . rawurlencode($user->id)
                . '/edit"><i class="ti ti-edit"></i></a></div>',
        ],
    ];
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="page-title">Пользователи</h2>
    <a class="btn" href="/dashboard/users/create">Добавить пользователя</a>
</div>

<?php
$action = '/dashboard/users';
$fields = [
    ['name' => 'search', 'label' => 'Поиск', 'value' => $search, 'placeholder' => 'email или имя'],
    [
        'name' => 'status',
        'label' => 'Статус',
        'value' => $status,
        'type' => 'select',
        'options' => ['active' => 'Активен', 'blocked' => 'Заблокирован', 'pending' => 'Ожидает'],
    ],
    ['name' => 'per_page', 'label' => 'На страницу', 'value' => (string) $perPage, 'type' => 'select', 'options' => [
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
$title = 'Удаление пользователя';
$message = 'Delete action is not connected yet.';
$confirmLabel = 'Удалить';
require __DIR__ . '/../Layout/Partial/confirm-delete-modal.php';
?>

