<?php
declare(strict_types=1);

final class ResponseHelper
{
    public static function jsonResponse(mixed $data, int $status = 200): void
    {
        self::send([
            'success' => true,
            'data' => $data,
            'meta' => ['timestamp' => gmdate('Y-m-d\TH:i:s\Z')],
        ], $status);
    }

    public static function jsonError(string $code, string $message, int $status, array $details = []): void
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== []) {
            $error['details'] = $details;
        }
        self::send([
            'success' => false,
            'error' => $error,
        ], $status);
    }

    private static function send(array $body, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
