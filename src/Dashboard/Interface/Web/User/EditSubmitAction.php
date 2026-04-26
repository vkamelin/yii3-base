<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\User;

use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use App\Shared\Application\Exception\ConflictException;
use App\Shared\Application\Exception\NotFoundException;
use App\Shared\Application\Exception\ValidationException;
use App\User\Application\Command\ChangeUserStatusCommand;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\Handler\ChangeUserStatusHandler;
use App\User\Application\Handler\UpdateUserHandler;
use App\User\Domain\Enum\UserStatus;
use App\User\Domain\ValueObject\UserId;
use App\User\Infrastructure\Persistence\UserReadRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\FlashInterface;

use function filter_var;
use function is_array;
use function is_string;
use function trim;

use const FILTER_VALIDATE_EMAIL;

final readonly class EditSubmitAction
{
    public function __construct(
        private DashboardViewRenderer $viewRenderer,
        private UserReadRepository $userReadRepository,
        private UpdateUserHandler $updateUserHandler,
        private ChangeUserStatusHandler $changeUserStatusHandler,
        private RedirectResponseFactory $redirectResponseFactory,
        private ResponseFactoryInterface $responseFactory,
        private FlashInterface $flash,
    ) {}

    public function __invoke(ServerRequestInterface $request, string $id): ResponseInterface
    {
        try {
            $userId = UserId::fromString($id);
        } catch (InvalidArgumentException) {
            return $this->responseFactory->createResponse(404);
        }

        $existing = $this->userReadRepository->getById($userId);
        if ($existing === null) {
            return $this->responseFactory->createResponse(404);
        }

        $payload = $request->getParsedBody();
        $payload = is_array($payload) ? $payload : [];

        $email = is_string($payload['email'] ?? null) ? trim($payload['email']) : '';
        $name = is_string($payload['name'] ?? null) ? trim($payload['name']) : '';
        $status = is_string($payload['status'] ?? null) ? trim($payload['status']) : '';

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
            return $this->viewRenderer->renderMain('User/edit', [
                'userId' => $id,
                'form' => ['email' => $email, 'name' => $name, 'status' => $status],
                'errors' => $errors,
            ]);
        }

        try {
            $updated = $this->updateUserHandler->handle(new UpdateUserCommand($id, $email, $name));

            if ($updated->status !== $status) {
                $this->changeUserStatusHandler->handle(new ChangeUserStatusCommand($id, $status));
            }
        } catch (ValidationException|ConflictException|NotFoundException $e) {
            $errors['common'][] = $e->getMessage();

            return $this->viewRenderer->renderMain('User/edit', [
                'userId' => $id,
                'form' => ['email' => $email, 'name' => $name, 'status' => $status],
                'errors' => $errors,
            ]);
        }

        $this->flash->set('success', 'User updated.');
        return $this->redirectResponseFactory->to('/dashboard/users');
    }
}
