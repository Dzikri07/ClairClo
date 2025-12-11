<?php
// backup.php - user-facing backup page (creates ZIP of user's files)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/connection.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: login.php');
    exit;
}

$user = fetchOne('SELECT id, username FROM users WHERE id = ?', [$userId]);
if (!$user) {
    echo "User not found";
    exit;
}

// Helper: scan for files (same logic as admin fallback)
function scanForFileLocal($directory, $filename, $fileId, $originalName) {
    $foundFiles = [];
    if (!is_dir($directory)) return $foundFiles;
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
                    if ($pattern !== '' && strpos($currentFilename, $pattern) !== false) {
                        $foundFiles[] = $file->getRealPath();
                        break;
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('scanForFileLocal error: ' . $e->getMessage());
    }
    return $foundFiles;
}

// POST handler: create zip and stream
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_backup'])) {
    $uploadsBase = realpath(__DIR__ . '/uploads') ?: (__DIR__ . '/uploads');

    $userFiles = fetchAll('SELECT f.id, f.filename, f.original_name FROM files f WHERE f.user_id = ? AND f.is_trashed = 0', [$userId]);

    if (empty($userFiles)) {
        $_SESSION['error_message'] = 'Tidak ada file untuk dibackup.';
        header('Location: backup.php');
        exit;
    }

    $tmpDir = sys_get_temp_dir();
    $userBackupDir = $tmpDir . DIRECTORY_SEPARATOR . 'user_' . $userId . '_' . preg_replace('/[^A-Za-z0-9]/', '_', $user['username']);
    if (!is_dir($userBackupDir)) @mkdir($userBackupDir, 0755, true);

    $copied = 0;
    $errors = [];

    foreach ($userFiles as $f) {
        $fileFound = false;
        $sourcePath = null;

        // Try storage_paths table first
        $storage = fetchOne('SELECT storage_path FROM file_storage_paths WHERE file_id = ?', [$f['id']]);
        if ($storage && !empty($storage['storage_path'])) {
            $sp = str_replace('\\', '/', $storage['storage_path']);
            // if starts with uploads, try relative under uploads
            if (preg_match('#^uploads(?:/.*)?$#i', $sp)) {
                $relative = ltrim(preg_replace('#^uploads#i', '', $sp), '/');
                $cand1 = $uploadsBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                $cand2 = $uploadsBase . DIRECTORY_SEPARATOR . ($relative ? $relative . DIRECTORY_SEPARATOR : '') . $f['filename'];
                $cand3 = $uploadsBase . DIRECTORY_SEPARATOR . $f['filename'];
                foreach ([$cand1, $cand2, $cand3] as $c) {
                    if (is_file($c)) { $fileFound = true; $sourcePath = $c; break; }
                }
            } else {
                $c1 = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storage['storage_path']);
                $c2 = $uploadsBase . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $storage['storage_path']), DIRECTORY_SEPARATOR);
                $c3 = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storage['storage_path']), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $f['filename'];
                foreach ([$c1, $c2, $c3] as $c) {
                    if (is_file($c)) { $fileFound = true; $sourcePath = $c; break; }
                }
            }
        }

        // scan uploads if not found
        if (!$fileFound) {
            $found = scanForFileLocal($uploadsBase, $f['filename'], $f['id'], $f['original_name']);
            if (!empty($found)) { $fileFound = true; $sourcePath = $found[0]; }
        }

        // try common paths
        if (!$fileFound) {
            $candidates = [
                $uploadsBase . DIRECTORY_SEPARATOR . $f['filename'],
                $uploadsBase . DIRECTORY_SEPARATOR . 'user_' . $userId . DIRECTORY_SEPARATOR . $f['filename'],
                $uploadsBase . DIRECTORY_SEPARATOR . 'user_' . $userId . '_' . $f['filename'],
                $uploadsBase . DIRECTORY_SEPARATOR . $f['id'] . '_' . $f['filename']
            ];
            foreach ($candidates as $c) {
                if (is_file($c)) { $fileFound = true; $sourcePath = $c; break; }
            }
        }

        if ($fileFound && $sourcePath) {
            $target = $userBackupDir . DIRECTORY_SEPARATOR . $f['id'] . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $f['original_name']);
            if (@copy($sourcePath, $target)) { $copied++; } else { $errors[] = $f['original_name'] . ' (copy failed)'; }
        } else {
            $errors[] = $f['original_name'] . ' (not found)';
        }
    }

    if ($copied > 0) {
        $zipName = 'user_' . $userId . '_' . preg_replace('/[^A-Za-z0-9]/', '_', $user['username']) . '_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($userBackupDir), RecursiveIteratorIterator::LEAVES_ONLY);
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relative = substr($filePath, strlen($userBackupDir) + 1);
                        $zip->addFile($filePath, $relative);
                    }
                }
                $zip->close();
            }
        }
        // cleanup temp folder
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($userBackupDir), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) { if ($f->isFile()) @unlink($f->getRealPath()); }
        @rmdir($userBackupDir);

        if (is_file($zipPath)) {
            // Stream zip to user
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
            header('Content-Length: ' . filesize($zipPath));
            flush();
            readfile($zipPath);
            @unlink($zipPath);
            exit;
        }
    }

    // fallback: redirect back with message
    $_SESSION['error_message'] = 'Gagal membuat backup. ' . ($errors ? implode('; ', array_slice($errors, 0, 5)) : '');
    header('Location: backup.php');
    exit;
}

// Render simple page with start button
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Backup File - Clario</title>
<link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="background-color: #f9f9f9;">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main flex-grow-1 p-4">
    <div class="header-section d-flex justify-content-between align-items-center mb-4">
        <div class="welcome-text">
            <p class="fs-5 mb-1">Backup</p>
            <h6 class="fw-bold mt-3">Backup File Anda</h6>
            <p class="text-muted small">Download semua file Anda sebagai satu file ZIP.</p>
        </div>
    </div>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    
    <div style="background:#fff; border:1px solid #e6e6e6; border-radius:8px; padding:20px; max-width:500px;">
        <p class="text-muted small mb-3">Tekan tombol di bawah untuk membuat backup semua file Anda. File akan diunduh sebagai ZIP.</p>
        <form method="post">
            <button type="submit" name="start_backup" class="btn btn-primary">Mulai Backup</button>
        </form>
    </div>
</div>
</body>
</html>
