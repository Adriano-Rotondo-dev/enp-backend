<?php
require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// legge il body JSON inviato da Angular
$body = json_decode(file_get_contents('php://input'), true);
$password = $body['password'] ?? '';
$username = $body['username'] ?? 'admin';

if (empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Password mancante']);
    exit;
}

// Legge dal DB invece che da config.php
$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Credenziali non valide']);
    exit;
}

// Aggiorna last_login
$pdo->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')
    ->execute([$admin['id']]);

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