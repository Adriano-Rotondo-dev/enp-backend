<?php
require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare('
    SELECT *, DATE_FORMAT(date, "%Y-%m-%dT%H:%i:%s") AS date_formatted
    FROM next_event
    ORDER BY id DESC LIMIT 1
');
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    echo json_encode(['error' => 'Nessun evento trovato']);
    exit;
}

$stmt = $pdo->prepare('
    SELECT time, act 
    FROM next_event_lineup 
    WHERE event_id = ? 
    ORDER BY sort_order ASC
');
$stmt->execute([$event['id']]);
$lineup = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'id' => (string) $event['id'],
    'title' => $event['title'],
    'date' => $event['date_formatted'],
    'time' => $event['time'],
    'location' => $event['location'],
    'address' => $event['address'],
    'mapsUrl' => $event['maps_url'],
    'description' => $event['description'],
    'price' => (float) $event['price'],
    'lineup' => $lineup
]);