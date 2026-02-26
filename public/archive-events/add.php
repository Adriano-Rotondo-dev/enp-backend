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

$eventJson = $_POST['event'] ?? null;
if (!$eventJson) {
    http_response_code(400);
    echo json_encode(['error' => 'Dati evento mancanti']);
    exit;
}

$event = json_decode($eventJson, true);
if (!$event) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON non valido']);
    exit;
}

$vol = trim($event['vol'] ?? '');
$name = trim($event['name'] ?? '');
$date = trim($event['date'] ?? '');
$description = trim($event['description'] ?? '');

if (empty($vol) || empty($name) || empty($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'vol, name e date sono obbligatori']);
    exit;
}

// Gestione upload poster
$posterUrl = '/poster_placeholder.webp';
if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/posters/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0755, true);

    $ext = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('poster_') . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['poster']['tmp_name'], $destPath)) {
        $posterUrl = '/uploads/posters/' . $filename;
    }
}

$pdo = getDB();
$stmt = $pdo->prepare('
    INSERT INTO archive_events (vol, name, date, description, poster_url)
    VALUES (?, ?, ?, ?, ?)
');
$stmt->execute([$vol, $name, $date, $description, $posterUrl]);
$newId = (int) $pdo->lastInsertId();

http_response_code(201);
echo json_encode([
    'id' => $newId,
    'vol' => $vol,
    'name' => $name,
    'date' => $date,
    'description' => $description,
    'posterUrl' => $posterUrl
]);
