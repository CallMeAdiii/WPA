<?php
// zapomenute-heslo.php – odeslání emailu s odkazem pro reset hesla
session_start();
require_once 'includes/api.php';

if (isset($_SESSION['user_id'], $_SESSION['api_token'])) {
    header('Location: zmena-hesla.php');
    exit;
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Zadej platný email.';
    }

    if (empty($errors)) {
        $result = apiRequest('POST', '/api/auth/forgot-password', [
            'email' => $email,
        ]);

        // Vždy zobraz úspěch – backend neodhaluje jestli email existuje
        $success = 'Pokud účet existuje, poslali jsme ti email s odkazem pro reset hesla. Zkontroluj i složku spam.';
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportHub – Zapomenuté heslo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="login-layout">

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
        </svg>
    </div>

    <div class="login-right">
        <div class="login-box">
            <div class="login-header">
                <h1>Zapomenuté heslo</h1>
                <p>Zadej svůj email a pošleme ti odkaz pro reset</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <div style="text-align:center; margin-top:20px;">
                    <a href="login.php" class="btn-primary" style="display:inline-block; text-decoration:none;">
                        Zpět na přihlášení
                    </a>
                </div>
            <?php else: ?>

            <form method="POST" action="zapomenute-heslo.php" novalidate>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="vas@email.cz"
                        class="<?= isset($errors['email']) ? 'input-error' : '' ?>"
                        autocomplete="email"
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary">Odeslat odkaz</button>
            </form>

            <?php endif; ?>

            <div class="login-footer">
                <p><a href="login.php">← Zpět na přihlášení</a></p>
            </div>
        </div>
    </div>

</div>

</body>
</html>
