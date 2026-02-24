<?php
require_once __DIR__ . '/../config/config.php';

function base64url_decode(string $data): string
{
    return base64_decode(str_pad(
        strtr($data, '-_', '+/'),
        strlen($data) % 4,
        '=',
        STR_PAD_RIGHT
    ));
}

function verify_jwt(string $token): array|false
{
    $parts = explode('.', $token);
    if (count($parts) !== 3)
        return false;

    [$header, $payload, $signature] = $parts;

    $expected = rtrim(strtr(base64_encode(
        hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
    ), '+/', '-_'), '=');

    if (!hash_equals($expected, $signature))
        return false;

    $data = json_decode(base64url_decode($payload), true);
    if (!$data || $data['exp'] < time())
        return false;

    return $data;
}

function require_auth(): array
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['error' => 'Token mancante']);
        exit;
    }

    $token = substr($authHeader, 7);
    $payload = verify_jwt($token);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Token non valido o scaduto']);
        exit;
    }

    return $payload;
}