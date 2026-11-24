<?php
// Menandai semua notifikasi sebagai sudah dilihat (dipanggil saat lonceng dibuka)
require_once 'includes/functions.php';
login_any();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    db_exec('UPDATE users SET notif_dilihat=NOW() WHERE id_user=?', 'i', [$_SESSION['user']['id']]);
}
http_response_code(204);