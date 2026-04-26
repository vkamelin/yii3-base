<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Definitions\Reference;

return [
    LoggerInterface::class => Reference::to('logger.app'),
];
