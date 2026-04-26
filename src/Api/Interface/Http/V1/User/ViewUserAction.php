<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\User;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\User\Domain\ValueObject\UserId;
use App\User\Infrastructure\Persistence\UserReadRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ViewUserAction
{
    public function __construct(
        private UserReadRepository $userReadRepository,
        private ApiResponseFactory $responseFactory,
        private ApiErrorResponseFactory $errorResponseFactory,
    ) {}

    /**
     * @param string $id
     */
    public function __invoke(ServerRequestInterface $request, string $id): ResponseInterface
    {
        try {
            $userId = UserId::fromString($id);
        } catch (InvalidArgumentException) {
            return $this->errorResponseFactory->validation($request, 'Validation failed.', [
                'id' => ['Invalid user ID.'],
            ]);
        }

        $user = $this->userReadRepository->getById($userId);
        if ($user === null) {
            return $this->errorResponseFactory->notFound($request, 'User not found.');
        }

        return $this->responseFactory->success($request, [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'status' => $user->status,
            'created_at' => $user->createdAt,
            'updated_at' => $user->updatedAt,
            'deleted_at' => $user->deletedAt,
        ]);
    }
}
