<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\Rbac;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\Rbac\Application\Command\CreateRoleCommand;
use App\Rbac\Application\Handler\CreateRoleHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_array;
use function is_string;
use function trim;

final readonly class CreateRoleAction
{
    public function __construct(
        private CreateRoleHandler $createRoleHandler,
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
        $code = $payload['code'] ?? null;
        $name = $payload['name'] ?? null;
        $description = $payload['description'] ?? null;
        $isSystem = (bool) ($payload['is_system'] ?? false);

        if (!is_string($code) || trim($code) === '') {
            $errors['code'][] = 'Code is required.';
        }

        if (!is_string($name) || trim($name) === '') {
            $errors['name'][] = 'Name is required.';
        }

        if ($description !== null && !is_string($description)) {
            $errors['description'][] = 'Description must be a string.';
        }

        if ($errors !== []) {
            return $this->errorResponseFactory->validation($request, 'Validation failed.', $errors);
        }

        $role = $this->createRoleHandler->handle(new CreateRoleCommand(
            code: trim($code),
            name: trim($name),
            description: $description !== null ? trim($description) : null,
            isSystem: $isSystem,
        ));

        return $this->responseFactory->success($request, [
            'id' => $role->id,
            'code' => $role->code,
            'name' => $role->name,
            'description' => $role->description,
            'is_system' => $role->isSystem,
            'created_at' => $role->createdAt,
            'updated_at' => $role->updatedAt,
        ], 201);
    }
}
