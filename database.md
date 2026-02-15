  Berikut adalah daftar lengkap tabel dan kolom untuk aplikasi pemesanan restoran multi-cabang:

---

## TABEL DAN KOLOM DATABASE

### 1. restaurants
Tabel master data restoran/cabang

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| name | VARCHAR(255) | Nama restoran/cabang |
| address | TEXT | Alamat lengkap restoran |
| phone | VARCHAR(20) | Nomor telepon restoran |
| email | VARCHAR(255) | Email restoran |
| tax_id | VARCHAR(50) | NPWP/ID pajak (opsional) |
| logo_url | VARCHAR(500) | URL logo restoran |
| is_active | BOOLEAN | Status aktif/tidak, default TRUE |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |
| deleted_at | TIMESTAMP NULL | Soft delete |

---

### 2. users
Tabel data pengguna aplikasi

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| restaurant_id | BIGINT UNSIGNED | Foreign Key ke restaurants.id |
| username | VARCHAR(50) | Username unik untuk login |
| password_hash | VARCHAR(255) | Hash password (bcrypt) |
| full_name | VARCHAR(255) | Nama lengkap user |
| email | VARCHAR(255) | Email user |
| phone | VARCHAR(20) | Nomor telepon user |
| role | ENUM | 'owner','admin','manager','waiter','kitchen' |
| avatar_url | VARCHAR(500) | URL foto profil |
| is_active | BOOLEAN | Status aktif, default TRUE |
| last_login_at | TIMESTAMP NULL | Waktu login terakhir |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |
| deleted_at | TIMESTAMP NULL | Soft delete |

---

### 3. menu_categories
Tabel kategori menu per restoran

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| restaurant_id | BIGINT UNSIGNED | Foreign Key ke restaurants.id |
| name | VARCHAR(100) | Nama kategori (Makanan, Minuman, dll) |
| description | TEXT | Deskripsi kategori |
| sort_order | INT | Urutan tampilan, default 0 |
| is_active | BOOLEAN | Status aktif, default TRUE |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |
| deleted_at | TIMESTAMP NULL | Soft delete |

---

### 4. menu_items
Tabel data menu/item makanan dan minuman

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| restaurant_id | BIGINT UNSIGNED | Foreign Key ke restaurants.id |
| category_id | BIGINT UNSIGNED | Foreign Key ke menu_categories.id |
| name | VARCHAR(255) | Nama menu |
| description | TEXT | Deskripsi menu |
| price | DECIMAL(12,2) | Harga jual |
| cost_price | DECIMAL(12,2) | Harga modal (untuk kalkulasi profit) |
| image_url | VARCHAR(500) | URL gambar menu |
| is_available | BOOLEAN | Tersedia/tidak, default TRUE |
| is_featured | BOOLEAN | Menu unggulan, default FALSE |
| preparation_time | INT | Estimasi waktu persiapan (menit) |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |
| deleted_at | TIMESTAMP NULL | Soft delete |

---

### 5. orders
Tabel data pesanan/transaksi

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| restaurant_id | BIGINT UNSIGNED | Foreign Key ke restaurants.id |
| user_id | BIGINT UNSIGNED | Foreign Key ke users.id (pelayan yang input) |
| customer_name | VARCHAR(255) | Nama pemesan (untuk dipanggil) |
| customer_phone | VARCHAR(20) | Nomor telepon pemesan (opsional) |
| order_number | VARCHAR(20) | Nomor order unik (format: ORD-YYYYMMDD-XXXX) |
| status | ENUM | 'pending','cooking','completed','paid','cancelled' |
| total_amount | DECIMAL(12,2) | Total harga pesanan |
| payment_amount | DECIMAL(12,2) | Jumlah uang dibayar customer |
| change_amount | DECIMAL(12,2) | Jumlah kembalian |
| payment_method | ENUM | 'cash','qris','transfer','debit','credit' |
| notes | TEXT | Catatan tambahan |
| completed_at | TIMESTAMP NULL | Waktu status jadi completed |
| paid_at | TIMESTAMP NULL | Waktu status jadi paid |
| cancelled_at | TIMESTAMP NULL | Waktu pembatalan |
| cancellation_reason | TEXT | Alasan pembatalan |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |

