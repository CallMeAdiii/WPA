<?php
// admin.php – administrátorský panel
session_start();
require_once 'includes/api.php';
require_once 'includes/auth.php';

requireLogin();

// Pouze admin má přístup
if (!isAdmin()) {
    header('Location: sportovistealt.php');
    exit;
}

$tab     = $_GET['tab'] ?? 'dashboard';
$success = '';
$error   = '';

/* ================================================================
   ZPRACOVÁNÍ AKCÍ (POST)
   ================================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Přidat sportoviště
    if ($action === 'add_facility') {
        $result = apiRequest('POST', '/api/facilities', [
            'name'        => trim($_POST['name']        ?? ''),
            'type'        => trim($_POST['type']        ?? ''),
            'capacity'    => (int)($_POST['capacity']   ?? 0),
            'location'    => trim($_POST['location']    ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ], getToken());
        handleUnauthorized($result);
        $success = ($result['status'] === 201)
            ? 'Sportoviště bylo úspěšně přidáno.'
            : ($result['data']['message'] ?? 'Nepodařilo se přidat sportoviště.');
        if ($result['status'] !== 201) $error = $success and $success = '';
        $tab = 'sportovistealt';
    }

    // Smazat sportoviště
    if ($action === 'delete_facility') {
        $id     = (int)($_POST['facility_id'] ?? 0);
        $result = apiRequest('DELETE', '/api/facilities/' . $id, [], getToken());
        handleUnauthorized($result);
        $success = ($result['status'] === 200)
            ? 'Sportoviště bylo smazáno.'
            : ($result['data']['message'] ?? 'Nepodařilo se smazat sportoviště.');
        if ($result['status'] !== 200) $error = $success and $success = '';
        $tab = 'sportovistealt';
    }

    // Zrušit rezervaci
    if ($action === 'cancel_reservation') {
        $id     = (int)($_POST['reservation_id'] ?? 0);
        $result = apiRequest('DELETE', '/api/reservations/' . $id, [], getToken());
        handleUnauthorized($result);
        $success = ($result['status'] === 200)
            ? 'Rezervace byla zrušena.'
            : ($result['data']['message'] ?? 'Nepodařilo se zrušit rezervaci.');
        if ($result['status'] !== 200) $error = $success and $success = '';
        $tab = 'rezervace';
    }
}

/* ================================================================
   NAČTENÍ DAT Z API
   ================================================================ */
$facResult  = apiRequest('GET', '/api/facilities', [], getToken());
handleUnauthorized($facResult);
$facilities = ($facResult['status'] === 200) ? $facResult['data'] : [];

$resResult       = apiRequest('GET', '/api/reservations', [], getToken());
handleUnauthorized($resResult);
$allReservations = ($resResult['status'] === 200) ? $resResult['data'] : [];

$activeRes    = array_values(array_filter($allReservations, fn($r) => $r['status'] === 'active'));
$cancelledRes = array_values(array_filter($allReservations, fn($r) => $r['status'] === 'cancelled'));

// Seřaď aktivní ASC, zrušené DESC
usort($activeRes, fn($a, $b) => strcmp($a['date'] . $a['time_from'], $b['date'] . $b['time_from']));
usort($cancelledRes, fn($a, $b) => strcmp($b['date'] . $b['time_from'], $a['date'] . $a['time_from']));

/* ================================================================
   POMOCNÉ FUNKCE
   ================================================================ */
function formatDate(string $date): string {
    $ts = strtotime($date);
    return $ts ? date('j. n. Y', $ts) : $date;
}

