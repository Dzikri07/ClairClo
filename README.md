# ClairoCloud

ClairoCloud adalah aplikasi penyimpanan cloud berbasis web yang dibangun dengan PHP dan MySQL. Aplikasi ini menyediakan platform untuk pengguna mengelola file mereka secara online dengan fitur-fitur seperti upload, download, favorit, dan pengelolaan sampah.

## Fitur Utama

### Untuk Pengguna
- **Upload File**: Unggah berbagai jenis file (gambar, dokumen, video, audio, dll.)
- **Manajemen File**: Lihat, unduh, ganti nama, dan hapus file
- **Favorit**: Tandai file favorit untuk akses cepat
- **Sampah**: File yang dihapus akan masuk ke folder sampah sebelum dihapus permanen
- **Dashboard Beranda**: Tampilan file terbaru yang diunggah
- **Penyimpanan**: Monitoring penggunaan penyimpanan dengan kuota
- **Permintaan Storage**: Minta penambahan kuota penyimpanan

### Untuk Admin
- **Dashboard Admin**: Statistik pengguna, file, dan penyimpanan
- **Manajemen Pengguna**: Kelola akun pengguna, aktifkan/nonaktifkan
- **File Internal**: Kelola file sistem internal
- **Server Storage**: Pantau dan kelola server penyimpanan
- **Activity Log**: Log aktivitas admin dan pengguna
- **Permintaan Storage**: Approve/reject permintaan penambahan kuota

## Teknologi yang Digunakan

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Bootstrap 5.3.3
- **Icons**: Font Awesome 6.6.0, Iconify
- **Charts**: Chart.js
- **SweetAlert**: SweetAlert2
- **Web Server**: Apache/Nginx (via XAMPP)

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)
- Composer (untuk dependency management)
- Node.js dan npm (untuk frontend dependencies)

## Instalasi

### 1. Clone Repository
```bash
git clone https://github.com/your-username/ClairoCloud.git
cd ClairoCloud
```

### 2. Install Dependencies
```bash
# Install PHP dependencies jika ada
composer install

# Install Node.js dependencies
npm install
```

### 3. Setup Database
1. Buat database MySQL baru:
```sql
CREATE DATABASE clariocloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

teraddpat 2 cara untuk menjankan database yang pertama adalah import clariocloud(1).sql di mySql phpmyadmin
atau dengan cara : 

2. Jalankan migrasi database:
```bash
php database/run_migrations.php
```

3. Seed data awal (opsional):
```bash
php database/seed_users.php
```

### 4. Konfigurasi
1. Salin file konfigurasi jika ada, atau edit `app/public/connection.php` untuk database connection
2. Pastikan folder `app/public/uploads/` memiliki permission write
3. Setup virtual host atau gunakan XAMPP untuk menjalankan di localhost

### 5. Jalankan Aplikasi
- Jika menggunakan XAMPP, letakkan folder project di `htdocs/`
- Akses via browser: `http://localhost/ClairoCloud-fdb78ea278ebf602e9626d6a2e26712ef4955628/app/public`

## Struktur Folder

```
ClairoCloud/
├── app/
│   ├── public/           # Web interface files
│   │   ├── admin/        # Admin panel pages
│   │   ├── assets/       # CSS, JS, images, icons
│   │   ├── uploads/      # Uploaded files storage
│   │   └── *.php         # Main application files
│   └── src/              # Source code (classes, utilities)
├── database/             # Database related files
│   ├── migrations/       # Database migration files
│   └── *.php             # Migration runners, seeders
├── examples/             # Example usage files
├── internal_files/       # Internal system files
├── logs/                 # Application logs
├── nginx/                # Nginx configuration
├── tools/                # Development tools
└── README.md             # This file
```

## Konfigurasi Database

Edit file `app/public/connection.php` untuk mengubah pengaturan database:

```php
private $host = '127.0.0.1';    // Database host
private $db = 'clariocloud';    // Database name
private $user = 'root';         // Database username
private $pass = '';             // Database password
```

## Penggunaan

### Login Admin
- Username: admin 
- Password: admin123

- user
- user3123

### Upload File
1. Login ke aplikasi
2. Pergi ke halaman "Semua File"
3. Klik tombol upload atau drag & drop file
4. File akan tersimpan di folder uploads/

### Manajemen File
- **Download**: Klik ikon download pada file
- **Rename**: Klik menu titik tiga > "Ganti nama"
- **Favorite**: Klik menu titik tiga > "Favorit"
- **Delete**: Klik menu titik tiga > "Hapus"

## API Endpoints

Aplikasi ini menggunakan PHP files sebagai endpoints:

- `upload.php` - Handle file upload
- `download.php` - Handle file download
- `delete.php` - Handle file deletion
- `rename.php` - Handle file rename
- `favorite.php` - Handle favorite toggle

## Development

### Menambah Fitur Baru
1. Buat file PHP baru di `app/public/`
2. Tambahkan route di sidebar jika diperlukan
3. Update database schema jika diperlukan dengan migration baru

### Database Migration
1. Buat file migration baru di `database/migrations/`
2. Ikuti format penomoran: `008_new_feature.php`
3. Jalankan `php database/run_migrations.php`

### Testing
- Gunakan file di `examples/` untuk testing functionality
- Tools di `tools/` untuk debugging database

## Troubleshooting

### Common Issues
1. **File tidak bisa diupload**
   - Periksa permission folder `uploads/`
   - Periksa konfigurasi PHP upload limits

2. **Database connection error**
   - Periksa kredensial database di `connection.php`
   - Pastikan MySQL service running

3. **Page not found**
   - Periksa URL rewriting atau gunakan `index.php` di URL

## Kontribusi

1. Fork repository
2. Buat branch fitur baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## Lisensi

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

Untuk support atau pertanyaan, silakan buat issue di repository GitHub atau hubungi 0897-6692-803.

---

**Catatan**: Aplikasi ini masih dalam development. Pastikan untuk backup data sebelum melakukan update atau migration.
