<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\User;

use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use App\User\Domain\ValueObject\UserId;
use App\User\Infrastructure\Persistence\UserReadRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class EditPageAction
{
    public function __construct(
        private DashboardViewRenderer $viewRenderer,
        private UserReadRepository $userReadRepository,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(string $id): ResponseInterface
    {
        try {
            $userId = UserId::fromString($id);
        } catch (InvalidArgumentException) {
            return $this->responseFactory->createResponse(404);
        }

        $user = $this->userReadRepository->getById($userId);
        if ($user === null) {
            return $this->responseFactory->createResponse(404);
        }

        return $this->viewRenderer->renderMain('User/edit', [
            'userId' => $id,
            'form' => [
                'email' => $user->email,
                'name' => $user->name,
                'status' => $user->status,
            ],
            'errors' => [],
        ]);
    }
}

