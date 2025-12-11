<?php
session_start();
require_once __DIR__ . '/../connection.php';

// Admin only
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit;
}

$internalDir = __DIR__ . '/../../internal_files';
$backupDir = $internalDir . '/backup';
$uploadsDir = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads'); // Base uploads directory (ensure points to app/public/uploads)

if (!is_dir($internalDir)) {
    @mkdir($internalDir, 0755, true);
}
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}

// Handle file download - HARUS DITARUH DI AWAL SEBELUM OUTPUT APAPUN
if (isset($_GET['download_backup'])) {
    $filename = basename($_GET['download_backup']);
    $filepath = $backupDir . '/' . $filename;
    
    if (is_file($filepath) && is_readable($filepath)) {
        // Clear any previous output
        if (ob_get_level()) ob_end_clean();
        
        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        // Flush system output buffer
        flush();
        
        // Read file and output
        readfile($filepath);
        exit;
    } else {
        $_SESSION['error_message'] = 'File backup tidak ditemukan atau tidak dapat diakses.';
        header('Location: file_internal.php?tab=backup');
        exit;
    }
}

// Handle delete operations BEFORE any output
if (isset($_GET['delete'])) {
    $d = basename($_GET['delete']);
    $p = $internalDir . '/' . $d;
    if (is_file($p)) {
        unlink($p);
        log_activity('DELETE_INTERNAL_FILE', "Deleted file: {$d}");
        header('Location: file_internal.php');
        exit;
    }
}

// Handle delete backup
if (isset($_GET['delete_backup'])) {
    $d = basename($_GET['delete_backup']);
    $p = $backupDir . '/' . $d;
    if (is_file($p)) {
        unlink($p);
        log_activity('DELETE_BACKUP', "Deleted backup: {$d}");
        header('Location: file_internal.php?tab=backup');
        exit;
    }
}

