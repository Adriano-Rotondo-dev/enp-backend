<?php
require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// legge il body JSON inviato da Angular
$body = json_decode(file_get_contents('php://input'), true);
$password = $body['password'] ?? '';

if (empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Password mancante']);
    exit;
}

// verifica la password contro l'hash bcrypt
if (!password_verify($password, ADMIN_PASSWORD_HASH)) {
    // error message per pass errata
    http_response_code(401);
    echo json_encode(['error' => 'Credenziali non valide']);
    exit;
}

// generazione JWT
function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function generate_jwt(): string
{
    $header = base64url_encode(json_encode([
        'alg' => 'HS256',
        'typ' => 'JWT'
    ]));

    $payload = base64url_encode(json_encode([
        'iat' => time(),
        'exp' => time() + TOKEN_EXPIRY,
        'role' => 'admin'
    ]));

    $signature = base64url_encode(
        hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
    );

    return "$header.$payload.$signature";
}

$token = generate_jwt();

http_response_code(200);
echo json_encode(['token' => $token]);