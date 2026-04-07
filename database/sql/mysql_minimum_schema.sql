CREATE DATABASE IF NOT EXISTS spareprod_db;
USE spareprod_db;

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
