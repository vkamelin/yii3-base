<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\Auth;

use App\Auth\Application\Session\AuthSessionInterface;
use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use Psr\Http\Message\ResponseInterface;

final readonly class LogoutAction
{
    public function __construct(
        private AuthSessionInterface $authSession,
        private RedirectResponseFactory $redirectResponseFactory,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $this->authSession->logout();
        return $this->redirectResponseFactory->to('/dashboard/login');
    }
}
