<?php
require_once 'includes/functions.php';
login_required();
$bisaUbah = !is_owner();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$bisaUbah) { flash('danger', 'Pemilik hanya dapat melihat data. Perubahan dilakukan oleh admin.'); redirect('pengeluaran.php'); }
    csrf_verify();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $id = (int)($_POST['id'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d'); $kat = $_POST['kategori'] ?? 'lainnya';
        $ket = trim($_POST['keterangan'] ?? ''); $jml = (float)($_POST['jumlah'] ?? 0);
        if (!isset(KATEGORI_PENGELUARAN[$kat])) { $kat = 'lainnya'; }
        if ($ket === '' || $jml <= 0) { flash('danger', 'Keterangan wajib diisi dan jumlah harus lebih dari 0.'); }
        elseif ($id > 0) { db_exec('UPDATE pengeluaran SET tanggal=?, kategori=?, keterangan=?, jumlah=? WHERE id_pengeluaran=?', 'sssdi', [$tanggal, $kat, $ket, $jml, $id]); flash('success', 'Pengeluaran diperbarui.'); }
        else { db_exec('INSERT INTO pengeluaran (id_user, tanggal, kategori, keterangan, jumlah) VALUES (?,?,?,?,?)', 'isssd', [$_SESSION['user']['id'], $tanggal, $kat, $ket, $jml]); flash('success', 'Pengeluaran dicatat.'); }
    } elseif ($aksi === 'hapus') {
        owner_only();
        db_exec('DELETE FROM pengeluaran WHERE id_pengeluaran=?', 'i', [(int)$_POST['id']]); flash('success', 'Pengeluaran dihapus.');
    }
    redirect('pengeluaran.php?' . http_build_query(['dari' => $_POST['dari'] ?? '', 'sampai' => $_POST['sampai'] ?? '']));
}
$dari = $_GET['dari'] ?? date('Y-m-01'); $sampai = $_GET['sampai'] ?? date('Y-m-t');
$rows = db_all('SELECT k.*, u.nama AS petugas FROM pengeluaran k JOIN users u ON u.id_user=k.id_user WHERE k.tanggal BETWEEN ? AND ? ORDER BY k.tanggal DESC, k.id_pengeluaran DESC', 'ss', [$dari, $sampai]);
$total = array_sum(array_column($rows, 'jumlah'));
$judul = 'Pengeluaran';
require 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h4 class="mb-0">Pengeluaran Usaha</h4>
  <div class="d-flex flex-wrap gap-2">
    <form class="d-flex gap-2"><input type="date" name="dari" value="<?= e($dari) ?>" class="form-control"><input type="date" name="sampai" value="<?= e($sampai) ?>" class="form-control"><button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button></form>
    <?php if ($bisaUbah): ?><button class="btn btn-dark text-nowrap" data-bs-toggle="modal" data-bs-target="#modalForm"><i class="bi bi-plus-lg me-1"></i>Tambah</button><?php endif; ?>
  </div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
  <thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th>Dicatat oleh</th><th class="text-end">Jumlah</th><?php if ($bisaUbah): ?><th class="text-end">Aksi</th><?php endif; ?></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= tgl($r['tanggal']) ?></td><td><span class="badge text-bg-light border"><?= e(KATEGORI_PENGELUARAN[$r['kategori']]) ?></span></td><td><?= e($r['keterangan']) ?></td><td><?= e($r['petugas']) ?></td><td class="text-end"><?= rupiah($r['jumlah']) ?></td>
      <?php if ($bisaUbah): ?><td class="text-end text-nowrap">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalForm" data-edit="<?= e(json_encode(['id' => $r['id_pengeluaran'], 'tanggal' => $r['tanggal'], 'kategori' => $r['kategori'], 'keterangan' => $r['keterangan'], 'jumlah' => $r['jumlah']])) ?>"><i class="bi bi-pencil"></i></button>
        <?php if (is_owner()): ?><form method="post" class="d-inline" data-confirm="Hapus pengeluaran ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id_pengeluaran'] ?>"><input type="hidden" name="dari" value="<?= e($dari) ?>"><input type="hidden" name="sampai" value="<?= e($sampai) ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form><?php endif; ?>
      </td><?php endif; ?></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="<?= $bisaUbah ? 6 : 5 ?>" class="text-center text-muted py-4">Tidak ada pengeluaran pada periode ini.</td></tr><?php endif; ?>
  </tbody>
  <tfoot><tr class="table-light fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end"><?= rupiah($total) ?></td><?php if ($bisaUbah): ?><td></td><?php endif; ?></tr></tfoot>
</table></div></div>

<?php if ($bisaUbah): ?>
<div class="modal fade" id="modalForm" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Tambah Data</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="0">
    <input type="hidden" name="dari" value="<?= e($dari) ?>"><input type="hidden" name="sampai" value="<?= e($sampai) ?>">
    <div class="row g-3 mb-3">
      <div class="col-6"><label class="form-label">Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
      <div class="col-6"><label class="form-label">Kategori</label><select name="kategori" class="form-select"><?php foreach (KATEGORI_PENGELUARAN as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="mb-3"><label class="form-label">Keterangan</label><input name="keterangan" class="form-control" placeholder="mis. Beli larutan HCl" required></div>
    <div class="mb-3"><label class="form-label">Jumlah (Rp)</label><input type="number" name="jumlah" class="form-control" min="1" step="any" required></div>
  </div>
  <div class="modal-footer"><button class="btn btn-dark">Simpan</button></div>
</form></div></div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
