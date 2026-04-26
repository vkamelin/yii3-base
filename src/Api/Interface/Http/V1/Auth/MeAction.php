<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\Auth;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\Auth\Application\DTO\AuthResult;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class MeAction
{
    public function __construct(
        private ApiResponseFactory $responseFactory,
        private ApiErrorResponseFactory $errorResponseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $authResult = $request->getAttribute(RequestAttributes::AUTH_RESULT);
        if (!$authResult instanceof AuthResult) {
            return $this->errorResponseFactory->unauthenticated($request, 'Unauthenticated.');
        }

        return $this->responseFactory->success($request, [
            'user' => [
                'id' => $authResult->userId,
                'email' => $authResult->email,
                'name' => $authResult->name,
                'status' => $authResult->status,
            ],
        ]);
    }
}
