<?php
/**
 * StorageManager.php - File storage management with user and category support
 */

require_once __DIR__ . '/../public/connection.php';

class StorageManager
{
    private $pdo;
    private $uploadDir;
    private $allowedMimeTypes = [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'application/rtf',
        
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/svg+xml',
        'image/webp',
        
        // Videos
        'video/mp4',
        'video/avi',
        'video/quicktime',
        'video/x-matroska',
        'video/x-msvideo',
        
        // Audio
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'audio/mp4',
        
        // Spreadsheets
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        
        // Presentations
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/x-tar',
        'application/gzip',
    ];

    public function __construct()
    {
        $this->pdo = getDB();
        $this->uploadDir = __DIR__ . '/../public/uploads';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Upload file with user and category association
     * Files are saved in /uploads/<user_id>/ directory
     */
    public function uploadFile($fileData, $userId, $description = null)
    {
        try {
            // Validate file
            if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                throw new Exception('Invalid file upload');
            }

            // Get file info
            $originalName = basename($fileData['name']);
            $fileSize = $fileData['size'];
            $mimeType = mime_content_type($fileData['tmp_name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            // Validate basic file properties
            if ($fileSize <= 0) {
                throw new Exception('Invalid file size');
            }

            if (empty($extension)) {
                throw new Exception('File must have an extension');
            }

            // Check user storage quota
            if (!$this->checkStorageQuota($userId, $fileSize)) {
                throw new Exception('Storage quota exceeded');
            }

            // Get category based on extension
            $categoryId = $this->getCategoryByExtension($extension);

            // Validate file against category rules
            if ($categoryId) {
                $this->validateFileAgainstCategory($categoryId, $extension, $fileSize);
            }

            // Generate unique filename
            $filename = $this->generateUniqueFilename($originalName);

            // Create user directory if needed
            $userDir = $this->uploadDir . DIRECTORY_SEPARATOR . 'user_' . $userId;
            if (!is_dir($userDir)) {
                if (!mkdir($userDir, 0777, true)) {
                    throw new Exception('Failed to create user upload directory');
                }
            }

            // Move file to user directory
            $finalPath = $userDir . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($fileData['tmp_name'], $finalPath)) {
                throw new Exception('Failed to move uploaded file');
            }

            // Generate thumbnail for images
            $thumbnailPath = null;
            if (strpos($mimeType, 'image/') === 0) {
                $thumbnailPath = $this->generateThumbnail($finalPath, $userDir, $userId);
            }

            // Save to files table
            $fileId = $this->saveFileMetadata([
                'user_id' => $userId,
                'category_id' => $categoryId,
                'filename' => $filename,
                'original_name' => $originalName,
                'file_path' => 'user_' . $userId . DIRECTORY_SEPARATOR . $filename,
                'thumbnail_path' => $thumbnailPath,
                'mime' => $mimeType,
                'extension' => $extension,
                'size' => $fileSize,
                'description' => $description,
                'download_count' => 0
            ]);

            // Save to file_storage_paths table
            $this->saveStoragePath($fileId, $userId, $filename, $originalName, $fileSize, $mimeType, $extension, $thumbnailPath);

            // Update user storage usage
            $this->updateStorageUsage($userId, $fileSize);

            return [
                'success' => true,
                'file_id' => $fileId,
                'filename' => $filename,
                'original_name' => $originalName,
                'size' => $fileSize,
                'storage_path' => 'user_' . $userId,
                'message' => 'File uploaded successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if user has enough storage quota
     */
    private function checkStorageQuota($userId, $fileSize)
    {
        $user = fetchOne("SELECT storage_quota, storage_used FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            throw new Exception('User not found');
        }

        $availableSpace = $user['storage_quota'] - $user['storage_used'];
        return $fileSize <= $availableSpace;
    }

    /**
     * Get category by file extension
     */
    private function getCategoryByExtension($extension)
    {
        $categories = fetchAll("SELECT id, allowed_extensions FROM file_categories WHERE is_active = 1");
        
        foreach ($categories as $category) {
            $allowedExts = explode(',', $category['allowed_extensions']);
            $allowedExts = array_map('trim', $allowedExts);
            
            if (in_array($extension, $allowedExts) || in_array('*', $allowedExts)) {
                return $category['id'];
            }
        }
        
        // Return 'Others' category if no match found
        $othersCategory = fetchOne("SELECT id FROM file_categories WHERE slug = 'others'");
        return $othersCategory ? $othersCategory['id'] : null;
    }

    /**
     * Validate file against category rules
     */
    private function validateFileAgainstCategory($categoryId, $extension, $fileSize)
    {
        $category = fetchOne("SELECT * FROM file_categories WHERE id = ?", [$categoryId]);
        
        if (!$category) {
            return;
        }

        // Check allowed extensions
        $allowedExts = explode(',', $category['allowed_extensions']);
        $allowedExts = array_map('trim', $allowedExts);
        
        if (!in_array('*', $allowedExts) && !in_array($extension, $allowedExts)) {
            throw new Exception("File type .{$extension} is not allowed in {$category['name']} category");
        }

        // Check max file size
        if ($category['max_file_size'] && $fileSize > $category['max_file_size']) {
            $maxSizeFormatted = $this->formatBytes($category['max_file_size']);
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeFormatted} for {$category['name']}");
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
        $nameOnly = preg_replace('/[^A-Za-z0-9\-_]/', '_', $nameOnly);
        
        $filename = $nameOnly . '_' . time() . '_' . uniqid();
        if ($extension) {
            $filename .= '.' . $extension;
        }
        
        return $filename;
    }

    /**
     * Generate thumbnail for images
     */
    private function generateThumbnail($imagePath, $outputDir, $userId)
    {
        try {
            // If GD functions are not available, skip thumbnail generation
            if (!function_exists('imagecreatetruecolor')) {
                error_log('GD library not available: skipping thumbnail generation');
                return null;
            }

            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return null;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $type = $imageInfo[2];

            // Create image resource based on type; check for function existence
            switch ($type) {
                case IMAGETYPE_JPEG:
                    if (!function_exists('imagecreatefromjpeg')) {
                        error_log('GD JPEG support missing');
                        return null;
                    }
                    $source = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    if (!function_exists('imagecreatefrompng')) {
                        error_log('GD PNG support missing');
                        return null;
                    }
                    $source = imagecreatefrompng($imagePath);
                    break;
                case IMAGETYPE_GIF:
                    if (!function_exists('imagecreatefromgif')) {
                        error_log('GD GIF support missing');
                        return null;
                    }
                    $source = imagecreatefromgif($imagePath);
                    break;
                default:
                    return null;
            }

            // Calculate thumbnail dimensions
            $thumbWidth = 200;
            $thumbHeight = ($height / $width) * $thumbWidth;

            // Create thumbnail
            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            
            // Preserve transparency for PNG and GIF
            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
            }

            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

            // Save thumbnail
            $thumbFilename = 'thumb_' . basename($imagePath);
            $thumbPath = $outputDir . DIRECTORY_SEPARATOR . $thumbFilename;

            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($thumb, $thumbPath, 85);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($thumb, $thumbPath, 8);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($thumb, $thumbPath);
                    break;
            }

            imagedestroy($source);
            imagedestroy($thumb);

            return str_replace($this->uploadDir . DIRECTORY_SEPARATOR, '', $thumbPath);

        } catch (Exception $e) {
            error_log("Thumbnail generation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Save file metadata to database
     */
    private function saveFileMetadata($data)
    {
        return insert('files', $data);
    }

    /**
     * Update user storage usage
     */
    private function updateStorageUsage($userId, $additionalSize)
    {
        query("UPDATE users SET storage_used = storage_used + ? WHERE id = ?", [$additionalSize, $userId]);
    }

    /**
     * Get user files
     */
    public function getUserFiles($userId, $filters = [])
    {
        $sql = "SELECT f.*, c.name as category_name, c.icon as category_icon, c.color as category_color 
                FROM files f 
                LEFT JOIN file_categories c ON f.category_id = c.id 
                WHERE f.user_id = ? AND f.is_trashed = 0";
        
        $params = [$userId];

        // Apply filters
        if (isset($filters['category_id'])) {
            $sql .= " AND f.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (isset($filters['is_favorite'])) {
            $sql .= " AND f.is_favorite = ?";
            $params[] = $filters['is_favorite'];
        }

        if (isset($filters['search'])) {
            $sql .= " AND (f.original_name LIKE ? OR f.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY f.uploaded_at DESC";

        return fetchAll($sql, $params);
    }

    /**
     * Get user storage info
     */
    public function getUserStorageInfo($userId)
    {
        $user = fetchOne("SELECT storage_quota, storage_used FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return null;
        }

        $available = $user['storage_quota'] - $user['storage_used'];
        $usedPercent = ($user['storage_used'] / $user['storage_quota']) * 100;

        return [
            'quota' => $user['storage_quota'],
            'used' => $user['storage_used'],
            'available' => $available,
            'used_percent' => round($usedPercent, 2),
            'quota_formatted' => $this->formatBytes($user['storage_quota']),
            'used_formatted' => $this->formatBytes($user['storage_used']),
            'available_formatted' => $this->formatBytes($available)
        ];
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Delete file
     */
    public function deleteFile($fileId, $userId)
    {
        // Keep backward compatibility: perform permanent delete
        return $this->permanentDeleteFile($fileId, $userId);
    }

    /**
     * Soft-delete a file (move to trash). Does not remove physical file nor free storage.
     */
    public function softDeleteFile($fileId, $userId)
    {
        $file = fetchOne("SELECT * FROM files WHERE id = ? AND user_id = ?", [$fileId, $userId]);
        if (!$file) {
            return ['success' => false, 'message' => 'File not found'];
        }

        try {
            // mark file as trashed (migration uses `trashed_at`)
            update('files', ['is_trashed' => 1, 'trashed_at' => date('Y-m-d H:i:s')], 'id = ?', [$fileId]);

            // mark storage path entry as deleted (soft)
            $this->softDeleteStoragePath($fileId);

            return ['success' => true, 'message' => 'File moved to trash'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to move file to trash: ' . $e->getMessage()];
        }
    }

    /**
     * Permanently delete a file: remove physical files, update storage usage and delete DB records.
     */
    public function permanentDeleteFile($fileId, $userId)
    {
        $file = fetchOne("SELECT * FROM files WHERE id = ? AND user_id = ?", [$fileId, $userId]);
        
        if (!$file) {
            return ['success' => false, 'message' => 'File not found'];
        }

        try {
            // Delete physical files
            $this->deletePhysicalFile($file);

            // Update storage usage
            query("UPDATE users SET storage_used = storage_used - ? WHERE id = ?", [$file['size'], $userId]);

            // Mark as deleted in file_storage_paths (soft delete) if present
            $this->softDeleteStoragePath($fileId);

            // Delete from files table
            delete('files', 'id = ?', [$fileId]);

            return ['success' => true, 'message' => 'File permanently deleted', 'freed_space' => $file['size']];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to permanently delete file: ' . $e->getMessage()];
        }
    }

    /**
     * Delete physical files from disk
     */
    private function deletePhysicalFile($file)
    {
        // Delete main file
        $filePath = $this->uploadDir . DIRECTORY_SEPARATOR . $file['file_path'];
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                throw new Exception('Failed to delete main file');
            }
        }

        // Delete thumbnail if exists
        if ($file['thumbnail_path']) {
            $thumbPath = $this->uploadDir . DIRECTORY_SEPARATOR . $file['thumbnail_path'];
            if (file_exists($thumbPath)) {
                unlink($thumbPath); // Don't throw error if thumbnail fails
            }
        }
    }

    /**
     * Get file for download with validation
     */
    public function getFileForDownload($fileId, $userId)
    {
        $file = fetchOne(
            "SELECT f.*, c.name as category_name, fsp.original_filename as storage_original_filename 
             FROM files f 
             LEFT JOIN file_categories c ON f.category_id = c.id 
             LEFT JOIN file_storage_paths fsp ON f.id = fsp.file_id AND f.user_id = fsp.user_id
             WHERE f.id = ? AND f.user_id = ? AND f.is_trashed = 0",
            [$fileId, $userId]
        );

        if (!$file) {
            return ['success' => false, 'message' => 'File not found'];
        }

        $filePath = $this->uploadDir . DIRECTORY_SEPARATOR . $file['file_path'];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['success' => false, 'message' => 'File not accessible'];
        }

        // Update download count and last accessed timestamp
        $this->updateDownloadStats($fileId);

        // Prefer original name from files table, fallback to storage path record, then stored filename
        $originalName = $file['original_name'] ?? $file['storage_original_filename'] ?? $file['filename'] ?? basename($filePath);

        return [
            'success' => true,
            'file_path' => $filePath,
            'original_name' => $originalName,
            'file_size' => filesize($filePath),
            'mime_type' => $file['mime'],
            'file_id' => $fileId
        ];
    }

    /**
     * Update download statistics
     */
    private function updateDownloadStats($fileId)
    {
        query(
            "UPDATE files SET download_count = download_count + 1, last_accessed = NOW() WHERE id = ?",
            [$fileId]
        );

        query(
            "UPDATE file_storage_paths SET download_count = download_count + 1, last_downloaded = NOW() WHERE file_id = ?",
            [$fileId]
        );
    }

    /**
     * Save storage path metadata
     */
    private function saveStoragePath($fileId, $userId, $filename, $originalName, $fileSize, $mimeType, $extension, $thumbnailPath)
    {
        try {
            insert('file_storage_paths', [
                'file_id' => $fileId,
                'user_id' => $userId,
                'storage_path' => 'uploads' . DIRECTORY_SEPARATOR . 'user_' . $userId,
                'file_path' => 'user_' . $userId . DIRECTORY_SEPARATOR . $filename,
                'thumbnail_path' => $thumbnailPath,
                'original_filename' => $originalName,
                'stored_filename' => $filename,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'file_extension' => $extension,
                'download_count' => 0,
                'is_deleted' => 0
            ]);
        } catch (Exception $e) {
            error_log("Failed to save storage path: " . $e->getMessage());
            // Don't throw error - continue with file upload even if storage path tracking fails
        }
    }

    /**
     * Soft delete storage path entry
     */
    private function softDeleteStoragePath($fileId)
    {
        try {
            update('file_storage_paths', [
                'is_deleted' => 1,
                'deleted_at' => date('Y-m-d H:i:s')
            ], 'file_id = ?', [$fileId]);
        } catch (Exception $e) {
            error_log("Failed to soft delete storage path: " . $e->getMessage());
        }
    }

    /**
     * Get file info for download
     */
    public function getFileInfo($fileId, $userId)
    {
        return fetchOne(
            "SELECT f.*, c.name as category_name 
             FROM files f 
             LEFT JOIN file_categories c ON f.category_id = c.id 
             WHERE f.id = ? AND f.user_id = ? AND f.is_trashed = 0",
            [$fileId, $userId]
        );
    }

    /**
     * Stream file download
     */
    public function streamFileDownload($filePath, $originalName, $mimeType)
    {
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers
        $filename = basename($originalName ?: $filePath);
        // Basic content type
        header('Content-Type: ' . ($mimeType ?: 'application/octet-stream'));
        // Content-Disposition with RFC5987 filename* for UTF-8 compatibility
        $safeFilename = str_replace(['"','\\'], ['',''], $filename);
        $disposition = "attachment; filename=\"" . $safeFilename . "\"; filename*=UTF-8''" . rawurlencode($safeFilename);
        header('Content-Disposition: ' . $disposition);
        header('Content-Length: ' . filesize($filePath));
        header('Pragma: public');
        header('Cache-Control: public, must-revalidate');
        header('Expires: 0');

        // Read and output file in chunks
        $chunkSize = 1024 * 1024; // 1MB chunks
        $handle = fopen($filePath, 'rb');

        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, $chunkSize);
                flush();
            }
            fclose($handle);
        }

        exit;
    }
}
