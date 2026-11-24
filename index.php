<?php
require_once 'includes/functions.php';
$judul = 'Beranda';
$layanan = db_all('SELECT * FROM layanan WHERE aktif=1 ORDER BY nama_layanan');
require 'includes/header_publik.php';
?>
<section class="hero rounded-4 p-4 p-md-5 mb-5">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <span class="badge badge-bw mb-3">Sejak 1990-an &middot; <?= ALAMAT_USAHA ?></span>
      <h1 class="hero-judul mb-3"><span class="kecil">Jasa Pelapisan Logam</span>Electroplating <span class="logam"><?= NAMA_USAHA ?></span></h1>
      <p class="lead mb-4">Percantik dan perkuat logam Anda: lebih tahan karat, lebih awet, tampil mengkilap. Pesan online, pantau tahapan pengerjaannya kapan saja.</p>
      <a href="pesan.php" class="btn btn-aksi btn-lg me-2 mb-2"><i class="bi bi-bag-plus me-1"></i>Pesan Sekarang</a>
      <a href="#layanan" class="btn btn-outline-dark btn-lg me-2 mb-2">Lihat Layanan</a>
      <a href="#kontak" class="btn btn-outline-dark btn-lg mb-2"><i class="bi bi-geo-alt me-1"></i>Lokasi &amp; Kontak</a>
    </div>
  </div>
</section>

<section id="layanan" class="mb-5">
  <h3 class="fw-bold mb-1">Layanan Kami</h3>
  <p class="text-muted">Biaya ditentukan setelah barang dicek langsung oleh pemilik/pegawai, karena menyesuaikan jenis logam dan berat barang. Semua layanan dihitung <b>per kilogram (kg)</b>. Silakan pesan atau tanyakan lewat WhatsApp.</p>
  <div class="row g-3">
    <?php foreach ($layanan as $l): ?>
    <div class="col-sm-6 col-lg-4"><div class="card h-100 fx-card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <h5 class="mb-1"><?= e($l['nama_layanan']) ?></h5><i class="bi bi-layers fs-4 text-secondary"></i>
      </div>
      <p class="text-muted small mb-3"><?= e($l['keterangan']) ?></p>
      <a href="pesan.php" class="btn btn-sm btn-outline-dark"><i class="bi bi-bag-plus me-1"></i>Pesan layanan ini</a>
    </div></div></div>
    <?php endforeach; if (!$layanan): ?><div class="col-12 text-muted">Daftar layanan belum tersedia.</div><?php endif; ?>
  </div>
</section>

<section class="mb-5">
  <h3 class="fw-bold mb-3">Tahapan Pengerjaan</h3>
  <div class="row g-3 text-center">
    <?php
    $tahap = [['droplet', 'Pembersihan', 'Logam dibersihkan dengan cairan pembersih.'], ['beaker', 'Perendaman HCl', 'Direndam larutan HCl agar permukaan aktif.'], ['water', 'Pembilasan', 'Dibilas hingga bersih.'], ['lightning-charge', 'Pelapisan', 'Proses electroplating sesuai kebutuhan.'], ['box-seam', 'Selesai', 'Siap diambil setelah pembayaran.']];
    foreach ($tahap as $i => $t): ?>
    <div class="col-6 col-md"><div class="card h-100 fx-card"><div class="card-body">
      <span class="tahap-ico tahap-<?= $i + 1 ?>"><i class="bi bi-<?= $t[0] ?>"></i></span>
      <div class="fw-semibold mt-2"><?= ($i + 1) . '. ' . $t[1] ?></div><div class="small text-muted"><?= $t[2] ?></div>
    </div></div></div>
    <?php endforeach; ?>
  </div>
</section>

<section id="kontak" class="mb-5">
  <h3 class="fw-bold mb-3">Hubungi &amp; Kunjungi Kami</h3>
  <div class="row g-3">
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
      <div class="d-flex gap-3 mb-3"><i class="bi bi-geo-alt-fill fs-4 text-danger"></i><div><div class="fw-semibold">Alamat</div><div class="text-muted"><?= e(ALAMAT_LENGKAP) ?></div></div></div>
      <div class="d-flex gap-3 mb-3"><i class="bi bi-clock-fill fs-4 text-secondary"></i><div><div class="fw-semibold">Jam Buka</div><div class="text-muted"><?= e(JAM_BUKA) ?></div></div></div>
      <div class="d-flex gap-3 mb-4"><i class="bi bi-whatsapp fs-4 text-success"></i><div><div class="fw-semibold">WhatsApp</div><div class="text-muted">+<?= e(WA_NOMOR) ?></div></div></div>
      <div class="d-grid gap-2">
        <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" class="btn btn-success"><i class="bi bi-whatsapp me-1"></i>Chat via WhatsApp</a>
        <a href="<?= e(maps_link()) ?>" target="_blank" rel="noopener" class="btn btn-outline-dark"><i class="bi bi-map me-1"></i>Buka di Google Maps</a>
      </div>
    </div></div></div>
    <div class="col-lg-7"><div class="card h-100 overflow-hidden" id="petaBox"><iframe title="Lokasi PT Cahaya Gumilang" src="<?= e(maps_embed()) ?>" style="border:0;width:100%;min-height:340px;height:100%" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div></div>
  </div>
</section>

<section class="mb-4">
  <h3 class="fw-bold mb-3">Cara Memesan</h3>
  <div class="row g-3">
    <div class="col-md-3"><div class="card h-100 fx-card"><div class="card-body"><span class="badge badge-bw mb-2">1</span><div class="fw-semibold">Daftar / Masuk</div><div class="small text-muted">Buat akun pelanggan, gratis.</div></div></div></div>
    <div class="col-md-3"><div class="card h-100 fx-card"><div class="card-body"><span class="badge badge-bw mb-2">2</span><div class="fw-semibold">Isi Pesanan</div><div class="small text-muted">Pilih layanan, isi barang dan jumlahnya.</div></div></div></div>
    <div class="col-md-3"><div class="card h-100 fx-card"><div class="card-body"><span class="badge badge-bw mb-2">3</span><div class="fw-semibold">Serahkan Barang</div><div class="small text-muted">Bawa logam ke <?= ALAMAT_USAHA ?>, kami cek dan konfirmasi biaya.</div></div></div></div>
    <div class="col-md-3"><div class="card h-100 fx-card"><div class="card-body"><span class="badge badge-bw mb-2">4</span><div class="fw-semibold">Pantau &amp; Ambil</div><div class="small text-muted">Lihat status di &ldquo;Pesanan Saya&rdquo;, bayar lalu ambil barang.</div></div></div></div>
  </div>
</section>
<a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" class="wa-float no-print" title="Chat WhatsApp" aria-label="Chat WhatsApp"><i class="bi bi-whatsapp"></i></a>
<?php require 'includes/footer.php'; ?>
