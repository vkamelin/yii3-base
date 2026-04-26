<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\Auth;

use App\Auth\Application\Command\LoginCommand;
use App\Auth\Application\Handler\LoginHandler;
use App\Auth\Application\Session\AuthSessionInterface;
use App\Auth\Interface\Web\Login\LoginForm;
use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use App\Rbac\Domain\Service\AccessCheckerInterface;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use App\User\Domain\ValueObject\UserId;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\FlashInterface;

use function is_array;

final readonly class LoginSubmitAction
{
    public function __construct(
        private LoginHandler $loginHandler,
        private AuthSessionInterface $authSession,
        private AccessCheckerInterface $accessChecker,
        private RedirectResponseFactory $redirectResponseFactory,
        private DashboardViewRenderer $viewRenderer,
        private FlashInterface $flash,
        private CsrfTokenInterface $csrfToken,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $form = LoginForm::fromArray(is_array($parsedBody) ? $parsedBody : []);

        if (!$form->validate()) {
            return $this->render($form);
        }

        try {
            $result = $this->loginHandler->handle(new LoginCommand(
                email: $form->email(),
                password: $form->password(),
                remember: $form->remember(),
            ));

            $this->authSession->login($result->userId);

            if (!$this->canAccessDashboard($result->userId)) {
                $this->authSession->logout();
                $form->addError('Access to Dashboard is denied.');
                return $this->render($form);
            }

            $this->flash->set('success', 'Welcome to Dashboard.');
            return $this->redirectResponseFactory->to('/dashboard');
        } catch (InvalidCredentialsException | AccessDeniedException | ValidationException) {
            $form->addError('Invalid credentials.');
            return $this->render($form);
        }
    }

    private function render(LoginForm $form): ResponseInterface
    {
        return $this->viewRenderer->renderEmpty('Auth/login', [
            'form' => $form,
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

