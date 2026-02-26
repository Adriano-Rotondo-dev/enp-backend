<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('
    SELECT id, vol, name, date, description, poster_url AS posterUrl
    FROM archive_events
    ORDER BY id DESC
');
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

http_response_code(200);
echo json_encode($events);