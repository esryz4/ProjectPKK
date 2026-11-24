<?php
require_once 'includes/functions.php';
login_required();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_layanan'] ?? ''); $satuan = 'kg';   // semua layanan dihitung per kilogram
        $harga = (float)($_POST['harga_satuan'] ?? 0); $ket = trim($_POST['keterangan'] ?? ''); $aktif = (int)($_POST['aktif'] ?? 1);
                if ($nama === '' || $harga < 0) { flash('danger', 'Nama layanan wajib diisi dan harga tidak boleh negatif.'); }
        elseif ($id > 0) { db_exec('UPDATE layanan SET nama_layanan=?, satuan=?, harga_satuan=?, keterangan=?, aktif=? WHERE id_layanan=?', 'ssdsii', [$nama, $satuan, $harga, $ket, $aktif, $id]); flash('success', 'Layanan diperbarui.'); }
        else { db_exec('INSERT INTO layanan (nama_layanan, satuan, harga_satuan, keterangan, aktif) VALUES (?,?,?,?,?)', 'ssdsi', [$nama, $satuan, $harga, $ket, $aktif]); flash('success', 'Layanan ditambahkan.'); }
    } elseif ($aksi === 'hapus') {
        try { db_exec('DELETE FROM layanan WHERE id_layanan=?', 'i', [(int)$_POST['id']]); flash('success', 'Layanan dihapus.'); }
        catch (mysqli_sql_exception $ex) { flash('danger', 'Layanan sudah dipakai pada pesanan. Nonaktifkan saja agar tidak muncul di pesanan baru.'); }
    }
    redirect('layanan.php');
}
$rows = db_all('SELECT * FROM layanan ORDER BY aktif DESC, nama_layanan');
$judul = 'Layanan';
require 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Layanan Electroplating</h4>
  <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalForm"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
  <thead><tr><th>#</th><th>Layanan</th><th>Satuan</th><th>Harga Acuan per kg (internal)</th><th>Keterangan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $i => $r): ?>
    <tr><td><?= $i + 1 ?></td><td class="fw-semibold"><?= e($r['nama_layanan']) ?></td><td><?= e($r['satuan']) ?></td><td><?= rupiah($r['harga_satuan']) ?></td><td><?= e($r['keterangan']) ?></td>
      <td><?= $r['aktif'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
      <td class="text-end text-nowrap">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalForm" data-edit="<?= e(json_encode(['id' => $r['id_layanan'], 'nama_layanan' => $r['nama_layanan'], 'satuan' => $r['satuan'], 'harga_satuan' => $r['harga_satuan'], 'keterangan' => $r['keterangan'], 'aktif' => $r['aktif']])) ?>"><i class="bi bi-pencil"></i></button>
        <form method="post" class="d-inline" data-confirm="Hapus layanan ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id_layanan'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
      </td></tr>
  <?php endforeach; ?>
  </tbody></table></div></div>

<div class="modal fade" id="modalForm" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Tambah Data</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="0">
    <div class="mb-3"><label class="form-label">Nama layanan</label><input name="nama_layanan" class="form-control" placeholder="mis. Pelapisan Nikel" required></div>
    <div class="row g-3 mb-3">
      <div class="col-5"><label class="form-label">Satuan</label><input class="form-control" value="kg (per kilo)" disabled></div>
      <div class="col-7"><label class="form-label">Harga acuan per kg (Rp) <small class="text-muted">internal</small></label><input type="number" name="harga_satuan" class="form-control" min="0" step="any" placeholder="Boleh kosong"></div>
    </div>
    <div class="mb-3"><label class="form-label">Keterangan</label><input name="keterangan" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Status</label><select name="aktif" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
  </div>
  <div class="modal-footer"><button class="btn btn-dark">Simpan</button></div>
</form></div></div>
<?php require 'includes/footer.php'; ?>
