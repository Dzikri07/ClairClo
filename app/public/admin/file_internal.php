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
if (!is_dir($internalDir)) {
    @mkdir($internalDir, 0755, true);
}
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
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
        } else {
            $message = 'Gagal menyimpan file.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Upload error code: ' . intval($f['error']);
        $messageType = 'danger';
    }
}

// Handle backup user files
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup') {
    $fileId = intval($_POST['file_id']);
    $userId = intval($_POST['user_id']);
    
    // Get file from database
    $file = fetchOne('SELECT * FROM files WHERE id = ? AND user_id = ?', [$fileId, $userId]);
    if ($file) {
        // Get file path from storage
        $storage = fetchOne('SELECT storage_path FROM file_storage_paths WHERE file_id = ?', [$fileId]);
        if ($storage && is_file($storage['storage_path'])) {
            $backupName = date('Y-m-d_H-i-s') . '_user' . $userId . '_' . $file['original_name'];
            $backupFile = $backupDir . '/' . preg_replace('/[^A-Za-z0-9._-]/', '_', $backupName);
            
            if (copy($storage['storage_path'], $backupFile)) {
                log_activity('FILE_BACKUP', "Backup file: {$file['original_name']} (ID: {$fileId}) dari user {$userId}");
                $message = "File berhasil dibackup: {$file['original_name']}";
                $messageType = 'success';
            } else {
                $message = 'Gagal membuat backup file.';
                $messageType = 'danger';
            }
        } else {
            $message = 'File tidak ditemukan di storage.';
            $messageType = 'danger';
        }
    } else {
        $message = 'File atau user tidak ditemukan.';
        $messageType = 'danger';
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

// Get list of user files for backup
$userFiles = [];
try {
    $userFiles = fetchAll('SELECT u.id, u.username, COUNT(f.id) as file_count FROM users u LEFT JOIN files f ON u.id = f.user_id WHERE u.is_admin = 0 GROUP BY u.id, u.username');
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
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
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
                                <i class="fa fa-server me-2"></i>Backup Server
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="fw-bold mb-2">Backup Semua Data Server</h6>
                                        <p class="text-muted small mb-3">Buat backup komprehensif dari semua data server termasuk file user, database, dan konfigurasi. File akan dikompres dalam format ZIP untuk efisiensi penyimpanan.</p>
                                        <ul class="small text-muted">
                                            <li>Semua file user dari folder <code>uploads/</code></li>
                                            <li>File internal dari <code>internal_files/</code></li>
                                            <li>Data tersimpan di <code>internal_files/backup/</code></li>
                                            <li>Format: ZIP (terkompresi)</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Buat backup lengkap server? Proses ini mungkin memakan waktu...')">
                                            <input type="hidden" name="action" value="backup_server">
                                            <button type="submit" class="btn btn-lg btn-danger">
                                                <i class="fa fa-download me-2"></i>Backup Sekarang
                                            </button>
                                        </form>
                                        <p class="small text-muted mt-3"><i class="fa fa-info-circle"></i> Backup otomatis dikompres</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup User Files Section -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <i class="fa fa-copy me-2"></i>Backup File User
                            </div>
                            <div class="card-body">
                                <p class="text-muted small">Pilih user dan backup seluruh file mereka</p>
                                <?php if (!empty($userFiles)): ?>
                                    <div class="list-group">
                                        <?php foreach ($userFiles as $user): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo $user['file_count']; ?> file(s)</small>
                                                    </div>
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Backup semua file user ini?')">
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
                            <div class="card-header bg-info text-white">
                                <i class="fa fa-archive me-2"></i>Daftar Backup
                            </div>
                            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                                <?php if (empty($backups)): ?>
                                    <p class="text-muted">Tidak ada backup file.</p>
                                <?php else: ?>
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr><th>Nama</th><th>Ukuran</th><th>Aksi</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($backups as $backup):
                                                $full = $backupDir . '/' . $backup;
                                                $size = 0;
                                                $time = 'N/A';
                                                if (is_file($full)) {
                                                    $size = @filesize($full);
                                                    $time = date('Y-m-d H:i:s', filemtime($full));
                                                }
                                            ?>
                                            <tr>
                                                <td><small><?php echo htmlspecialchars($backup); ?></small></td>
                                                <td><small><?php echo humanBytes($size); ?></small></td>
                                                <td>
                                                    <a href="../../internal_files/backup/<?php echo rawurlencode($backup); ?>" class="btn btn-xs btn-outline-primary" style="font-size:0.75rem; padding: 2px 6px;">DL</a>
                                                    <a href="?delete_backup=<?php echo rawurlencode($backup); ?>" class="btn btn-xs btn-outline-danger" style="font-size:0.75rem; padding: 2px 6px;" onclick="return confirm('Hapus backup?')">X</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
// handle delete after output to avoid headers issues
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

// Handle backup all user files
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup_user') {
    $userId = intval($_POST['user_id']);
    $userFiles = fetchAll('SELECT id, original_name FROM files WHERE user_id = ? AND is_trashed = 0', [$userId]);
    
    $backupCount = 0;
    foreach ($userFiles as $file) {
        $storage = fetchOne('SELECT storage_path FROM file_storage_paths WHERE file_id = ?', [$file['id']]);
        if ($storage && is_file($storage['storage_path'])) {
            $backupName = date('Y-m-d_H-i-s') . '_user' . $userId . '_' . $file['original_name'];
            $backupFile = $backupDir . '/' . preg_replace('/[^A-Za-z0-9._-]/', '_', $backupName);
            
            if (copy($storage['storage_path'], $backupFile)) {
                $backupCount++;
            }
        }
    }
    
    if ($backupCount > 0) {
        log_activity('BACKUP_USER_FILES', "Backed up {$backupCount} file(s) from user {$userId}");
        $message = "Berhasil backup {$backupCount} file dari user ini.";
        $messageType = 'success';
    } else {
        $message = 'Tidak ada file yang berhasil dibackup.';
        $messageType = 'warning';
    }
}

// Handle backup server (all data with compression)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup_server') {
    try {
        $uploadsDir = __DIR__ . '/../../uploads';
        $internalFilesDir = $internalDir;
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
        
        // Recursive function to add files to zip
        $fileCount = 0;
        $addFilesToZip = function($dir, $zipPath, $exclude) use (&$zip, &$fileCount, &$addFilesToZip) {
            if (!is_dir($dir)) return;
            
            $files = @scandir($dir);
            if ($files === false) return;
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (in_array($file, $exclude)) continue;
                
                $filePath = $dir . '/' . $file;
                $relativePath = $zipPath . '/' . $file;
                
                if (is_dir($filePath)) {
                    $zip->addEmptyDir($relativePath);
                    $addFilesToZip($filePath, $relativePath, []);
                } else if (is_file($filePath)) {
                    if ($zip->addFile($filePath, $relativePath)) {
                        $fileCount++;
                    }
                }
            }
        };
        
        // Add uploads directory
        if (is_dir($uploadsDir)) {
            $addFilesToZip($uploadsDir, 'uploads', []);
        }
        
        // Add internal_files directory (except backup subdirectory to avoid recursion)
        if (is_dir($internalFilesDir)) {
            $addFilesToZip($internalFilesDir, 'internal_files', ['backup']);
        }
        
        $zip->close();
        
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
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>