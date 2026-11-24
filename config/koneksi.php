<?php
// Konfigurasi koneksi database (XAMPP default: user root, tanpa password)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'db_cahaya_gumilang';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    die('Koneksi database gagal. Pastikan MySQL di XAMPP aktif dan database <b>' . htmlspecialchars($DB_NAME) . '</b> sudah diimpor.');
}
date_default_timezone_set('Asia/Jakarta');
