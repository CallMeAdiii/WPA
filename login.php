<?php
// login.php
session_start();
require_once 'includes/db.php';  // Opravená cesta
require_once 'includes/auth.php'; // Opravená cesta

// Pokud je uživatel přihlášen, přesměruj na sportoviště
if (isLoggedIn()) {
    header('Location: sportovistealt.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vyplň prosím email a heslo.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: sportovistealt.php');
            exit;
        } else {
            $error = 'Nesprávný email nebo heslo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportHub – Přihlášení</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="login-layout">

    <!-- Levá strana – hřiště grafika -->
    <div class="login-left">
        <svg viewBox="0 0 450 560" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <rect width="450" height="560" fill="#1c5c22"/>
            <!-- Stripe pattern -->
            <rect x="0"   y="0" width="50" height="560" fill="rgba(0,0,0,0.07)"/>
            <rect x="100" y="0" width="50" height="560" fill="rgba(0,0,0,0.07)"/>
            <rect x="200" y="0" width="50" height="560" fill="rgba(0,0,0,0.07)"/>
            <rect x="300" y="0" width="50" height="560" fill="rgba(0,0,0,0.07)"/>
            <rect x="400" y="0" width="50" height="560" fill="rgba(0,0,0,0.07)"/>
            <!-- Field lines -->
            <rect x="38" y="54" width="374" height="462" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="2.5"/>
            <line x1="38" y1="285" x2="412" y2="285" stroke="rgba(255,255,255,0.55)" stroke-width="2"/>
            <circle cx="225" cy="285" r="64" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="2"/>
            <circle cx="225" cy="285" r="5" fill="rgba(255,255,255,0.7)"/>
            <rect x="114" y="54"  width="222" height="92" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <rect x="158" y="54"  width="134" height="50" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <rect x="114" y="424" width="222" height="92" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
            <rect x="158" y="466" width="134" height="50" fill="none" stroke="rgba(255,255,255,0.45)" stroke-width="2"/>
        </svg>
    </div>

    <!-- Pravá strana – přihlašovací formulář -->
    <div class="login-right">
        <div class="login-box">
            <div class="login-header">
                <h1>Přihlášení</h1>
                <p>Přihlas se ke svému SportHub účtu</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Heslo</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn-primary">Přihlásit se</button>
            </form>

            <div class="login-footer">
                <p>Nemáš účet? <a href="register.php">Zaregistruj se</a></p>
            </div>
        </div>
    </div>

</div>

</body>
</html>