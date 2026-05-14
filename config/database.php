<?php
// ============================================================
// config/database.php — Konfigurasi Koneksi Database
// Sesuaikan dengan setting Laragon Anda
// ============================================================

define('DB_HOST',   'localhost');
define('DB_NAME',   'ev_planner');
define('DB_USER',   'root');
define('DB_PASS',   '');          // Laragon default: kosong
define('DB_CHARSET','utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Koneksi DB gagal: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
