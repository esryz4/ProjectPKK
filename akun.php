<?php
require_once 'includes/functions.php';
login_any();
if (!is_pelanggan()) { redirect('dashboard.php'); }
$pl = db_one('SELECT * FROM pelanggan WHERE id_user=?', 'i', [$_SESSION['user']['id']]);
$rows = $pl ? db_all('SELECT p.*, (SELECT COALESCE(SUM(b.jumlah),0) FROM pembayaran b WHERE b.id_pesanan=p.id_pesanan) AS dibayar FROM pesanan p WHERE p.id_pelanggan=? ORDER BY p.id_pesanan DESC', 'i', [$pl['id_pelanggan']]) : [];
$judul = 'Pesanan Saya';
require 'includes/header_publik.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><h4 class="mb-0">Pesanan Saya</h4><div class="text-muted small">Halo, <?= e($_SESSION['user']['nama']) ?></div></div>
  <a href="pesan.php" class="btn btn-dark"><i class="bi bi-plus-lg me-1"></i>Pesan Sekarang</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
  <thead><tr><th>No. Pesanan</th><th>Tanggal</th><th class="text-end">Total</th><th>Status Pengerjaan</th><th>Pembayaran</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td class="fw-semibold"><?= e($r['no_pesanan']) ?></td><td><?= tgl($r['tgl_masuk']) ?></td><td class="text-end"><?= rupiah($r['total_biaya']) ?></td><td><?= badge_status($r['status']) ?></td><td><?= badge_bayar($r['status_bayar']) ?></td>
      <td class="text-end"><a href="pesanan_saya.php?id=<?= $r['id_pesanan'] ?>" class="btn btn-sm btn-outline-dark">Detail</a></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada pesanan. <a href="pesan.php">Buat pesanan pertama Anda</a>.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php require 'includes/footer.php'; ?>
