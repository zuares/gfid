============================================================
 GFID – DATABASE BACKUP & RESTORE ARTISAN COMMANDS
============================================================

1) Membuat backup database SQLite terbaru
------------------------------------------------------------
php artisan db:backup


2) Melihat daftar semua file backup
------------------------------------------------------------
php artisan db:backup:list


3) Melihat daftar backup dengan batas jumlah tertentu
------------------------------------------------------------
php artisan db:backup:list --limit=5
php artisan db:backup:list --limit=10


4) Restore database dari file backup tertentu
------------------------------------------------------------
php artisan db:restore backup_20251120_163501.sqlite


5) Backup otomatis sebelum migrate:fresh
------------------------------------------------------------
php artisan migrate:fresh

(Perintah ini otomatis akan membuat backup sebelum database direset)


============================================================
 Lokasi File Backup:
 storage/backups/
============================================================


# 🧱 LARAVEL MIGRATION (RAPI & TERPISAH)

# 1. Buat tabel (CREATE TABLE)
php artisan make:migration create_products_table

# 2. Tambah kolom ke tabel (ADD COLUMN)
php artisan make:migration add_stock_to_products_table --table=products

# 3. Ubah kolom (CHANGE COLUMN)
php artisan make:migration change_price_type_in_products_table --table=products

# 4. Tambah foreign key (ADD FOREIGN KEY)
php artisan make:migration add_user_id_foreign_to_products_table --table=products

# 5. Hapus kolom (DROP COLUMN)
php artisan make:migration drop_status_from_products_table --table=products

# 6. Hapus tabel (DROP TABLE)
php artisan make:migration drop_products_table --table=products

