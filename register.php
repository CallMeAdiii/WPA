<?php
// register.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Pokud je uživatel přihlášen, přesměruj na sportoviště
if (isLoggedIn()) {
    header('Location: sportovistealt.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Validace
    if (empty($name)) {
        $errors['name'] = 'Zadej své jméno.';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Jméno musí mít alespoň 2 znaky.';
    }

    if (empty($email)) {
        $errors['email'] = 'Zadej email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Zadej platný email.';
    } else {
        // Zkontroluj duplicitu emailu
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Tento email je již zaregistrován.';
        }
    }

    if (empty($password)) {
        $errors['password'] = 'Zadej heslo.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Heslo musí mít alespoň 6 znaků.';
    }

    if ($password !== $password2) {
        $errors['password2'] = 'Hesla se neshodují.';
    }

    // Pokud nejsou chyby, zaregistruj uživatele
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, \'student\')');
        $stmt->execute([$name, $email, $hashed]);

        $userId = $pdo->lastInsertId();

        // Automaticky přihlásit po registraci
        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = 'student';

        header('Location: sportovistealt.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportHub – Registrace</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="login-layout">

    <!-- Levá strana – hřiště grafika -->
    <div class="login-left">
        <svg viewBox="0 0 450 560" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <rect width="450" height="560" fill="#1c5c22"/>
            <rect x="0"   y="0" width="50" height="560" fill="rgba(0,0,0,0.07)"/>
            <rect x="100" y="0" width="50" height="560" fill="rgba(0,0,0,0.07)"/>
            <rect x="200" y="0" width="50" height="560" fill="rgba(0,0,0,0.07)"/>
            <rect x="300" y="0" width="50" height="560" fill="rgba(0,0,0,0.07)"/>
            <rect x="400" y="0" width="50" height="560" fill="rgba(0,0,0,0.07)"/>
            <rect x="38" y="54" width="374" height="462" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="2.5"/>
            <line x1="38" y1="285" x2="412" y2="285" stroke="rgba(255,255,255,0.55)" stroke-width="2"/>
            <circle cx="225" cy="285" r="64" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="2"/>
            <circle cx="225" cy="285" r="5" fill="rgba(255,255,255,0.7)"/>
            <rect x="114" y="54"  width="222" height="92" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <rect x="158" y="54"  width="134" height="50" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <rect x="114" y="424" width="222" height="92" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <rect x="158" y="466" width="134" height="50" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <rect x="178" y="42"  width="94" height="14" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="2"/>
            <rect x="178" y="504" width="94" height="14" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="2"/>
            <path d="M38 65 A12 12 0 0 1 50 54"  fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <path d="M400 54 A12 12 0 0 1 412 66" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <path d="M38 505 A12 12 0 0 0 50 516"  fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <path d="M400 516 A12 12 0 0 0 412 504" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
        </svg>
        <div class="login-overlay"></div>
        <div class="login-left-text">
            <span class="login-tagline">Rezervační systém</span>
            <p class="login-title">Rezervuj<br>sportoviště<br>jednoduše.</p>
            <p class="login-sub">Tělocvična, posilovna, hřiště<br>– vše na jednom místě.</p>
        </div>
    </div>

    <!-- Pravá strana – registrační formulář -->
    <div class="login-right">
        <div class="login-logo">
            <div class="nav-logo-icon">S</div>
            <span class="login-logo-text">SportHub</span>
        </div>

        <h2>Registrace</h2>
        <p class="sub">Vytvoř si účet a začni rezervovat sportoviště.</p>

        <form method="POST" action="register.php" novalidate>

            <div class="form-group">
                <label for="name">Jméno a příjmení</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Jan Novák"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    class="<?= isset($errors['name']) ? 'input-error' : '' ?>"
                    autocomplete="name"
                >
                <?php if (isset($errors['name'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="jan@skola.cz"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    class="<?= isset($errors['email']) ? 'input-error' : '' ?>"
                    autocomplete="email"
                >
                <?php if (isset($errors['email'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Heslo</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    class="<?= isset($errors['password']) ? 'input-error' : '' ?>"
                    autocomplete="new-password"
                >
                <?php if (isset($errors['password'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['password']) ?></span>
                <?php else: ?>
                    <span class="register-note">Alespoň 6 znaků</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password2">Heslo znovu</label>
                <input
                    type="password"
                    id="password2"
                    name="password2"
                    placeholder="••••••••"
                    class="<?= isset($errors['password2']) ? 'input-error' : '' ?>"
                    autocomplete="new-password"
                >
                <?php if (isset($errors['password2'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['password2']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary">Vytvořit účet</button>
        </form>

        <div class="divider">nebo</div>

        <p class="register-link">Už máš účet? <a href="login.php">Přihlásit se →</a></p>
    </div>

</div>

<script src="js/script.js"></script>
</body>
</html>
