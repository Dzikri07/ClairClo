<?php
// file_functions.php - helper functions for file listing and upload

/**
 * Check if current user is an admin
 * @return bool
 */
function is_admin()
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get all files for a specific user
 * @param int|null $userId
 * @return array
 */
function get_user_files($userId)
{
    if (!$userId) {
        return [];
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT f.*, u.username AS owner,
                   CASE 
                       WHEN f.mime LIKE 'image/%' THEN 'image'
                       WHEN f.mime LIKE 'video/%' THEN 'video'
                       WHEN f.mime LIKE 'application/pdf' OR f.mime LIKE 'application/msword' OR f.mime LIKE 'application/vnd%' THEN 'document'
                       ELSE 'other'
                   END AS category,
                   CASE 
                       WHEN f.mime LIKE 'image/%' THEN 'image.svg'
                       WHEN f.mime LIKE 'video/%' THEN 'video.svg'
                       WHEN f.mime LIKE 'application/pdf' OR f.mime LIKE 'application/msword' OR f.mime LIKE 'application/vnd%' THEN 'doc.svg'
                       ELSE 'file.svg'
                   END AS icon
            FROM files f 
            JOIN users u ON f.user_id = u.id 
            WHERE f.user_id = ? AND f.is_trashed = 0 
            ORDER BY f.modified_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in get_user_files: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all files (admin view)
 * @return array
 */
function get_all_files()
{
    try {
        $pdo = getDB();
        $stmt = $pdo->query("
            SELECT f.*, u.username AS owner,
                   CASE 
                       WHEN f.mime LIKE 'image/%' THEN 'image'
                       WHEN f.mime LIKE 'video/%' THEN 'video'
                       WHEN f.mime LIKE 'application/pdf' OR f.mime LIKE 'application/msword' OR f.mime LIKE 'application/vnd%' THEN 'document'
                       ELSE 'other'
                   END AS category,
                   CASE 
                       WHEN f.mime LIKE 'image/%' THEN 'image.svg'
                       WHEN f.mime LIKE 'video/%' THEN 'video.svg'
                       WHEN f.mime LIKE 'application/pdf' OR f.mime LIKE 'application/msword' OR f.mime LIKE 'application/vnd%' THEN 'doc.svg'
                       ELSE 'file.svg'
                   END AS icon
            FROM files f 
            JOIN users u ON f.user_id = u.id 
            WHERE f.is_trashed = 0 
            ORDER BY f.modified_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in get_all_files: " . $e->getMessage());
        return [];
    }
}

/**
 * Get favorite files for a specific user
 * @param int|null $userId
 * @return array
 */
function get_favorite_files_for_user($userId)
{
    if (!$userId) {
        return [];
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT f.*, u.username AS owner,
                   CASE 
                       WHEN f.mime LIKE 'image/%' THEN 'image'
                       WHEN f.mime LIKE 'video/%' THEN 'video'
                       WHEN f.mime LIKE 'application/pdf' OR f.mime LIKE 'application/msword' OR f.mime LIKE 'application/vnd%' THEN 'document'
                       ELSE 'other'
                   END AS category,
                   CASE 
                       WHEN f.mime LIKE 'image/%' THEN 'image.svg'
                       WHEN f.mime LIKE 'video/%' THEN 'video.svg'
                       WHEN f.mime LIKE 'application/pdf' OR f.mime LIKE 'application/msword' OR f.mime LIKE 'application/vnd%' THEN 'doc.svg'
                       ELSE 'file.svg'
                   END AS icon
            FROM files f 
            JOIN users u ON f.user_id = u.id 
            WHERE f.user_id = ? AND f.is_trashed = 0 AND f.is_favorite = 1
            ORDER BY f.modified_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in get_favorite_files_for_user: " . $e->getMessage());
        return [];
    }
}

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

    return ['success' => true, 'file' => basename($target)];
}
