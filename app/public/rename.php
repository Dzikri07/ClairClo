<?php
/**
 * rename.php - rename a file's display name
 * Accepts JSON body { file_id, new_name } or form data.
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
$newName = $input['new_name'] ?? $_POST['new_name'] ?? null;

if (!$fileId || !is_numeric($fileId) || !$newName || trim($newName) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Ensure file exists and belongs to user via file_storage_paths
    $row = fetchOne('SELECT id, original_filename FROM file_storage_paths WHERE file_id = ? AND user_id = ?', [$fileId, $userId]);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'File not found']);
        exit;
    }

    // Determine existing extension from stored record (original_filename or stored filename)
    $existingName = $row['original_filename'] ?? null;
    if (!$existingName) {
        // try to fetch from files table
        try {
            $f = fetchOne('SELECT filename, original_name FROM files WHERE id = ?', [$fileId]);
            $existingName = $f['original_name'] ?? $f['filename'] ?? $existingName;
        } catch (Exception $e) {
            // ignore
        }
    }

    $existingExt = '';
    if ($existingName) {
        $existingExt = pathinfo($existingName, PATHINFO_EXTENSION);
    }

    // If provided new name doesn't include an extension, append the existing one automatically
    $providedExt = pathinfo($newName, PATHINFO_EXTENSION);
    $finalName = $newName;
    if (empty($providedExt) && !empty($existingExt)) {
        $finalName = $newName . '.' . $existingExt;
    }

    // Update original_filename in file_storage_paths with finalized name
    $stmt = getDB()->prepare('UPDATE file_storage_paths SET original_filename = ?, updated_at = NOW() WHERE file_id = ? AND user_id = ?');
    $stmt->execute([ $finalName, $fileId, $userId ]);

    // Optionally update files.original_name if that column exists
    try {
        // Attempt to update files.original_name (if present)
        $db = getDB();
        $cols = $db->query("SHOW COLUMNS FROM files LIKE 'original_name'")->fetchAll();
        if (count($cols) > 0) {
            $stmt2 = $db->prepare('UPDATE files SET original_name = ? WHERE id = ?');
            $stmt2->execute([$finalName, $fileId]);
        }
    } catch (Exception $e) {
        // ignore if files table doesn't have original_name column
    }

    echo json_encode(['success' => true, 'message' => 'Nama file berhasil diperbarui', 'new_name' => $finalName]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>