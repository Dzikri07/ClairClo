<?php
// Simple JSON API to list user files with optional search and filters
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../../src/StorageManager.php';

header('Content-Type: application/json');
session_start();
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$q = $_GET['q'] ?? $_POST['q'] ?? null;
$filters = [];
if ($q) $filters['search'] = $q;

try {
    $sm = new StorageManager();
    $rows = $sm->getUserFiles($userId, $filters);

    $items = [];
    $baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if (basename($baseUrl) === 'api') $baseUrl = dirname($baseUrl);
    $baseUrl = $baseUrl === '/' ? '' : $baseUrl;

    foreach ($rows as $r) {
        $url = $baseUrl . '/uploads/' . ($r['thumbnail_path'] ?: $r['file_path']);
        $items[] = [
            'id' => $r['id'],
            'name' => $r['original_name'] ?? $r['filename'],
            'size' => intval($r['size'] ?? $r['file_size'] ?? 0),
            'url' => $url,
            'mime' => $r['mime'] ?? $r['mime_type'] ?? 'application/octet-stream',
            'is_favorite' => intval($r['is_favorite'] ?? 0),
            'category' => ['name' => $r['category_name'] ?? null, 'icon' => $r['category_icon'] ?? null, 'color' => $r['category_color'] ?? null]
        ];
    }

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
