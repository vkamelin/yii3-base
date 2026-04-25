<?php

declare(strict_types=1);

namespace App\Auth\Interface\Web\Login;

use App\Auth\Application\Command\LoginCommand;
use App\Auth\Application\Handler\LoginHandler;
use App\Auth\Application\Session\AuthSessionInterface;
use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function is_array;

final readonly class LoginSubmitAction
{
    public function __construct(
        private LoginHandler $loginHandler,
        private AuthSessionInterface $authSession,
        private RedirectResponseFactory $redirectResponseFactory,
        private WebViewRenderer $viewRenderer,
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
            return $this->redirectResponseFactory->to('/');
        } catch (InvalidCredentialsException | AccessDeniedException | ValidationException) {
            $form->addError('Invalid credentials.');
            return $this->render($form);
        }
    }

    private function render(LoginForm $form): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/template', [
            'form' => $form,
            'csrfToken' => $this->csrfToken->getValue(),
        ]);
    }
}