// Handle upload
$message = null;
$messageType = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['internal_file'])) {
    $f = $_FILES['internal_file'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        $name = basename($f['name']);
        $target = $internalDir . '/' . time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
        if (move_uploaded_file($f['tmp_name'], $target)) {
            $message = 'File internal berhasil diunggah.';
            $messageType = 'success';
            log_activity('UPLOAD_INTERNAL_FILE', "Uploaded internal file: {$name}");
        } else {
            $message = 'Gagal menyimpan file.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Upload error code: ' . intval($f['error']);
        $messageType = 'danger';
    }
}

// Handle backup user files - SIMPLIFIED AND WORKING VERSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup_user') {
    $userId = intval($_POST['user_id']);
    
    // Get user info
    $user = fetchOne('SELECT username FROM users WHERE id = ?', [$userId]);
    if (!$user) {
        $message = 'User tidak ditemukan.';
        $messageType = 'danger';
    } else {
        // Get all files for this user
        $userFiles = fetchAll('SELECT id, original_name, filename FROM files WHERE user_id = ? AND is_trashed = 0', [$userId]);
        
        $backupCount = 0;
        $backupErrors = [];
        $debugInfo = [];
        
        if (empty($userFiles)) {
            $message = "User {$user['username']} tidak memiliki file.";
            $messageType = 'warning';
        } else {
            // Create user backup directory
            $userBackupDir = $backupDir . '/user_' . $userId . '_' . preg_replace('/[^A-Za-z0-9]/', '_', $user['username']);
            if (!is_dir($userBackupDir)) {
                @mkdir($userBackupDir, 0755, true);
            }
            
            // Get the correct base uploads path
            $baseUploads = realpath($uploadsDir);
            if (!$baseUploads) {
                $baseUploads = $uploadsDir;
            }
            $debugInfo[] = "Base uploads: " . $baseUploads;
            
            foreach ($userFiles as $file) {
                $fileFound = false;
                $sourcePath = null;
                
                // Strategy 1: Try to find file using storage_paths table
                $storage = fetchOne('SELECT storage_path FROM file_storage_paths WHERE file_id = ?', [$file['id']]);
                if ($storage && !empty($storage['storage_path'])) {
                    $storagePath = $storage['storage_path'];
                    $debugInfo[] = "DB storage_path: " . $storagePath;

                    // Normalize separators for processing
                    $normalizedStorage = str_replace('\\', '/', $storagePath);

                    // Prepare candidate absolute paths to try
                    $candidates = [];

                    // If storage path looks like it references uploads (relative)
                    if (preg_match('#^uploads(?:/.*)?$#i', $normalizedStorage)) {
                        // Remove leading 'uploads' to get relative remainder (may be just folder)
                        $relative = preg_replace('#^uploads#i', '', $normalizedStorage);
                        $relative = ltrim($relative, '/'); // e.g. 'user_3' or 'user_3/subdir'

                        // Candidate: the storage path resolved directly under base uploads
                        $candidates[] = $baseUploads . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

                        // If storage path is a directory (no filename), try appending the expected filename
                        $candidates[] = $baseUploads . DIRECTORY_SEPARATOR . ($relative ? $relative . DIRECTORY_SEPARATOR : '') . $file['filename'];

                        // Also try top-level under uploads with filename
                        $candidates[] = $baseUploads . DIRECTORY_SEPARATOR . $file['filename'];
                    } else {
                        // Try the storagePath interpreted as absolute or relative path
                        $candidates[] = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storagePath);
                        // Also try combining base uploads + storage path if it looks like a relative path
                        $candidates[] = $baseUploads . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $storagePath), DIRECTORY_SEPARATOR);
                        // Also try appending filename to storage path
                        $candidates[] = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storagePath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file['filename'];
                    }

                    // Normalize and test candidates
                    foreach ($candidates as $cand) {
                        $candNorm = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cand);
                        if (is_file($candNorm)) {
                            $fileFound = true;
                            $sourcePath = $candNorm;
                            $debugInfo[] = "Found via DB path (candidate): " . $candNorm;
                            break;
                        }
                    }
                }
                
                // Strategy 2: Scan entire uploads directory for matching files
                if (!$fileFound) {
                    $foundFiles = scanForFile($baseUploads, $file['filename'], $file['id'], $file['original_name']);
                    if (!empty($foundFiles)) {
                        $fileFound = true;
                        $sourcePath = $foundFiles[0];
                        $debugInfo[] = "Found via scan: " . $sourcePath;
                    }
                }
                
                // Strategy 3: Try common file locations
                if (!$fileFound) {
                    $commonPaths = [
                        $baseUploads . '/' . $file['filename'],
                        $baseUploads . '/user_' . $userId . '/' . $file['filename'],
                        $baseUploads . '/user_' . $userId . '_' . $file['filename'],
                        $baseUploads . '/' . $file['id'] . '_' . $file['filename'],
                        $baseUploads . '/user_' . $userId . '/' . $file['id'] . '_' . $file['filename']
                    ];
                    
                    foreach ($commonPaths as $testPath) {
                        if (is_file($testPath)) {
                            $fileFound = true;
                            $sourcePath = $testPath;
                            $debugInfo[] = "Found via common path: " . $testPath;
                            break;
                        }
                    }
                }
                
                if ($fileFound && $sourcePath) {
                    $backupName = $file['id'] . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file['original_name']);
                    $backupFile = $userBackupDir . '/' . $backupName;
                    
                    if (copy($sourcePath, $backupFile)) {
                        $backupCount++;
                        $debugInfo[] = "Backup SUCCESS: {$file['original_name']}";
                    } else {
                        $backupErrors[] = $file['original_name'] . " (copy failed)";
                    }
                } else {
                    $backupErrors[] = $file['original_name'] . " (file not found)";
                    $debugInfo[] = "NOT FOUND: " . $file['original_name'];
                }
            }
            
            if ($backupCount > 0) {
                // Create a zip of all backed up files
                $zipFileName = 'user_' . $userId . '_' . preg_replace('/[^A-Za-z0-9]/', '_', $user['username']) . '_backup_' . date('Y-m-d_H-i-s') . '.zip';
                $zipFilePath = $backupDir . '/' . $zipFileName;
                
                if (class_exists('ZipArchive') && $backupCount > 0) {
                    $zip = new ZipArchive();
                    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                        $zipFiles = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($userBackupDir),
                            RecursiveIteratorIterator::LEAVES_ONLY
                        );
                        
                        foreach ($zipFiles as $file) {
                            if (!$file->isDir()) {
                                $filePath = $file->getRealPath();
                                $relativePath = substr($filePath, strlen($userBackupDir) + 1);
                                $zip->addFile($filePath, $relativePath);
                            }
                        }
                        $zip->close();
                        
                        // Remove the temporary directory
                        removeDirectory($userBackupDir);
                    }
                }
                
                log_activity('BACKUP_USER_FILES', "Backed up {$backupCount} file(s) from user {$user['username']} (ID: {$userId})");
                $message = "Berhasil backup {$backupCount} file dari user {$user['username']}.";
                if (!empty($backupErrors)) {
                    $message .= " Gagal backup " . count($backupErrors) . " file.";
                }
                $messageType = 'success';
                // If ZIP was created, trigger immediate download response
                if (!empty($zipFilePath) && is_file($zipFilePath)) {
                    // Redirect to the download handler at the top of this file
                    header('Location: file_internal.php?download_backup=' . urlencode(basename($zipFilePath)));
                    exit;
                }
            } else {
                // Clean up empty directory
                if (is_dir($userBackupDir)) {
                    removeDirectory($userBackupDir);
                }
                $message = "Tidak ada file yang berhasil dibackup dari user {$user['username']}.";
                if (!empty($backupErrors)) {
                    $message .= " Error: " . implode(', ', array_slice($backupErrors, 0, 3));
                    if (count($backupErrors) > 3) {
                        $message .= " dan " . (count($backupErrors) - 3) . " file lainnya.";
                    }
                }
                $messageType = 'warning';
                
                // Add debug info to message for admin
                if (count($debugInfo) > 0) {
                    $debugSummary = array_slice($debugInfo, 0, 8);
                    $message .= "<br><small class='text-muted'>Debug: " . implode('; ', $debugSummary) . "</small>";
                }
            }
        }
    }
}

