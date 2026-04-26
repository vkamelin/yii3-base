<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\User;

use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\User\Application\Command\ChangeUserStatusCommand;
use App\User\Application\Handler\ChangeUserStatusHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteUserAction
{
    public function __construct(
        private ChangeUserStatusHandler $changeUserStatusHandler,
        private ApiResponseFactory $responseFactory,
    ) {}

    /**
     * @param string $id
     */
    public function __invoke(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $user = $this->changeUserStatusHandler->handle(new ChangeUserStatusCommand(
            id: $id,
            status: 'blocked',
        ));

        return $this->responseFactory->success($request, [
            'id' => $user->id,
            'status' => $user->status,
        ]);
    }
}
