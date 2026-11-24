<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notif.php';
$hal = basename($_SERVER['PHP_SELF']);
$u = $_SESSION['user'] ?? null;
$notifList = []; $notifBaru = 0;
if ($u && $u['role'] === 'pelanggan') { [$notifList, $notifBaru] = notif_pelanggan($u['id']); }
function pub_nav($file, $label) {
    global $hal;
    echo '<li><a class="side-link' . ($hal === $file ? ' active' : '') . '" href="' . $file . '">' . $label . '</a></li>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul ?? 'Jasa Electroplating') ?> - <?= NAMA_USAHA ?></title>
<script>try{if(localStorage.getItem("sb")==="0")document.documentElement.classList.add("sb-hide")}catch(e){}</script>
<link href="assets/vendor/bootstrap.min.css" rel="stylesheet">
<link href="assets/vendor/icons.css" rel="stylesheet">
<link href="assets/style.css?v=<?= @filemtime(__DIR__ . '/../assets/style.css') ?>" rel="stylesheet">
</head>
<body>
<button type="button" class="sb-toggle no-print" id="sbToggle" aria-label="Tampilkan atau sembunyikan menu samping" title="Tampilkan / sembunyikan menu"><i class="bi bi-list"></i></button>
<div class="app-shell">
<aside class="sidebar no-print">
  <div class="side-top">
    <a class="side-brand" href="index.php">CAHAYA GUMILANG<small>Jasa Electroplating</small></a>
    <button class="btn btn-outline-dark btn-sm side-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sideMenu">Menu</button>
  </div>
  <div class="collapse side-collapse" id="sideMenu">
    <ul class="side-menu">
      <?php
      pub_nav('index.php', 'Beranda');
      pub_nav('pesan.php', 'Pesan');
      if ($u && $u['role'] === 'pelanggan') { pub_nav('akun.php', 'Pesanan Saya'); }
      ?>
      <?php if ($u && $u['role'] === 'pelanggan'): ?>
      <li>
        <a class="side-link" href="#" data-bs-toggle="collapse" data-bs-target="#notifPanel" role="button">Notifikasi<?php if ($notifBaru > 0): ?><span class="side-cnt" id="notifBadge"><?= $notifBaru > 9 ? '9+' : $notifBaru ?></span><?php endif; ?></a>
        <div class="collapse notif-panel" id="notifPanel"><div class="notif-scroll">
          <?php foreach ($notifList as $n): ?>
            <a class="item <?= $n['baru'] ? 'baru' : '' ?>" href="pesanan_saya.php?id=<?= $n['id'] ?>">
              <div class="fw-semibold"><?= e($n['teks']) ?></div>
              <div class="text-muted"><?= e($n['no']) ?> &middot; <?= waktu_lalu($n['waktu']) ?></div>
            </a>
          <?php endforeach; if (!$notifList): ?><div class="kosong">Belum ada kabar terbaru untuk pesanan Anda.</div><?php endif; ?>
        </div></div>
      </li>
      <?php endif; ?>
      <li><a class="side-link" href="index.php#kontak">Lokasi &amp; Kontak</a></li>
    </ul>
    <div class="side-user">
      <?php if (!$u): ?>
        <a href="login.php" class="btn btn-outline-dark btn-sm w-100 mb-2">Masuk</a>
        <a href="daftar.php" class="btn btn-dark btn-sm w-100">Daftar</a>
      <?php elseif ($u['role'] === 'pelanggan'): ?>
        <span class="nm"><?= e($u['nama']) ?></span><span class="rl">Pelanggan</span>
        <a class="mini" href="profil.php">Pengaturan Akun</a><a class="mini" href="logout.php">Keluar</a>
      <?php else: ?>
        <span class="nm"><?= e($u['nama']) ?></span><span class="rl"><?= $u['role'] === 'pemilik' ? 'Pemilik' : 'Admin / Pegawai' ?></span>
        <a href="dashboard.php" class="btn btn-dark btn-sm w-100 mb-2">Panel Pengelola</a>
        <a class="mini" href="logout.php">Keluar</a>
      <?php endif; ?>
    </div>
  </div>
</aside>
<?php if ($u && $u['role'] === 'pelanggan'): ?>
<script>
(function () {
  var p = document.getElementById('notifPanel'); if (!p) return;
  p.addEventListener('show.bs.collapse', function () {
    var b = document.getElementById('notifBadge'); if (!b) return;
    var fd = new FormData(); fd.append('csrf', <?= json_encode(csrf_token()) ?>);
    fetch('notif_baca.php', { method: 'POST', body: fd }).then(function () { b.remove(); });
  });
})();
</script>
<?php endif; ?>
<main class="app-main"><div class="app-content">
<?php flash_show(); ?>
