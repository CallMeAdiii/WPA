<?php
// check-availability.php – AJAX endpoint pro kontrolu dostupnosti termínu
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['available' => false, 'error' => 'Nepřihlášen']);
    exit;
}

$facilityId = (int)($_GET['id']        ?? 0);
$date       = $_GET['date']       ?? '';
$timeFrom   = $_GET['time_from']  ?? '';
$timeTo     = $_GET['time_to']    ?? '';

// Základní validace
if (!$facilityId || !$date || !$timeFrom || !$timeTo) {
    echo json_encode(['available' => false, 'error' => 'Chybějící parametry']);
    exit;
}

// Čas konce musí být po začátku
if (strtotime($date . ' ' . $timeTo) <= strtotime($date . ' ' . $timeFrom)) {
    echo json_encode(['available' => false, 'error' => 'Neplatný časový rozsah']);
    exit;
}

// Zkontroluj kolizi v databázi
$stmt = $pdo->prepare('
    SELECT id FROM reservations
    WHERE facility_id = ?
      AND date = ?
      AND status = \'active\'
      AND time_from < ?
      AND time_to   > ?
');
$stmt->execute([$facilityId, $date, $timeTo, $timeFrom]);
$conflict = $stmt->fetch();

echo json_encode(['available' => $conflict === false]);
