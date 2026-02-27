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

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'File mancante o errore upload']);
    exit;
}

$photoJson = $_POST['photo'] ?? null;
if (!$photoJson) {
    http_response_code(400);
    echo json_encode(['error' => 'Metadati foto mancanti']);
    exit;
}

$photo = json_decode($photoJson, true);
if (!$photo) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON non valido']);
    exit;
}

$title = trim($photo['title'] ?? '');
$tag = trim($photo['tag'] ?? '');
$eventDate = trim($photo['eventDate'] ?? '');
$author = trim($photo['author'] ?? '');
$archiveEventId = !empty($photo['archiveEventId']) ? (int) $photo['archiveEventId'] : null;

if (empty($title) || empty($tag)) {
    http_response_code(400);
    echo json_encode(['error' => 'Titolo e tag sono obbligatori']);
    exit;
}

$uploadDir = __DIR__ . '/../../uploads/photos/';
if (!is_dir($uploadDir))
    mkdir($uploadDir, 0755, true);

$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$filename = uniqid('photo_') . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore salvataggio file']);
    exit;
}

// TODO : CAMBIARE IN PRODUZIONE CON IL DOMINIO REALE
$url = 'http://localhost/enp-backend/uploads/photos/' . $filename;

$pdo = getDB();
$stmt = $pdo->prepare('
    INSERT INTO photos (url, title, tag, event_date, author, archive_event_id)
    VALUES (?, ?, ?, ?, ?, ?)
');
$stmt->execute([$url, $title, $tag, $eventDate, $author, $archiveEventId]);
$newId = (int) $pdo->lastInsertId();

http_response_code(201);
echo json_encode([
    'id' => $newId,
    'url' => $url,
    'title' => $title,
    'tag' => $tag,
    'eventDate' => $eventDate,
    'author' => $author,
    'archiveEventId' => $archiveEventId
]);