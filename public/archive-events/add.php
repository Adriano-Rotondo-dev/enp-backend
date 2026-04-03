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

// 1. DEFINISCI IL DOMINIO
$baseUrl = "http://localhost/enp-backend";

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
$spotifyUrl = trim($event['spotifyUrl'] ?? '');
$liveMusicUrl = trim($event['liveMusicUrl'] ?? '');

if (empty($vol) || empty($name) || empty($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'vol, name e date sono obbligatori']);
    exit;
}

// 2. GESTIONE UPLOAD POSTER
// Importante: Cambiato da 'poster' a 'file' per coerenza con l'update e il service
$posterUrl = $baseUrl . '/poster_placeholder.webp';

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // Corretto il percorso: deve salire di due livelli per uscire da archive-events/
    $uploadDir = __DIR__ . '/../../uploads/posters/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('poster_') . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
        $posterUrl = $baseUrl . '/uploads/posters/' . $filename;
    }
}

// 3. SALVATAGGIO NEL DATABASE
try {
    $pdo = getDB();
    $stmt = $pdo->prepare('
        INSERT INTO archive_events (vol, name, date, description, poster_url, spotify_url, live_music_url)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $vol,
        $name,
        $date,
        $description,
        $posterUrl,
        $spotifyUrl,
        $liveMusicUrl
    ]);

    $newId = (int) $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'id' => $newId,
        'vol' => $vol,
        'name' => $name,
        'date' => $date,
        'description' => $description,
        'posterUrl' => $posterUrl,
        'spotifyUrl' => $spotifyUrl,
        'liveMusicUrl' => $liveMusicUrl
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore database: ' . $e->getMessage()]);
}