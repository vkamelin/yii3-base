<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\User;

use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use App\Shared\Application\Exception\ConflictException;
use App\Shared\Application\Exception\ValidationException;
use App\User\Application\Command\ChangeUserStatusCommand;
use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Handler\ChangeUserStatusHandler;
use App\User\Application\Handler\CreateUserHandler;
use App\User\Domain\Enum\UserStatus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\FlashInterface;

use function filter_var;
use function is_array;
use function is_string;
use function trim;

use const FILTER_VALIDATE_EMAIL;

final readonly class CreateSubmitAction
{
    public function __construct(
        private DashboardViewRenderer $viewRenderer,
        private CreateUserHandler $createUserHandler,
        private ChangeUserStatusHandler $changeUserStatusHandler,
        private RedirectResponseFactory $redirectResponseFactory,
        private FlashInterface $flash,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $payload = $request->getParsedBody();
        $payload = is_array($payload) ? $payload : [];

        $email = is_string($payload['email'] ?? null) ? trim($payload['email']) : '';
        $name = is_string($payload['name'] ?? null) ? trim($payload['name']) : '';
        $status = is_string($payload['status'] ?? null) ? trim($payload['status']) : 'active';

        $errors = [];
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'Enter a valid email.';
        }

        if ($name === '') {
            $errors['name'][] = 'Name is required.';
        }

        if ($status === '' || UserStatus::tryFrom($status) === null) {
            $errors['status'][] = 'Invalid status.';
        }

        if ($errors !== []) {
            return $this->viewRenderer->renderMain('User/create', [
                'form' => ['email' => $email, 'name' => $name, 'status' => $status],
                'errors' => $errors,
            ]);
        }

        try {
            $created = $this->createUserHandler->handle(new CreateUserCommand($email, $name));
            if ($status !== 'active') {
                $this->changeUserStatusHandler->handle(new ChangeUserStatusCommand($created->id, $status));
            }
        } catch (ValidationException|ConflictException $e) {
            $errors['common'][] = $e->getMessage();

            return $this->viewRenderer->renderMain('User/create', [
                'form' => ['email' => $email, 'name' => $name, 'status' => $status],
                'errors' => $errors,
            ]);
        }

        $this->flash->set('success', 'User created.');
        return $this->redirectResponseFactory->to('/dashboard/users');
    }
}