// Simple function to scan for files
function scanForFile($directory, $filename, $fileId, $originalName) {
    $foundFiles = [];
    
    if (!is_dir($directory)) {
        return $foundFiles;
    }
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        $searchPatterns = [
            $filename,
            $fileId . '_' . $filename,
            pathinfo($filename, PATHINFO_FILENAME),
            pathinfo($originalName, PATHINFO_FILENAME)
        ];
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $currentFilename = $file->getFilename();
                foreach ($searchPatterns as $pattern) {
                    if (strpos($currentFilename, $pattern) !== false) {
                        $foundFiles[] = $file->getRealPath();
                        break;
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Scan error: " . $e->getMessage());
    }
    
    return $foundFiles;
}

// Helper function to remove directory recursively
function removeDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

// Handle backup users table SQL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup_users_sql') {
    try {
        $backupTimestamp = date('Y-m-d_H-i-s');
        $backupName = "users_table_backup_{$backupTimestamp}.sql";
        $backupPath = $backupDir . '/' . $backupName;
        
        // Get all users data
        $users = fetchAll("SELECT * FROM users");
        
        if (empty($users)) {
            throw new Exception('Tidak ada data user untuk dibackup.');
        }
        
        // Create SQL dump
        $sqlContent = "-- Users Table Backup\n";
        $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sqlContent .= "-- Table structure for users\n";
        $sqlContent .= "CREATE TABLE IF NOT EXISTS `users` (\n";
        $sqlContent .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
        $sqlContent .= "  `username` varchar(50) NOT NULL,\n";
        $sqlContent .= "  `email` varchar(100) NOT NULL,\n";
        $sqlContent .= "  `password` varchar(255) NOT NULL,\n";
        $sqlContent .= "  `full_name` varchar(100) DEFAULT NULL,\n";
        $sqlContent .= "  `storage_quota` bigint(20) DEFAULT 5368709120,\n";
        $sqlContent .= "  `storage_used` bigint(20) DEFAULT 0,\n";
        $sqlContent .= "  `is_admin` tinyint(1) DEFAULT 0,\n";
        $sqlContent .= "  `is_active` tinyint(1) DEFAULT 1,\n";
        $sqlContent .= "  `last_login` datetime DEFAULT NULL,\n";
        $sqlContent .= "  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,\n";
        $sqlContent .= "  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        $sqlContent .= "  PRIMARY KEY (`id`),\n";
        $sqlContent .= "  UNIQUE KEY `username` (`username`),\n";
        $sqlContent .= "  UNIQUE KEY `email` (`email`)\n";
        $sqlContent .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
        
        $sqlContent .= "-- Dumping data for table `users`\n\n";
        
        foreach ($users as $user) {
            $values = [];
            foreach ($user as $key => $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . addslashes($value) . "'";
                }
            }
            $sqlContent .= "INSERT INTO `users` VALUES (" . implode(', ', $values) . ");\n";
        }
        
        // Write to file
        if (file_put_contents($backupPath, $sqlContent) === false) {
            throw new Exception('Gagal menulis file SQL backup.');
        }
        
        $fileSize = @filesize($backupPath);
        log_activity('BACKUP_USERS_SQL', "Created users table SQL backup: {$backupName} ({$fileSize} bytes, " . count($users) . " users)");
        
        $message = "Backup SQL users table berhasil: {$backupName} (" . humanBytes($fileSize) . ", " . count($users) . " users).";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "Gagal membuat backup SQL: " . $e->getMessage();
        $messageType = 'danger';
        error_log('Users SQL backup error: ' . $e->getMessage());
    }
}

