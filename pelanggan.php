<?php
require_once 'includes/functions.php';
login_required();
$bisaUbah = !is_owner();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$bisaUbah) { flash('danger', 'Pemilik hanya dapat melihat data. Perubahan dilakukan oleh admin.'); redirect('pelanggan.php'); }
    csrf_verify();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? ''); $hp = trim($_POST['no_hp'] ?? ''); $alamat = trim($_POST['alamat'] ?? '');
        if ($nama === '') { flash('danger', 'Nama pelanggan wajib diisi.'); }
        elseif ($id > 0) { db_exec('UPDATE pelanggan SET nama=?, no_hp=?, alamat=? WHERE id_pelanggan=?', 'sssi', [$nama, $hp, $alamat, $id]); flash('success', 'Data pelanggan diperbarui.'); }
        else { db_exec('INSERT INTO pelanggan (nama, no_hp, alamat) VALUES (?,?,?)', 'sss', [$nama, $hp, $alamat]); flash('success', 'Pelanggan ditambahkan.'); }
    } elseif ($aksi === 'hapus') {
        try { db_exec('DELETE FROM pelanggan WHERE id_pelanggan=?', 'i', [(int)$_POST['id']]); flash('success', 'Pelanggan dihapus.'); }
        catch (mysqli_sql_exception $ex) { flash('danger', 'Pelanggan tidak bisa dihapus karena sudah memiliki pesanan.'); }
    }
    redirect('pelanggan.php');
}
$q = trim($_GET['q'] ?? '');
$rows = db_all("SELECT pl.*, (SELECT COUNT(*) FROM pesanan p WHERE p.id_pelanggan=pl.id_pelanggan) AS jml_pesanan
                FROM pelanggan pl WHERE pl.nama LIKE ? OR pl.no_hp LIKE ? ORDER BY pl.nama", 'ss', ["%$q%", "%$q%"]);
$judul = 'Pelanggan';
require 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h4 class="mb-0">Data Pelanggan</h4>
  <div class="d-flex gap-2">
    <form class="d-flex gap-2"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari nama / no. HP"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button></form>
    <?php if ($bisaUbah): ?><button class="btn btn-dark text-nowrap" data-bs-toggle="modal" data-bs-target="#modalForm"><i class="bi bi-plus-lg me-1"></i>Tambah</button><?php endif; ?>
  </div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
  <thead><tr><th>#</th><th>Nama</th><th>No. HP</th><th>Alamat</th><th>Pesanan</th><?php if ($bisaUbah): ?><th class="text-end">Aksi</th><?php endif; ?></tr></thead>
  <tbody>
  <?php foreach ($rows as $i => $r): ?>
    <tr><td><?= $i + 1 ?></td><td class="fw-semibold"><?= e($r['nama']) ?></td><td><?= e($r['no_hp']) ?></td><td><?= e($r['alamat']) ?></td><td><?= (int)$r['jml_pesanan'] ?></td>
      <?php if ($bisaUbah): ?><td class="text-end text-nowrap">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalForm" data-edit="<?= e(json_encode(['id' => $r['id_pelanggan'], 'nama' => $r['nama'], 'no_hp' => $r['no_hp'], 'alamat' => $r['alamat']])) ?>"><i class="bi bi-pencil"></i></button>
        <form method="post" class="d-inline" data-confirm="Hapus pelanggan ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id_pelanggan'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
      </td><?php endif; ?></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="<?= $bisaUbah ? 6 : 5 ?>" class="text-center text-muted py-4">Belum ada data.</td></tr><?php endif; ?>
  </tbody></table></div></div>

<?php if ($bisaUbah): ?>
<div class="modal fade" id="modalForm" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Tambah Data</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="0">
    <div class="mb-3"><label class="form-label">Nama</label><input name="nama" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">No. HP / WhatsApp</label><input name="no_hp" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-dark">Simpan</button></div>
</form></div></div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
