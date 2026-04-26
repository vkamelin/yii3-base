<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\User;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\User\Application\Command\ChangeUserStatusCommand;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\Handler\ChangeUserStatusHandler;
use App\User\Application\Handler\UpdateUserHandler;
use App\User\Domain\ValueObject\UserId;
use App\User\Infrastructure\Persistence\UserReadRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_key_exists;
use function is_array;
use function is_string;
use function trim;

final readonly class UpdateUserAction
{
    public function __construct(
        private UserReadRepository $userReadRepository,
        private UpdateUserHandler $updateUserHandler,
        private ChangeUserStatusHandler $changeUserStatusHandler,
        private ApiResponseFactory $responseFactory,
        private ApiErrorResponseFactory $errorResponseFactory,
    ) {
    }

    /**
     * @param string $id
     */
    public function __invoke(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $payload = $request->getParsedBody();
        if (!is_array($payload) || $payload === []) {
            return $this->errorResponseFactory->validation($request, 'Validation failed.', [
                'body' => ['Request body must be a non-empty JSON object.'],
            ]);
        }

        try {
            $userId = UserId::fromString($id);
        } catch (InvalidArgumentException) {
            return $this->errorResponseFactory->validation($request, 'Validation failed.', [
                'id' => ['Invalid user ID.'],
            ]);
        }

        $currentUser = $this->userReadRepository->getById($userId);
        if ($currentUser === null) {
            return $this->errorResponseFactory->notFound($request, 'User not found.');
        }

        $email = array_key_exists('email', $payload) ? $payload['email'] : $currentUser->email;
        $name = array_key_exists('name', $payload) ? $payload['name'] : $currentUser->name;

        if (!is_string($email) || trim($email) === '') {
            return $this->errorResponseFactory->validation($request, 'Validation failed.', [
                'email' => ['Email must be a non-empty string.'],
            ]);
        }

        if (!is_string($name) || trim($name) === '') {
            return $this->errorResponseFactory->validation($request, 'Validation failed.', [
                'name' => ['Name must be a non-empty string.'],
            ]);
        }

        $updated = $this->updateUserHandler->handle(new UpdateUserCommand(
            id: $id,
            email: trim($email),
            name: trim($name),
        ));

        if (array_key_exists('status', $payload)) {
            $status = $payload['status'];
            if (!is_string($status) || trim($status) === '') {
                return $this->errorResponseFactory->validation($request, 'Validation failed.', [
                    'status' => ['Status must be a non-empty string.'],
                ]);
            }

            $updated = $this->changeUserStatusHandler->handle(new ChangeUserStatusCommand($id, trim($status)));
        }

        return $this->responseFactory->success($request, [
            'id' => $updated->id,
            'email' => $updated->email,
            'name' => $updated->name,
            'status' => $updated->status,
            'created_at' => $updated->createdAt,
            'updated_at' => $updated->updatedAt,
            'deleted_at' => $updated->deletedAt,
        ]);
    }
}
