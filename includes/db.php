<?php
// includes/db.php – připojení k databázi

define('DB_HOST', 'localhost');
define('DB_NAME', 'sportoviste');
define('DB_USER', 'root');      // změň na svého DB uživatele
define('DB_PASS', '');          // změň na své DB heslo
define('DB_CHARSET', 'utf8mb4');

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // V produkci nikdy nevypisuj detaily chyby
    error_log('DB chyba: ' . $e->getMessage());
    die('Nepodařilo se připojit k databázi. Zkontroluj nastavení v includes/db.php');
}
