<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;

use function getenv;
use function is_array;
use function json_decode;
use function json_encode;
use function mb_strtolower;
use function password_hash;
use function sprintf;
use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertIsString;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertSame;

use const PASSWORD_DEFAULT;

final class ApiV1Cest
{
    private const BASIC_USER_EMAIL = 'api.basic@example.com';
    private const BASIC_USER_PASSWORD = 'api-basic-password';

    public function loginSuccess(FunctionalTester $tester): void
    {
        $this->ensureUser(self::BASIC_USER_EMAIL, self::BASIC_USER_PASSWORD, 'API Basic User');

        $response = $this->sendJsonRequest($tester, 'POST', '/api/v1/auth/login', [
            'email' => self::BASIC_USER_EMAIL,
            'password' => self::BASIC_USER_PASSWORD,
        ]);

        $payload = $this->decodeJson($response->getBody()->getContents());

        assertSame(200, $response->getStatusCode());
        assertSame(self::BASIC_USER_EMAIL, $payload['data']['user']['email'] ?? null);
        assertNotEmpty($payload['data']['token']['value'] ?? null);
        assertArrayHasKey('request_id', $payload);
    }

    public function loginInvalidCredentials(FunctionalTester $tester): void
    {
        $response = $this->sendJsonRequest($tester, 'POST', '/api/v1/auth/login', [
            'email' => self::BASIC_USER_EMAIL,
            'password' => 'wrong-password',
        ]);

        $payload = $this->decodeJson($response->getBody()->getContents());

        assertSame(401, $response->getStatusCode());
        assertSame('UNAUTHENTICATED', $payload['error']['code'] ?? null);
        assertArrayHasKey('request_id', $payload);
    }

    public function meWithoutTokenReturns401(FunctionalTester $tester): void
    {
        $response = $this->sendJsonRequest($tester, 'GET', '/api/v1/auth/me');
        $payload = $this->decodeJson($response->getBody()->getContents());

        assertSame(401, $response->getStatusCode());
        assertSame('UNAUTHENTICATED', $payload['error']['code'] ?? null);
    }

    public function meWithTokenReturnsCurrentUser(FunctionalTester $tester): void
    {
        $this->ensureUser(self::BASIC_USER_EMAIL, self::BASIC_USER_PASSWORD, 'API Basic User');
        $token = $this->loginAndGetToken($tester, self::BASIC_USER_EMAIL, self::BASIC_USER_PASSWORD);

        $response = $this->sendJsonRequest(
            $tester,
            'GET',
            '/api/v1/auth/me',
            null,
            ['Authorization' => 'Bearer ' . $token],
        );

        $payload = $this->decodeJson($response->getBody()->getContents());

        assertSame(200, $response->getStatusCode());
        assertSame(self::BASIC_USER_EMAIL, $payload['data']['user']['email'] ?? null);
        assertArrayHasKey('request_id', $payload);
    }

    public function usersListRequiresAuth(FunctionalTester $tester): void
    {
        $response = $this->sendJsonRequest($tester, 'GET', '/api/v1/users');
        $payload = $this->decodeJson($response->getBody()->getContents());

        assertSame(401, $response->getStatusCode());
        assertSame('UNAUTHENTICATED', $payload['error']['code'] ?? null);
    }

    public function usersListRequiresPermission(FunctionalTester $tester): void
    {
        $this->ensureUser(self::BASIC_USER_EMAIL, self::BASIC_USER_PASSWORD, 'API Basic User');
        $token = $this->loginAndGetToken($tester, self::BASIC_USER_EMAIL, self::BASIC_USER_PASSWORD);

        $response = $this->sendJsonRequest(
            $tester,
            'GET',
            '/api/v1/users',
            null,
            ['Authorization' => 'Bearer ' . $token],
        );

        $payload = $this->decodeJson($response->getBody()->getContents());

        assertSame(403, $response->getStatusCode());
        assertSame('FORBIDDEN', $payload['error']['code'] ?? null);
    }

    public function validationErrorFormat(FunctionalTester $tester): void
    {
        $request = (new ServerRequest(method: 'POST', uri: '/api/v1/auth/login'))
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withBody((new StreamFactory())->createStream('{"email":'));

        $response = $tester->sendRequest($request);
        $payload = $this->decodeJson($response->getBody()->getContents());

        assertSame(422, $response->getStatusCode());
        assertSame('VALIDATION_ERROR', $payload['error']['code'] ?? null);
        assertArrayHasKey('request_id', $payload);
    }

