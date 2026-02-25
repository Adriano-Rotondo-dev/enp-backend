<?php
require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_auth();

$pdo = getDB();
$stmt = $pdo->prepare('
  SELECT sr.*, ne.title as event_title
  FROM song_requests sr
  LEFT JOIN next_event ne ON sr.event_id = ne.id
  ORDER BY sr.requested_at DESC
');
$stmt->execute();
$requests = $stmt->fetchAll();

echo json_encode($requests);