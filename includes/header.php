<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notif.php';
login_required();
$u = $_SESSION['user'];
$hal = basename($_SERVER['PHP_SELF']);
[$notifList, $notifBaru] = notif_ambil($u['id']);
function nav_item($files, $icon, $label) {
    global $hal;
    $files = (array)$files;
    $aktif = in_array($hal, $files) ? ' active' : '';
    echo '<li><a class="side-link' . $aktif . '" href="' . $files[0] . '">' . $label . '</a></li>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul ?? 'Dashboard') ?> - <?= NAMA_USAHA ?></title>
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
    <a class="side-brand" href="dashboard.php">CAHAYA GUMILANG<small>Panel Pengelola</small></a>
    <button class="btn btn-outline-dark btn-sm side-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sideMenu">Menu</button>
  </div>
  <div class="collapse side-collapse" id="sideMenu">
    <ul class="side-menu">
      <?php
      nav_item('dashboard.php', '', 'Dashboard');
      nav_item(['pesanan.php', 'pesanan_tambah.php', 'pesanan_detail.php', 'nota.php'], '', 'Pesanan');
      nav_item('pelanggan.php', '', 'Pelanggan');
      nav_item('layanan.php', '', 'Layanan');
      nav_item('pengeluaran.php', '', 'Pengeluaran');
      if (is_owner()) {
          nav_item('laporan.php', '', 'Laporan');
          nav_item('users.php', '', 'Pengguna');
      }
      ?>
      <li>
        <a class="side-link" href="#" data-bs-toggle="collapse" data-bs-target="#notifPanel" role="button">Notifikasi<?php if ($notifBaru > 0): ?><span class="side-cnt" id="notifBadge"><?= $notifBaru > 9 ? '9+' : $notifBaru ?></span><?php endif; ?></a>
        <div class="collapse notif-panel" id="notifPanel"><div class="notif-scroll">
          <?php foreach ($notifList as $n): ?>
            <a class="item <?= $n['baru'] ? 'baru' : '' ?>" href="pesanan_detail.php?id=<?= $n['id'] ?>">
              <div class="fw-semibold"><?= e($n['teks']) ?></div>
              <div class="text-muted"><?= e($n['no']) ?> &middot; <?= e($n['pelanggan']) ?> &middot; <?= waktu_lalu($n['waktu']) ?></div>
            </a>
          <?php endforeach; if (!$notifList): ?><div class="kosong">Belum ada aktivitas.</div><?php endif; ?>
        </div></div>
      </li>
    </ul>
    <div class="side-user">
      <span class="nm"><?= e($u['nama']) ?></span>
      <span class="rl"><?= $u['role'] === 'pemilik' ? 'Pemilik' : 'Admin / Pegawai' ?></span>
      <a class="mini" href="index.php" target="_blank">Website Publik</a><a class="mini" href="profil.php">Pengaturan Akun</a><a class="mini" href="logout.php">Keluar</a>
    </div>
  </div>
</aside>
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
<main class="app-main"><div class="app-content">
<?php flash_show(); ?>
