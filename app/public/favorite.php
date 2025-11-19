<?php
/**
 * favorite.php - toggle favorite status for a file
 * Accepts POST JSON or form with `file_id`.
 */
require_once __DIR__ . '/connection.php';

header('Content-Type: application/json');
session_start();
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$fileId = $input['file_id'] ?? $_POST['file_id'] ?? null;
if (!$fileId || !is_numeric($fileId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file_id']);
    exit;
}

try {
    // Check ownership
    $file = fetchOne('SELECT id, is_favorite FROM files WHERE id = ? AND user_id = ?', [$fileId, $userId]);
    if (!$file) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'File not found']);
        exit;
    }

    $new = $file['is_favorite'] ? 0 : 1;
    update('files', ['is_favorite' => $new], 'id = ?', [$fileId]);

    // Recompute counts
    $total = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0', [$userId]);
    $favorites = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0 AND is_favorite = 1', [$userId]);
    $trash = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 1', [$userId]);

    echo json_encode([
        'success' => true,
        'is_favorite' => $new,
        'counts' => [
            'total' => intval($total['cnt'] ?? 0),
            'favorites' => intval($favorites['cnt'] ?? 0),
            'trash' => intval($trash['cnt'] ?? 0)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
