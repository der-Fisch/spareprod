/*
  Jalankan setelah copy data SQLite -> MySQL selesai.
  Fokus file ini: tabel inti bisnis, bukan tabel runtime seperti sessions/cache/jobs.
*/

SELECT 'categories' AS table_name, COUNT(*) AS row_count FROM categories
UNION ALL
SELECT 'brand', COUNT(*) FROM brand
UNION ALL
SELECT 'products', COUNT(*) FROM products
UNION ALL
SELECT 'category_product', COUNT(*) FROM category_product
UNION ALL
SELECT 'product_compatibilities', COUNT(*) FROM product_compatibilities
UNION ALL
SELECT 'product_specifications', COUNT(*) FROM product_specifications
UNION ALL
SELECT 'product_images', COUNT(*) FROM product_images
UNION ALL
SELECT 'variations', COUNT(*) FROM variations
UNION ALL
SELECT 'users', COUNT(*) FROM users
UNION ALL
SELECT 'password_reset_tokens', COUNT(*) FROM password_reset_tokens
UNION ALL
SELECT 'account_profiles', COUNT(*) FROM account_profiles
UNION ALL
SELECT 'user_checkouts', COUNT(*) FROM user_checkouts
UNION ALL
SELECT 'user_addresses', COUNT(*) FROM user_addresses
UNION ALL
SELECT 'carts', COUNT(*) FROM carts
UNION ALL
SELECT 'cart_items', COUNT(*) FROM cart_items
UNION ALL
SELECT 'orders', COUNT(*) FROM orders
UNION ALL
SELECT 'order_items', COUNT(*) FROM order_items
ORDER BY table_name;

SELECT 'account_profiles.user_id missing users' AS issue, COUNT(*) AS affected_rows
FROM account_profiles ap
LEFT JOIN users u ON u.id = ap.user_id
WHERE u.id IS NULL
UNION ALL
SELECT 'products.default_category_id missing categories', COUNT(*)
FROM products p
LEFT JOIN categories c ON c.id = p.default_category_id
WHERE p.default_category_id IS NOT NULL AND c.id IS NULL
UNION ALL
SELECT 'products.kategori_id missing categories', COUNT(*)
FROM products p
LEFT JOIN categories c ON c.id = p.kategori_id
WHERE p.kategori_id IS NOT NULL AND c.id IS NULL
UNION ALL
SELECT 'products.brand_id missing brand', COUNT(*)
FROM products p
LEFT JOIN brand b ON b.id = p.brand_id
WHERE p.brand_id IS NOT NULL AND b.id IS NULL
UNION ALL
SELECT 'variations.product_id missing products', COUNT(*)
FROM variations v
LEFT JOIN products p ON p.id = v.product_id
WHERE p.id IS NULL
UNION ALL
SELECT 'category_product.category_id missing categories', COUNT(*)
FROM category_product cp
LEFT JOIN categories c ON c.id = cp.category_id
WHERE c.id IS NULL
UNION ALL
SELECT 'category_product.product_id missing products', COUNT(*)
FROM category_product cp
LEFT JOIN products p ON p.id = cp.product_id
WHERE p.id IS NULL
UNION ALL
SELECT 'product_compatibilities.product_id missing products', COUNT(*)
FROM product_compatibilities pc
LEFT JOIN products p ON p.id = pc.product_id
WHERE p.id IS NULL
UNION ALL
SELECT 'product_specifications.product_id missing products', COUNT(*)
FROM product_specifications ps
LEFT JOIN products p ON p.id = ps.product_id
WHERE p.id IS NULL
UNION ALL
SELECT 'product_images.product_id missing products', COUNT(*)
FROM product_images pi
LEFT JOIN products p ON p.id = pi.product_id
WHERE p.id IS NULL
UNION ALL
SELECT 'user_checkouts.user_id missing users', COUNT(*)
FROM user_checkouts uc
LEFT JOIN users u ON u.id = uc.user_id
WHERE uc.user_id IS NOT NULL AND u.id IS NULL
UNION ALL
SELECT 'user_addresses.user_checkout_id missing user_checkouts', COUNT(*)
FROM user_addresses ua
LEFT JOIN user_checkouts uc ON uc.id = ua.user_checkout_id
WHERE uc.id IS NULL
UNION ALL
SELECT 'carts.user_id missing users', COUNT(*)
FROM carts c
LEFT JOIN users u ON u.id = c.user_id
WHERE c.user_id IS NOT NULL AND u.id IS NULL
UNION ALL
SELECT 'cart_items.cart_id missing carts', COUNT(*)
FROM cart_items ci
LEFT JOIN carts c ON c.id = ci.cart_id
WHERE c.id IS NULL
UNION ALL
SELECT 'cart_items.variation_id missing variations', COUNT(*)
FROM cart_items ci
LEFT JOIN variations v ON v.id = ci.variation_id
WHERE v.id IS NULL
UNION ALL
SELECT 'orders.cart_id missing carts', COUNT(*)
FROM orders o
LEFT JOIN carts c ON c.id = o.cart_id
WHERE c.id IS NULL
UNION ALL
SELECT 'orders.user_id missing users', COUNT(*)
FROM orders o
LEFT JOIN users u ON u.id = o.user_id
WHERE o.user_id IS NOT NULL AND u.id IS NULL
UNION ALL
SELECT 'orders.user_checkout_id missing user_checkouts', COUNT(*)
FROM orders o
LEFT JOIN user_checkouts uc ON uc.id = o.user_checkout_id
WHERE o.user_checkout_id IS NOT NULL AND uc.id IS NULL
UNION ALL
SELECT 'orders.shipping_address_id missing user_addresses', COUNT(*)
FROM orders o
LEFT JOIN user_addresses ua ON ua.id = o.shipping_address_id
WHERE o.shipping_address_id IS NOT NULL AND ua.id IS NULL
UNION ALL
SELECT 'orders.billing_address_id missing user_addresses', COUNT(*)
FROM orders o
LEFT JOIN user_addresses ua ON ua.id = o.billing_address_id
WHERE o.billing_address_id IS NOT NULL AND ua.id IS NULL
UNION ALL
SELECT 'order_items.order_id missing orders', COUNT(*)
FROM order_items oi
LEFT JOIN orders o ON o.id = oi.order_id
WHERE o.id IS NULL
UNION ALL
SELECT 'order_items.variation_id missing variations', COUNT(*)
FROM order_items oi
LEFT JOIN variations v ON v.id = oi.variation_id
WHERE oi.variation_id IS NOT NULL AND v.id IS NULL
ORDER BY issue;

SELECT
    id,
    id_pembelian,
    user_id,
    kode_produk,
    jumlah,
    status,
    payment_method,
    tanggal_transaksi,
    items_subtotal,
    items_tax_total,
    items_total,
    shipping_total_price,
    order_total,
    total_bayar
FROM orders
WHERE status <> 'draft'
ORDER BY id;
