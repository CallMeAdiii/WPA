<?php
// moje-rezervace.php
session_start();
require_once 'includes/api.php';
require_once 'includes/auth.php';

requireLogin();

// Zrušení rezervace přes API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancelId = (int)$_POST['cancel_id'];
    $delResult = apiRequest('DELETE', '/api/reservations/' . $cancelId, [], getToken());
    handleUnauthorized($delResult);
    header('Location: moje-rezervace.php?cancelled=1');
    exit;
}

// Záložka: aktivní nebo zrušené
$tab = $_GET['tab'] ?? 'active';

// Načti všechny rezervace uživatele z API
$resResult = apiRequest('GET', '/api/reservations', [], getToken());
handleUnauthorized($resResult);

$allReservations = ($resResult['status'] === 200) ? $resResult['data'] : [];

// Normalizuj pole 'type' → 'facility_type' (API vrací 'type', šablona čeká 'facility_type')
// Admin vidí rezervace všech — filtrujeme jen na aktuálního uživatele
$myId = currentUserId();
$allReservations = array_map(function ($r) {
    $r['facility_type'] = $r['type'] ?? '';
    return $r;
}, $allReservations);

$allReservations = array_values(array_filter(
    $allReservations,
    fn($r) => (int)$r['user_id'] === $myId
));

// Rozděl podle statusu
$activeReservations = array_values(array_filter(
    $allReservations,
    fn($r) => $r['status'] === 'active'
));
$cancelledReservations = array_values(array_filter(
    $allReservations,
    fn($r) => $r['status'] === 'cancelled'
));

// Seřaď aktivní ASC, zrušené DESC
usort($activeReservations, fn($a, $b) =>
    strcmp($a['date'] . $a['time_from'], $b['date'] . $b['time_from'])
);
usort($cancelledReservations, fn($a, $b) =>
    strcmp($b['date'] . $b['time_from'], $a['date'] . $a['time_from'])
);

$shown = $tab === 'cancelled' ? $cancelledReservations : $activeReservations;
$nextReservation = $activeReservations[0] ?? null;

// Pomocné funkce
function formatDate(string $date): string {
    $ts = strtotime($date);
    return $ts ? date('j. n. Y', $ts) : $date;
}

function dateDay(string $date): string {
    $ts = strtotime($date);
    return $ts ? date('j', $ts) : '?';
}

function dateMonth(string $date): string {
    static $months = ['','led','úno','bře','dub','kvě','čvn','čvc','srp','zář','říj','lis','pro'];
    $ts = strtotime($date);
    return $ts ? $months[(int)date('n', $ts)] : '';
}

