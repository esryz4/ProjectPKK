<?php
// ===== PENGATURAN PEMBAYARAN ONLINE (EDIT DI SINI) =====
const BANK_NAMA      = 'BCA';                  // nama bank
const BANK_NOREK     = '1234567890';           // nomor rekening
const BANK_ATAS_NAMA = 'PT Cahaya Gumilang';   // nama pemilik rekening
function metode_label($m) {
    return ['transfer' => 'Transfer Bank', 'tunai' => 'Tunai (datang ke lokasi)', 'qris' => 'QRIS (lama)'][$m] ?? '-';
}
// Petunjuk pembayaran sesuai metode yang dipilih pelanggan
function blok_pembayaran($metode, $jumlah = null) {
    $nominal = $jumlah !== null ? '<div class="mb-2">Jumlah yang dibayar: <b>' . rupiah($jumlah) . '</b></div>' : '';
    $catatan = '<div class="small text-muted mt-2">Pembayaran akan dicatat oleh pengelola setelah kami konfirmasi, lalu statusnya berubah otomatis di sini.</div>';
    if ($metode === 'tunai') {
        return $nominal . '<div class="alert alert-info mb-0"><div class="fw-semibold mb-1"><i class="bi bi-geo-alt me-1"></i>Pembayaran tunai dilakukan di lokasi kami</div>'
            . '<div>Mohon datang langsung ke <b>' . e(ALAMAT_LENGKAP) . '</b> untuk membayar dan mengambil barang.</div>'
            . '<div class="small mt-1">Jam buka: ' . e(JAM_BUKA) . '</div></div>' . $catatan;
    }
    if ($metode === 'transfer') {
        return $nominal . '<div class="border rounded p-3 bg-light">'
            . '<div class="small text-muted">Bank</div><div class="fw-semibold mb-2">' . e(BANK_NAMA) . '</div>'
            . '<div class="small text-muted">Nomor rekening</div><div class="fs-5 fw-bold mb-2">' . e(BANK_NOREK)
            . ' <button type="button" class="btn btn-sm btn-outline-secondary ms-2 align-middle" onclick="navigator.clipboard.writeText(' . e(json_encode(BANK_NOREK)) . ');this.textContent=\'Tersalin\'">Salin</button></div>'
            . '<div class="small text-muted">Atas nama</div><div class="fw-semibold">' . e(BANK_ATAS_NAMA) . '</div></div>' . $catatan;
    }
    return '';
}
