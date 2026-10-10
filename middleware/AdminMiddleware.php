<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/repositories/UserRepository.php';

final class AdminMiddleware
{
    public function __construct(private readonly AuthMiddleware $auth)
    {
    }

    /**
     * Authenticate + authorize admin role.
     * Return session (token + user) kalau admin. Throw 403 kalau bukan admin.
     */
    public function authorize(?string $authorization): array
    {
        $session = $this->auth->authenticate($authorization);

        if ($session['user']['role'] !== 'admin') {
            throw new ApiException(
                'FORBIDDEN',
                'Akses ditolak. Hanya administrator yang dapat mengakses endpoint ini.',
                403
            );
        }

        return $session;
    }

    public static function authorizationHeader(): ?string
    {
        return AuthMiddleware::authorizationHeader();
    }
}