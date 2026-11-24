<?php
require_once 'includes/functions.php';
if (!empty($_SESSION['user'])) { redirect(after_login_url()); }
$f = ['nama' => '', 'no_hp' => '', 'alamat' => '', 'username' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach ($f as $k => $_) { $f[$k] = trim($_POST[$k] ?? ''); }
    $pass = $_POST['password'] ?? ''; $ulang = $_POST['ulang'] ?? '';
    $err = '';
    if ($f['nama'] === '' || $f['no_hp'] === '') { $err = 'Nama dan No. HP wajib diisi.'; }
    elseif (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $f['username'])) { $err = 'Username 4-30 karakter: huruf, angka, titik, atau garis bawah.'; }
    elseif (strlen($pass) < 6) { $err = 'Password minimal 6 karakter.'; }
    elseif ($pass !== $ulang) { $err = 'Konfirmasi password tidak sama.'; }
    elseif (db_val('SELECT COUNT(*) FROM users WHERE username=?', 's', [$f['username']])) { $err = 'Username sudah dipakai.'; }
    if ($err === '') {
        try {
            $db->begin_transaction();
            $uid = db_exec('INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)', 'ssss', [$f['nama'], $f['username'], password_hash($pass, PASSWORD_DEFAULT), 'pelanggan']);
            db_exec('INSERT INTO pelanggan (nama, no_hp, alamat, id_user) VALUES (?,?,?,?)', 'sssi', [$f['nama'], $f['no_hp'], $f['alamat'], $uid]);
            $db->commit();
            set_login_session(['id_user' => $uid, 'nama' => $f['nama'], 'username' => $f['username'], 'role' => 'pelanggan']);
            flash('success', 'Pendaftaran berhasil. Selamat datang!');
            redirect(after_login_url());
        } catch (mysqli_sql_exception $ex) {
            $db->rollback();
            $err = 'Pendaftaran gagal, coba lagi.';
        }
    }
    flash('danger', $err);
}
$judul = 'Daftar';
require 'includes/header_publik.php';
?>
<div class="row justify-content-center"><div class="col-md-6 col-lg-5">
<div class="card"><div class="card-body p-4">
  <h4 class="mb-1">Daftar Pelanggan</h4>
  <p class="text-muted small">Akun diperlukan untuk memesan dan memantau pesanan.</p>
  <form method="post"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Nama lengkap / usaha</label><input name="nama" class="form-control" value="<?= e($f['nama']) ?>" required></div>
    <div class="mb-3"><label class="form-label">No. HP / WhatsApp</label><input name="no_hp" class="form-control" value="<?= e($f['no_hp']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2"><?= e($f['alamat']) ?></textarea></div>
    <div class="mb-3"><label class="form-label">Username</label><input name="username" class="form-control" value="<?= e($f['username']) ?>" required></div>
    <div class="row g-2 mb-3">
      <div class="col-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" minlength="6" required></div>
      <div class="col-6"><label class="form-label">Ulangi password</label><input type="password" name="ulang" class="form-control" required></div>
    </div>
    <button class="btn btn-dark w-100">Daftar</button>
  </form>
  <div class="text-center mt-3 small">Sudah punya akun? <a href="login.php">Masuk</a></div>
</div></div></div></div>
<?php require 'includes/footer.php'; ?>
