<?php
// check-availability.php – AJAX endpoint pro kontrolu dostupnosti termínu
//
// Pozn.: API neposkytuje dedikovaný endpoint pro kontrolu dostupnosti.
// Skutečná kontrola kolizí probíhá na backendu při POST /api/reservations (vrací 409).
// Zde pouze validujeme parametry a vracíme optimistickou odpověď.

session_start();
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['available' => false, 'error' => 'Nepřihlášen']);
    exit;
}

$facilityId = (int)($_GET['id']       ?? 0);
$date       = $_GET['date']      ?? '';
$timeFrom   = $_GET['time_from'] ?? '';
$timeTo     = $_GET['time_to']   ?? '';

// Základní validace parametrů
if (!$facilityId || !$date || !$timeFrom || !$timeTo) {
    echo json_encode(['available' => false, 'error' => 'Chybějící parametry']);
    exit;
}

// Čas konce musí být po začátku
if (strtotime($date . ' ' . $timeTo) <= strtotime($date . ' ' . $timeFrom)) {
    echo json_encode(['available' => false, 'error' => 'Neplatný časový rozsah']);
    exit;
}

// Datum nesmí být v minulosti
if (strtotime($date) < strtotime('today')) {
    echo json_encode(['available' => false, 'error' => 'Datum v minulosti']);
    exit;
}

// Vrátíme optimisticky true – kolize je ošetřena při odeslání formuláře (HTTP 409)
echo json_encode(['available' => true]);
