<?php
require_once 'includes/functions.php';
require_once 'config/pembayaran.php';
login_any();
if (!is_pelanggan()) { redirect('dashboard.php'); }
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p = db_one('SELECT p.* FROM pesanan p JOIN pelanggan pl ON pl.id_pelanggan=p.id_pelanggan WHERE p.id_pesanan=? AND pl.id_user=?', 'ii', [$id, $_SESSION['user']['id']]);
if (!$p) { flash('danger', 'Pesanan tidak ditemukan.'); redirect('akun.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'batal') {
    csrf_verify();
    if ($p['status'] === 'menunggu') { db_exec('DELETE FROM pesanan WHERE id_pesanan=?', 'i', [$id]); flash('success', 'Pesanan dibatalkan.'); redirect('akun.php'); }
    flash('danger', 'Pesanan sudah diproses dan tidak bisa dibatalkan sendiri. Hubungi kami.');
    redirect('pesanan_saya.php?id=' . $id);
}
$items = db_all('SELECT d.*, l.nama_layanan, l.satuan FROM detail_pesanan d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_pesanan=?', 'i', [$id]);
$bayar = db_all('SELECT * FROM pembayaran WHERE id_pesanan=? ORDER BY tgl_bayar, id_pembayaran', 'i', [$id]);
$log = db_all('SELECT * FROM log_status WHERE id_pesanan=? ORDER BY waktu DESC, id_log DESC', 'i', [$id]);
$dibayar = array_sum(array_column($bayar, 'jumlah'));
$sisa = max(0, (float)$p['total_biaya'] - $dibayar);
$belumHarga = (float)$p['total_biaya'] <= 0;   // biaya belum ditetapkan pengelola
$judul = 'Pesanan ' . $p['no_pesanan'];
require 'includes/header_publik.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><a href="akun.php" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Pesanan saya</a>
    <h4 class="mb-0"><?= e($p['no_pesanan']) ?> <?= badge_status($p['status']) ?> <?= badge_bayar($p['status_bayar']) ?></h4></div>
  <div class="d-flex gap-2">
    <a href="nota.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-dark"><i class="bi bi-printer me-1"></i>Nota</a>
    <?php if ($p['status'] === 'menunggu'): ?><form method="post" data-confirm="Batalkan pesanan ini?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="batal"><button class="btn btn-outline-danger">Batalkan</button></form><?php endif; ?>
  </div>
</div>
<?php if ($p['status'] === 'menunggu'): ?><div class="alert alert-info">Pesanan Anda menunggu konfirmasi. Silakan serahkan barang ke lokasi kami agar dapat dicek dan diproses. Biaya akan kami tetapkan setelah barang dicek.</div><?php endif; ?>
<?php if ($p['sumber'] === 'online' && ($belumHarga || $sisa > 0)): ?>
<div class="card mb-3"><div class="card-body">
  <h6 class="mb-3"><i class="bi bi-wallet2 me-1"></i>Pembayaran</h6>
  <?php if (!empty($p['metode_bayar'])): ?>
    <div class="mb-2">Metode pembayaran: <span class="badge text-bg-dark"><?= e(metode_label($p['metode_bayar'])) ?></span></div>
    <?php if ($belumHarga): ?><div class="alert alert-secondary py-2 small">Biaya belum ditetapkan. Nominal yang harus dibayar akan muncul di sini setelah barang dicek.</div><?php endif; ?>
    <?= blok_pembayaran($p['metode_bayar'], $belumHarga ? null : $sisa) ?>
  <?php else: ?>
    <div class="alert alert-warning py-2">Anda belum memilih metode pembayaran.</div>
  <?php endif; ?>
</div></div>
<?php endif; ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body"><h6>Rincian Barang</h6>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Barang</th><th>Layanan</th><th class="text-end">Berat</th><th class="text-end">Subtotal</th></tr></thead><tbody>
        <?php foreach ($items as $it): ?><tr><td><?= e($it['nama_barang']) ?><?= $it['spesifikasi'] ? '<div class="small text-muted">' . e($it['spesifikasi']) . '</div>' : '' ?></td><td><?= e($it['nama_layanan']) ?></td><td class="text-end"><?= rtrim(rtrim(number_format($it['jumlah'], 2, ',', '.'), '0'), ',') . ' ' . e($it['satuan']) ?></td><td class="text-end"><?= ($belumHarga ? '-' : rupiah($it['subtotal'])) ?></td></tr><?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="3" class="text-end">Total biaya</td><td class="text-end fw-bold"><?= ($belumHarga ? 'Menunggu penetapan' : rupiah($p['total_biaya'])) ?></td></tr>
          <tr><td colspan="3" class="text-end">Sudah dibayar</td><td class="text-end text-success"><?= ($belumHarga ? '-' : rupiah($dibayar)) ?></td></tr>
          <tr><td colspan="3" class="text-end">Sisa tagihan</td><td class="text-end fw-bold <?= $sisa > 0 ? 'text-danger' : '' ?>"><?= ($belumHarga ? '-' : rupiah($sisa)) ?></td></tr>
        </tfoot></table></div>
      <div class="small text-muted mt-2">Estimasi selesai: <?= tgl($p['tgl_estimasi']) ?> &middot; Selesai: <?= tgl($p['tgl_selesai']) ?> &middot; Diambil: <?= tgl($p['tgl_diambil']) ?></div>
    </div></div>
  </div>
  <div class="col-lg-4"><div class="card"><div class="card-body"><h6 class="mb-3">Perkembangan Pengerjaan</h6>
    <ul class="timeline"><?php foreach ($log as $s): ?><li><div><?= badge_status($s['status']) ?></div><div class="small text-muted"><?= tgl_waktu($s['waktu']) ?></div><?php if ($s['catatan']): ?><div class="small"><?= e($s['catatan']) ?></div><?php endif; ?></li><?php endforeach; ?></ul>
  </div></div></div>
</div>
<?php require 'includes/footer.php'; ?>