function facilityTypeLabel(string $type): string {
    return match($type) {
        'tělocvična' => 'Tělocvična',
        'posilovna'  => 'Posilovna',
        'ovál'       => 'Ovál',
        'hřiště'     => 'Hřiště',
        default      => ucfirst($type),
    };
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportHub – Moje rezervace</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <a href="sportovistealt.php" class="nav-logo">
        <div class="nav-logo-icon">S</div>
        <span class="nav-logo-text">SportHub</span>
    </a>
    <a href="sportovistealt.php">Sportoviště</a>
    <a href="moje-rezervace.php" class="active nav-active-bar">Moje rezervace</a>
    <?php if (isAdmin()): ?><a href="admin.php">Admin</a><?php endif; ?>
    <a href="zmena-hesla.php">Změna hesla</a>
    <a href="logout.php" class="nav-btn" style="color:white;">Odhlásit</a>
</nav>

<div class="my-res-page">
    <div class="my-res-inner">

        <h1 class="page-heading">Moje rezervace</h1>
        <p class="page-sub">Přehled tvých aktuálních rezervací</p>

        <!-- Stats -->
        <div class="res-stats-header">
            <div class="res-stat-card">
                <div class="res-stat-icon">🏟️</div>
                <div>
                    <div class="res-stat-val"><?= count($activeReservations) ?></div>
                    <div class="res-stat-label">Aktivních rezervací</div>
                </div>
            </div>
            <div class="res-stat-card">
                <div class="res-stat-icon">📅</div>
                <div>
                    <?php if ($nextReservation): ?>
                        <div class="res-stat-val" style="font-size:15px;font-weight:500;line-height:1.3;">
                            <?= formatDate($nextReservation['date']) ?>
                        </div>
                        <div class="res-stat-label"><?= htmlspecialchars($nextReservation['facility_name']) ?></div>
                    <?php else: ?>
                        <div class="res-stat-val" style="font-size:20px;color:#bbb;">—</div>
                        <div class="res-stat-label">Žádná nadcházející</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Rezervace byla úspěšně vytvořena!</div>
        <?php endif; ?>

        <?php if (isset($_GET['cancelled'])): ?>
            <div class="alert alert-success">Rezervace byla zrušena.</div>
        <?php endif; ?>

        <!-- Záložky -->
        <div class="tabs">
            <a href="?tab=active"
               class="pill <?= $tab !== 'cancelled' ? 'active' : '' ?>">
                Aktivní (<?= count($activeReservations) ?>)
            </a>
            <a href="?tab=cancelled"
               class="pill <?= $tab === 'cancelled' ? 'active' : '' ?>">
                Zrušené (<?= count($cancelledReservations) ?>)
            </a>
        </div>

        <!-- Seznam rezervací -->
        <div class="res-list">

            <?php if (empty($shown)): ?>
                <div class="res-empty">
                    <span class="res-empty-icon"><?= $tab === 'cancelled' ? '🚫' : '🏟️' ?></span>
                    <p class="res-empty-title">
                        <?= $tab === 'cancelled' ? 'Žádné zrušené rezervace' : 'Zatím žádné rezervace' ?>
                    </p>
                    <p class="res-empty-sub">
                        <?= $tab === 'cancelled'
                            ? 'Žádnou rezervaci jsi zatím nezrušil.'
                            : 'Vyber si sportoviště a rezervuj si termín.' ?>
                    </p>
                </div>

            <?php else: ?>
                <?php foreach ($shown as $r): ?>
                <div class="res-card">
                    <!-- Barevný proužek vlevo -->
                    <div class="<?= $r['status'] === 'active' ? 'res-stripe' : 'res-stripe-cancel' ?>"></div>

                    <!-- Mini kalendář -->
                    <div class="res-date-box">
                        <div class="res-date-day"><?= dateDay($r['date']) ?></div>
                        <div class="res-date-month"><?= dateMonth($r['date']) ?></div>
                    </div>

                    <!-- Info -->
                    <div class="res-info">
                        <p class="res-name"><?= htmlspecialchars($r['facility_name']) ?></p>
                        <p class="res-time">
                            <?= substr($r['time_from'], 0, 5) ?> – <?= substr($r['time_to'], 0, 5) ?>
                            · <?= (int)($r['people_count'] ?? 1) ?> os.
                        </p>
                        <p class="res-id">#<?= $r['id'] ?> · <?= facilityTypeLabel($r['facility_type']) ?></p>
                    </div>

                    <!-- Akce -->
                    <div class="res-actions">
                        <?php if ($r['status'] === 'active'): ?>
                            <span class="badge-active">aktivní</span>
                            <form method="POST" action="moje-rezervace.php"
                                  onsubmit="return confirm('Opravdu chceš zrušit tuto rezervaci?')">
                                <input type="hidden" name="cancel_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn-cancel">Zrušit</button>
                            </form>
                        <?php else: ?>
                            <span class="badge-cancelled">zrušeno</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Tlačítko nová rezervace (jen na aktivní záložce) -->
            <?php if ($tab !== 'cancelled'): ?>
            <a href="sportovistealt.php" class="new-res-btn">+ Nová rezervace</a>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="js/script.js"></script>
</body>
</html>
