<?php
// Notifikasi aktivitas pesanan untuk panel pengelola (pemilik/admin)
function waktu_lalu($ts) {
    $d = time() - strtotime($ts);
    if ($d < 60) return 'baru saja';
    if ($d < 3600) return floor($d / 60) . ' menit lalu';
    if ($d < 86400) return floor($d / 3600) . ' jam lalu';
    if ($d < 172800) return 'kemarin';
    return tgl($ts);
}

// Ambil aktivitas terbaru: perubahan status/biaya (log_status) dan pembayaran
function notif_ambil($uid, $batas = 30) {
    $rows = db_all("
        SELECT * FROM (
          SELECT 'status' AS tipe, s.waktu, s.status, s.catatan, NULL AS jumlah, NULL AS metode,
                 p.id_pesanan, p.no_pesanan, pl.nama AS pelanggan, u.nama AS petugas, u.role, s.id_user
          FROM log_status s
          JOIN pesanan p ON p.id_pesanan = s.id_pesanan
          JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
          JOIN users u ON u.id_user = s.id_user
          UNION ALL
          SELECT 'bayar', b.waktu, NULL, b.keterangan, b.jumlah, b.metode,
                 p.id_pesanan, p.no_pesanan, pl.nama, u.nama, u.role, b.id_user
          FROM pembayaran b
          JOIN pesanan p ON p.id_pesanan = b.id_pesanan
          JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
          JOIN users u ON u.id_user = b.id_user
        ) x ORDER BY waktu DESC LIMIT " . (int)$batas);
    $dilihat = db_val('SELECT notif_dilihat FROM users WHERE id_user=?', 'i', [$uid]);
    $out = []; $baru = 0;
    foreach ($rows as $r) {
        if ($r['tipe'] === 'bayar') {
            $ikon = 'cash-coin'; $warna = 'success';
            $teks = $r['petugas'] . ' mencatat pembayaran ' . rupiah($r['jumlah']) . ' (' . strtoupper($r['metode']) . ')';
        } elseif (strpos((string)$r['catatan'], 'Biaya diperbarui') === 0) {
            $ikon = 'pencil-square'; $warna = 'warning';
            $teks = $r['petugas'] . ' memperbarui biaya pesanan';
        } elseif ($r['status'] === 'menunggu') {
            $ikon = 'bell-fill'; $warna = 'danger';
            $teks = 'Pesanan online baru dari ' . $r['pelanggan'];
        } else {
            $ikon = 'arrow-repeat'; $warna = 'primary';
            $teks = $r['petugas'] . ' mengubah status menjadi ' . (STATUS_URUTAN[$r['status']] ?? $r['status']);
        }
        $belumDilihat = $dilihat !== null && strtotime($r['waktu']) > strtotime($dilihat) && (int)$r['id_user'] !== (int)$uid;
        if ($belumDilihat) { $baru++; }
        $out[] = ['id' => $r['id_pesanan'], 'no' => $r['no_pesanan'], 'pelanggan' => $r['pelanggan'], 'teks' => $teks,
                  'waktu' => $r['waktu'], 'ikon' => $ikon, 'warna' => $warna, 'baru' => $belumDilihat];
    }
    return [array_slice($out, 0, 15), $baru];
}

// Notifikasi untuk pelanggan: perkembangan pesanan miliknya sendiri
function notif_pelanggan($uid, $batas = 30) {
    $pl = db_one('SELECT id_pelanggan FROM pelanggan WHERE id_user=?', 'i', [$uid]);
    if (!$pl) { return [[], 0]; }
    $idpl = (int)$pl['id_pelanggan'];
    $rows = db_all("
        SELECT * FROM (
          SELECT 'status' AS tipe, s.waktu, s.status, s.catatan, NULL AS jumlah, NULL AS metode, p.id_pesanan, p.no_pesanan, s.id_user
          FROM log_status s JOIN pesanan p ON p.id_pesanan = s.id_pesanan
          WHERE p.id_pelanggan = ?
          UNION ALL
          SELECT 'bayar', b.waktu, NULL, b.keterangan, b.jumlah, b.metode, p.id_pesanan, p.no_pesanan, b.id_user
          FROM pembayaran b JOIN pesanan p ON p.id_pesanan = b.id_pesanan
          WHERE p.id_pelanggan = ?
        ) x ORDER BY waktu DESC LIMIT " . (int)$batas, 'ii', [$idpl, $idpl]);
    $dilihat = db_val('SELECT notif_dilihat FROM users WHERE id_user=?', 'i', [$uid]);
    $kata = [
        'diterima'       => 'Barang Anda sudah kami terima',
        'pembersihan'    => 'Barang sedang dibersihkan',
        'perendaman_hcl' => 'Barang sedang direndam larutan HCl',
        'pembilasan'     => 'Barang sedang dibilas',
        'pelapisan'      => 'Barang sedang dalam proses pelapisan',
        'selesai'        => 'Pesanan selesai, siap diambil',
        'diambil'        => 'Barang sudah diambil. Terima kasih!',
    ];
    $out = []; $baru = 0;
    foreach ($rows as $r) {
        if ((int)$r['id_user'] === (int)$uid) { continue; }   // aksi pelanggan sendiri tidak perlu diberitahukan
        if ($r['tipe'] === 'bayar') {
            $ikon = 'cash-coin'; $warna = 'success';
            $teks = 'Pembayaran ' . rupiah($r['jumlah']) . ' (' . strtoupper($r['metode']) . ') telah dicatat';
        } elseif (strpos((string)$r['catatan'], 'Biaya diperbarui') === 0) {
            $ikon = 'pencil-square'; $warna = 'warning';
            $teks = 'Biaya diperbarui setelah pengecekan: ' . preg_replace('/^Biaya diperbarui setelah pengecekan:\s*/', '', $r['catatan']);
        } elseif ($r['status'] === 'selesai' || $r['status'] === 'diambil') {
            $ikon = 'check-circle-fill'; $warna = 'success'; $teks = $kata[$r['status']];
        } else {
            $ikon = 'gear-wide-connected'; $warna = 'primary'; $teks = $kata[$r['status']] ?? ('Status: ' . $r['status']);
        }
        $belumDilihat = $dilihat !== null && strtotime($r['waktu']) > strtotime($dilihat);
        if ($belumDilihat) { $baru++; }
        $out[] = ['id' => $r['id_pesanan'], 'no' => $r['no_pesanan'], 'teks' => $teks, 'waktu' => $r['waktu'],
                  'ikon' => $ikon, 'warna' => $warna, 'baru' => $belumDilihat];
    }
    return [array_slice($out, 0, 15), $baru];
}