// Handle backup server (all data with compression)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup_server') {
    try {
        $uploadsDir = realpath(__DIR__ . '/../uploads');
        $internalFilesDir = realpath($internalDir);
        $backupTimestamp = date('Y-m-d_H-i-s');
        $backupName = "server_backup_{$backupTimestamp}.zip";
        $backupPath = $backupDir . '/' . $backupName;
        
        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive tidak tersedia di server ini.');
        }
        
        $zip = new ZipArchive();
        if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Gagal membuat file ZIP.');
        }
        
        $fileCount = 0;
        
        // Add uploads directory if exists
        if ($uploadsDir && is_dir($uploadsDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploadsDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'uploads' . substr($filePath, strlen($uploadsDir));
                    
                    if ($zip->addFile($filePath, $relativePath)) {
                        $fileCount++;
                    }
                }
            }
        }
        
        // Add internal_files directory (excluding backup subdirectory)
        if ($internalFilesDir && is_dir($internalFilesDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($internalFilesDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    // Skip backup directory files
                    if (strpos($filePath, $backupDir) !== false) {
                        continue;
                    }
                    $relativePath = 'internal_files' . substr($filePath, strlen($internalFilesDir));
                    
                    if ($zip->addFile($filePath, $relativePath)) {
                        $fileCount++;
                    }
                }
            }
        }
        
        // Add SQL dump of all database tables
        $sqlBackupName = "database_backup_{$backupTimestamp}.sql";
        $tempSqlPath = sys_get_temp_dir() . '/' . $sqlBackupName;
        
        // Create SQL dump
        $sqlContent = "-- Database Backup\n";
        $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Tables to backup
        $tables = ['users', 'files', 'file_categories', 'file_storage_paths', 'storage_requests', 'activity_logs'];
        
        foreach ($tables as $table) {
            try {
                $result = fetchAll("SELECT * FROM {$table}");
                if (!empty($result)) {
                    $sqlContent .= "-- Table: {$table}\n";
                    $sqlContent .= "DELETE FROM `{$table}`;\n";
                    
                    foreach ($result as $row) {
                        $values = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $sqlContent .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sqlContent .= "\n";
                }
            } catch (Exception $e) {
                $sqlContent .= "-- Error backing up table {$table}: " . $e->getMessage() . "\n\n";
            }
        }
        
        if (file_put_contents($tempSqlPath, $sqlContent) !== false) {
            $zip->addFile($tempSqlPath, $sqlBackupName);
        }
        
        $zip->close();
        
        // Clean up temp SQL file
        @unlink($tempSqlPath);
        
        $fileSize = 0;
        if (is_file($backupPath)) {
            $fileSize = @filesize($backupPath);
        }
        
        log_activity('BACKUP_SERVER', "Created server backup: {$backupName} ({$fileSize} bytes, {$fileCount} files included)");
        
        $message = "Server backup berhasil dibuat: {$backupName} (" . humanBytes($fileSize) . ", {$fileCount} file).";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "Gagal membuat backup server: " . $e->getMessage();
        $messageType = 'danger';
        error_log('Server backup error: ' . $e->getMessage());
    }
}

