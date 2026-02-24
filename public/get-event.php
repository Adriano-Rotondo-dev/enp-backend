<?php
require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare('SELECT * FROM next_event ORDER BY id DESC LIMIT 1');
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    echo json_encode(['error' => 'Nessun evento trovato']);
    exit;
}

$stmt = $pdo->prepare('SELECT time, act FROM next_event_lineup WHERE event_id = ? ORDER BY sort_order ASC');
$stmt->execute([$event['id']]);
$lineup = $stmt->fetchAll();

echo json_encode([
    'id' => (string) $event['id'],
    'title' => $event['title'],
    'date' => $event['date'],
    'time' => $event['time'],
    'location' => $event['location'],
    'address' => $event['address'],
    'mapsUrl' => $event['maps_url'],
    'description' => $event['description'],
    'price' => (float) $event['price'],
    'lineup' => $lineup
]);

