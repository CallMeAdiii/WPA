<?php
// sportovistealt.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin(); // přesměruje na login.php pokud není přihlášen

// Filtr podle typu
$filter = $_GET['filter'] ?? 'vse';

// Připravíme filtr – typy sportovišť (dynamicky z DB)
$typeMap = [
    'vse'        => null,
    'telocvicna' => 'tělocvična',
    'posilovna'  => 'posilovna',
    'oval'       => 'ovál',
    'hriste'     => 'hřiště',
];

$activeType = $typeMap[$filter] ?? null;

if ($activeType !== null) {
    $stmt = $pdo->prepare('SELECT * FROM facilities WHERE type = ? ORDER BY id ASC');
    $stmt->execute([$activeType]);
} else {
    $stmt = $pdo->query('SELECT * FROM facilities ORDER BY id ASC');
}

$facilities = $stmt->fetchAll();

// Helper: vrátí CSS třídu pro tag podle typu sportoviště
function tagClass(string $type): string {
    return match($type) {
        'tělocvična' => 'tag-gym',
        'posilovna'  => 'tag-weights',
        'ovál'       => 'tag-oval',
        'hřiště'     => 'tag-field',
        default      => 'tag-other',
    };
}

// Helper: vrátí CSS třídu pro tlačítko Rezervovat
function btnClass(string $type): string {
    return match($type) {
        'tělocvična' => 'btn-reserve-gym',
        'posilovna'  => 'btn-reserve-weights',
        'ovál'       => 'btn-reserve-oval',
        'hřiště'     => 'btn-reserve-field',
        default      => 'btn-reserve-default',
    };
}

