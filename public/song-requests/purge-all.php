<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_auth();

$pdo = getDB();

$stmt = $pdo->prepare('DELETE FROM song_requests');
$stmt->execute();

$deleted = $stmt->rowCount();

echo json_encode([
    'success' => true,
    'deleted' => $deleted,
    'message' => "$deleted richieste eliminate"
]);