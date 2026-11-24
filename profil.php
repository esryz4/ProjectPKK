<?php
require_once 'includes/functions.php';
login_any();
$uid = $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = $_POST['aksi'] ?? 'password';
    $row = db_one('SELECT * FROM users WHERE id_user=?', 'i', [$uid]);
    $pw = $_POST['lama'] ?? '';
    if (!$row || !password_verify($pw, $row['password'])) { flash('danger', 'Password saat ini salah.'); }
    elseif ($aksi === 'username') {
        $u = trim($_POST['username'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $u)) { flash('danger', 'Username 4-30 karakter: huruf, angka, titik, atau garis bawah.'); }
        elseif (db_one('SELECT id_user FROM users WHERE username=? AND id_user<>?', 'si', [$u, $uid])) { flash('danger', 'Username sudah dipakai orang lain.'); }
        else {
            db_exec('UPDATE users SET username=? WHERE id_user=?', 'si', [$u, $uid]);
            $_SESSION['user']['username'] = $u;
            flash('success', 'Username berhasil diganti menjadi "' . $u . '".');
        }
    } else {
        $baru = $_POST['baru'] ?? ''; $ulang = $_POST['ulang'] ?? '';
        if (strlen($baru) < 6) { flash('danger', 'Password baru minimal 6 karakter.'); }
        elseif ($baru !== $ulang) { flash('danger', 'Konfirmasi password tidak sama.'); }
        else {
            db_exec('UPDATE users SET password=? WHERE id_user=?', 'si', [password_hash($baru, PASSWORD_DEFAULT), $uid]);
            flash('success', 'Password berhasil diganti.');
        }
    }
    redirect('profil.php');
}
$me = db_one('SELECT username FROM users WHERE id_user=?', 'i', [$uid]);
$judul = 'Pengaturan Akun';
require is_pelanggan() ? 'includes/header_publik.php' : 'includes/header.php';
?>
<div class="row g-3 justify-content-center">
  <div class="col-md-5"><div class="card h-100"><div class="card-body">
    <h5 class="mb-3"><i class="bi bi-person me-2"></i>Ganti Username</h5>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="username">
      <div class="mb-3"><label class="form-label">Username baru</label><input name="username" class="form-control" value="<?= e($me['username'] ?? '') ?>" minlength="4" maxlength="30" required></div>
      <div class="mb-3"><label class="form-label">Password saat ini</label><input type="password" name="lama" class="form-control" required></div>
      <button class="btn btn-dark">Simpan Username</button>
    </form>
  </div></div></div>
  <div class="col-md-5"><div class="card h-100"><div class="card-body">
    <h5 class="mb-3"><i class="bi bi-key me-2"></i>Ganti Password</h5>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="password">
      <div class="mb-3"><label class="form-label">Password lama</label><input type="password" name="lama" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Password baru</label><input type="password" name="baru" class="form-control" minlength="6" required></div>
      <div class="mb-3"><label class="form-label">Ulangi password baru</label><input type="password" name="ulang" class="form-control" required></div>
      <button class="btn btn-dark">Simpan Password</button>
    </form>
  </div></div></div>
</div>
<?php require 'includes/footer.php'; ?>
