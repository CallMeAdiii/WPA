<?php
// zmena-hesla.php
session_start();
require_once 'includes/api.php';
require_once 'includes/auth.php';

requireLogin();

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password']     ?? '';
    $newPassword2    = $_POST['new_password2']    ?? '';

    if (empty($currentPassword)) {
        $errors['current_password'] = 'Zadej současné heslo.';
    }

    if (empty($newPassword)) {
        $errors['new_password'] = 'Zadej nové heslo.';
    } elseif (strlen($newPassword) < 6) {
        $errors['new_password'] = 'Nové heslo musí mít alespoň 6 znaků.';
    }

    if ($newPassword !== $newPassword2) {
        $errors['new_password2'] = 'Hesla se neshodují.';
    }

    if ($currentPassword === $newPassword && empty($errors)) {
        $errors['new_password'] = 'Nové heslo musí být odlišné od současného.';
    }

    if (empty($errors)) {
        $result = apiRequest('PATCH', '/api/auth/change-password', [
            'currentPassword' => $currentPassword,
            'newPassword'     => $newPassword,
        ], getToken());

        handleUnauthorized($result);

        if ($result['status'] === 200) {
            $success = 'Heslo bylo úspěšně změněno.';
        } elseif ($result['status'] === 401) {
            $errors['current_password'] = 'Současné heslo je nesprávné.';
        } else {
            $errors['general'] = $result['data']['error'] ?? 'Nepodařilo se změnit heslo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportHub – Změna hesla</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <div class="nav-logo">
        <div class="nav-logo-icon">S</div>
        <span class="nav-logo-text">SportHub</span>
    </div>
    <a href="sportovistealt.php">Sportoviště</a>
    <a href="moje-rezervace.php">Moje rezervace</a>
    <?php if (isAdmin()): ?><a href="admin.php">Admin</a><?php endif; ?>
    <a href="zmena-hesla.php" class="active nav-active-bar">Změna hesla</a>
    <a href="logout.php" class="nav-btn" style="color:white;">Odhlásit</a>
</nav>

<div class="my-res-page">
    <div class="my-res-inner" style="max-width: 480px;">

        <h1 class="page-heading">Změna hesla</h1>
        <p class="page-sub">Zadej současné heslo a zvol nové</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <div class="admin-form-card" style="margin-top: 20px;">
            <form method="POST" action="zmena-hesla.php" novalidate>

                <div class="form-group">
                    <label for="current_password">Současné heslo</label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        placeholder="••••••••"
                        class="<?= isset($errors['current_password']) ? 'input-error' : '' ?>"
                        autocomplete="current-password"
                    >
                    <?php if (isset($errors['current_password'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['current_password']) ?></span>
                    <?php endif; ?>
                </div>

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

                <button type="submit" class="btn-primary" style="margin-top: 8px;">
                    Změnit heslo
                </button>

            </form>
        </div>

        <div style="margin-top: 16px; text-align: center;">
            <a href="sportovistealt.php" style="font-size: 13px; color: #888;">← Zpět na sportoviště</a>
        </div>

    </div>
</div>

</body>
</html>
