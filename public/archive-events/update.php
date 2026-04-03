<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Gestione preflight per CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Usiamo POST per supportare $_FILES in PHP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST for updates with files.']);
    exit;
}

require_auth();

$baseUrl = "http://localhost/enp-backend";

// Recupero l'oggetto event inviato come stringa JSON nel FormData
$eventJson = $_POST['event'] ?? null;
$event = json_decode($eventJson, true);

if (!$event || empty($event['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dati non validi o ID mancante']);
    exit;
}

$id = (int) $event['id'];
$vol = trim($event['vol'] ?? '');
$name = trim($event['name'] ?? '');
$date = trim($event['date'] ?? '');
$description = trim($event['description'] ?? '');
$spotifyUrl = trim($event['spotifyUrl'] ?? ''); // Recupero camelCase da Angular
$liveMusicUrl = trim($event['liveMusicUrl'] ?? ''); // Recupero camelCase da Angular

$pdo = getDB();

// 1. Recupero l'URL attuale del poster dal database per non perderlo
$stmt = $pdo->prepare("SELECT poster_url FROM archive_events WHERE id = ?");
$stmt->execute([$id]);
$currentPoster = $stmt->fetchColumn();
$posterUrl = $currentPoster;

// 2. GESTIONE UPLOAD NUOVO POSTER

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // Percorso verso la tua cartella uploads/posters
    $uploadDir = __DIR__ . '/../../uploads/posters/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('poster_upd_') . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
        // Aggiorniamo l'URL solo se il caricamento è riuscito
        $posterUrl = $baseUrl . '/uploads/posters/' . $filename;
    }
}

// 3. UPDATE FINALE 
try {
    $stmt = $pdo->prepare('
            UPDATE archive_events
            SET vol = ?, 
                name = ?, 
                date = ?, 
                description = ?, 
                poster_url = ?, 
                spotify_url = ?, 
                live_music_url = ?
            WHERE id = ?
        ');

    $stmt->execute([
        $vol,
        $name,
        $date,
        $description,
        $posterUrl,
        $spotifyUrl,
        $liveMusicUrl,
        $id
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Evento aggiornato',
        'posterUrl' => $posterUrl
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore database: ' . $e->getMessage()]);
}