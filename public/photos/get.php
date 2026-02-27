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
    SELECT 
        p.id,
        p.url,
        p.title,
        p.tag,
        p.event_date AS eventDate,
        p.author,
        p.archive_event_id AS archiveEventId,
        ae.vol AS eventVol,
        ae.name AS eventName
    FROM photos p
    LEFT JOIN archive_events ae ON p.archive_event_id = ae.id
    ORDER BY p.id DESC
');
$stmt->execute();
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

http_response_code(200);
echo json_encode($photos);