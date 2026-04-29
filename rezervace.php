<?php
// rezervace.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

// Načteme sportoviště podle ID z URL
$facilityId = (int)($_GET['id'] ?? 0);
if ($facilityId <= 0) {
    header('Location: sportovistealt.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM facilities WHERE id = ?');
$stmt->execute([$facilityId]);
$facility = $stmt->fetch();

if (!$facility) {
    header('Location: sportovistealt.php');
    exit;
}

$errors  = [];
$success = false;

// Výchozí hodnoty formuláře
$date     = $_POST['date']      ?? date('Y-m-d');
$timeFrom = $_POST['time_from'] ?? '10:00';
$timeTo   = $_POST['time_to']   ?? '11:00';

// Zkontroluj dostupnost (AJAX i normální POST)
$isAvailable = null;
$duration    = null;

if ($date && $timeFrom && $timeTo) {
    // Vypočítej délku
    $from = strtotime($date . ' ' . $timeFrom);
    $to   = strtotime($date . ' ' . $timeTo);
    if ($to > $from) {
        $duration = ($to - $from) / 60; // minuty

        // Zkontroluj kolizi rezervací pro dané sportoviště ve stejný den a čas
        $stmtCheck = $pdo->prepare('
            SELECT id FROM reservations
            WHERE facility_id = ?
              AND date = ?
              AND status = \'active\'
              AND time_from < ?
              AND time_to   > ?
        ');
        $stmtCheck->execute([$facilityId, $date, $timeTo, $timeFrom]);
        $isAvailable = $stmtCheck->fetch() === false;
    }
}

// Zpracování odeslání formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (empty($date)) {
        $errors['date'] = 'Vyber datum.';
    } elseif (strtotime($date) < strtotime('today')) {
        $errors['date'] = 'Datum nemůže být v minulosti.';
    }

    if (empty($timeFrom)) {
        $errors['time_from'] = 'Zadej čas začátku.';
    }

    if (empty($timeTo)) {
        $errors['time_to'] = 'Zadej čas konce.';
    }

    if (empty($errors)) {
        $from = strtotime($date . ' ' . $timeFrom);
        $to   = strtotime($date . ' ' . $timeTo);

        if ($to <= $from) {
            $errors['time_to'] = 'Čas konce musí být po čase začátku.';
        }
    }

    if (empty($errors) && !$isAvailable) {
        $errors['general'] = 'Termín je již obsazený. Vyber jiný čas.';
    }

    if (empty($errors)) {
        $stmtIns = $pdo->prepare('
            INSERT INTO reservations (user_id, facility_id, date, time_from, time_to, status)
            VALUES (?, ?, ?, ?, ?, \'active\')
        ');
        $stmtIns->execute([currentUserId(), $facilityId, $date, $timeFrom, $timeTo]);
        header('Location: moje-rezervace.php?success=1');
        exit;
    }
}

// Pomocné funkce pro SVG vizuál (stejné jako ve sportovistealt.php)
function reservationVisualSVG(string $type): string {
    // Fotbalové hřiště je výchozí vizuál pro rezervaci (ze screenshotu)
    return '
        <svg viewBox="0 0 450 540" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <rect width="450" height="540" fill="#1c5c22"/>
            <rect x="0"   y="0" width="50" height="540" fill="rgba(0,0,0,0.07)"/>
            <rect x="100" y="0" width="50" height="540" fill="rgba(0,0,0,0.07)"/>
            <rect x="200" y="0" width="50" height="540" fill="rgba(0,0,0,0.07)"/>
            <rect x="300" y="0" width="50" height="540" fill="rgba(0,0,0,0.07)"/>
            <rect x="400" y="0" width="50" height="540" fill="rgba(0,0,0,0.07)"/>
            <rect x="36" y="48" width="378" height="448" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2.5"/>
            <line x1="36" y1="272" x2="414" y2="272" stroke="rgba(255,255,255,0.6)" stroke-width="2"/>
            <circle cx="225" cy="272" r="62" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"/>
            <circle cx="225" cy="272" r="5" fill="rgba(255,255,255,0.7)"/>
            <rect x="112" y="48"  width="226" height="90" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
            <rect x="158" y="48"  width="134" height="48" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
            <rect x="112" y="406" width="226" height="90" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
            <rect x="158" y="448" width="134" height="48" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
            <rect x="178" y="37"  width="94" height="13" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"/>
            <rect x="178" y="494" width="94" height="13" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"/>
            <circle cx="225" cy="106" r="4" fill="rgba(255,255,255,0.6)"/>
            <circle cx="225" cy="438" r="4" fill="rgba(255,255,255,0.6)"/>
            <path d="M36 60 A12 12 0 0 1 48 48"   fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <path d="M402 48 A12 12 0 0 1 414 60"  fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <path d="M36 484 A12 12 0 0 0 48 496"  fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <path d="M402 496 A12 12 0 0 0 414 484" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <text x="225" y="34" text-anchor="middle" font-family="sans-serif" font-size="9"
                  fill="rgba(255,255,255,0.35)" letter-spacing="4">
                FOTBALOVÉ HŘIŠTĚ
            </text>
        </svg>';
}

// Formát data pro zobrazení (Y-m-d → d. m. Y)
function formatDate(string $date): string {
    $ts = strtotime($date);
    return $ts ? date('j. n. Y', $ts) : $date;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportHub – Rezervace · <?= htmlspecialchars($facility['name']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <div class="nav-logo">
        <div class="nav-logo-icon">S</div>
        <span class="nav-logo-text">SportHub</span>
    </div>
    <a href="sportovistealt.php" class="active nav-active-bar">Sportoviště</a>
    <a href="moje-rezervace.php">Moje rezervace</a>
    <a href="logout.php" class="nav-btn" style="color:white;">Odhlásit</a>
</nav>

<div class="reservation-page">
    <div class="reservation-layout">

        <!-- Levá strana – vizuál sportoviště -->
        <div class="reservation-visual">
            <?= reservationVisualSVG($facility['type']) ?>
        </div>

        <!-- Pravá strana – formulář -->
        <div class="res-form-side">
            <p class="breadcrumb">
                <a href="sportovistealt.php">Sportoviště</a> › <span>Rezervace</span>
            </p>
            <h2>Nová rezervace</h2>

            <div style="margin-bottom:20px; margin-top:8px;">
                <span class="facility-badge">
                    <span class="badge-dot"></span>
                    <?= htmlspecialchars($facility['name']) ?>
                </span>
            </div>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <form method="POST" action="rezervace.php?id=<?= $facilityId ?>" id="reservationForm" novalidate>
                <input type="hidden" name="submit" value="1">

                <!-- Datum a čas -->
                <div class="form-card">
                    <div class="form-group">
                        <label for="date">Datum</label>
                        <input
                            type="date"
                            id="date"
                            name="date"
                            value="<?= htmlspecialchars($date) ?>"
                            min="<?= date('Y-m-d') ?>"
                            class="<?= isset($errors['date']) ? 'input-error' : '' ?>"
                            style="max-width:200px;"
                        >
                        <?php if (isset($errors['date'])): ?>
                            <span class="field-error"><?= htmlspecialchars($errors['date']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="time-row">
                        <div class="form-group">
                            <label for="time_from">Čas začátku</label>
                            <input
                                type="time"
                                id="time_from"
                                name="time_from"
                                value="<?= htmlspecialchars($timeFrom) ?>"
                                step="1800"
                                class="<?= isset($errors['time_from']) ? 'input-error' : '' ?>"
                            >
                            <?php if (isset($errors['time_from'])): ?>
                                <span class="field-error"><?= htmlspecialchars($errors['time_from']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="time_to">Čas konce</label>
                            <input
                                type="time"
                                id="time_to"
                                name="time_to"
                                value="<?= htmlspecialchars($timeTo) ?>"
                                step="1800"
                                class="<?= isset($errors['time_to']) ? 'input-error' : '' ?>"
                            >
                            <?php if (isset($errors['time_to'])): ?>
                                <span class="field-error"><?= htmlspecialchars($errors['time_to']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Souhrn -->
                <div class="form-card">
                    <div class="summary-row">
                        <span>Sportoviště</span>
                        <span class="val"><?= htmlspecialchars($facility['name']) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Datum</span>
                        <span class="val" id="summaryDate">
                            <?= $date ? formatDate($date) : '—' ?>
                        </span>
                    </div>
                    <div class="summary-row">
                        <span>Čas</span>
                        <span class="val" id="summaryTime">
                            <?= $timeFrom && $timeTo ? $timeFrom . ' – ' . $timeTo : '—' ?>
                        </span>
                    </div>
                    <div class="summary-row">
                        <span>Délka</span>
                        <span class="val" id="summaryDuration">
                            <?php if ($duration): ?>
                                <?= $duration >= 60
                                    ? floor($duration/60) . ' h ' . ($duration % 60 ? ($duration % 60) . ' min' : '')
                                    : $duration . ' minut' ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Dostupnost -->
                <?php if ($isAvailable === true): ?>
                    <div class="avail-ok">
                        <span class="avail-dot"></span>Termín je volný
                    </div>
                <?php elseif ($isAvailable === false): ?>
                    <div class="avail-error">
                        <span class="avail-error-dot"></span>Termín je obsazený
                    </div>
                <?php endif; ?>

                <button
                    type="submit"
                    class="btn-primary"
                    style="margin-bottom:0;"
                    <?= ($isAvailable === false) ? 'disabled style="opacity:.5;cursor:not-allowed;margin-bottom:0;"' : '' ?>
                >
                    Potvrdit rezervaci
                </button>
            </form>
        </div>

    </div>
</div>

<script src="js/script.js"></script>
</body>
</html>
