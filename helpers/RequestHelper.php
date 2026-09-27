<?php
declare(strict_types=1);

require_once __DIR__ . '/ApiException.php';

final class RequestHelper
{
    public static function jsonBody(): array
    {
        if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
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
