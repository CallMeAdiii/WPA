<?php
// moje-rezervace.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$userId = currentUserId();

// Zrušení rezervace
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancelId = (int)$_POST['cancel_id'];
    // Zruš jen pokud patří přihlášenému uživateli
    $stmt = $pdo->prepare('
        UPDATE reservations SET status = \'cancelled\'
        WHERE id = ? AND user_id = ? AND status = \'active\'
    ');
    $stmt->execute([$cancelId, $userId]);
    header('Location: moje-rezervace.php?cancelled=1');
    exit;
}

// Záložka: aktivní nebo zrušené
$tab = $_GET['tab'] ?? 'active';

// Načti aktivní rezervace uživatele
$stmtActive = $pdo->prepare('
    SELECT r.*, f.name AS facility_name, f.type AS facility_type
    FROM reservations r
    JOIN facilities f ON f.id = r.facility_id
    WHERE r.user_id = ? AND r.status = \'active\'
    ORDER BY r.date ASC, r.time_from ASC
');
$stmtActive->execute([$userId]);
$activeReservations = $stmtActive->fetchAll();

// Načti zrušené rezervace uživatele
$stmtCancelled = $pdo->prepare('
    SELECT r.*, f.name AS facility_name, f.type AS facility_type
    FROM reservations r
    JOIN facilities f ON f.id = r.facility_id
    WHERE r.user_id = ? AND r.status = \'cancelled\'
    ORDER BY r.date DESC, r.time_from DESC
');
$stmtCancelled->execute([$userId]);
$cancelledReservations = $stmtCancelled->fetchAll();

$shown = $tab === 'cancelled' ? $cancelledReservations : $activeReservations;

// Pomocné funkce
function formatDate(string $date): string {
    $ts = strtotime($date);
    return $ts ? date('j. n. Y', $ts) : $date;
}

function facilityIcon(string $type): string {
    return match($type) {
        'tělocvična' => '🏀',
        'posilovna'  => '🏋️',
        'ovál'       => '🏃',
        'hřiště'     => '⚽',
        default      => '🏟️',
    };
}

function iconBgClass(string $type): string {
    return match($type) {
        'tělocvična' => 'icon-blue',
        'posilovna'  => 'icon-dark',
        'ovál'       => 'icon-blue',
        'hřiště'     => 'icon-green',
        default      => 'icon-green',
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
    <div class="nav-logo">
        <div class="nav-logo-icon">S</div>
        <span class="nav-logo-text">SportHub</span>
    </div>
    <a href="sportovistealt.php">Sportoviště</a>
    <a href="moje-rezervace.php" class="active nav-active-bar">Moje rezervace</a>
    <a href="logout.php" class="nav-btn" style="color:white;">Odhlásit</a>
</nav>

<div class="my-res-page">
    <div class="my-res-inner">

        <h1 class="page-heading">Moje rezervace</h1>
        <p class="page-sub">Přehled tvých aktuálních rezervací</p>

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
                <p class="res-empty">
                    <?= $tab === 'cancelled' ? 'Žádné zrušené rezervace.' : 'Nemáš žádné aktivní rezervace.' ?>
                </p>

            <?php else: ?>
                <?php foreach ($shown as $r): ?>
                <div class="res-card">
                    <!-- Barevný proužek vlevo -->
                    <div class="<?= $r['status'] === 'active' ? 'res-stripe' : 'res-stripe-cancel' ?>"></div>

                    <!-- Ikona sportoviště -->
                    <div class="res-icon-box">
                        <div class="res-icon <?= iconBgClass($r['facility_type']) ?>">
                            <?= facilityIcon($r['facility_type']) ?>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="res-info">
                        <p class="res-name"><?= htmlspecialchars($r['facility_name']) ?></p>
                        <p class="res-time">
                            <?= formatDate($r['date']) ?> · <?= substr($r['time_from'], 0, 5) ?> – <?= substr($r['time_to'], 0, 5) ?>
                        </p>
                        <p class="res-id">#<?= $r['id'] ?></p>
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
