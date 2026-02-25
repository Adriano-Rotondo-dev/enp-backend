<?php
// 1. CATTURA IMMEDIATA (Prima di ogni altra operazione)
$rawBody = file_get_contents('php://input');

// 2. HEADER CORS & JSON
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

// 3. GESTIONE PRE-FLIGHT (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Includi il database qui (assicurati che il percorso sia corretto)
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 4. PARSING DEL BODY (Con fallback per XAMPP)
$body = json_decode($rawBody, true);

// Se json_decode fallisce, proviamo a vedere se PHP ha popolato $_POST automaticamente
if (empty($body) && !empty($_POST)) {
    $body = $_POST;
}

if (empty($body)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'JSON non valido o body vuoto',
        'raw_received' => $rawBody,
        'post_data' => $_POST // Ti aiuta a capire se i dati sono finiti qui
    ]);
    exit;
}

// 5. LOGICA DI BUSINESS
try {
    $userEmail = trim($body['userEmail'] ?? '');
    $songRequest = trim($body['songRequest'] ?? '');
    $eventId = $body['eventId'] ?? null;

    if (empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email non valida']);
        exit;
    }

    if (empty($songRequest) || strlen($songRequest) < 3) {
        http_response_code(400);
        echo json_encode(['error' => 'Richiesta troppo corta']);
        exit;
    }

    $pdo = getDB();

    // Controllo spam
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM song_requests WHERE user_email = ? AND event_id = ?');
    $stmt->execute([$userEmail, $eventId]);
    if ($stmt->fetchColumn() >= 2) {
        http_response_code(429);
        echo json_encode(['error' => 'Hai già inviato troppe richieste per questo evento']);
        exit;
    }

    // Inserimento
    $stmt = $pdo->prepare('INSERT INTO song_requests (event_id, user_email, song_request) VALUES (?, ?, ?)');
    $stmt->execute([$eventId, $userEmail, $songRequest]);

    http_response_code(201);
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore Database', 'details' => $e->getMessage()]);
}