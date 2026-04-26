<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\User;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\User\Application\Command\CreateUserCommand;
use App\User\Application\DTO\UserView;
use App\User\Application\Handler\CreateUserHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function filter_var;
use function is_array;
use function is_string;
use function trim;

use const FILTER_VALIDATE_EMAIL;

final readonly class CreateUserAction
{
    public function __construct(
        private CreateUserHandler $createUserHandler,
        private ApiResponseFactory $responseFactory,
        private ApiErrorResponseFactory $errorResponseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $payload = $request->getParsedBody();
        if (!is_array($payload)) {
            return $this->errorResponseFactory->validation($request, 'Validation failed.', [
                'body' => ['Request body must be a JSON object.'],
            ]);
        }

        $errors = [];
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? null;

        if (!is_string($email) || trim($email) === '') {
            $errors['email'][] = 'Email is required.';
        } elseif (filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'Invalid email.';
        }

        if (!is_string($name) || trim($name) === '') {
            $errors['name'][] = 'Name is required.';
        }

        if ($errors !== []) {
            return $this->errorResponseFactory->validation($request, 'Validation failed.', $errors);
        }

        $user = $this->createUserHandler->handle(new CreateUserCommand(trim($email), trim($name)));

        return $this->responseFactory->success($request, $this->userToArray($user), 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function userToArray(UserView $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'status' => $user->status,
            'created_at' => $user->createdAt,
            'updated_at' => $user->updatedAt,
            'deleted_at' => $user->deletedAt,
        ];
    }
}
