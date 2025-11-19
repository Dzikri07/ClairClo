<?php
/**
 * delete.php - File deletion handler
 * 
 * Usage: DELETE request to delete.php with JSON body
 * 
 * Request:
 * {
 *   "file_id": <ID>
 * }
 * 
 * Response:
 * {
 *   "success": true/false,
 *   "message": "...",
 *   "freed_space": <bytes> (if successful)
 * }
 * 
 * HTTP Status Codes:
 * - 200: Success
 * - 400: Bad request (missing/invalid parameters)
 * - 401: Unauthorized (not logged in)
 * - 403: Forbidden (file not owned by user)
 * - 404: Not found
 * - 500: Server error
 */

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/../src/StorageManager.php';

// Set response content type
header('Content-Type: application/json');

// Check if user is logged in
session_start();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    http_response_code(401);
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login first.'
    ]));
}

// Accept both GET and POST requests
$fileId = null;
$action = 'delete'; // default action

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Try JSON body first
    $input = json_decode(file_get_contents('php://input'), true);
    $fileId = $input['file_id'] ?? null;
    $action = $input['action'] ?? 'delete';
    
    // Fallback to POST data
    if (!$fileId) {
        $fileId = $_POST['file_id'] ?? null;
        $action = $_POST['action'] ?? 'delete';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Also support GET requests
    $fileId = $_GET['file_id'] ?? null;
    $action = $_GET['action'] ?? 'delete';
}

// Validate file_id parameter
if (!$fileId || !is_numeric($fileId)) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'message' => 'Invalid or missing file_id parameter'
    ]));
}

try {
    $storage = new StorageManager();

    // fetch file to inspect trash status
    $fileRow = fetchOne('SELECT id, is_trashed, trashed_at FROM files WHERE id = ? AND user_id = ?', [$fileId, $userId]);
    if (!$fileRow) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'File not found']));
    }

    $isTrashed = intval($fileRow['is_trashed'] ?? 0);
    $trashedAt = $fileRow['trashed_at'] ?? null;

    // Handle restore action
    if ($action === 'restore') {
        if ($isTrashed === 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'File is not in trash']));
        }
        
        // Restore the file from trash
        $stmt = getDB()->prepare('UPDATE files SET is_trashed = 0, trashed_at = NULL WHERE id = ? AND user_id = ?');
        $stmt->execute([$fileId, $userId]);
        
        // Also update file_storage_paths to mark it as not deleted
        $stmt = getDB()->prepare('UPDATE file_storage_paths SET is_deleted = 0, deleted_at = NULL WHERE file_id = ? AND user_id = ?');
        $stmt->execute([$fileId, $userId]);
        
        $result = ['success' => true, 'message' => 'File successfully restored'];
    } 
    // If not trashed yet -> perform soft delete (move to trash)
    elseif ($isTrashed === 0) {
        $result = $storage->softDeleteFile($fileId, $userId);
    } else {
        // Already trashed: check for permanent deletion request or age
        $forcePermanent = false;
        // allow permanent flag from JSON body, POST data, or query param to force immediate deletion
        if ((isset($input) && isset($input['permanent']) && intval($input['permanent']) === 1)
            || (isset($_POST['permanent']) && intval($_POST['permanent']) === 1)
            || (isset($_GET['permanent']) && intval($_GET['permanent']) === 1)) {
            $forcePermanent = true;
        }

        // Check if trashed_at older than 30 days
        $olderThan30 = false;
        if ($trashedAt) {
            $deletedTs = strtotime($trashedAt);
            if ($deletedTs !== false && time() - $deletedTs >= 30 * 24 * 3600) {
                $olderThan30 = true;
            }
        }

        if ($forcePermanent || $olderThan30) {
            $result = $storage->permanentDeleteFile($fileId, $userId);
        } else {
            $when = $trashedAt ? date('Y-m-d H:i:s', strtotime($trashedAt) + 30 * 24 * 3600) : 'in 30 days';
            $result = ['success' => false, 'message' => 'File is in trash and will be permanently deleted on ' . $when];
        }
    }

    if (!$result['success']) {
        // return whatever message the StorageManager provided
        http_response_code(400);
        die(json_encode($result));
    }

    // Recompute counts after operation
    $total = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0', [$userId]);
    $favorites = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0 AND is_favorite = 1', [$userId]);
    $trash = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 1', [$userId]);

    $result['counts'] = [
        'total' => intval($total['cnt'] ?? 0),
        'favorites' => intval($favorites['cnt'] ?? 0),
        'trash' => intval($trash['cnt'] ?? 0)
    ];

    http_response_code(200);
    die(json_encode($result));

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]));
}
?>
