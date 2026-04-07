from __future__ import annotations

import sqlite3
from dataclasses import dataclass
from decimal import Decimal, ROUND_HALF_UP
from pathlib import Path
from typing import Iterable


ROOT = Path(__file__).resolve().parent.parent
SQLITE_PATH = ROOT / "database" / "database.sqlite"
OUTPUT_PATH = ROOT / "database" / "sql" / "mysql_minimum_from_sqlite.sql"


DDL = """CREATE DATABASE IF NOT EXISTS spareprod_db;
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
"""


@dataclass
class SqlBuilder:
    statements: list[str]

    def add(self, sql: str) -> None:
        self.statements.append(sql.rstrip() + "\n")

    def extend(self, sql_lines: Iterable[str]) -> None:
        for sql in sql_lines:
            self.add(sql)

    def render(self) -> str:
        return "\n".join(self.statements).rstrip() + "\n"


def sql_quote(value: object | None) -> str:
    if value is None:
        return "NULL"

    text = str(value).replace("\\", "\\\\").replace("'", "''")
    return f"'{text}'"


def minor_units(value: object | None) -> int:
    amount = Decimal(str(value if value is not None else 0))
    return int((amount * Decimal("100")).quantize(Decimal("1"), rounding=ROUND_HALF_UP))


def map_user_role(user_row: sqlite3.Row) -> str:
    if str(user_row["role"] or "").lower() == "admin" or int(user_row["is_staff"] or 0) == 1:
        return "admin"

    return "user"


def map_order_status(status: str | None) -> str:
    normalized = (status or "").strip().lower()

    return {
        "created": "pending",
        "pending": "pending",
        "paid": "dibayar",
        "dibayar": "dibayar",
        "shipped": "dikirim",
        "dikirim": "dikirim",
        "completed": "selesai",
        "complete": "selesai",
        "selesai": "selesai",
        "cancelled": "batal",
        "canceled": "batal",
        "batal": "batal",
        "refunded": "batal",
    }.get(normalized, "pending")


def pick_order_user_id(conn: sqlite3.Connection, order_row: sqlite3.Row) -> str:
    if order_row["user_id"] is not None:
        return str(order_row["user_id"])

    if order_row["user_checkout_id"] is not None:
        row = conn.execute(
            "SELECT user_id FROM user_checkouts WHERE id = ?",
            (order_row["user_checkout_id"],),
        ).fetchone()
        if row and row["user_id"] is not None:
            return str(row["user_id"])

    raise ValueError(f"Order {order_row['id']} tidak punya user_id yang bisa dipetakan.")


def order_item_rows(conn: sqlite3.Connection, order_row: sqlite3.Row) -> list[sqlite3.Row]:
    rows = conn.execute(
        """
        SELECT oi.quantity, oi.line_item_total, oi.unit_price, oi.variation_id, p.kode_produk, v.price, v.sale_price
        FROM order_items oi
        LEFT JOIN variations v ON v.id = oi.variation_id
        LEFT JOIN products p ON p.id = v.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.id
        """,
        (order_row["id"],),
    ).fetchall()

    if rows:
        return rows

    return conn.execute(
        """
        SELECT ci.quantity, ci.line_item_total, NULL AS unit_price, ci.variation_id, p.kode_produk, v.price, v.sale_price
        FROM cart_items ci
        LEFT JOIN variations v ON v.id = ci.variation_id
        LEFT JOIN products p ON p.id = v.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.id
        """,
        (order_row["cart_id"],),
    ).fetchall()


def derive_order_values(conn: sqlite3.Connection, order_row: sqlite3.Row) -> tuple[str, int, int]:
    items = order_item_rows(conn, order_row)
    if not items:
        raise ValueError(f"Order {order_row['id']} tidak punya item yang bisa dipetakan.")

    kode_produk = items[0]["kode_produk"]
    jumlah = 0
    total_minor = 0

    for item in items:
        quantity = int(item["quantity"] or 0)
        jumlah += quantity

        if item["line_item_total"] not in (None, 0, 0.0, "0", "0.0", "0.00"):
            total_minor += minor_units(item["line_item_total"])
            continue

        unit_price = item["sale_price"] if item["sale_price"] is not None else item["unit_price"]
        if unit_price is None:
            unit_price = item["price"]

        total_minor += minor_units(unit_price) * quantity

    shipping_minor = minor_units(order_row["shipping_total_price"])
    explicit_total_minor = minor_units(order_row["total_bayar"] if order_row["total_bayar"] is not None else order_row["order_total"])

    if explicit_total_minor > 0:
        total_minor = explicit_total_minor
    else:
        total_minor += shipping_minor

    if not kode_produk:
        raise ValueError(f"Order {order_row['id']} tidak punya kode_produk yang bisa dipetakan.")

    return str(kode_produk), jumlah, total_minor


