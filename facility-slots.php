<?php
// facility-slots.php – AJAX proxy: vrátí obsazenost sportoviště po hodinách
session_start();
require_once 'includes/api.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Přihlašte se']);
    exit;
}

$id   = (int)($_GET['id']   ?? 0);
$date = $_GET['date'] ?? '';

if ($id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatné parametry']);
    exit;
}

$result = apiRequest('GET', "/api/facilities/{$id}/slots?date={$date}", [], getToken());
http_response_code($result['status']);
echo json_encode($result['data']);