---

### 6. order_items
Tabel detail item dalam satu pesanan

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| order_id | BIGINT UNSIGNED | Foreign Key ke orders.id |
| menu_item_id | BIGINT UNSIGNED | Foreign Key ke menu_items.id |
| quantity | INT | Jumlah item dipesan |
| price_at_time | DECIMAL(12,2) | Harga saat dipesan (snapshot) |
| subtotal | DECIMAL(12,2) | quantity * price_at_time |
| notes | TEXT | Catatan khusus item ini |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |

---

### 7. order_status_logs
Tabel audit trail perubahan status order

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| order_id | BIGINT UNSIGNED | Foreign Key ke orders.id |
| old_status | VARCHAR(20) | Status lama |
| new_status | VARCHAR(20) | Status baru |
| changed_by | BIGINT UNSIGNED | Foreign Key ke users.id |
| changed_at | TIMESTAMP | Waktu perubahan |
| notes | TEXT | Keterangan perubahan |

---

### 8. inventory_items
Tabel master bahan baku/inventory

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| restaurant_id | BIGINT UNSIGNED | Foreign Key ke restaurants.id |
| name | VARCHAR(255) | Nama bahan |
| unit | VARCHAR(20) | Satuan (kg, liter, pcs, dll) |
| min_stock | DECIMAL(10,2) | Stok minimum (alert) |
| current_stock | DECIMAL(10,2) | Stok saat ini |
| unit_cost | DECIMAL(12,2) | Harga per unit |
| supplier_name | VARCHAR(255) | Nama supplier |
| supplier_phone | VARCHAR(20) | Telepon supplier |
| is_active | BOOLEAN | Status aktif, default TRUE |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |

---

### 9. inventory_transactions
Tabel transaksi stok masuk/keluar

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| restaurant_id | BIGINT UNSIGNED | Foreign Key ke restaurants.id |
| inventory_item_id | BIGINT UNSIGNED | Foreign Key ke inventory_items.id |
| type | ENUM | 'in' (masuk), 'out' (keluar), 'adjustment' |
| quantity | DECIMAL(10,2) | Jumlah |
| unit_cost | DECIMAL(12,2) | Harga per unit saat transaksi |
| total_cost | DECIMAL(12,2) | quantity * unit_cost |
| reference_type | VARCHAR(50) | Referensi (order, purchase, waste) |
| reference_id | BIGINT UNSIGNED | ID referensi |
| notes | TEXT | Keterangan |
| created_by | BIGINT UNSIGNED | Foreign Key ke users.id |
| created_at | TIMESTAMP | Waktu transaksi |

---

### 10. daily_sales_summary
Tabel ringkasan penjualan harian (untuk performa query)

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| restaurant_id | BIGINT UNSIGNED | Foreign Key ke restaurants.id |
| date | DATE | Tanggal |
| total_orders | INT | Jumlah order |
| total_items_sold | INT | Jumlah item terjual |
| gross_sales | DECIMAL(12,2) | Total penjualan kotor |
| net_sales | DECIMAL(12,2) | Total penjualan bersih |
| total_discount | DECIMAL(12,2) | Total diskon |
| total_tax | DECIMAL(12,2) | Total pajak |
| created_at | TIMESTAMP | Waktu pembuatan record |
| updated_at | TIMESTAMP | Waktu update terakhir |

---

### 11. settings
Tabel konfigurasi sistem per restoran

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| restaurant_id | BIGINT UNSIGNED | Foreign Key ke restaurants.id |
| key | VARCHAR(100) | Nama setting |
| value | TEXT | Nilai setting |
| type | VARCHAR(20) | Tipe data (string, number, boolean, json) |
| description | TEXT | Keterangan setting |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update terakhir |

---