def generate_sql() -> str:
    if not SQLITE_PATH.exists():
        raise FileNotFoundError(f"SQLite source tidak ditemukan: {SQLITE_PATH}")

    conn = sqlite3.connect(SQLITE_PATH)
    conn.row_factory = sqlite3.Row

    sql = SqlBuilder(statements=[DDL, "SET FOREIGN_KEY_CHECKS=0;\n"])

    users = conn.execute("SELECT * FROM users ORDER BY id").fetchall()
    if users:
        values = []
        for row in users:
            created_at = row["created_at"] or row["date_joined"]
            values.append(
                f"({sql_quote(row['id'])}, {sql_quote(row['username'])}, {sql_quote(row['email'])}, "
                f"{sql_quote(row['password'])}, {sql_quote(map_user_role(row))}, {sql_quote(created_at)})"
            )
        sql.add(
            "INSERT INTO users (id, username, email, password, role, created_at)\nVALUES\n    "
            + ",\n    ".join(values)
            + ";"
        )

    categories = conn.execute("SELECT * FROM categories ORDER BY id").fetchall()
    if categories:
        values = [
            f"({sql_quote(row['id'])}, {sql_quote(row['nama_kategori'] or row['title'])})"
            for row in categories
        ]
        sql.add(
            "INSERT INTO kategori (id, nama_kategori)\nVALUES\n    "
            + ",\n    ".join(values)
            + ";"
        )

    brands = conn.execute("SELECT * FROM brand ORDER BY id").fetchall()
    if brands:
        values = [
            f"({sql_quote(row['id'])}, {sql_quote(row['nama_brand'])})"
            for row in brands
        ]
        sql.add(
            "INSERT INTO brand (id, nama_brand)\nVALUES\n    "
            + ",\n    ".join(values)
            + ";"
        )

    products = conn.execute("SELECT * FROM products ORDER BY id").fetchall()
    if products:
        values = []
        for row in products:
            kategori_id = row["kategori_id"] or row["default_category_id"]
            values.append(
                f"({sql_quote(row['kode_produk'])}, {sql_quote(row['nama_produk'] or row['title'])}, "
                f"{sql_quote(row['tipe_kendaraan'] or 'Universal')}, {sql_quote(kategori_id)}, "
                f"{minor_units(row['harga'] if row['harga'] is not None else row['price'])}, {int(row['stok'] or 0)}, "
                f"{sql_quote(row['gambar'])}, {sql_quote(row['brand_id'])})"
            )
        sql.add(
            "INSERT INTO produk (kode_produk, nama_produk, tipe_kendaraan, kategori_id, harga, stok, gambar, brand_id)\nVALUES\n    "
            + ",\n    ".join(values)
            + ";"
        )

    orders = conn.execute("SELECT * FROM orders ORDER BY id").fetchall()
    if orders:
        values = []
        for row in orders:
            kode_produk, jumlah, total_bayar = derive_order_values(conn, row)
            id_pembelian = row["id_pembelian"] or row["order_id"] or f"PBL-{int(row['id']):06d}"
            user_id = pick_order_user_id(conn, row)
            tanggal = row["tanggal_transaksi"] or row["created_at"]
            values.append(
                f"({sql_quote(id_pembelian)}, {sql_quote(user_id)}, {sql_quote(kode_produk)}, {jumlah}, "
                f"{total_bayar}, {sql_quote(tanggal)}, {sql_quote(map_order_status(row['status']))})"
            )
        sql.add(
            "INSERT INTO pembelian (id_pembelian, user_id, kode_produk, jumlah, total_bayar, tanggal_transaksi, status)\nVALUES\n    "
            + ",\n    ".join(values)
            + ";"
        )

    sql.add("SET FOREIGN_KEY_CHECKS=1;")
    return sql.render()


def main() -> None:
    rendered = generate_sql()
    OUTPUT_PATH.write_text(rendered, encoding="utf-8")
    print(f"MySQL minimum export written to {OUTPUT_PATH}")


if __name__ == "__main__":
    main()
