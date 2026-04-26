<?php

declare(strict_types=1);

namespace App\Console;

use App\Auth\Domain\Service\PasswordHasherInterface;
use App\Auth\Domain\ValueObject\PlainPassword;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Console\ExitCode;

use function is_array;
use function is_string;
use function mb_strtolower;
use function sprintf;
use function trim;

#[AsCommand(
    name: 'seed:run',
    description: 'Seed baseline permissions, roles and administrator account.',
)]
final class SeedCommand extends Command
{
    private const ADMIN_EMAIL = 'admin@example.com';
    private const ADMIN_NAME = 'Administrator';
    private const ADMIN_PASSWORD = 'admin123456';
    private const ADMIN_STATUS = 'active';

    /**
     * @var array<string,array{name:string,description:string,is_system:int}>
     */
    private const ROLES = [
        'admin' => [
            'name' => 'Administrator',
            'description' => 'System administrator',
            'is_system' => 1,
        ],
        'manager' => [
            'name' => 'Manager',
            'description' => 'Manager role',
            'is_system' => 1,
        ],
        'user' => [
            'name' => 'User',
            'description' => 'Basic user role',
            'is_system' => 1,
        ],
    ];

    /**
     * @var array<string,array{name:string,description:string,group_code:string,is_system:int}>
     */
    private const PERMISSIONS = [
        'dashboard.access' => [
            'name' => 'Access dashboard',
            'description' => 'Open dashboard pages',
            'group_code' => 'dashboard',
            'is_system' => 1,
        ],
        'users.view' => [
            'name' => 'View users',
            'description' => 'Read users list and profile',
            'group_code' => 'users',
            'is_system' => 1,
        ],
        'users.manage' => [
            'name' => 'Manage users',
            'description' => 'Create, edit and disable users',
            'group_code' => 'users',
            'is_system' => 1,
        ],
        'roles.view' => [
            'name' => 'View roles',
            'description' => 'Read roles list',
            'group_code' => 'roles',
            'is_system' => 1,
        ],
        'roles.manage' => [
            'name' => 'Manage roles',
            'description' => 'Create and update roles',
            'group_code' => 'roles',
            'is_system' => 1,
        ],
        'permissions.view' => [
            'name' => 'View permissions',
            'description' => 'Read permissions list',
            'group_code' => 'permissions',
            'is_system' => 1,
        ],
        'permissions.manage' => [
            'name' => 'Manage permissions',
            'description' => 'Assign and update permissions',
            'group_code' => 'permissions',
            'is_system' => 1,
        ],
        'activity_log.view' => [
            'name' => 'View activity log',
            'description' => 'Read security and audit log',
            'group_code' => 'activity_log',
            'is_system' => 1,
        ],
        'api.access' => [
            'name' => 'API access',
            'description' => 'Use API endpoints',
            'group_code' => 'api',
            'is_system' => 1,
        ],
    ];