function typeLabel(string $type): string {
    return match($type) {
        'tělocvična' => '🏀 Tělocvična',
        'posilovna'  => '🏋️ Posilovna',
        'ovál'       => '🏃 Ovál',
        'hřiště'     => '⚽ Hřiště',
        default      => $type,
    };
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportHub – Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <a href="sportovistealt.php" class="nav-logo">
        <div class="nav-logo-icon">S</div>
        <span class="nav-logo-text">SportHub</span>
    </a>
    <a href="sportovistealt.php">Sportoviště</a>
    <a href="moje-rezervace.php">Moje rezervace</a>
    <a href="admin.php" class="active nav-active-bar">Admin</a>
    <a href="zmena-hesla.php">Změna hesla</a>
    <a href="logout.php" class="nav-btn" style="color:white;">Odhlásit</a>
</nav>

<div class="admin-page">
    <div class="admin-inner">

        <div class="admin-header">
            <div>
                <h1 class="page-heading">Administrace</h1>
                <p class="page-sub">Správa sportovišť a rezervací</p>
            </div>
            <span class="admin-badge">Admin</span>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Záložky -->
        <div class="admin-tabs">
            <a href="?tab=dashboard"      class="admin-tab <?= $tab === 'dashboard'      ? 'active' : '' ?>">Přehled</a>
            <a href="?tab=sportovistealt" class="admin-tab <?= $tab === 'sportovistealt' ? 'active' : '' ?>">Sportoviště</a>
            <a href="?tab=rezervace"      class="admin-tab <?= $tab === 'rezervace'      ? 'active' : '' ?>">Rezervace</a>
        </div>

        <!-- ====================================================
             TAB: DASHBOARD
             ==================================================== -->
        <?php if ($tab === 'dashboard'): ?>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-green">🏟️</div>
                <div class="stat-body">
                    <p class="stat-value"><?= count($facilities) ?></p>
                    <p class="stat-label">Sportoviště</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue">📅</div>
                <div class="stat-body">
                    <p class="stat-value"><?= count($activeRes) ?></p>
                    <p class="stat-label">Aktivní rezervace</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-grey">🚫</div>
                <div class="stat-body">
                    <p class="stat-value"><?= count($cancelledRes) ?></p>
                    <p class="stat-label">Zrušené rezervace</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-orange">📊</div>
                <div class="stat-body">
                    <p class="stat-value"><?= count($allReservations) ?></p>
                    <p class="stat-label">Rezervace celkem</p>
                </div>
            </div>
        </div>

        <!-- Poslední aktivní rezervace -->
        <?php if (!empty($activeRes)): ?>
        <div class="admin-section">
            <h2 class="section-title">Nejbližší rezervace</h2>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Uživatel</th>
                            <th>Sportoviště</th>
                            <th>Datum</th>
                            <th>Čas</th>
                            <th>Stav</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($activeRes, 0, 5) as $r): ?>
                        <tr>
                            <td class="td-muted">#<?= $r['id'] ?></td>
                            <td>
                                <span class="td-name"><?= htmlspecialchars($r['user_name'] ?? '—') ?></span>
                                <span class="td-email"><?= htmlspecialchars($r['email'] ?? '') ?></span>
                            </td>
                            <td><?= htmlspecialchars($r['facility_name']) ?></td>
                            <td><?= formatDate($r['date']) ?></td>
                            <td><?= substr($r['time_from'], 0, 5) ?> – <?= substr($r['time_to'], 0, 5) ?></td>
                            <td><span class="badge-active">aktivní</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($activeRes) > 5): ?>
                <a href="?tab=rezervace" class="show-all-link">Zobrazit všechny rezervace →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ====================================================
             TAB: SPORTOVIŠTĚ
             ==================================================== -->
        <?php elseif ($tab === 'sportovistealt'): ?>

        <!-- Formulář: přidat sportoviště -->
        <div class="admin-section">
            <h2 class="section-title">Přidat sportoviště</h2>
            <div class="admin-form-card">
                <form method="POST" action="admin.php?tab=sportovistealt">
                    <input type="hidden" name="action" value="add_facility">
                    <div class="admin-form-grid">
                        <div class="form-group">
                            <label>Název</label>
                            <input type="text" name="name" placeholder="Tělocvična 3" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Typ</label>
                            <select name="type" required>
                                <option value="">— Vyber typ —</option>
                                <option value="tělocvična">Tělocvična</option>
                                <option value="posilovna">Posilovna</option>
                                <option value="ovál">Ovál</option>
                                <option value="hřiště">Hřiště</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kapacita (osob)</label>
                            <input type="number" name="capacity" placeholder="25" min="1" max="10000" required>
                        </div>
                        <div class="form-group">
                            <label>Umístění <span class="optional">(volitelné)</span></label>
                            <input type="text" name="location" placeholder="2. patro" maxlength="100">
                        </div>
                        <div class="form-group admin-form-full">
                            <label>Popis <span class="optional">(volitelné)</span></label>
                            <input type="text" name="description" placeholder="Stručný popis sportoviště">
                        </div>
                    </div>
                    <button type="submit" class="btn-admin-add">+ Přidat sportoviště</button>
                </form>
            </div>
        </div>

        <!-- Tabulka sportovišť -->
        <div class="admin-section">
            <h2 class="section-title">Přehled sportovišť <span class="count-badge"><?= count($facilities) ?></span></h2>
            <?php if (empty($facilities)): ?>
                <p class="res-empty">Žádná sportoviště.</p>
            <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Název</th>
                            <th>Typ</th>
                            <th>Kapacita</th>
                            <th>Umístění</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facilities as $f): ?>
                        <tr>
                            <td class="td-muted">#<?= $f['id'] ?></td>
                            <td class="td-bold"><?= htmlspecialchars($f['name']) ?></td>
                            <td><?= typeLabel($f['type']) ?></td>
                            <td><?= (int)$f['capacity'] ?> osob</td>
                            <td class="td-muted"><?= htmlspecialchars($f['location'] ?? '—') ?></td>
                            <td>
                                <form method="POST" action="admin.php?tab=sportovistealt"
                                      onsubmit="return confirm('Opravdu chceš smazat sportoviště \"<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>\"? Smažou se i všechny jeho rezervace.')">
                                    <input type="hidden" name="action" value="delete_facility">
                                    <input type="hidden" name="facility_id" value="<?= $f['id'] ?>">
                                    <button type="submit" class="btn-delete">Smazat</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ====================================================
             TAB: REZERVACE
             ==================================================== -->
        <?php elseif ($tab === 'rezervace'): ?>

        <!-- Záložky Aktivní / Zrušené -->
        <?php $resTab = $_GET['res'] ?? 'active'; ?>
        <div class="sub-tabs">
            <a href="?tab=rezervace&res=active"
               class="pill <?= $resTab !== 'cancelled' ? 'active' : '' ?>">
                Aktivní (<?= count($activeRes) ?>)
            </a>
            <a href="?tab=rezervace&res=cancelled"
               class="pill <?= $resTab === 'cancelled' ? 'active' : '' ?>">
                Zrušené (<?= count($cancelledRes) ?>)
            </a>
        </div>

        <?php $shownRes = $resTab === 'cancelled' ? $cancelledRes : $activeRes; ?>

        <div class="admin-section">
            <?php if (empty($shownRes)): ?>
                <p class="res-empty">Žádné rezervace.</p>
            <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Uživatel</th>
                            <th>Sportoviště</th>
                            <th>Datum</th>
                            <th>Čas</th>
                            <th>Stav</th>
                            <?php if ($resTab !== 'cancelled'): ?>
                            <th>Akce</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shownRes as $r): ?>
                        <tr>
                            <td class="td-muted">#<?= $r['id'] ?></td>
                            <td>
                                <span class="td-name"><?= htmlspecialchars($r['user_name'] ?? '—') ?></span>
                                <span class="td-email"><?= htmlspecialchars($r['email'] ?? '') ?></span>
                            </td>
                            <td><?= htmlspecialchars($r['facility_name']) ?></td>
                            <td><?= formatDate($r['date']) ?></td>
                            <td><?= substr($r['time_from'], 0, 5) ?> – <?= substr($r['time_to'], 0, 5) ?></td>
                            <td>
                                <?php if ($r['status'] === 'active'): ?>
                                    <span class="badge-active">aktivní</span>
                                <?php else: ?>
                                    <span class="badge-cancelled">zrušeno</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($resTab !== 'cancelled'): ?>
                            <td>
                                <form method="POST" action="admin.php?tab=rezervace&res=active"
                                      onsubmit="return confirm('Opravdu chceš zrušit tuto rezervaci?')">
                                    <input type="hidden" name="action" value="cancel_reservation">
                                    <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn-delete">Zrušit</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>

    </div>
</div>

</body>
</html>
