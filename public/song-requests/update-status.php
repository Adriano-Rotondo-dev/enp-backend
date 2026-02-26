<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_auth();

$body = json_decode(file_get_contents('php://input'), true);
$id = $body['id'] ?? null;
$status = $body['status'] ?? null;

if (!$id || !in_array($status, ['pending', 'played', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dati non validi']);
    exit;
}

$pdo = getDB();
$pdo->prepare('UPDATE song_requests SET status = ? WHERE id = ?')
    ->execute([$status, $id]);

echo json_encode(['success' => true]);