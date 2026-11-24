<?php
require_once 'includes/functions.php';
owner_only();
$dari = $_GET['dari'] ?? date('Y-m-01'); $sampai = $_GET['sampai'] ?? date('Y-m-t');

$pendapatan = db_all('SELECT b.*, p.no_pesanan, pl.nama AS pelanggan FROM pembayaran b JOIN pesanan p ON p.id_pesanan=b.id_pesanan JOIN pelanggan pl ON pl.id_pelanggan=p.id_pelanggan WHERE b.tgl_bayar BETWEEN ? AND ? ORDER BY b.tgl_bayar, b.id_pembayaran', 'ss', [$dari, $sampai]);
$pengeluaran = db_all('SELECT * FROM pengeluaran WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal, id_pengeluaran', 'ss', [$dari, $sampai]);
$pesanan = db_all('SELECT p.*, pl.nama AS pelanggan, (SELECT COALESCE(SUM(b.jumlah),0) FROM pembayaran b WHERE b.id_pesanan=p.id_pesanan) AS dibayar
                   FROM pesanan p JOIN pelanggan pl ON pl.id_pelanggan=p.id_pelanggan WHERE p.tgl_masuk BETWEEN ? AND ? ORDER BY p.tgl_masuk, p.id_pesanan', 'ss', [$dari, $sampai]);
$perLayanan = db_all('SELECT l.nama_layanan, l.satuan, SUM(d.jumlah) AS qty, SUM(d.subtotal) AS omzet FROM detail_pesanan d JOIN pesanan p ON p.id_pesanan=d.id_pesanan JOIN layanan l ON l.id_layanan=d.id_layanan
                      WHERE p.tgl_masuk BETWEEN ? AND ? GROUP BY l.id_layanan ORDER BY omzet DESC', 'ss', [$dari, $sampai]);
$perKategori = db_all('SELECT kategori, SUM(jumlah) AS total FROM pengeluaran WHERE tanggal BETWEEN ? AND ? GROUP BY kategori ORDER BY total DESC', 'ss', [$dari, $sampai]);

$totPendapatan = array_sum(array_column($pendapatan, 'jumlah'));
$totPengeluaran = array_sum(array_column($pengeluaran, 'jumlah'));
$laba = $totPendapatan - $totPengeluaran;
$totOmzet = array_sum(array_column($pesanan, 'total_biaya'));
$piutang = $totOmzet - array_sum(array_column($pesanan, 'dibayar'));

$judul = 'Laporan';
require 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <h4 class="mb-0">Laporan Operasional &amp; Keuangan</h4>
  <div class="d-flex gap-2">
    <form class="d-flex gap-2"><input type="date" name="dari" value="<?= e($dari) ?>" class="form-control"><input type="date" name="sampai" value="<?= e($sampai) ?>" class="form-control"><button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button></form>
    <button class="btn btn-dark text-nowrap" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>
  </div>
</div>

<div class="d-none d-print-block text-center mb-3"><h4 class="mb-0"><?= NAMA_USAHA ?></h4><div>Laporan periode <?= tgl($dari) ?> s.d. <?= tgl($sampai) ?></div></div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Pendapatan (kas masuk)</div><div class="fs-5 fw-bold text-success"><?= rupiah($totPendapatan) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Pengeluaran</div><div class="fs-5 fw-bold text-danger"><?= rupiah($totPengeluaran) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Laba Bersih</div><div class="fs-5 fw-bold <?= $laba >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($laba) ?></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Pesanan Masuk / Piutang</div><div class="fs-6 fw-bold"><?= count($pesanan) ?> pesanan &middot; <?= rupiah($piutang) ?></div></div></div></div>
</div>

<div class="card mb-3"><div class="card-body"><h6>Pendapatan (Pembayaran Diterima)</h6>
<div class="table-responsive"><table class="table table-sm mb-0">
  <thead><tr><th>Tanggal</th><th>No. Pesanan</th><th>Pelanggan</th><th>Metode</th><th>Ket.</th><th class="text-end">Jumlah</th></tr></thead>
  <tbody><?php foreach ($pendapatan as $r): ?><tr><td><?= tgl($r['tgl_bayar']) ?></td><td><?= e($r['no_pesanan']) ?></td><td><?= e($r['pelanggan']) ?></td><td><?= e(strtoupper($r['metode'])) ?></td><td><?= e($r['keterangan']) ?></td><td class="text-end"><?= rupiah($r['jumlah']) ?></td></tr>
  <?php endforeach; if (!$pendapatan): ?><tr><td colspan="6" class="text-center text-muted">Tidak ada data.</td></tr><?php endif; ?></tbody>
  <tfoot><tr class="fw-bold"><td colspan="5" class="text-end">Total</td><td class="text-end"><?= rupiah($totPendapatan) ?></td></tr></tfoot>
</table></div></div></div>

<div class="card mb-3"><div class="card-body"><h6>Pengeluaran</h6>
<div class="table-responsive"><table class="table table-sm mb-0">
  <thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th class="text-end">Jumlah</th></tr></thead>
  <tbody><?php foreach ($pengeluaran as $r): ?><tr><td><?= tgl($r['tanggal']) ?></td><td><?= e(KATEGORI_PENGELUARAN[$r['kategori']]) ?></td><td><?= e($r['keterangan']) ?></td><td class="text-end"><?= rupiah($r['jumlah']) ?></td></tr>
  <?php endforeach; if (!$pengeluaran): ?><tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr><?php endif; ?></tbody>
  <tfoot><tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= rupiah($totPengeluaran) ?></td></tr></tfoot>
</table></div></div></div>

<div class="card mb-3"><div class="card-body"><h6>Transaksi Pesanan</h6>
<div class="table-responsive"><table class="table table-sm mb-0">
  <thead><tr><th>Tgl Masuk</th><th>No. Pesanan</th><th>Pelanggan</th><th>Status</th><th>Pembayaran</th><th class="text-end">Total</th><th class="text-end">Dibayar</th></tr></thead>
  <tbody><?php foreach ($pesanan as $r): ?><tr><td><?= tgl($r['tgl_masuk']) ?></td><td><?= e($r['no_pesanan']) ?></td><td><?= e($r['pelanggan']) ?></td><td><?= badge_status($r['status']) ?></td><td><?= badge_bayar($r['status_bayar']) ?></td><td class="text-end"><?= rupiah($r['total_biaya']) ?></td><td class="text-end"><?= rupiah($r['dibayar']) ?></td></tr>
  <?php endforeach; if (!$pesanan): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr><?php endif; ?></tbody>
  <tfoot><tr class="fw-bold"><td colspan="5" class="text-end">Total</td><td class="text-end"><?= rupiah($totOmzet) ?></td><td class="text-end"><?= rupiah($totOmzet - $piutang) ?></td></tr></tfoot>
</table></div></div></div>

<div class="row g-3">
  <div class="col-md-6"><div class="card h-100"><div class="card-body"><h6>Omzet per Layanan</h6>
    <table class="table table-sm mb-0"><thead><tr><th>Layanan</th><th class="text-end">Jumlah</th><th class="text-end">Omzet</th></tr></thead><tbody>
    <?php foreach ($perLayanan as $r): ?><tr><td><?= e($r['nama_layanan']) ?></td><td class="text-end"><?= rtrim(rtrim(number_format($r['qty'], 2, ',', '.'), '0'), ',') . ' ' . e($r['satuan']) ?></td><td class="text-end"><?= rupiah($r['omzet']) ?></td></tr>
    <?php endforeach; if (!$perLayanan): ?><tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr><?php endif; ?></tbody></table></div></div></div>
  <div class="col-md-6"><div class="card h-100"><div class="card-body"><h6>Pengeluaran per Kategori</h6>
    <table class="table table-sm mb-0"><thead><tr><th>Kategori</th><th class="text-end">Total</th></tr></thead><tbody>
    <?php foreach ($perKategori as $r): ?><tr><td><?= e(KATEGORI_PENGELUARAN[$r['kategori']]) ?></td><td class="text-end"><?= rupiah($r['total']) ?></td></tr>
    <?php endforeach; if (!$perKategori): ?><tr><td colspan="2" class="text-center text-muted">Tidak ada data.</td></tr><?php endif; ?></tbody></table></div></div></div>
</div>
<?php require 'includes/footer.php'; ?>
