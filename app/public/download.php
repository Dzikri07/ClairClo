<?php
/**
 * download.php - File download handler
 * 
 * Usage: download.php?file_id=<ID>
 * 
 * Query Parameters:
 * - file_id (required): The file ID to download
 * 
 * Returns:
 * - File download if user is authenticated and owns the file
 * - 404 error if file not found
 * - 401 error if not authenticated
 * - 403 error if access denied
 */

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/../src/StorageManager.php';

// Check if user is logged in (you may need to adjust this based on your session handling)
session_start();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized. Please login first.']));
}

// Validate file_id parameter
$fileId = $_GET['file_id'] ?? null;

if (!$fileId || !is_numeric($fileId)) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid or missing file_id parameter']));
}

try {
    $storage = new StorageManager();
    
    // Get file and validate access
    $result = $storage->getFileForDownload($fileId, $userId);
    
    if (!$result['success']) {
        http_response_code(404);
        die(json_encode(['error' => $result['message']]));
    }
    
    // Stream the file download
    $storage->streamFileDownload(
        $result['file_path'],
        $result['original_name'],
        $result['mime_type']
    );
    
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
}
?>
