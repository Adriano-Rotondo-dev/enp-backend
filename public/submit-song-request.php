<?php
// Leggi il body PRIMA di includere cors.php
$rawBody = file_get_contents('php://input');

require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body = json_decode($rawBody, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON non valido', 'raw' => $rawBody]);
    exit;
}

$userEmail = trim($body['userEmail'] ?? '');
$songRequest = trim($body['songRequest'] ?? '');
$eventId = $body['eventId'] ?? null;

if (empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email non valida', 'received' => $userEmail]);
    exit;
}

if (empty($songRequest) || strlen($songRequest) < 3) {
    http_response_code(400);
    echo json_encode(['error' => 'Richiesta troppo corta']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM song_requests WHERE user_email = ? AND event_id = ?');
$stmt->execute([$userEmail, $eventId]);
$count = $stmt->fetchColumn();

if ($count >= 2) {
    http_response_code(429);
    echo json_encode(['error' => 'Hai già inviato troppe richieste per questo evento']);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO song_requests (event_id, user_email, song_request) VALUES (?, ?, ?)');
$stmt->execute([$eventId, $userEmail, $songRequest]);

http_response_code(201);
echo json_encode(['success' => true]);