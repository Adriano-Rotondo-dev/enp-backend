<?php
header('Content-Type: application/json');

$methods = [
    'php://input' => file_get_contents('php://input'),
    'HTTP_CONTENT_TYPE' => $_SERVER['HTTP_CONTENT_TYPE'] ?? 'not set',
    'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'POST_data' => $_POST,
];

echo json_encode($methods);