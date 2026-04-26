<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\Auth;

use App\Auth\Application\Session\AuthSessionInterface;
use App\Auth\Interface\Web\Login\LoginForm;
use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use App\Rbac\Domain\Service\AccessCheckerInterface;
use App\User\Domain\ValueObject\UserId;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\FlashInterface;

final readonly class LoginPageAction
{
    public function __construct(
        private DashboardViewRenderer $viewRenderer,
        private AuthSessionInterface $authSession,
        private AccessCheckerInterface $accessChecker,
        private RedirectResponseFactory $redirectResponseFactory,
        private FlashInterface $flash,
        private CsrfTokenInterface $csrfToken,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        $userId = $this->authSession->userId();
        if ($userId !== null) {
            if ($this->canAccessDashboard($userId)) {
                return $this->redirectResponseFactory->to('/dashboard');
            }

            $this->authSession->logout();
            $this->flash->set('error', 'Access to Dashboard is denied.');
        }

        return $this->viewRenderer->renderEmpty('Auth/login', [
            'form' => LoginForm::fromArray([]),
            'csrfToken' => $this->csrfToken->getValue(),
        ]);
    }

    private function canAccessDashboard(string $userId): bool
    {
        try {
            return $this->accessChecker->userHasPermission(UserId::fromString($userId), 'dashboard.access');
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}

