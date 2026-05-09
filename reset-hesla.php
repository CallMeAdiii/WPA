<?php
// reset-hesla.php – nastavení nového hesla přes token z emailu
session_start();
require_once 'includes/api.php';

if (isset($_SESSION['user_id'], $_SESSION['api_token'])) {
    header('Location: zmena-hesla.php');
    exit;
}

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    header('Location: zapomenute-heslo.php');
    exit;
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword  = $_POST['new_password']  ?? '';
    $newPassword2 = $_POST['new_password2'] ?? '';

    if (empty($newPassword)) {
        $errors['new_password'] = 'Zadej nové heslo.';
    } elseif (strlen($newPassword) < 6) {
        $errors['new_password'] = 'Heslo musí mít alespoň 6 znaků.';
    }

    if ($newPassword !== $newPassword2) {
        $errors['new_password2'] = 'Hesla se neshodují.';
    }

    if (empty($errors)) {
        $result = apiRequest('POST', '/api/auth/reset-password/' . urlencode($token), [
            'newPassword' => $newPassword,
        ]);

        if ($result['status'] === 200) {
            $success = 'Heslo bylo úspěšně změněno. Nyní se můžeš přihlásit.';
        } else {
            $errors['general'] = $result['data']['message'] ?? 'Nepodařilo se změnit heslo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportHub – Nové heslo</title>
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
                <h1>Nové heslo</h1>
                <p>Zvol si nové heslo pro svůj účet</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <div style="text-align:center; margin-top:20px;">
                    <a href="login.php" class="btn-primary" style="display:inline-block; text-decoration:none;">
                        Přejít na přihlášení
                    </a>
                </div>
            <?php else: ?>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <form method="POST" action="reset-hesla.php?token=<?= htmlspecialchars($token) ?>" novalidate>

                <div class="form-group">
                    <label for="new_password">Nové heslo</label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="••••••••"
                        class="<?= isset($errors['new_password']) ? 'input-error' : '' ?>"
                        autocomplete="new-password"
                    >
                    <?php if (isset($errors['new_password'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['new_password']) ?></span>
                    <?php else: ?>
                        <span class="register-note">Alespoň 6 znaků</span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="new_password2">Nové heslo znovu</label>
                    <input
                        type="password"
                        id="new_password2"
                        name="new_password2"
                        placeholder="••••••••"
                        class="<?= isset($errors['new_password2']) ? 'input-error' : '' ?>"
                        autocomplete="new-password"
                    >
                    <?php if (isset($errors['new_password2'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['new_password2']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary">Nastavit nové heslo</button>
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