    public function notFoundErrorFormat(FunctionalTester $tester): void
    {
        $this->ensureUser(self::BASIC_USER_EMAIL, self::BASIC_USER_PASSWORD, 'API Basic User');
        $token = $this->loginAndGetToken($tester, self::BASIC_USER_EMAIL, self::BASIC_USER_PASSWORD);

        $response = $this->sendJsonRequest(
            $tester,
            'GET',
            '/api/v1/not-existing-endpoint',
            null,
            ['Authorization' => 'Bearer ' . $token],
        );

        $payload = $this->decodeJson($response->getBody()->getContents());

        assertSame(404, $response->getStatusCode());
        assertSame('NOT_FOUND', $payload['error']['code'] ?? null);
        assertArrayHasKey('request_id', $payload);
    }

    public function bearerTokenMiddlewareRejectsInvalidToken(FunctionalTester $tester): void
    {
        $response = $this->sendJsonRequest(
            $tester,
            'GET',
            '/api/v1/auth/me',
            null,
            ['Authorization' => 'Bearer definitely-invalid-token'],
        );

        $payload = $this->decodeJson($response->getBody()->getContents());

        assertSame(401, $response->getStatusCode());
        assertSame('UNAUTHENTICATED', $payload['error']['code'] ?? null);
        assertArrayHasKey('request_id', $payload);
    }

    private function loginAndGetToken(FunctionalTester $tester, string $email, string $password): string
    {
        $response = $this->sendJsonRequest($tester, 'POST', '/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $payload = $this->decodeJson($response->getBody()->getContents());
        $token = $payload['data']['token']['value'] ?? null;
        assertIsString($token);
        assertNotEmpty($token);

        return $token;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, string> $headers
     */
    private function sendJsonRequest(
        FunctionalTester $tester,
        string $method,
        string $uri,
        ?array $payload = null,
        array $headers = [],
    ): ResponseInterface {
        $request = new ServerRequest(method: $method, uri: $uri);
        $request = $request->withHeader('Accept', 'application/json');

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($payload !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody((new StreamFactory())->createStream((string) json_encode($payload)));
        }

        return $tester->sendRequest($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $raw): array
    {
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function ensureUser(string $email, string $password, string $name): void
    {
        $pdo = $this->createPdo();
        $normalizedEmail = mb_strtolower($email);
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s.u');

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email_normalized = :email LIMIT 1');
        $stmt->execute([':email' => $normalizedEmail]);
        $userId = $stmt->fetchColumn();
        if ($userId === false) {
            $userId = Uuid::uuid7()->toString();
            $insertUser = $pdo->prepare(
                'INSERT INTO users (id, email, email_normalized, name, status, created_at, updated_at, deleted_at)
                 VALUES (:id, :email, :email_normalized, :name, :status, :created_at, :updated_at, NULL)',
            );
            $insertUser->execute([
                ':id' => $userId,
                ':email' => $email,
                ':email_normalized' => $normalizedEmail,
                ':name' => $name,
                ':status' => 'active',
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        } else {
            $updateUser = $pdo->prepare(
                'UPDATE users
                    SET email = :email,
                        name = :name,
                        status = :status,
                        deleted_at = NULL,
                        updated_at = :updated_at
                  WHERE id = :id',
            );
            $updateUser->execute([
                ':id' => $userId,
                ':email' => $email,
                ':name' => $name,
                ':status' => 'active',
                ':updated_at' => $now,
            ]);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insertCredentials = $pdo->prepare(
            'INSERT INTO user_credentials (user_id, password_hash, password_changed_at, created_at, updated_at)
             VALUES (:user_id, :password_hash, :password_changed_at, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
                password_hash = VALUES(password_hash),
                password_changed_at = VALUES(password_changed_at),
                updated_at = VALUES(updated_at)',
        );

        $insertCredentials->execute([
            ':user_id' => $userId,
            ':password_hash' => $hash,
            ':password_changed_at' => $now,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    private function createPdo(): PDO
    {
        $host = getenv('DB_HOST') ?: 'mysql';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_NAME') ?: 'app';
        $username = getenv('DB_USER') ?: 'app';
        $password = getenv('DB_PASSWORD') ?: 'app';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);
        return new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
}
