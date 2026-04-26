<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Query;

interface ActivityLogQueryInterface
{
    public function list(ActivityLogFilter $filter): ActivityLogPage;

    public function getById(string $id): ?ActivityLogView;
}
