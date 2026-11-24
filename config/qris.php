<?php
// Pengaturan QRIS dinamis.
// Isi QRIS_PAYLOAD dengan teks hasil scan (decode) gambar QRIS statis toko Anda
// (mulai dengan 000201...). Pakai aplikasi pemindai QR apa saja, salin teksnya ke sini.
// Jika dikosongkan, sistem menampilkan gambar QRIS statis biasa sebagai cadangan.
if (!defined('QRIS_PAYLOAD')) { define('QRIS_PAYLOAD', ''); }

function qris_crc16(string $s): string {
    $crc = 0xFFFF;
    for ($i = 0, $n = strlen($s); $i < $n; $i++) {
        $crc ^= ord($s[$i]) << 8;
        for ($b = 0; $b < 8; $b++) { $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF; }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

// Ubah QRIS statis menjadi dinamis dengan nominal tertentu.
function qris_dinamis(string $payload, float $nominal): ?string {
    $payload = trim($payload);
    if (strlen($payload) < 30 || substr($payload, 0, 6) !== '000201') { return null; }
    $tags = []; $i = 0; $n = strlen($payload);
    while ($i + 4 <= $n) {
        $id = substr($payload, $i, 2); $len = (int)substr($payload, $i + 2, 2);
        $tags[] = [$id, substr($payload, $i + 4, $len)]; $i += 4 + $len;
    }
    $out = ''; $nom = (string)(int)round($nominal); $sisip = false;
    foreach ($tags as [$id, $val]) {
        if ($id === '63' || $id === '54') { continue; }
        if ($id === '01') { $val = '12'; }
        if ($id === '58' && !$sisip) { $out .= '54' . str_pad((string)strlen($nom), 2, '0', STR_PAD_LEFT) . $nom; $sisip = true; }
        $out .= $id . str_pad((string)strlen($val), 2, '0', STR_PAD_LEFT) . $val;
    }
    if (!$sisip) { $out .= '54' . str_pad((string)strlen($nom), 2, '0', STR_PAD_LEFT) . $nom; }
    $out .= '6304';
    return $out . qris_crc16($out);
}

// Blok tampilan QRIS: kode QR dibuat langsung di browser.
function blok_qris(?float $nominal): string {
    if ($nominal === null || $nominal <= 0) {
        return '<div class="alert alert-secondary py-2 small mb-0">Kode QR pembayaran akan muncul di sini setelah biaya ditetapkan.</div>';
    }
    $dyn = qris_dinamis(QRIS_PAYLOAD, $nominal);
    if ($dyn === null) { return blok_pembayaran('qris', $nominal); }
    $id = 'qr' . substr(md5($dyn), 0, 8);
    return '<div class="text-center qris-box">'
        . '<div class="small text-muted mb-2">Scan dengan aplikasi e-wallet / mobile banking</div>'
        . '<div id="' . $id . '" class="qris-kode mx-auto" data-qris="' . htmlspecialchars($dyn, ENT_QUOTES) . '"></div>'
        . '<div class="mt-2 small text-muted">Nominal</div><div class="fs-4 fw-bold">Rp ' . number_format($nominal, 0, ',', '.') . '</div>'
        . '<div class="small text-muted">Setelah membayar, hubungi kami agar pembayaran dicatat.</div>'
        . '</div>'
        . '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>'
        . '<script>(function(){var el=document.getElementById("' . $id . '");if(!el||typeof qrcode==="undefined"){if(el)el.textContent="Kode QR gagal dimuat (perlu internet).";return;}'
        . 'var q=qrcode(0,"M");q.addData(el.getAttribute("data-qris"));q.make();el.innerHTML=q.createSvgTag({cellSize:4,margin:2,scalable:true});})();</script>';
}
