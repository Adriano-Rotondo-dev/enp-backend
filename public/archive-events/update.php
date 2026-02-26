<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';


// Gestione preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_auth();

$body = json_decode(file_get_contents('php://input'), true);

if (!$body || empty($body['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dati non validi']);
    exit;
}

$id = (int) $body['id'];
$vol = trim($body['vol'] ?? '');
$name = trim($body['name'] ?? '');
$date = trim($body['date'] ?? '');
$description = trim($body['description'] ?? '');

if (empty($vol) || empty($name) || empty($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'vol, name e date sono obbligatori']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('
    UPDATE archive_events
    SET vol = ?, name = ?, date = ?, description = ?
    WHERE id = ?
');
$stmt->execute([$vol, $name, $date, $description, $id]);

http_response_code(200);
echo json_encode(['success' => true]);