// Helper: vrátí SVG vizuál dle typu
function facilityVisualSVG(string $type, int $id): string {
    return match($type) {
        'tělocvična' => '
            <svg viewBox="0 0 290 155" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="plank'.$id.'" x="0" y="0" width="16" height="16" patternUnits="userSpaceOnUse">
                        <rect width="16" height="16" fill="#c8902a"/>
                        <line x1="0" y1="8" x2="16" y2="8" stroke="rgba(0,0,0,0.07)" stroke-width="0.8"/>
                        <line x1="8" y1="0" x2="8" y2="16" stroke="rgba(0,0,0,0.05)" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="290" height="155" fill="url(#plank'.$id.')"/>
                <rect x="18" y="12" width="254" height="132" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="2"/>
                <line x1="145" y1="12" x2="145" y2="144" stroke="rgba(255,255,255,0.55)" stroke-width="2"/>
                <circle cx="145" cy="78" r="30" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="2"/>
                <circle cx="145" cy="78" r="4" fill="rgba(255,255,255,0.7)"/>
                <path d="M18 22 Q58 78 18 134" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="1.5"/>
                <path d="M272 22 Q232 78 272 134" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="1.5"/>
                <circle cx="45" cy="78" r="12" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
                <circle cx="245" cy="78" r="12" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
            </svg>',

        'posilovna' => '
            <svg viewBox="0 0 290 155" xmlns="http://www.w3.org/2000/svg">
                <rect width="290" height="155" fill="#1f1f1f"/>
                <line x1="0" y1="51" x2="290" y2="51" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                <line x1="0" y1="103" x2="290" y2="103" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                <line x1="96" y1="0" x2="96" y2="155" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                <line x1="193" y1="0" x2="193" y2="155" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                <rect x="72" y="70" width="146" height="13" rx="4" fill="#4a4a4a"/>
                <rect x="54" y="58" width="20" height="37" rx="5" fill="#333" stroke="#555" stroke-width="1"/>
                <rect x="38" y="64" width="17" height="25" rx="4" fill="#3a3a3a" stroke="#555" stroke-width="1"/>
                <rect x="216" y="58" width="20" height="37" rx="5" fill="#333" stroke="#555" stroke-width="1"/>
                <rect x="235" y="64" width="17" height="25" rx="4" fill="#3a3a3a" stroke="#555" stroke-width="1"/>
                <rect x="132" y="70" width="26" height="13" rx="2" fill="#5a5a5a"/>
                <line x1="137" y1="70" x2="137" y2="83" stroke="#444" stroke-width="1.2"/>
                <line x1="143" y1="70" x2="143" y2="83" stroke="#444" stroke-width="1.2"/>
                <line x1="149" y1="70" x2="149" y2="83" stroke="#444" stroke-width="1.2"/>
                <line x1="155" y1="70" x2="155" y2="83" stroke="#444" stroke-width="1.2"/>
                <rect x="105" y="108" width="80" height="7" rx="3" fill="#3a3a3a"/>
                <rect x="96" y="103" width="12" height="17" rx="4" fill="#333" stroke="#4a4a4a" stroke-width="1"/>
                <rect x="182" y="103" width="12" height="17" rx="4" fill="#333" stroke="#4a4a4a" stroke-width="1"/>
                <text x="145" y="142" text-anchor="middle" font-family="sans-serif" font-size="10" fill="rgba(255,255,255,0.18)" letter-spacing="3">POSILOVNA</text>
            </svg>',

        'ovál' => '
            <svg viewBox="0 0 290 155" xmlns="http://www.w3.org/2000/svg">
                <rect width="290" height="155" fill="#0f4a0f"/>
                <ellipse cx="145" cy="77" rx="118" ry="66" fill="none" stroke="#c84020" stroke-width="22" opacity="0.92"/>
                <ellipse cx="145" cy="77" rx="106" ry="56" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="1.2"/>
                <ellipse cx="145" cy="77" rx="113" ry="62" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="1.2"/>
                <ellipse cx="145" cy="77" rx="118" ry="66" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="1.2"/>
                <ellipse cx="145" cy="77" rx="88" ry="48" fill="#1a6a1a"/>
                <ellipse cx="145" cy="77" rx="88" ry="48" fill="none" stroke="rgba(255,255,255,0.25)" stroke-width="1.5"/>
                <line x1="57" y1="77" x2="233" y2="77" stroke="rgba(255,255,255,0.25)" stroke-width="1.2"/>
                <line x1="263" y1="55" x2="263" y2="100" stroke="white" stroke-width="2.5"/>
            </svg>',

        'hřiště' => '
            <svg viewBox="0 0 290 155" xmlns="http://www.w3.org/2000/svg">
                <rect width="290" height="155" fill="#1c5c22"/>
                <rect x="0"   y="0" width="40" height="155" fill="rgba(0,0,0,0.07)"/>
                <rect x="80"  y="0" width="40" height="155" fill="rgba(0,0,0,0.07)"/>
                <rect x="160" y="0" width="40" height="155" fill="rgba(0,0,0,0.07)"/>
                <rect x="240" y="0" width="40" height="155" fill="rgba(0,0,0,0.07)"/>
                <rect x="14" y="10" width="262" height="135" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"/>
                <line x1="145" y1="10" x2="145" y2="145" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
                <circle cx="145" cy="77" r="26" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
                <circle cx="145" cy="77" r="3" fill="rgba(255,255,255,0.7)"/>
                <rect x="14" y="30" width="58" height="95" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="1.5"/>
                <rect x="218" y="30" width="58" height="95" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="1.5"/>
            </svg>',

        default => '
            <svg viewBox="0 0 290 155" xmlns="http://www.w3.org/2000/svg">
                <rect width="290" height="155" fill="#e8e8e8"/>
                <text x="145" y="85" text-anchor="middle" font-family="sans-serif" font-size="13" fill="#aaa">Sportoviště</text>
            </svg>',
    };
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportHub – Sportoviště</title>
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

<div class="facilities-page">
    <div class="facilities-inner">
        <h1 class="page-heading">Sportoviště</h1>
        <p class="page-sub">Vyber sportoviště a rezervuj si termín</p>

        <!-- Filtry -->
        <div class="filter-pills">
            <a href="?filter=vse"        class="pill <?= $filter === 'vse'        ? 'active' : '' ?>">Vše</a>
            <a href="?filter=telocvicna" class="pill <?= $filter === 'telocvicna' ? 'active' : '' ?>">Tělocvična</a>
            <a href="?filter=posilovna"  class="pill <?= $filter === 'posilovna'  ? 'active' : '' ?>">Posilovna</a>
            <a href="?filter=oval"       class="pill <?= $filter === 'oval'       ? 'active' : '' ?>">Ovál</a>
            <a href="?filter=hriste"     class="pill <?= $filter === 'hriste'     ? 'active' : '' ?>">Hřiště</a>
        </div>

        <!-- Grid sportovišť -->
        <div class="facilities-grid">
            <?php if (empty($facilities)): ?>
                <p class="empty-state">Žádná sportoviště v této kategorii.</p>
            <?php else: ?>
                <?php foreach ($facilities as $f): ?>
                <div class="facility-card">
                    <div class="facility-visual">
                        <?= facilityVisualSVG($f['type'], $f['id']) ?>
                    </div>
                    <div class="facility-info">
                        <span class="facility-type-tag <?= tagClass($f['type']) ?>">
                            <?= htmlspecialchars($f['type']) ?>
                        </span>
                        <p class="facility-name"><?= htmlspecialchars($f['name']) ?></p>
                        <p class="facility-desc"><?= htmlspecialchars($f['description']) ?></p>
                        <div class="facility-footer">
                            <p class="capacity">Kapacita: <span><?= (int)$f['capacity'] ?> osob</span></p>
                            <a href="rezervace.php?id=<?= $f['id'] ?>"
                               class="btn-reserve <?= btnClass($f['type']) ?>">
                                Rezervovat
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="js/script.js"></script>
</body>
</html>