    /**
     * @var array<string,list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'dashboard.access',
            'users.view',
            'users.manage',
            'roles.view',
            'roles.manage',
            'permissions.view',
            'permissions.manage',
            'activity_log.view',
            'api.access',
        ],
        'manager' => [
            'dashboard.access',
            'users.view',
            'roles.view',
            'permissions.view',
            'activity_log.view',
        ],
        'user' => [
            'api.access',
        ],
    ];

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'reset-admin-password',
            null,
            InputOption::VALUE_NONE,
            'Reset admin password to default value.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $resetAdminPassword = (bool) $input->getOption('reset-admin-password');
        $now = $this->now();

        try {
            $this->db->transaction(function (ConnectionInterface $db) use ($now, $resetAdminPassword): void {
                $permissionIds = $this->seedPermissions($db, $now);
                $roleIds = $this->seedRoles($db, $now);
                $this->seedRolePermissions($db, $roleIds, $permissionIds, $now);
                [$adminUserId, $isNewAdminUser] = $this->seedAdminUser($db, $now);
                $this->seedAdminCredentials($db, $adminUserId, $now, $resetAdminPassword, $isNewAdminUser);
                $this->attachUserRole($db, $adminUserId, $roleIds['admin'], $now);
            });
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>Seed failed: %s</error>', $e->getMessage()));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $output->writeln('<info>Seed completed successfully.</info>');
        $output->writeln(
            '<comment>WARNING: Default admin password is for development only. Change it immediately.</comment>',
        );

        return ExitCode::OK;
    }

    /**
     * @return array<string,string>
     */
    private function seedPermissions(ConnectionInterface $db, string $now): array
    {
        $ids = [];

        foreach (self::PERMISSIONS as $code => $permission) {
            $existingId = $this->findIdByCode($db, 'permissions', $code);

            if ($existingId !== null) {
                $ids[$code] = $existingId;
                continue;
            }

            $id = Uuid::uuid7()->toString();
            $db->createCommand()->insert('permissions', [
                'id' => $id,
                'code' => $code,
                'name' => $permission['name'],
                'description' => $permission['description'],
                'group_code' => $permission['group_code'],
                'is_system' => $permission['is_system'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            $ids[$code] = $id;
        }

        return $ids;
    }

    /**
     * @return array<string,string>
     */
    private function seedRoles(ConnectionInterface $db, string $now): array
    {
        $ids = [];

        foreach (self::ROLES as $code => $role) {
            $existingId = $this->findIdByCode($db, 'roles', $code);

            if ($existingId !== null) {
                $ids[$code] = $existingId;
                continue;
            }

            $id = Uuid::uuid7()->toString();
            $db->createCommand()->insert('roles', [
                'id' => $id,
                'code' => $code,
                'name' => $role['name'],
                'description' => $role['description'],
                'is_system' => $role['is_system'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            $ids[$code] = $id;
        }

        return $ids;
    }

    /**
     * @param array<string,string> $roleIds
     * @param array<string,string> $permissionIds
     */
    private function seedRolePermissions(
        ConnectionInterface $db,
        array $roleIds,
        array $permissionIds,
        string $now,
    ): void {
        foreach (self::ROLE_PERMISSIONS as $roleCode => $rolePermissionCodes) {
            $roleId = $roleIds[$roleCode] ?? null;

            if ($roleId === null) {
                continue;
            }

            foreach ($rolePermissionCodes as $permissionCode) {
                $permissionId = $permissionIds[$permissionCode] ?? null;
                if ($permissionId === null) {
                    continue;
                }

                $db->createCommand(
                    'INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
                     VALUES (:role_id, :permission_id, :created_at)
                     ON DUPLICATE KEY UPDATE `created_at` = `created_at`',
                    [
                        ':role_id' => $roleId,
                        ':permission_id' => $permissionId,
                        ':created_at' => $now,
                    ],
                )->execute();
            }
        }
    }

    /**
     * @return array{0:string,1:bool}
     */
    private function seedAdminUser(ConnectionInterface $db, string $now): array
    {
        $emailNormalized = $this->normalizeEmail(self::ADMIN_EMAIL);
        $existingId = $this->findUserIdByNormalizedEmail($db, $emailNormalized);

        if ($existingId !== null) {
            $db->createCommand()->update(
                'users',
                [
                    'email' => self::ADMIN_EMAIL,
                    'email_normalized' => $emailNormalized,
                    'name' => self::ADMIN_NAME,
                    'status' => self::ADMIN_STATUS,
                    'deleted_at' => null,
                    'updated_at' => $now,
                ],
                ['id' => $existingId],
            )->execute();

            return [$existingId, false];
        }

        $id = Uuid::uuid7()->toString();
        $db->createCommand()->insert('users', [
            'id' => $id,
            'email' => self::ADMIN_EMAIL,
            'email_normalized' => $emailNormalized,
            'name' => self::ADMIN_NAME,
            'status' => self::ADMIN_STATUS,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ])->execute();

        return [$id, true];
    }

    private function seedAdminCredentials(
        ConnectionInterface $db,
        string $adminUserId,
        string $now,
        bool $resetAdminPassword,
        bool $isNewAdminUser,
    ): void {
        $existing = $this->findCredentials($db, $adminUserId);
        $shouldReset = $resetAdminPassword || $isNewAdminUser || $existing === null;

        if ($existing === null) {
            $db->createCommand()->insert('user_credentials', [
                'user_id' => $adminUserId,
                'password_hash' => $this->passwordHasher->hash(PlainPassword::fromString(self::ADMIN_PASSWORD))->value(),
                'password_changed_at' => $shouldReset ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            return;
        }

        if (!$shouldReset) {
            return;
        }

        $db->createCommand()->update(
            'user_credentials',
            [
                'password_hash' => $this->passwordHasher->hash(PlainPassword::fromString(self::ADMIN_PASSWORD))->value(),
                'password_changed_at' => $now,
                'updated_at' => $now,
            ],
            ['user_id' => $adminUserId],
        )->execute();
    }

    private function attachUserRole(ConnectionInterface $db, string $userId, string $roleId, string $now): void
    {
        $db->createCommand(
            'INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`)
             VALUES (:user_id, :role_id, :created_at)
             ON DUPLICATE KEY UPDATE `created_at` = `created_at`',
            [
                ':user_id' => $userId,
                ':role_id' => $roleId,
                ':created_at' => $now,
            ],
        )->execute();
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s.u');
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function findIdByCode(ConnectionInterface $db, string $table, string $code): ?string
    {
        $id = $db->createCommand(
            sprintf('SELECT `id` FROM `%s` WHERE `code` = :code LIMIT 1', $table),
            [':code' => $code],
        )->queryScalar();

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function findUserIdByNormalizedEmail(ConnectionInterface $db, string $emailNormalized): ?string
    {
        $id = $db->createCommand(
            'SELECT `id` FROM `users` WHERE `email_normalized` = :email LIMIT 1',
            [':email' => $emailNormalized],
        )->queryScalar();

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * @return array{password_hash:string}|null
     */
    private function findCredentials(ConnectionInterface $db, string $userId): ?array
    {
        $row = $db->createCommand(
            'SELECT `password_hash` FROM `user_credentials` WHERE `user_id` = :user_id LIMIT 1',
            [':user_id' => $userId],
        )->queryOne();

        if (!is_array($row)) {
            return null;
        }

        $passwordHash = $row['password_hash'] ?? null;
        if (!is_string($passwordHash) || $passwordHash === '') {
            return null;
        }

        return ['password_hash' => $passwordHash];
    }
}
