<?php
require_once 'includes/functions.php';
owner_only();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? ''); $username = trim($_POST['username'] ?? '');
        $role = ($_POST['role'] ?? 'admin') === 'pemilik' ? 'pemilik' : 'admin';
        $aktif = (int)($_POST['aktif'] ?? 1); $pass = $_POST['password'] ?? '';
        if ($id === $_SESSION['user']['id']) { $aktif = 1; $role = $_SESSION['user']['role']; } // jangan kunci diri sendiri
        $dup = db_val('SELECT COUNT(*) FROM users WHERE username=? AND id_user<>?', 'si', [$username, $id]);
        if ($nama === '' || $username === '') { flash('danger', 'Nama dan username wajib diisi.'); }
        elseif ($dup) { flash('danger', 'Username sudah dipakai.'); }
        elseif ($id > 0) {
            db_exec('UPDATE users SET nama=?, username=?, role=?, aktif=? WHERE id_user=?', 'sssii', [$nama, $username, $role, $aktif, $id]);
            if ($pass !== '') {
                if (strlen($pass) < 6) { flash('danger', 'Data tersimpan, tetapi password minimal 6 karakter sehingga tidak diubah.'); redirect('users.php'); }
                db_exec('UPDATE users SET password=? WHERE id_user=?', 'si', [password_hash($pass, PASSWORD_DEFAULT), $id]);
            }
            flash('success', 'Pengguna diperbarui.');
        } else {
            if (strlen($pass) < 6) { flash('danger', 'Password minimal 6 karakter.'); redirect('users.php'); }
            db_exec('INSERT INTO users (nama, username, password, role, aktif) VALUES (?,?,?,?,?)', 'ssssi', [$nama, $username, password_hash($pass, PASSWORD_DEFAULT), $role, $aktif]);
            flash('success', 'Pengguna ditambahkan.');
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)$_POST['id'];
        if ($id === $_SESSION['user']['id']) { flash('danger', 'Anda tidak bisa menghapus akun sendiri.'); }
        else {
            try { db_exec('DELETE FROM users WHERE id_user=?', 'i', [$id]); flash('success', 'Pengguna dihapus.'); }
            catch (mysqli_sql_exception $ex) { flash('danger', 'Pengguna sudah punya riwayat transaksi. Nonaktifkan saja.'); }
        }
    }
    redirect('users.php');
}
$rows = db_all("SELECT * FROM users WHERE role<>'pelanggan' ORDER BY role, nama");
$judul = 'Pengguna';
require 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Pengguna Sistem</h4>
  <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalForm"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
  <thead><tr><th>Nama</th><th>Username</th><th>Hak Akses</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td class="fw-semibold"><?= e($r['nama']) ?></td><td><?= e($r['username']) ?></td>
      <td><?= $r['role'] === 'pemilik' ? '<span class="badge text-bg-warning">Pemilik</span>' : '<span class="badge text-bg-info">Admin / Pegawai</span>' ?></td>
      <td><?= $r['aktif'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
      <td class="text-end text-nowrap">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalForm" data-edit="<?= e(json_encode(['id' => $r['id_user'], 'nama' => $r['nama'], 'username' => $r['username'], 'role' => $r['role'], 'aktif' => $r['aktif']])) ?>"><i class="bi bi-pencil"></i></button>
        <?php if ((int)$r['id_user'] !== $_SESSION['user']['id']): ?><form method="post" class="d-inline" data-confirm="Hapus pengguna ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id_user'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form><?php endif; ?>
      </td></tr>
  <?php endforeach; ?>
  </tbody></table></div></div>

<div class="modal fade" id="modalForm" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Tambah Data</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="0">
    <div class="mb-3"><label class="form-label">Nama lengkap</label><input name="nama" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Username</label><input name="username" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Password <small class="text-muted">(kosongkan jika tidak diubah)</small></label><input type="password" name="password" class="form-control" autocomplete="new-password"></div>
    <div class="row g-3">
      <div class="col-6"><label class="form-label">Hak akses</label><select name="role" class="form-select"><option value="admin">Admin / Pegawai</option><option value="pemilik">Pemilik</option></select></div>
      <div class="col-6"><label class="form-label">Status</label><select name="aktif" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-dark">Simpan</button></div>
</form></div></div>
<?php require 'includes/footer.php'; ?>
