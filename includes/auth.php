<?php
// includes/auth.php – pomocné funkce pro autentizaci

/**
 * Vrátí true pokud je uživatel přihlášen (má user_id + JWT token v session).
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id'], $_SESSION['api_token']);
}

/**
 * Přesměruje na login.php pokud uživatel není přihlášen.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Vrátí ID přihlášeného uživatele nebo null.
 */
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Vrátí jméno přihlášeného uživatele nebo prázdný řetězec.
 */
function currentUserName(): string {
    return $_SESSION['user_name'] ?? '';
}

/**
 * Vrátí roli přihlášeného uživatele (student / teacher / admin).
 */
function currentUserRole(): string {
    return $_SESSION['user_role'] ?? 'student';
}

/**
 * Vrátí true pokud je přihlášený uživatel admin.
 */
function isAdmin(): bool {
    return currentUserRole() === 'admin';
}