### 12. notifications
Tabel notifikasi real-time

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| restaurant_id | BIGINT UNSIGNED | Foreign Key ke restaurants.id |
| user_id | BIGINT UNSIGNED | Foreign Key ke users.id (target user) |
| type | VARCHAR(50) | Jenis notifikasi (order_completed, dll) |
| title | VARCHAR(255) | Judul notifikasi |
| message | TEXT | Isi notifikasi |
| data | JSON | Data tambahan |
| is_read | BOOLEAN | Sudah dibaca, default FALSE |
| read_at | TIMESTAMP NULL | Waktu dibaca |
| created_at | TIMESTAMP | Waktu pembuatan |

---

## INDEX DATABASE

### Index untuk Performance

```
TABEL: orders
- INDEX idx_restaurant_status (restaurant_id, status)
- INDEX idx_created_at (created_at)
- INDEX idx_order_number (order_number)

TABEL: order_items
- INDEX idx_order_id (order_id)
- INDEX idx_menu_item_id (menu_item_id)

TABEL: users
- UNIQUE INDEX idx_username (username)
- INDEX idx_restaurant_role (restaurant_id, role)

TABEL: menu_items
- INDEX idx_restaurant_category (restaurant_id, category_id)
- INDEX idx_is_available (is_available)

TABEL: order_status_logs
- INDEX idx_order_id (order_id)
- INDEX idx_changed_at (changed_at)

TABEL: daily_sales_summary
- UNIQUE INDEX idx_restaurant_date (restaurant_id, date)
```

---

## RELASI FOREIGN KEY

```
restaurants.id 
    ├──> users.restaurant_id (ON DELETE CASCADE)
    ├──> menu_categories.restaurant_id (ON DELETE CASCADE)
    ├──> menu_items.restaurant_id (ON DELETE CASCADE)
    ├──> orders.restaurant_id (ON DELETE RESTRICT)
    ├──> inventory_items.restaurant_id (ON DELETE CASCADE)
    ├──> inventory_transactions.restaurant_id (ON DELETE CASCADE)
    ├──> daily_sales_summary.restaurant_id (ON DELETE CASCADE)
    ├──> settings.restaurant_id (ON DELETE CASCADE)
    └──> notifications.restaurant_id (ON DELETE CASCADE)

users.id
    ├──> orders.user_id (ON DELETE SET NULL)
    ├──> order_status_logs.changed_by (ON DELETE SET NULL)
    ├──> inventory_transactions.created_by (ON DELETE SET NULL)
    └──> notifications.user_id (ON DELETE CASCADE)

menu_categories.id
    └──> menu_items.category_id (ON DELETE SET NULL)

menu_items.id
    └──> order_items.menu_item_id (ON DELETE RESTRICT)

orders.id
    ├──> order_items.order_id (ON DELETE CASCADE)
    └──> order_status_logs.order_id (ON DELETE CASCADE)

inventory_items.id
    └──> inventory_transactions.inventory_item_id (ON DELETE CASCADE)
```

---

## ENUM VALUES

```
users.role:
    - 'owner'      : Pemilik bisnis
    - 'admin'      : Administrator
    - 'manager'    : Manager cabang
    - 'waiter'     : Pelayan
    - 'kitchen'    : Staff dapur

orders.status:
    - 'pending'    : Baru dibuat, menunggu dapur
    - 'cooking'    : Sedang dimasak (opsional)
    - 'completed'  : Selesai dimasak, siap diambil
    - 'paid'       : Sudah dibayar dan diambil
    - 'cancelled'  : Dibatalkan

orders.payment_method:
    - 'cash'       : Tunai
    - 'qris'       : QRIS
    - 'transfer'   : Transfer bank
    - 'debit'      : Kartu debit
    - 'credit'     : Kartu kredit

inventory_transactions.type:
    - 'in'         : Stok masuk
    - 'out'        : Stok keluar
    - 'adjustment' : Penyesuaian stok
```

---

Total: **12 Tabel** dengan struktur lengkap siap implementasi.