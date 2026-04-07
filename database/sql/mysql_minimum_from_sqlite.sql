CREATE DATABASE IF NOT EXISTS spareprod_db;
USE spareprod_db;

DROP TABLE IF EXISTS pembelian;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS brand;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id VARCHAR(50) PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kategori (
    id VARCHAR(50) PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL
);

CREATE TABLE brand (
    id VARCHAR(50) PRIMARY KEY,
    nama_brand VARCHAR(100) NOT NULL
);

CREATE TABLE produk (
    kode_produk VARCHAR(50) PRIMARY KEY,
    nama_produk VARCHAR(150) NOT NULL,
    tipe_kendaraan VARCHAR(100) NOT NULL,
    kategori_id VARCHAR(50) NOT NULL,
    harga BIGINT NOT NULL COMMENT 'Nilai hasil migrasi disimpan dalam satuan sen dari harga SQLite.',
    stok INT NOT NULL,
    gambar VARCHAR(255),
    brand_id VARCHAR(50) NOT NULL,
    CONSTRAINT fk_produk_kategori
        FOREIGN KEY (kategori_id) REFERENCES kategori(id),
    CONSTRAINT fk_produk_brand
        FOREIGN KEY (brand_id) REFERENCES brand(id)
);

CREATE TABLE pembelian (
    id_pembelian VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    kode_produk VARCHAR(50) NOT NULL,
    jumlah INT NOT NULL,
    total_bayar BIGINT NOT NULL COMMENT 'Nilai hasil migrasi disimpan dalam satuan sen dari total SQLite.',
    tanggal_transaksi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'dibayar', 'dikirim', 'selesai', 'batal') NOT NULL,
    CONSTRAINT fk_pembelian_user
        FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_pembelian_produk
        FOREIGN KEY (kode_produk) REFERENCES produk(kode_produk)
);

SET FOREIGN_KEY_CHECKS=0;

INSERT INTO users (id, username, email, password, role, created_at)
VALUES
    ('1', 'admin', 'admin@sparesoko.test', '$2y$12$b1LRQJvE4rNd9.aD.SmTS.HgfIwFvl7mnwpYMe339ZL0Nyl3LFk.O', 'admin', '2026-04-07 06:19:14'),
    ('2', 'demo', 'demo@sparesoko.test', '$2y$12$5bTTTs/O59ZyZuDBojmkjO48BeRPtVoflXHKjVthhPxKtuJyRdwcC', 'user', '2026-04-07 06:19:15');

INSERT INTO kategori (id, nama_kategori)
VALUES
    ('1', 'Brakes'),
    ('2', 'Engine'),
    ('3', 'Electrical');

INSERT INTO brand (id, nama_brand)
VALUES
    ('advics', 'Advics'),
    ('bosch', 'Bosch'),
    ('denso', 'Denso'),
    ('ngk', 'NGK'),
    ('toyota_genuine_parts', 'Toyota Genuine Parts');

INSERT INTO produk (kode_produk, nama_produk, tipe_kendaraan, kategori_id, harga, stok, gambar, brand_id)
VALUES
    ('BPS-CER-001', 'Ceramic Brake Pad Set', 'Toyota Avanza', '1', 4500, 40, 'theme/img/products/ceramic-brake-pad-set.jpg', 'bosch'),
    ('ROT-VNT-014', 'Ventilated Brake Disc Rotor', 'Toyota Avanza', '1', 7250, 10, 'theme/img/products/ventilated-brake-disc-rotor.jpg', 'advics'),
    ('OFL-SPN-018', 'Spin-On Oil Filter', 'Toyota Avanza', '2', 1290, 58, 'theme/img/products/spin-on-oil-filter.jpg', 'toyota_genuine_parts'),
    ('AIR-PNL-021', 'Panel Air Filter', 'Toyota Rush', '2', 1825, 30, 'theme/img/products/panel-air-filter.jpg', 'denso'),
    ('SPK-IRD-006', 'Iridium Spark Plug', 'Honda Brio', '3', 890, 80, 'theme/img/products/iridium-spark-plug.jpg', 'ngk'),
    ('BTC-12V-009', 'Battery Terminal Clamp', 'Toyota Avanza', '3', 1450, 30, 'theme/img/products/battery-terminal-clamp.jpg', 'bosch');

INSERT INTO pembelian (id_pembelian, user_id, kode_produk, jumlah, total_bayar, tanggal_transaksi, status)
VALUES
    ('SSK-1001', '2', 'BPS-CER-001', 3, 13079, '2026-04-07 06:19:15', 'pending');

SET FOREIGN_KEY_CHECKS=1;
