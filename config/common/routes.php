<?php

declare(strict_types=1);

$webRoutes = require __DIR__ . '/../web/routes/web.php';
$apiRoutes = require __DIR__ . '/../web/routes/api.php';

return [
    ...$webRoutes,
    ...$apiRoutes,
];
