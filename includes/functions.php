<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/koneksi.php';

const NAMA_USAHA = 'PT Cahaya Gumilang';
const ALAMAT_USAHA = 'Blok Sukamaju';

// ===== KONTAK USAHA (EDIT DI SINI) =====
const WA_NOMOR   = '6281234567890';   // format internasional tanpa + / spasi / 0 di depan, mis. 6281234567890
const ALAMAT_LENGKAP = 'Blok Sukamaju';   // tulis selengkap mungkin: jalan, desa, kecamatan, kota
const MAPS_QUERY = 'PT Cahaya Gumilang, Blok Sukamaju';   // kata kunci pencarian di Google Maps (nama usaha + alamat)
const JAM_BUKA   = 'Senin - Sabtu, 08.00 - 16.00';   // sesuaikan
function wa_link($pesan = 'Halo PT Cahaya Gumilang, saya ingin bertanya tentang jasa electroplating.') {
    return 'https://wa.me/' . WA_NOMOR . '?text=' . rawurlencode($pesan);
}
function maps_link()  { return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(MAPS_QUERY); }
function maps_embed() { return 'https://www.google.com/maps?q=' . rawurlencode(MAPS_QUERY) . '&output=embed'; }

// Tahapan proses electroplating sesuai proposal
const STATUS_URUTAN = [
    'menunggu'       => 'Menunggu Konfirmasi',
    'diterima'       => 'Diterima',
    'pembersihan'    => 'Pembersihan',
    'perendaman_hcl' => 'Perendaman HCl',
    'pembilasan'     => 'Pembilasan',
    'pelapisan'      => 'Pelapisan',
    'selesai'        => 'Selesai',
    'diambil'        => 'Diambil',
];
const STATUS_WARNA = [
    'menunggu' => 'danger', 'diterima' => 'secondary', 'pembersihan' => 'info', 'perendaman_hcl' => 'warning',
    'pembilasan' => 'primary', 'pelapisan' => 'dark', 'selesai' => 'success', 'diambil' => 'success',
];
const KATEGORI_PENGELUARAN = [
    'bahan_kimia' => 'Bahan Kimia', 'listrik' => 'Listrik', 'gaji' => 'Gaji',
    'peralatan' => 'Peralatan', 'operasional' => 'Operasional', 'lainnya' => 'Lainnya',
];

/* ---------- Helper umum ---------- */
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function tgl($d) { return $d ? date('d/m/Y', strtotime($d)) : '-'; }
function tgl_waktu($d) { return $d ? date('d/m/Y H:i', strtotime($d)) : '-'; }
function redirect($url) { header('Location: ' . $url); exit; }

function badge_status($s) {
    $label = STATUS_URUTAN[$s] ?? $s;
    $warna = STATUS_WARNA[$s] ?? 'secondary';
    return '<span class="badge text-bg-' . $warna . '">' . e($label) . '</span>';
}
function badge_bayar($s) {
    $map = ['belum' => ['danger', 'Belum Bayar'], 'dp' => ['warning', 'DP / Sebagian'], 'lunas' => ['success', 'Lunas']];
    [$w, $l] = $map[$s] ?? ['secondary', $s];
    return '<span class="badge text-bg-' . $w . '">' . $l . '</span>';
}

/* ---------- Database helper (prepared statement) ---------- */
function db_stmt($sql, $types = '', $params = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($types !== '') { $st->bind_param($types, ...$params); }
    $st->execute();
    return $st;
}
function db_all($sql, $types = '', $params = []) {
    $st = db_stmt($sql, $types, $params);
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
}
function db_one($sql, $types = '', $params = []) {
    $rows = db_all($sql, $types, $params);
    return $rows[0] ?? null;
}
function db_val($sql, $types = '', $params = []) {
    $r = db_one($sql, $types, $params);
    return $r ? reset($r) : null;
}
function db_exec($sql, $types = '', $params = []) {
    $st = db_stmt($sql, $types, $params);
    $hasil = $st->insert_id > 0 ? $st->insert_id : $st->affected_rows;
    $st->close();
    return $hasil;
}

/* ---------- Autentikasi & hak akses ---------- */
// Harus sudah login (siapa pun: pemilik, admin, atau pelanggan)
function login_any() {
    if (empty($_SESSION['user'])) { redirect('login.php'); }
}
// Hanya pemilik & admin/pegawai (panel pengelolaan). Pelanggan diarahkan ke halaman akunnya.
function login_required() {
    login_any();
    if ($_SESSION['user']['role'] === 'pelanggan') { redirect('akun.php'); }
}
function is_pelanggan() { return ($_SESSION['user']['role'] ?? '') === 'pelanggan'; }
// Tujuan setelah login berhasil
function after_login_url() {
    $next = $_SESSION['next'] ?? '';
    unset($_SESSION['next']);
    if (is_pelanggan()) { return $next === 'pesan.php' ? 'pesan.php' : 'akun.php'; }
    return 'dashboard.php';
}
function set_login_session($user) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int)$user['id_user'], 'nama' => $user['nama'], 'username' => $user['username'], 'role' => $user['role']];
}
function is_owner() { return ($_SESSION['user']['role'] ?? '') === 'pemilik'; }
function owner_only() {
    login_required();
    if (!is_owner()) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:40px"><h3>Akses ditolak</h3><p>Halaman ini hanya untuk pemilik usaha.</p><a href="dashboard.php">Kembali</a></div>');
    }
}

/* ---------- CSRF & flash message ---------- */
function csrf_token() {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_verify() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}
function flash($tipe, $pesan) { $_SESSION['flash'] = [$tipe, $pesan]; }
function flash_show() {
    if (!empty($_SESSION['flash'])) {
        [$t, $m] = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($t) . ' alert-dismissible fade show" role="alert">' . e($m) .
             '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

/* ---------- Logika bisnis ---------- */
function total_dibayar($id_pesanan) {
    return (float)db_val('SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE id_pesanan=?', 'i', [$id_pesanan]);
}
// Hitung ulang status pembayaran: belum / dp / lunas
function sinkron_status_bayar($id_pesanan) {
    $total = (float)db_val('SELECT total_biaya FROM pesanan WHERE id_pesanan=?', 'i', [$id_pesanan]);
    $bayar = total_dibayar($id_pesanan);
    $st = $bayar <= 0 ? 'belum' : ($bayar >= $total ? 'lunas' : 'dp');
    db_exec('UPDATE pesanan SET status_bayar=? WHERE id_pesanan=?', 'si', [$st, $id_pesanan]);
}
function buat_no_pesanan() {
    $prefix = 'PO-' . date('ymd') . '-';
    $n = (int)db_val('SELECT COUNT(*) FROM pesanan WHERE no_pesanan LIKE ?', 's', [$prefix . '%']);
    return $prefix . str_pad($n + 1, 3, '0', STR_PAD_LEFT);
}