// List internal files
$files = array_values(array_filter(scandir($internalDir), function($v){ 
    return $v !== '.' && $v !== '..' && $v !== 'backup'; 
}));

// List backup files
$backups = array_values(array_filter(scandir($backupDir), function($v){ 
    return $v !== '.' && $v !== '..'; 
}));

// Sort backups by date (newest first)
usort($backups, function($a, $b) use ($backupDir) {
    return filemtime($backupDir . '/' . $b) - filemtime($backupDir . '/' . $a);
});

// Get list of user files for backup
$userFiles = [];
try {
    $userFiles = fetchAll('SELECT u.id, u.username, COUNT(f.id) as file_count FROM users u LEFT JOIN files f ON u.id = f.user_id AND f.is_trashed = 0 WHERE u.is_admin = 0 GROUP BY u.id, u.username');
} catch (Exception $e) {
    error_log('Error fetching user files: ' . $e->getMessage());
}

function humanBytes($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = ['B','KB','MB','GB','TB'];
    $i = floor(log($bytes,1024));
    return round($bytes/pow(1024,$i),2) . ' ' . $units[$i];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>File Internal - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
    .bg-gradient {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe9e9 100%);
    }
    body.dark-mode .bg-gradient {
        background: linear-gradient(135deg, #3a2a2a 0%, #4a2a2a 100%);
    }
    .backup-file-item:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }
    body.dark-mode .backup-file-item:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    .user-file-count {
        font-size: 0.8rem;
    }
