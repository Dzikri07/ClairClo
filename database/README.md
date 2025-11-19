# Database Migrations - ClairoCloud

Sistem migration untuk mengelola struktur database ClairoCloud.

## Struktur Database

### Tabel Users
Menyimpan informasi pengguna dengan fitur:
- Autentikasi (username, email, password)
- Manajemen storage quota
- Role management (admin/user)
- Tracking aktivitas login

### Tabel File Categories
Mengorganisir file berdasarkan kategori:
- Documents (PDF, DOC, DOCX, TXT, dll)
- Images (PNG, JPG, JPEG, GIF, SVG, dll)
- Videos (MP4, AVI, MOV, MKV, dll)
- Audio (MP3, WAV, OGG, dll)
- Spreadsheets (XLSX, XLS, CSV, dll)
- Presentations (PPT, PPTX, dll)
- Archives (ZIP, RAR, 7Z, dll)
- Code (PHP, JS, HTML, CSS, dll)
- Others (file lainnya)

### Tabel Files (Updated)
Menyimpan metadata file dengan relasi ke users dan categories:
- Informasi file (nama, ukuran, mime type, extension)
- Relasi ke user (pemilik file)
- Relasi ke category (jenis file)
- Thumbnail untuk preview
- Tracking download dan akses
- Fitur favorit dan trash

## Cara Menggunakan Migration

### 1. Cek Status Migration
```bash
php database/migrate.php status
```

### 2. Menjalankan Migration (Up)
```bash
php database/migrate.php up
```
Perintah ini akan menjalankan semua migration yang belum dieksekusi.

### 3. Rollback Migration (Down)
```bash
php database/migrate.php down
```
Perintah ini akan membatalkan batch migration terakhir.

### 4. Reset Semua Migration
```bash
php database/migrate.php reset
```
Perintah ini akan rollback semua migration dan menjalankan ulang dari awal.

## Urutan Migration

1. **001_create_users_table.php**
   - Membuat tabel users
   - Membuat user admin default (username: admin, password: admin123)

2. **002_create_file_categories_table.php**
   - Membuat tabel file_categories

3. **003_update_files_table.php**
   - Menambahkan kolom user_id, category_id, dan kolom lainnya ke tabel files
   - Menambahkan foreign key constraints
   - Menambahkan indexes untuk performa

4. **004_seed_file_categories.php**
   - Mengisi data kategori file default

## Default Admin User

Setelah menjalankan migration, akan dibuat user admin default:
- **Username**: admin
- **Password**: admin123
- **Email**: admin@clariocloud.local
- **Storage Quota**: 100GB

⚠️ **PENTING**: Segera ubah password admin setelah login pertama kali!

## Konfigurasi Storage Quota

Default storage quota per user:
- User biasa: 5GB (5,368,709,120 bytes)
- Admin: 100GB (107,374,182,400 bytes)

Quota dapat diubah melalui database atau panel admin.

## File Categories - Allowed Extensions

### Documents
- Extensions: pdf, doc, docx, txt, rtf, odt
- Max Size: 50MB

### Images
- Extensions: jpg, jpeg, png, gif, bmp, svg, webp, ico
- Max Size: 10MB

### Videos
- Extensions: mp4, avi, mov, mkv, wmv, flv, webm, m4v
- Max Size: 500MB

### Audio
- Extensions: mp3, wav, ogg, m4a, flac, aac, wma
- Max Size: 50MB

### Spreadsheets
- Extensions: xlsx, xls, csv, ods
- Max Size: 20MB

### Presentations
- Extensions: ppt, pptx, odp, key
- Max Size: 50MB

### Archives
- Extensions: zip, rar, 7z, tar, gz, bz2
- Max Size: 100MB

### Code
- Extensions: php, js, html, css, json, xml, sql, py, java, cpp, c, h, sh
- Max Size: 5MB

### Others
- Extensions: * (semua)
- Max Size: 100MB

## Troubleshooting

### Error: Could not connect to database
Pastikan konfigurasi database di `app/public/connection.php` sudah benar:
- Host: 127.0.0.1
- Database: clariocloud
- Username: root
- Password: (sesuaikan dengan setup MySQL Anda)

### Error: Table already exists
Jika tabel sudah ada, gunakan perintah `status` untuk melihat migration mana yang sudah dijalankan.

### Error: Foreign key constraint fails
Pastikan menjalankan migration sesuai urutan. Gunakan `reset` untuk memulai dari awal.

## Membuat Migration Baru

1. Buat file baru di folder `database/migrations/` dengan format:
   ```
   00X_nama_migration.php
   ```

2. Gunakan template berikut:
   ```php
   <?php
   require_once __DIR__ . '/../../app/src/Migration.php';

   class NamaMigration extends Migration
   {
       public function up()
       {
           $this->log("Running migration...");
           // Kode migration di sini
           return true;
       }

       public function down()
       {
           $this->log("Rolling back migration...");
           // Kode rollback di sini
           return true;
       }
   }
   ```

3. Jalankan migration:
   ```bash
   php database/migrate.php up
   ```

## Catatan Penting

- Selalu backup database sebelum menjalankan migration di production
- Test migration di development environment terlebih dahulu
- Gunakan `status` untuk melihat migration yang sudah dijalankan
- Jangan edit file migration yang sudah dijalankan
- Buat migration baru untuk perubahan database
