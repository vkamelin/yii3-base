<?php

declare(strict_types=1);

namespace App\Auth\Interface\Web\Login;

use App\Auth\Application\Session\AuthSessionInterface;
use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class LoginPageAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private AuthSessionInterface $authSession,
        private RedirectResponseFactory $redirectResponseFactory,
        private CsrfTokenInterface $csrfToken,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        if ($this->authSession->userId() !== null) {
            return $this->redirectResponseFactory->to('/');
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'form' => LoginForm::fromArray([]),
            'csrfToken' => $this->csrfToken->getValue(),
        ]);
    }
}
