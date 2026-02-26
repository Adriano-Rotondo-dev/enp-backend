<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_auth();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID non valido']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT url FROM photos WHERE id = ?');
$stmt->execute([$id]);
$photo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($photo) {
    $filePath = __DIR__ . '/../../' . ltrim($photo['url'], '/');
    if (file_exists($filePath))
        unlink($filePath);
}

$pdo->prepare('DELETE FROM photos WHERE id = ?')->execute([$id]);

http_response_code(200);
echo json_encode(['success' => true]);