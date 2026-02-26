<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_auth();

$body = $_POST;
if (empty($body)) {
    $body = json_decode(file_get_contents('php://input'), true);
}

if (empty($body) || empty($body['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dati non validi']);
    exit;
}

$id = (int) $body['id'];
$title = trim($body['title'] ?? '');
$tag = trim($body['tag'] ?? '');
$eventDate = trim($body['eventDate'] ?? '');
$author = trim($body['author'] ?? '');

if (empty($title) || empty($tag)) {
    http_response_code(400);
    echo json_encode(['error' => 'Titolo e tag sono obbligatori']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('
    UPDATE photos
    SET title = ?, tag = ?, event_date = ?, author = ?
    WHERE id = ?
');
$stmt->execute([$title, $tag, $eventDate, $author, $id]);

http_response_code(200);
echo json_encode(['success' => true]);