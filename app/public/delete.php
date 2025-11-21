<?php
/**
 * delete.php - File deletion handler
 * 
 * Usage: DELETE request to delete.php with JSON body
 * 
 * Request:
 * {
 *   "file_id": <ID> OR "file_ids": [array_of_ids],
 *   "action": "delete|restore|delete_permanent_all",
 *   "delete_all": true/false,
 *   "permanent": true/false
 * }
 * 
 * Response:
 * {
 *   "success": true/false,
 *   "message": "...",
 *   "freed_space": <bytes> (if successful),
 *   "counts": {
 *     "total": x,
 *     "favorites": y,
 *     "trash": z
 *   }
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
$fileIds = [];
$action = 'delete'; // default action
$deleteAll = false;
$permanent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Try JSON body first
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input) {
        $fileId = $input['file_id'] ?? null;
        $fileIds = $input['file_ids'] ?? [];
        $action = $input['action'] ?? 'delete';
        $deleteAll = $input['delete_all'] ?? false;
        $permanent = $input['permanent'] ?? false;
    }
    
    // Fallback to POST data
    if (!$fileId && empty($fileIds)) {
        $fileId = $_POST['file_id'] ?? null;
        $fileIds = $_POST['file_ids'] ?? [];
        $action = $_POST['action'] ?? 'delete';
        $deleteAll = $_POST['delete_all'] ?? false;
        $permanent = $_POST['permanent'] ?? false;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Also support GET requests
    $fileId = $_GET['file_id'] ?? null;
    $action = $_GET['action'] ?? 'delete';
    $permanent = $_GET['permanent'] ?? false;
}

// Convert single fileId to array for consistent processing
if ($fileId && empty($fileIds)) {
    $fileIds = [$fileId];
}

// Validate parameters for delete all action
if ($deleteAll && $action === 'delete_permanent_all') {
    // For delete all, we don't need specific file IDs
    // We'll get all trash files for this user
} elseif (empty($fileIds)) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'message' => 'Invalid or missing file_id/file_ids parameter'
    ]));
}

// Validate file IDs are numeric
foreach ($fileIds as $id) {
    if (!is_numeric($id)) {
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'message' => 'Invalid file_id parameter: ' . $id
        ]));
    }
}

try {
    $storage = new StorageManager();
    $results = [];

    // Handle delete all action
    if ($deleteAll && $action === 'delete_permanent_all') {
        // Get all trashed files for this user
        $trashedFiles = fetchAll('SELECT id FROM files WHERE user_id = ? AND is_trashed = 1', [$userId]);
        
        if (empty($trashedFiles)) {
            http_response_code(404);
            die(json_encode([
                'success' => false, 
                'message' => 'No files found in trash'
            ]));
        }
        
        $fileIds = array_column($trashedFiles, 'id');
        $deletedCount = 0;
        $totalFreedSpace = 0;
        
        foreach ($fileIds as $fileId) {
            $result = $storage->permanentDeleteFile($fileId, $userId);
            if ($result['success']) {
                $deletedCount++;
                $totalFreedSpace += $result['freed_space'] ?? 0;
            }
        }
        
        $results = [
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} files permanently",
            'deleted_count' => $deletedCount,
            'freed_space' => $totalFreedSpace
        ];
    } 
    // Handle multiple files restoration
    elseif ($action === 'restore' && count($fileIds) > 1) {
        $restoredCount = 0;
        
        foreach ($fileIds as $fileId) {
            $fileRow = fetchOne('SELECT id, is_trashed FROM files WHERE id = ? AND user_id = ?', [$fileId, $userId]);
            
            if (!$fileRow) {
                continue; // Skip if file not found
            }
            
            if (intval($fileRow['is_trashed']) === 0) {
                continue; // Skip if file is not in trash
            }
            
            // Restore the file from trash
            $stmt = getDB()->prepare('UPDATE files SET is_trashed = 0, trashed_at = NULL WHERE id = ? AND user_id = ?');
            $stmt->execute([$fileId, $userId]);
            
            // Also update file_storage_paths to mark it as not deleted
            $stmt = getDB()->prepare('UPDATE file_storage_paths SET is_deleted = 0, deleted_at = NULL WHERE file_id = ? AND user_id = ?');
            $stmt->execute([$fileId, $userId]);
            
            $restoredCount++;
        }
        
        $results = [
            'success' => true,
            'message' => "Successfully restored {$restoredCount} files",
            'restored_count' => $restoredCount
        ];
    }
    // Handle single file operations
    else {
        foreach ($fileIds as $fileId) {
            $fileRow = fetchOne('SELECT id, is_trashed, trashed_at FROM files WHERE id = ? AND user_id = ?', [$fileId, $userId]);
            if (!$fileRow) {
                $results[$fileId] = ['success' => false, 'message' => 'File not found'];
                continue;
            }

            $isTrashed = intval($fileRow['is_trashed'] ?? 0);
            $trashedAt = $fileRow['trashed_at'] ?? null;

            // Handle restore action
            if ($action === 'restore') {
                if ($isTrashed === 0) {
                    $results[$fileId] = ['success' => false, 'message' => 'File is not in trash'];
                    continue;
                }
                
                // Restore the file from trash
                $stmt = getDB()->prepare('UPDATE files SET is_trashed = 0, trashed_at = NULL WHERE id = ? AND user_id = ?');
                $stmt->execute([$fileId, $userId]);
                
                // Also update file_storage_paths to mark it as not deleted
                $stmt = getDB()->prepare('UPDATE file_storage_paths SET is_deleted = 0, deleted_at = NULL WHERE file_id = ? AND user_id = ?');
                $stmt->execute([$fileId, $userId]);
                
                $results[$fileId] = ['success' => true, 'message' => 'File successfully restored'];
            } 
            // If not trashed yet -> perform soft delete (move to trash)
            elseif ($isTrashed === 0) {
                $result = $storage->softDeleteFile($fileId, $userId);
                $results[$fileId] = $result;
            } else {
                // Already trashed: check for permanent deletion request or age
                $forcePermanent = $permanent;

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
                    $results[$fileId] = $result;
                } else {
                    $when = $trashedAt ? date('Y-m-d H:i:s', strtotime($trashedAt) + 30 * 24 * 3600) : 'in 30 days';
                    $results[$fileId] = ['success' => false, 'message' => 'File is in trash and will be permanently deleted on ' . $when];
                }
            }
        }

        // For single file operations, return the first result
        if (count($fileIds) === 1) {
            $results = $results[$fileIds[0]];
        } else {
            // For multiple files, return aggregated result
            $successCount = 0;
            $totalFreedSpace = 0;
            $messages = [];
            
            foreach ($results as $fileId => $result) {
                if ($result['success']) {
                    $successCount++;
                    $totalFreedSpace += $result['freed_space'] ?? 0;
                } else {
                    $messages[] = "File {$fileId}: " . $result['message'];
                }
            }
            
            $results = [
                'success' => $successCount > 0,
                'message' => "Processed " . count($fileIds) . " files: {$successCount} successful, " . (count($fileIds) - $successCount) . " failed",
                'success_count' => $successCount,
                'failed_count' => count($fileIds) - $successCount,
                'freed_space' => $totalFreedSpace,
                'details' => $messages
            ];
        }
    }

    // Check if any operation failed
    if (isset($results['success']) && !$results['success']) {
        http_response_code(400);
        die(json_encode($results));
    }

    // Recompute counts after operation
    $total = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0', [$userId]);
    $favorites = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0 AND is_favorite = 1', [$userId]);
    $trash = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 1', [$userId]);

    $results['counts'] = [
        'total' => intval($total['cnt'] ?? 0),
        'favorites' => intval($favorites['cnt'] ?? 0),
        'trash' => intval($trash['cnt'] ?? 0)
    ];

    http_response_code(200);
    die(json_encode($results));

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]));
}
?>