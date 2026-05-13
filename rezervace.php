<?php
// rezervace.php
session_start();
require_once 'includes/api.php';
require_once 'includes/auth.php';

requireLogin();

// Načti ID sportoviště z URL
$facilityId = (int)($_GET['id'] ?? 0);
if ($facilityId <= 0) {
    header('Location: sportovistealt.php');
    exit;
}

// Načti detail sportoviště z API
$facResult = apiRequest('GET', '/api/facilities/' . $facilityId, [], getToken());
handleUnauthorized($facResult);

if ($facResult['status'] !== 200) {
    header('Location: sportovistealt.php');
    exit;
}
$facility = $facResult['data'];

$errors  = [];

// Výchozí hodnoty
$date        = $_POST['date']         ?? date('Y-m-d');
$timeFrom    = $_POST['time_from']    ?? '';
$timeTo      = $_POST['time_to']      ?? '';
$peopleCount = max(1, (int)($_POST['people_count'] ?? 1));

// Zpracování odeslání formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (empty($date)) {
        $errors['date'] = 'Vyber datum.';
    } elseif (strtotime($date) < strtotime('today')) {
        $errors['date'] = 'Datum nemůže být v minulosti.';
    }

    if (empty($timeFrom)) {
        $errors['general'] = 'Vyber čas začátku kliknutím na slot v kalendáři.';
    } elseif (empty($timeTo)) {
        $errors['general'] = 'Vyber čas konce kliknutím na slot v kalendáři.';
    } elseif ($timeFrom >= $timeTo) {
        $errors['general'] = 'Čas konce musí být po čase začátku.';
    }

    if (empty($errors)) {
        $resResult = apiRequest('POST', '/api/reservations', [
            'facility_id'  => $facilityId,
            'date'         => $date,
            'time_from'    => $timeFrom,
            'time_to'      => $timeTo,
            'people_count' => $peopleCount,
        ], getToken());

        handleUnauthorized($resResult);

        if ($resResult['status'] === 201) {
            header('Location: moje-rezervace.php?success=1');
            exit;
        } elseif ($resResult['status'] === 409) {
            $errors['general'] = $resResult['data']['error'] ?? 'Termín je již obsazený.';
        } else {
            $errors['general'] = $resResult['data']['error'] ?? 'Nepodařilo se vytvořit rezervaci.';
        }
    }
}

// Načti sloty pro aktuální datum (server-side pro první render)
$slotsResult   = apiRequest('GET', "/api/facilities/{$facilityId}/slots?date={$date}", [], getToken());
$initialSlots  = ($slotsResult['status'] === 200) ? $slotsResult['data'] : null;

