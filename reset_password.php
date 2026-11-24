<?php
// DARURAT: jalankan SEKALI jika login gagal setelah impor SQL, lalu HAPUS file ini.
// Mengatur ulang password akun 'pemilik', 'admin', dan 'budi' menjadi: password
require_once 'config/koneksi.php';
$hash = password_hash('password', PASSWORD_DEFAULT);
$st = $db->prepare("UPDATE users SET password=? WHERE username IN ('pemilik','admin','budi')");
$st->bind_param('s', $hash);
$st->execute();
echo 'Password akun pemilik & admin direset menjadi "password". Hapus file reset_password.php sekarang.';
