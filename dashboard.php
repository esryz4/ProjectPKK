<?php
require_once 'includes/functions.php';
login_required();
$judul = 'Dashboard';

$aktif      = (int)db_val("SELECT COUNT(*) FROM pesanan WHERE status NOT IN ('selesai','diambil')");
$siap       = (int)db_val("SELECT COUNT(*) FROM pesanan WHERE status='selesai'");
$belumLunas = (int)db_val("SELECT COUNT(*) FROM pesanan WHERE status_bayar<>'lunas'");
$bulanAwal  = date('Y-m-01');
$bulanAkhir = date('Y-m-t');
$pendapatan = (float)db_val('SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE tgl_bayar BETWEEN ? AND ?', 'ss', [$bulanAwal, $bulanAkhir]);
$pengeluaran= (float)db_val('SELECT COALESCE(SUM(jumlah),0) FROM pengeluaran WHERE tanggal BETWEEN ? AND ?', 'ss', [$bulanAwal, $bulanAkhir]);

$perStatus = [];
foreach (db_all('SELECT status, COUNT(*) AS n FROM pesanan GROUP BY status') as $r) { $perStatus[$r['status']] = (int)$r['n']; }

$terbaru = db_all("SELECT p.*, pl.nama AS pelanggan FROM pesanan p JOIN pelanggan pl ON pl.id_pelanggan=p.id_pelanggan ORDER BY p.id_pesanan DESC LIMIT 8");
require 'includes/header.php';
?>
<h4 class="mb-3">Dashboard</h4>
<?php $menunggu = (int)db_val("SELECT COUNT(*) FROM pesanan WHERE status='menunggu'"); if ($menunggu > 0): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center"><span><i class="bi bi-bell me-2"></i><b><?= $menunggu ?></b> pesanan online menunggu konfirmasi.</span><a href="pesanan.php?status=menunggu" class="btn btn-sm btn-dark">Lihat</a></div>
<?php endif; ?>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
    <div class="icon bg-warning-subtle text-warning"><i class="bi bi-gear-wide-connected"></i></div>
    <div><div class="text-muted small">Pesanan Diproses</div><div class="fs-4 fw-bold"><?= $aktif ?></div></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
    <div class="icon bg-success-subtle text-success"><i class="bi bi-box-seam"></i></div>
    <div><div class="text-muted small">Siap Diambil</div><div class="fs-4 fw-bold"><?= $siap ?></div></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
    <div class="icon bg-danger-subtle text-danger"><i class="bi bi-cash-coin"></i></div>
    <div><div class="text-muted small">Belum Lunas</div><div class="fs-4 fw-bold"><?= $belumLunas ?></div></div></div></div></div>
  <?php if (is_owner()): ?>
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
    <div class="icon bg-primary-subtle text-primary"><i class="bi bi-graph-up-arrow"></i></div>
    <div><div class="text-muted small">Pendapatan Bulan Ini</div><div class="fs-5 fw-bold"><?= rupiah($pendapatan) ?></div></div></div></div></div>
  <?php else: ?>
  <div class="col-6 col-lg-3"><a href="pesanan_tambah.php" class="btn btn-dark w-100 h-100 d-flex align-items-center justify-content-center"><i class="bi bi-plus-circle me-2"></i>Pesanan Baru</a></div>
  <?php endif; ?>
</div>

<?php if (is_owner()): ?>
<div class="row g-3 mb-4">
  <div class="col-md-6"><div class="card"><div class="card-body"><div class="text-muted small">Pengeluaran Bulan Ini</div><div class="fs-4 fw-bold text-danger"><?= rupiah($pengeluaran) ?></div></div></div></div>
  <div class="col-md-6"><div class="card"><div class="card-body"><div class="text-muted small">Laba Bersih Bulan Ini</div><div class="fs-4 fw-bold <?= $pendapatan - $pengeluaran >= 0 ? 'text-success' : 'text-danger' ?>"><?= rupiah($pendapatan - $pengeluaran) ?></div></div></div></div>
</div>
<?php endif; ?>

<div class="card mb-4"><div class="card-body">
  <h6 class="mb-3">Posisi Pesanan per Tahapan</h6>
  <div class="d-flex flex-wrap gap-2">
    <?php foreach (STATUS_URUTAN as $k => $label): ?>
      <a href="pesanan.php?status=<?= $k ?>" class="text-decoration-none"><span class="badge text-bg-<?= STATUS_WARNA[$k] ?> fs-6 fw-normal"><?= $label ?> <span class="badge bg-light text-dark"><?= $perStatus[$k] ?? 0 ?></span></span></a>
    <?php endforeach; ?>
  </div>
</div></div>

<div class="card"><div class="card-body">
  <div class="d-flex justify-content-between mb-2"><h6 class="mb-0">Pesanan Terbaru</h6><a href="pesanan.php" class="small">Lihat semua</a></div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>No. Pesanan</th><th>Pelanggan</th><th>Tgl Masuk</th><th>Total</th><th>Status</th><th>Pembayaran</th></tr></thead>
    <tbody>
    <?php foreach ($terbaru as $r): ?>
      <tr><td><a href="pesanan_detail.php?id=<?= $r['id_pesanan'] ?>"><?= e($r['no_pesanan']) ?></a></td>
        <td><?= e($r['pelanggan']) ?></td><td><?= tgl($r['tgl_masuk']) ?></td><td><?= rupiah($r['total_biaya']) ?></td>
        <td><?= badge_status($r['status']) ?></td><td><?= badge_bayar($r['status_bayar']) ?></td></tr>
    <?php endforeach; if (!$terbaru): ?><tr><td colspan="6" class="text-center text-muted py-3">Belum ada pesanan.</td></tr><?php endif; ?>
    </tbody></table></div>
</div></div>
<?php require 'includes/footer.php'; ?>
