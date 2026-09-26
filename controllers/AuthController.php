<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/services/AuthService.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';

final class AuthController
{
    public function __construct(
        private readonly AuthService $service,
        private readonly AuthMiddleware $middleware,
        private readonly UserRepository $users
    ) {
    }

    public function register(): void
    {
        ResponseHelper::jsonResponse($this->service->register($this->jsonBody()), 201);
    }

    public function login(): void
    {
        ResponseHelper::jsonResponse($this->service->login($this->jsonBody()));
    }

    public function me(): void
    {
        $session = $this->middleware->authenticate(AuthMiddleware::authorizationHeader());
        ResponseHelper::jsonResponse($session['user']);
    }

    public function logout(): void
    {
        $session = $this->middleware->authenticate(AuthMiddleware::authorizationHeader());
        $this->users->revokeToken($session['token']);
        ResponseHelper::jsonResponse(['message' => 'Berhasil logout.']);
    }

    private function jsonBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== 0) {
            throw new ApiException('INVALID_REQUEST', 'Content-Type harus application/json.', 415);
        }
        try {
            $body = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new ApiException('INVALID_JSON', 'Body JSON tidak valid.', 400);
        }
        if (!is_array($body) || array_is_list($body)) {
            throw new ApiException('INVALID_REQUEST', 'Body harus berupa objek JSON.', 400);
        }
        return $body;
    }
}
