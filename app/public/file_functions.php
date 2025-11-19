<?php
// file_functions.php - helper functions for file listing and upload

function get_upload_dir()
{
    // Prefer per-user upload directory when session user_id is available
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        $dir = __DIR__ . '/uploads/user_' . intval($userId);
    } else {
        $dir = __DIR__ . '/uploads';
    }

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function list_files()
{
    $dir = get_upload_dir();
    $items = [];
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = $dir . '/' . $f;
        if (is_file($path)) {
            $items[] = [
                'name' => $f,
                'size' => filesize($path),
                'url' => 'uploads/' . rawurlencode($f),
                'mime' => mime_content_type($path)
            ];
        }
    }
    return $items;
}

function human_filesize($bytes, $decimals = 2)
{
    $sz = 'BKMGTP';
    $factor = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    $factor = (int) $factor;
    if ($factor === 0) return $bytes . ' B';
    $unit = isset($sz[$factor]) ? $sz[$factor] : $sz[strlen($sz) - 1];
    return sprintf("%.{$decimals}f %s", $bytes / pow(1024, $factor), $unit);
}

function handle_upload($field = 'upload_file')
{
    if (!isset($_FILES[$field])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . $file['error']];
    }

    $upload_dir = get_upload_dir();
    $original = basename($file['name']);
    // sanitize filename
    $original = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $original);
    $target = $upload_dir . '/' . $original;
    $i = 1;
    while (file_exists($target)) {
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        $nameOnly = pathinfo($original, PATHINFO_FILENAME);
        $target = $upload_dir . '/' . $nameOnly . '-' . $i . ($ext ? '.' . $ext : '');
        $i++;
    }

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }

    $result = ['success' => true, 'file' => basename($target)];

    // optional: store metadata in database if config available
    $configPath = __DIR__ . '/config.php';
    if (file_exists($configPath)) {
        try {
            require_once $configPath;
            if (function_exists('get_db_pdo')) {
                $pdo = get_db_pdo();
                if ($pdo) {
                    $stmt = $pdo->prepare('INSERT INTO files (filename, original_name, mime, size) VALUES (?, ?, ?, ?)');
                    $stmt->execute([basename($target), $original, mime_content_type($target), filesize($target)]);
                    $result['db_id'] = $pdo->lastInsertId();
                }
            }
        } catch (Exception $e) {
            // ignore DB errors for now but return file uploaded successfully
            $result['db_error'] = $e->getMessage();
        }
    }

    return $result;
}
