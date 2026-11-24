-- Jalankan sekali di phpMyAdmin (database db_cahaya_gumilang) agar semua layanan dihitung per kilogram.
-- Harga acuan di bawah hanya contoh. Silakan sesuaikan lewat menu Layanan.
UPDATE layanan SET satuan = 'kg',
  harga_satuan = CASE id_layanan
    WHEN 1 THEN 40000    -- Pelapisan Nikel
    WHEN 2 THEN 65000    -- Pelapisan Krom
    WHEN 3 THEN 15000    -- Pelapisan Zinc
    WHEN 4 THEN 35000    -- Pelapisan Tembaga
    WHEN 5 THEN 350000   -- Pelapisan Emas
    ELSE harga_satuan END;
ALTER TABLE layanan ALTER COLUMN satuan SET DEFAULT 'kg';