// Pomocné funkce pro SVG vizuál – různý design podle typu sportoviště
function reservationVisualSVG(string $type): string {
    return match($type) {
        'tělocvična' => '
        <svg viewBox="0 0 450 600" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <rect width="450" height="600" fill="#2a1a0a"/>
            <rect x="0"   y="0" width="30" height="600" fill="rgba(255,185,95,0.07)"/>
            <rect x="50"  y="0" width="30" height="600" fill="rgba(255,185,95,0.07)"/>
            <rect x="100" y="0" width="30" height="600" fill="rgba(255,185,95,0.07)"/>
            <rect x="150" y="0" width="30" height="600" fill="rgba(255,185,95,0.07)"/>
            <rect x="200" y="0" width="30" height="600" fill="rgba(255,185,95,0.07)"/>
            <rect x="250" y="0" width="30" height="600" fill="rgba(255,185,95,0.07)"/>
            <rect x="300" y="0" width="30" height="600" fill="rgba(255,185,95,0.07)"/>
            <rect x="350" y="0" width="30" height="600" fill="rgba(255,185,95,0.07)"/>
            <rect x="400" y="0" width="30" height="600" fill="rgba(255,185,95,0.07)"/>
            <rect x="38" y="38" width="374" height="524" fill="none" stroke="rgba(220,100,20,0.7)" stroke-width="2.5"/>
            <line x1="38" y1="300" x2="412" y2="300" stroke="rgba(220,100,20,0.6)" stroke-width="2"/>
            <circle cx="225" cy="300" r="62" fill="none" stroke="rgba(220,100,20,0.6)" stroke-width="2"/>
            <circle cx="225" cy="300" r="5" fill="rgba(220,100,20,0.65)"/>
            <rect x="155" y="38" width="140" height="148" fill="rgba(220,100,20,0.07)" stroke="rgba(220,100,20,0.6)" stroke-width="2"/>
            <path d="M 155,186 A 70 70 0 0 0 295,186" fill="none" stroke="rgba(220,100,20,0.55)" stroke-width="2"/>
            <rect x="193" y="38" width="64" height="5" fill="rgba(220,100,20,0.55)"/>
            <circle cx="225" cy="70" r="18" fill="none" stroke="rgba(220,100,20,0.75)" stroke-width="2.5"/>
            <path d="M 38,38 L 38,111 A 192 192 0 0 0 412,111 L 412,38" fill="rgba(220,100,20,0.05)" stroke="rgba(220,100,20,0.5)" stroke-width="2"/>
            <rect x="155" y="414" width="140" height="148" fill="rgba(220,100,20,0.07)" stroke="rgba(220,100,20,0.6)" stroke-width="2"/>
            <path d="M 155,414 A 70 70 0 0 1 295,414" fill="none" stroke="rgba(220,100,20,0.55)" stroke-width="2"/>
            <rect x="193" y="557" width="64" height="5" fill="rgba(220,100,20,0.55)"/>
            <circle cx="225" cy="530" r="18" fill="none" stroke="rgba(220,100,20,0.75)" stroke-width="2.5"/>
            <path d="M 38,562 L 38,489 A 192 192 0 0 1 412,489 L 412,562" fill="rgba(220,100,20,0.05)" stroke="rgba(220,100,20,0.5)" stroke-width="2"/>
            <text x="225" y="26" text-anchor="middle" font-family="sans-serif" font-size="9" fill="rgba(220,100,20,0.38)" letter-spacing="4">TĚLOCVIČNA</text>
        </svg>',

        'posilovna' => '
        <svg viewBox="0 0 450 540" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <rect width="450" height="540" fill="#141414"/>
            <rect x="0" y="380" width="450" height="160" fill="rgba(255,255,255,0.025)"/>
            <line x1="0" y1="380" x2="450" y2="380" stroke="rgba(255,255,255,0.08)" stroke-width="1"/>
            <line x1="0" y1="0"   x2="0"   y2="380" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
            <line x1="450" y1="0" x2="450" y2="380" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
            <circle cx="88"  cy="270" r="68" fill="#222222" stroke="#404040" stroke-width="3"/>
            <circle cx="88"  cy="270" r="52" fill="#1a1a1a" stroke="#505050" stroke-width="1.5"/>
            <circle cx="88"  cy="270" r="10" fill="#3a3a3a"/>
            <path d="M 88,202 A 68 68 0 0 1 144,226" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="2" stroke-linecap="round"/>
            <circle cx="362" cy="270" r="68" fill="#222222" stroke="#404040" stroke-width="3"/>
            <circle cx="362" cy="270" r="52" fill="#1a1a1a" stroke="#505050" stroke-width="1.5"/>
            <circle cx="362" cy="270" r="10" fill="#3a3a3a"/>
            <path d="M 306,226 A 68 68 0 0 1 362,202" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="2" stroke-linecap="round"/>
            <circle cx="128" cy="270" r="44" fill="#252525" stroke="#454545" stroke-width="2.5"/>
            <circle cx="128" cy="270" r="32" fill="#1e1e1e" stroke="#3a3a3a" stroke-width="1"/>
            <circle cx="322" cy="270" r="44" fill="#252525" stroke="#454545" stroke-width="2.5"/>
            <circle cx="322" cy="270" r="32" fill="#1e1e1e" stroke="#3a3a3a" stroke-width="1"/>
            <rect x="148" y="264" width="18" height="12" rx="2" fill="#5a5a5a"/>
            <rect x="284" y="264" width="18" height="12" rx="2" fill="#5a5a5a"/>
            <rect x="28"  y="264" width="430" height="12" rx="6" fill="#3a3a3a" stroke="#505050" stroke-width="1"/>
            <rect x="28"  y="264" width="60"  height="12" rx="6" fill="#444444"/>
            <rect x="362" y="264" width="96"  height="12" rx="6" fill="#444444"/>
            <text x="225" y="22" text-anchor="middle" font-family="sans-serif" font-size="9" fill="rgba(255,255,255,0.18)" letter-spacing="4">POSILOVNA</text>
        </svg>',

        'ovál' => '
        <svg viewBox="0 0 450 540" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <rect width="450" height="540" fill="#1c5c22"/>
            <rect x="0"   y="0" width="450" height="36" fill="rgba(0,0,0,0.06)"/>
            <rect x="0"   y="72"  width="450" height="36" fill="rgba(0,0,0,0.06)"/>
            <rect x="0"   y="144" width="450" height="36" fill="rgba(0,0,0,0.06)"/>
            <rect x="0"   y="216" width="450" height="36" fill="rgba(0,0,0,0.06)"/>
            <rect x="0"   y="288" width="450" height="36" fill="rgba(0,0,0,0.06)"/>
            <rect x="0"   y="360" width="450" height="36" fill="rgba(0,0,0,0.06)"/>
            <rect x="0"   y="432" width="450" height="36" fill="rgba(0,0,0,0.06)"/>
            <rect x="0"   y="504" width="450" height="36" fill="rgba(0,0,0,0.06)"/>
            <ellipse cx="225" cy="270" rx="195" ry="248" fill="#c87840"/>
            <ellipse cx="225" cy="270" rx="182" ry="235" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="1"/>
            <ellipse cx="225" cy="270" rx="169" ry="222" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="1"/>
            <ellipse cx="225" cy="270" rx="156" ry="209" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="1"/>
            <ellipse cx="225" cy="270" rx="143" ry="196" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="1"/>
            <ellipse cx="225" cy="270" rx="130" ry="183" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="1"/>
            <ellipse cx="225" cy="270" rx="117" ry="170" fill="#2a7a2a"/>
            <rect x="195" y="22" width="60" height="8" rx="4" fill="rgba(255,255,255,0.7)"/>
            <rect x="195" y="510" width="60" height="8" rx="4" fill="rgba(255,255,255,0.7)"/>
            <circle cx="225" cy="270" r="4" fill="rgba(255,255,255,0.5)"/>
            <line x1="225" y1="87" x2="225" y2="453" stroke="rgba(255,255,255,0.25)" stroke-width="1" stroke-dasharray="6 4"/>
            <text x="225" y="14" text-anchor="middle" font-family="sans-serif" font-size="9" fill="rgba(255,255,255,0.35)" letter-spacing="4">OVÁL</text>
        </svg>',

        default => '
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
            <path d="M36 60 A12 12 0 0 1 48 48"    fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <path d="M402 48 A12 12 0 0 1 414 60"   fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <path d="M36 484 A12 12 0 0 0 48 496"   fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <path d="M402 496 A12 12 0 0 0 414 484" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <text x="225" y="34" text-anchor="middle" font-family="sans-serif" font-size="9" fill="rgba(255,255,255,0.35)" letter-spacing="4">HŘIŠTĚ</text>
        </svg>',
    };
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
    <a href="sportovistealt.php" class="nav-logo">
        <div class="nav-logo-icon">S</div>
        <span class="nav-logo-text">SportHub</span>
    </a>
    <a href="sportovistealt.php" class="active nav-active-bar">Sportoviště</a>
    <a href="moje-rezervace.php">Moje rezervace</a>
    <?php if (isAdmin()): ?><a href="admin.php">Admin</a><?php endif; ?>
    <a href="zmena-hesla.php">Změna hesla</a>
    <a href="logout.php" class="nav-btn" style="color:white;">Odhlásit</a>
</nav>

<div class="reservation-page">
    <div class="reservation-layout">

        <!-- Levá strana – vizuál sportoviště -->
        <div class="reservation-visual">
            <?= reservationVisualSVG($facility['type']) ?>
            <div class="reservation-visual-overlay"></div>
            <div class="reservation-visual-info">
                <span class="visual-facility-label">Sportoviště</span>
                <div class="visual-facility-name"><?= htmlspecialchars($facility['name']) ?></div>
                <div class="visual-facility-meta">
                    <?php if (!empty($facility['location'])): ?>
                        <span class="visual-meta-item">📍 <?= htmlspecialchars($facility['location']) ?></span>
                    <?php endif; ?>
                    <span class="visual-meta-item">👥 <?= (int)($facility['capacity'] ?? 20) ?> míst</span>
                    <span class="visual-meta-item"><?= ucfirst(htmlspecialchars($facility['type'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Pravá strana – formulář -->
        <div class="res-form-side" style="justify-content:flex-start;padding-top:40px;overflow-y:auto;">
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
            <?php if (isset($errors['date'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errors['date']) ?></div>
            <?php endif; ?>

            <form method="POST" action="rezervace.php?id=<?= $facilityId ?>" id="reservationForm" novalidate>
                <input type="hidden" name="submit" value="1">
                <input type="hidden" name="time_from" id="time_from" value="<?= htmlspecialchars($timeFrom) ?>">
                <input type="hidden" name="time_to"   id="time_to"   value="<?= htmlspecialchars($timeTo) ?>">

                <!-- 1. Datum -->
                <div class="form-card">
                    <span class="form-section-label">Datum</span>
                    <div class="form-group" style="margin-bottom:0;">
                        <input
                            type="date"
                            id="date"
                            name="date"
                            value="<?= htmlspecialchars($date) ?>"
                            min="<?= date('Y-m-d') ?>"
                            style="max-width:200px;"
                        >
                    </div>
                </div>

                <!-- 2. Slot kalendář -->
                <div class="form-card">
                    <span class="form-section-label" style="margin-bottom:10px;">Dostupné termíny</span>
                    <div id="slotsLoading" class="slots-loading"
                         style="<?= $initialSlots ? 'display:none;' : '' ?>">
                        Načítám termíny…
                    </div>
                    <div id="slotsGrid" class="slots-grid"></div>
                </div>

                <!-- 3. Počet osob (zobrazí JS po výběru slotu) -->
                <div class="form-card" id="peopleCard" style="display:none;">
                    <span class="form-section-label">Počet osob</span>
                    <div class="people-counter-wrap">
                        <div class="people-stepper">
                            <button type="button" class="people-btn" id="peopleMinus" disabled>−</button>
                            <input
                                type="number"
                                class="people-val"
                                id="people_count"
                                name="people_count"
                                value="<?= $peopleCount ?>"
                                min="1"
                                max="<?= (int)($facility['capacity'] ?? 20) ?>"
                            >
                            <button type="button" class="people-btn" id="peoplePlus">+</button>
                        </div>
                        <span class="people-avail-note" id="peopleAvailNote">
                            max <strong><?= (int)($facility['capacity'] ?? 20) ?></strong> volných míst
                        </span>
                    </div>
                </div>

                <!-- 4. Souhrn (zobrazí JS po výběru) -->
                <div class="form-card" id="summaryCard" style="display:none;">
                    <span class="form-section-label">Souhrn</span>
                    <div class="summary-row">
                        <span>Sportoviště</span>
                        <span class="val"><?= htmlspecialchars($facility['name']) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Datum</span>
                        <span class="val" id="summaryDate">—</span>
                    </div>
                    <div class="summary-row">
                        <span>Čas</span>
                        <span class="val" id="summaryTime">—</span>
                    </div>
                    <div class="summary-row">
                        <span>Délka</span>
                        <span class="val" id="summaryDuration">—</span>
                    </div>
                    <div class="summary-row summary-row-highlight">
                        <span>Osob</span>
                        <span class="val" id="summaryPeople">—</span>
                    </div>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn"
                        style="margin-bottom:0;" disabled>
                    Potvrdit rezervaci
                </button>
            </form>
        </div>

    </div>
</div>

<script>
window.__facilityId       = <?= $facilityId ?>;
window.__facilityCapacity = <?= (int)($facility['capacity'] ?? 20) ?>;
window.__slots            = <?= $initialSlots ? json_encode($initialSlots) : 'null' ?>;
</script>
<script src="js/script.js"></script>
</body>
</html>
