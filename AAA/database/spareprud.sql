CREATE DATABASE IF NOT EXISTS spareprud
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE spareprud;

DROP TABLE IF EXISTS pembelian;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS brand;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id VARCHAR(20) NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_username (username),
    UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE kategori (
    id VARCHAR(20) NOT NULL,
    nama_kategori VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_kategori_nama (nama_kategori)
) ENGINE=InnoDB;

CREATE TABLE brand (
    id VARCHAR(20) NOT NULL,
    nama_brand VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_brand_nama (nama_brand)
) ENGINE=InnoDB;

CREATE TABLE produk (
    kode_produk VARCHAR(30) NOT NULL,
    nama_produk VARCHAR(150) NOT NULL,
    tipe_kendaraan VARCHAR(100) NOT NULL,
    kategori_id VARCHAR(20) NOT NULL,
    harga BIGINT NOT NULL,
    stok INT NOT NULL,
    gambar VARCHAR(255) DEFAULT NULL,
    brand_id VARCHAR(20) NOT NULL,
    PRIMARY KEY (kode_produk),
    KEY idx_produk_kategori (kategori_id),
    KEY idx_produk_brand (brand_id),
    CONSTRAINT fk_produk_kategori
        FOREIGN KEY (kategori_id) REFERENCES kategori (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_produk_brand
        FOREIGN KEY (brand_id) REFERENCES brand (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE pembelian (
    id_pembelian VARCHAR(20) NOT NULL,
    user_id VARCHAR(20) NOT NULL,
    kode_produk VARCHAR(30) NOT NULL,
    jumlah INT NOT NULL,
    total_bayar BIGINT NOT NULL,
    tanggal_transaksi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('menunggu', 'dikonfirmasi', 'ditolak') NOT NULL DEFAULT 'menunggu',
    PRIMARY KEY (id_pembelian),
    KEY idx_pembelian_user (user_id),
    KEY idx_pembelian_produk (kode_produk),
    CONSTRAINT fk_pembelian_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_pembelian_produk
        FOREIGN KEY (kode_produk) REFERENCES produk (kode_produk)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO users (id, username, email, password, role, created_at) VALUES
('USR001', 'admin', 'admin@spareprud.test', '$2y$12$IqUjmK3xMSJmoj52bePGKej5Me70CnH9Ba9.Vx.AfbTRm9kFVCXla', 'admin', '2026-04-27 08:00:00'),
('USR002', 'budi', 'budi@spareprud.test', '$2y$12$.3udqMGwyJBpLYkn7UfirOAmN6CbJB2Xz0YlihdPgh3DP0FzUsP5y', 'customer', '2026-04-27 08:05:00'),
('USR003', 'sari', 'sari@spareprud.test', '$2y$12$.3udqMGwyJBpLYkn7UfirOAmN6CbJB2Xz0YlihdPgh3DP0FzUsP5y', 'customer', '2026-04-27 08:10:00');

INSERT INTO kategori (id, nama_kategori) VALUES
('KAT001', 'Oli'),
('KAT002', 'Rem'),
('KAT003', 'Kelistrikan'),
('KAT004', 'Ban');

INSERT INTO brand (id, nama_brand) VALUES
('BRD001', 'AHM'),
('BRD002', 'Yamaha Genuine'),
('BRD003', 'Federal Oil'),
('BRD004', 'IRC');

INSERT INTO produk (kode_produk, nama_produk, tipe_kendaraan, kategori_id, harga, stok, gambar, brand_id) VALUES
('PRD001', 'Oli Mesin MPX 2 10W-30', 'Honda Beat', 'KAT001', 65000, 25, 'oli-mpx-2.jpg', 'BRD001'),
('PRD002', 'Kampas Rem Depan', 'Yamaha Mio', 'KAT002', 45000, 18, 'kampas-rem-mio.jpg', 'BRD002'),
('PRD003', 'Aki Kering Motor 12V', 'Honda Vario', 'KAT003', 235000, 10, 'aki-kering-12v.jpg', 'BRD001'),
('PRD004', 'Ban Luar 80/90-14', 'Honda Scoopy', 'KAT004', 185000, 12, 'ban-irc-80-90-14.jpg', 'BRD004'),
('PRD005', 'Oli Yamalube Sport', 'Yamaha NMAX', 'KAT001', 72000, 20, 'oli-yamalube-sport.jpg', 'BRD002'),
('PRD006', 'Lampu Depan LED', 'Universal', 'KAT003', 95000, 14, 'lampu-led.jpg', 'BRD001');

INSERT INTO pembelian (id_pembelian, user_id, kode_produk, jumlah, total_bayar, tanggal_transaksi, status) VALUES
('PBL001', 'USR002', 'PRD001', 2, 130000, '2026-04-27 09:00:00', 'menunggu'),
('PBL002', 'USR003', 'PRD004', 1, 185000, '2026-04-27 09:30:00', 'dikonfirmasi'),
('PBL003', 'USR002', 'PRD002', 1, 45000, '2026-04-27 10:00:00', 'menunggu');