</style>
</head>
<body style="background-color:var(--bg-primary);">
<script>
// Load theme preference immediately to prevent flash
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-mode');
    document.body.classList.add('dark-mode');
}
</script>
<div class="d-flex">
    <?php include __DIR__ . '/../sidebar.php'; ?>
    <div class="main flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold"><i class="fa fa-folder-open me-2 text-primary"></i>File Internal</h4>
            <small class="text-muted">Lampiran file untuk penanganan darurat</small>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" href="#files-tab" data-bs-toggle="tab" role="tab">File Internal</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#backup-tab" data-bs-toggle="tab" role="tab">Backup</a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Files Tab -->
            <div class="tab-pane fade show active" id="files-tab">
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Pilih file internal</label>
                                <input type="file" name="internal_file" class="form-control" required>
                            </div>
                            <button class="btn btn-primary">Unggah</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white">Daftar File Internal</div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr><th>Nama</th><th>Ukuran</th><th>Diunggah</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($files)): ?>
                                    <tr><td colspan="4" class="text-muted">Tidak ada file internal</td></tr>
                                <?php else: ?>
                                    <?php foreach ($files as $fn):
                                        $full = $internalDir . '/' . $fn;
                                        $size = 0;
                                        $time = 'N/A';
                                        if (is_file($full)) {
                                            $size = @filesize($full);
                                            $time = date('Y-m-d H:i:s', filemtime($full));
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fn); ?></td>
                                        <td><?php echo humanBytes($size); ?></td>
                                        <td><?php echo $time; ?></td>
                                        <td>
                                            <a href="../../internal_files/<?php echo rawurlencode($fn); ?>" class="btn btn-sm btn-outline-primary" target="_blank">Download</a>
                                            <a href="?delete=<?php echo rawurlencode($fn); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus file internal?')">Hapus</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Backup Tab -->
            <div class="tab-pane fade" id="backup-tab">
                <div class="row">
                    <!-- Backup Server Data Card -->
                    <div class="col-md-12 mb-4">
                        <div class="card shadow-sm bg-gradient">
                            <div class="card-header bg-danger text-white">
                                <i class="fa fa-server me-2"></i>Backup Server & Database
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-2">Backup Semua Data Server</h6>
                                        <p class="text-muted small mb-3">Buat backup komprehensif dari semua data server termasuk file user, database, dan konfigurasi. File akan dikompres dalam format ZIP untuk efisiensi penyimpanan.</p>
                                        <ul class="small text-muted">
                                            <li>Semua file user dari folder <code>uploads/</code></li>
                                            <li>File internal dari <code>internal_files/</code></li>
                                            <li>Database SQL dump (semua tabel)</li>
                                            <li>Format: ZIP (terkompresi)</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Buat backup lengkap server? Proses ini mungkin memakan waktu...')">
                                            <input type="hidden" name="action" value="backup_server">
                                            <button type="submit" class="btn btn-lg btn-danger mb-2">
                                                <i class="fa fa-download me-2"></i>Backup Server
                                            </button>
                                        </form>
                                        <p class="small text-muted"><i class="fa fa-info-circle"></i> Full backup dengan SQL</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Backup tabel users ke file SQL?')">
                                            <input type="hidden" name="action" value="backup_users_sql">
                                            <button type="submit" class="btn btn-lg btn-warning mb-2">
                                                <i class="fa fa-database me-2"></i>Backup Users SQL
                                            </button>
                                        </form>
                                        <p class="small text-muted"><i class="fa fa-info-circle"></i> Hanya tabel users</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup User Files Section -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <i class="fa fa-users me-2"></i>Backup File per User
                            </div>
                            <div class="card-body">
                                <p class="text-muted small">Pilih user untuk backup semua file mereka. Sistem akan mencari file di berbagai lokasi yang mungkin.</p>
                                <?php if (!empty($userFiles)): ?>
                                    <div class="list-group">
                                        <?php foreach ($userFiles as $user): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                        <br>
                                                        <small class="text-muted user-file-count">
                                                            <?php echo $user['file_count']; ?> file(s) di database
                                                        </small>
                                                    </div>
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Backup semua file user <?php echo htmlspecialchars($user['username']); ?>?')">
                                                        <input type="hidden" name="action" value="backup_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success" <?php echo $user['file_count'] == 0 ? 'disabled' : ''; ?>>
                                                            <i class="fa fa-save me-1"></i>Backup
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada user ditemukan.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Files List Section -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <span><i class="fa fa-archive me-2"></i>Daftar Backup</span>
                                <small class="text-white"><?php echo count($backups); ?> file(s)</small>
                            </div>
                            <div class="card-body">
                                <!-- Search Bar -->
                                <div class="mb-3">
                                    <input type="text" id="backupSearch" class="form-control form-control-sm" placeholder="Cari backup file...">
                                </div>
                                
                                <div style="max-height: 450px; overflow-y: auto;">
                                    <?php if (empty($backups)): ?>
                                        <p class="text-muted">Tidak ada backup file.</p>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush" id="backupList">
                                            <?php foreach ($backups as $backup):
                                                $full = $backupDir . '/' . $backup;
                                                $size = 0;
                                                $time = 'N/A';
                                                if (is_file($full)) {
                                                    $size = @filesize($full);
                                                    $time = date('Y-m-d H:i:s', filemtime($full));
                                                }
                                            ?>
                                            <div class="list-group-item backup-file-item backup-row">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="flex-grow-1">
                                                        <div class="backup-name small fw-bold"><?php echo htmlspecialchars($backup); ?></div>
                                                        <div class="small text-muted">
                                                            <?php echo humanBytes($size); ?> • <?php echo $time; ?>
                                                        </div>
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?download_backup=<?php echo rawurlencode($backup); ?>" class="btn btn-outline-primary" title="Download">
                                                            <i class="fa fa-download"></i>
                                                        </a>
                                                        <a href="?delete_backup=<?php echo rawurlencode($backup); ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus backup <?php echo htmlspecialchars($backup); ?>?')" title="Hapus">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Backup search functionality
document.getElementById('backupSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#backupList .backup-row');
    
    rows.forEach(row => {
        const fileName = row.querySelector('.backup-name').textContent.toLowerCase();
        if (fileName.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Auto-dismiss alerts after 5 seconds
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>
</body>
</html>