<?php
require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verifica JWT - blocca se non viene autenticato
require_auth();

$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Body non valido']);
    exit;
}

// Validazione campi obbligatori
$required = ['title', 'date', 'time', 'location', 'address'];
foreach ($required as $field) {
    if (empty($body[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Campo obbligatorio mancante: $field"]);
        exit;
    }
}

$pdo = getDB();

// aggiorna l'evento principale
$stmt = $pdo->prepare('
UPDATE next_event SET
   title       = ?,
    date        = ?,
    time        = ?,
    location    = ?,
    address     = ?,
    maps_url    = ?,
    description = ?,
    price       = ?
  WHERE id = 1
');

$stmt->execute([
    $body['title'],
    $body['date'],
    $body['time'],
    $body['location'],
    $body['address'],
    $body['mapsUrl'] ?? '',
    $body['description'] ?? '',
    $body['price'] ?? 0
]);

// Update della lineup - delete e reinsert
if (!empty($body['lineup']) && is_array($body['lineup'])) {
    $pdo->prepare('DELETE FROM next_event_lineup WHERE event_id = 1')->execute();

    $stmt = $pdo->prepare('
    INSERT INTO next_event_lineup (event_id, time, act, sort_order)
    VALUES (1, ?, ?, ?)
  ');

    foreach ($body['lineup'] as $index => $item) {
        if (!empty($item['time']) && !empty($item['act'])) {
            $stmt->execute([$item['time'], $item['act'], $index]);
        }
    }
}

http_response_code(200);
echo json_encode(['success' => true]);