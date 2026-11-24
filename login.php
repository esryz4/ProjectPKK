<?php
require_once 'includes/functions.php';
if (!empty($_SESSION['user'])) { redirect(after_login_url()); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $user = db_one('SELECT * FROM users WHERE username=? AND aktif=1', 's', [trim($_POST['username'] ?? '')]);
    if ($user && password_verify($_POST['password'] ?? '', $user['password'])) {
        set_login_session($user);
        redirect(after_login_url());
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - <?= NAMA_USAHA ?></title>
<link href="assets/vendor/bootstrap.min.css" rel="stylesheet">
<link href="assets/vendor/icons.css" rel="stylesheet">
<link href="assets/style.css?v=<?= @filemtime(__DIR__ . '/assets/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="login-wrap">
  <div class="card p-4" style="width:380px;max-width:92vw">
    <div class="text-center mb-3">
      <i class="bi bi-gem fs-1" style="color:#111"></i>
      <h5 class="fw-bold mb-0"><?= NAMA_USAHA ?></h5>
      <small class="text-muted">Jasa Electroplating &middot; <?= ALAMAT_USAHA ?></small>
    </div>
    <?php if (($_SESSION['next'] ?? '') === 'pesan.php' && !$error): ?><div class="alert alert-info py-2 small">Silakan masuk terlebih dahulu untuk membuat pesanan.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Username</label><input name="username" class="form-control" required autofocus></div>
      <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-dark w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</button>
    </form>
    <div class="text-center mt-3 small">Belum punya akun? <a href="daftar.php">Daftar sebagai pelanggan</a><br><a href="index.php" class="text-muted">&larr; Kembali ke beranda</a></div>
  </div>
</div>
</body>
</html>
