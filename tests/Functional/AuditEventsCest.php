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
use function PHPUnit\Framework\assertTrue;

use const PASSWORD_DEFAULT;

final class AuditEventsCest
{
    public function failedBearerAuthCreatesAuditEvent(FunctionalTester $tester): void
    {
        $response = $this->sendJsonRequest(
            $tester,
            'GET',
            '/api/v1/auth/me',
            null,
            ['Authorization' => 'Bearer invalid-token'],
        );
        $payload = $this->decodeJson($response->getBody()->getContents());
        $requestId = (string) ($payload['request_id'] ?? '');

        assertSame(401, $response->getStatusCode());
        assertNotEmpty($requestId);
        assertTrue($this->hasAuditEvent($requestId, 'api.token.auth.failed'));
    }

    public function rateLimitExceededCreatesAuditEvent(FunctionalTester $tester): void
    {
        $requestId = '';
        $status = 0;
        for ($i = 0; $i < 80; $i++) {
            $response = $this->sendJsonRequest($tester, 'POST', '/api/v1/auth/login', [
                'email' => 'rate-limit@example.com',
                'password' => 'definitely-invalid',
            ], [
                'X-Forwarded-For' => '198.51.100.77',
            ]);
            $payload = $this->decodeJson($response->getBody()->getContents());
            $status = $response->getStatusCode();
            if ($status === 429) {
                $requestId = (string) ($payload['request_id'] ?? '');
                break;
            }
        }

        assertSame(429, $status);
        assertNotEmpty($requestId);
        assertTrue($this->hasAuditEvent($requestId, 'api.rate_limit.exceeded'));
    }

    public function accessDeniedCreatesAuditEvent(FunctionalTester $tester): void
    {
        $email = 'audit.access.denied@example.com';
        $password = 'audit-password';
        $this->ensureUser($email, $password, 'Audit User');

        $loginResponse = $this->sendJsonRequest($tester, 'POST', '/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $loginPayload = $this->decodeJson($loginResponse->getBody()->getContents());
        $token = $loginPayload['data']['token']['value'] ?? null;

        assertIsString($token);
        assertNotEmpty($token);

        $response = $this->sendJsonRequest(
            $tester,
            'GET',
            '/api/v1/users',
            null,
            ['Authorization' => 'Bearer ' . $token],
        );
        $payload = $this->decodeJson($response->getBody()->getContents());
        $requestId = (string) ($payload['request_id'] ?? '');

        assertSame(403, $response->getStatusCode());
        assertArrayHasKey('request_id', $payload);
        assertTrue($this->hasAuditEvent($requestId, 'dashboard.access.denied'));
    }

    public function dashboardActivityLogRequiresPermission(FunctionalTester $tester): void
    {
        $response = $tester->sendRequest(new ServerRequest(method: 'GET', uri: '/dashboard/activity-log'));
        assertSame(302, $response->getStatusCode());
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

    private function hasAuditEvent(string $requestId, string $action): bool
    {
        $pdo = $this->createPdo();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM activity_logs WHERE request_id = :request_id AND action = :action',
        );
        $stmt->execute([
            ':request_id' => $requestId,
            ':action' => $action,
        ]);

        return (int) $stmt->fetchColumn() > 0;
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
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $credentialsStmt = $pdo->prepare('SELECT user_id FROM user_credentials WHERE user_id = :user_id LIMIT 1');
        $credentialsStmt->execute([':user_id' => $userId]);
        $credentialsExists = $credentialsStmt->fetchColumn() !== false;

        if ($credentialsExists) {
            $update = $pdo->prepare(
                'UPDATE user_credentials
                    SET password_hash = :password_hash,
                        password_changed_at = :password_changed_at,
                        updated_at = :updated_at
                  WHERE user_id = :user_id',
            );
            $update->execute([
                ':user_id' => $userId,
                ':password_hash' => $hash,
                ':password_changed_at' => $now,
                ':updated_at' => $now,
            ]);
            return;
        }

        $insert = $pdo->prepare(
            'INSERT INTO user_credentials (user_id, password_hash, password_changed_at, created_at, updated_at)
             VALUES (:user_id, :password_hash, :password_changed_at, :created_at, :updated_at)',
        );
        $insert->execute([
            ':user_id' => $userId,
            ':password_hash' => $hash,
            ':password_changed_at' => $now,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    private function createPdo(): PDO
    {
        return new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'mysql',
                getenv('DB_PORT') ?: '3306',
                getenv('DB_NAME') ?: 'app',
            ),
            getenv('DB_USER') ?: 'app',
            getenv('DB_PASSWORD') ?: 'app',
        );
    }
}
