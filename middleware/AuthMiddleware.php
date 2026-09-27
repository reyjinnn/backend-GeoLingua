<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/repositories/UserRepository.php';

final class AuthMiddleware
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function authenticate(?string $authorization): array
    {
        if ($authorization === null || preg_match('/^Bearer ([a-fA-F0-9]{64})$/', trim($authorization), $matches) !== 1) {
            throw new ApiException('UNAUTHORIZED', 'Token autentikasi tidak valid.', 401);
        }

        $token = strtolower($matches[1]);
        $user = $this->users->findByValidToken($token, gmdate('Y-m-d H:i:s'));
        if ($user === null) {
            throw new ApiException('UNAUTHORIZED', 'Token autentikasi tidak valid atau kedaluwarsa.', 401);
        }
        $user['id'] = (int) $user['id'];
        return ['token' => $token, 'user' => $user];
    }

    public static function authorizationHeader(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if ($header === null && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    $header = $value;
                    break;
                }
            }
        }
        return is_string($header) ? $header : null;
    }
}
