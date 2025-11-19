<?php
/**
 * Example: File Upload with StorageManager
 * Demonstrates how to use the new StorageManager class
 */

require_once __DIR__ . '/../app/src/StorageManager.php';

// Start session to get user ID
session_start();

// Example: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

$userId = $_SESSION['user_id'];
$storageManager = new StorageManager();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    
    $description = $_POST['description'] ?? null;
    
    // Upload file
    $result = $storageManager->uploadFile($_FILES['file'], $userId, $description);
    
    if ($result['success']) {
        echo "File uploaded successfully!<br>";
        echo "File ID: " . $result['file_id'] . "<br>";
        echo "Filename: " . $result['filename'] . "<br>";
    } else {
        echo "Upload failed: " . $result['message'] . "<br>";
    }
}

// Get user storage info
$storageInfo = $storageManager->getUserStorageInfo($userId);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload File Example</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .storage-info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .progress-bar { width: 100%; height: 20px; background: #ddd; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: #4CAF50; transition: width 0.3s; }
        .upload-form { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        input[type="file"] { margin: 10px 0; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>File Upload Example</h1>
    
    <?php if ($storageInfo): ?>
    <div class="storage-info">
        <h3>Storage Usage</h3>
        <p>
            Used: <?= $storageInfo['used_formatted'] ?> / <?= $storageInfo['quota_formatted'] ?>
            (<?= $storageInfo['used_percent'] ?>%)
        </p>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= $storageInfo['used_percent'] ?>%"></div>
        </div>
        <p>Available: <?= $storageInfo['available_formatted'] ?></p>
    </div>
    <?php endif; ?>
    
    <div class="upload-form">
        <h3>Upload New File</h3>
        <form method="POST" enctype="multipart/form-data">
            <div>
                <label>Select File:</label><br>
                <input type="file" name="file" required>
            </div>
            <div>
                <label>Description (optional):</label><br>
                <textarea name="description" rows="3" style="width: 100%; padding: 5px;"></textarea>
            </div>
            <button type="submit">Upload File</button>
        </form>
    </div>
    
    <div style="margin-top: 30px;">
        <h3>Supported File Types:</h3>
        <ul>
            <li><strong>Documents:</strong> PDF, DOC, DOCX, TXT, RTF (max 50MB)</li>
            <li><strong>Images:</strong> JPG, PNG, GIF, SVG, WEBP (max 10MB)</li>
            <li><strong>Videos:</strong> MP4, AVI, MOV, MKV (max 500MB)</li>
            <li><strong>Audio:</strong> MP3, WAV, OGG, M4A (max 50MB)</li>
            <li><strong>Spreadsheets:</strong> XLSX, XLS, CSV (max 20MB)</li>
            <li><strong>Presentations:</strong> PPT, PPTX (max 50MB)</li>
            <li><strong>Archives:</strong> ZIP, RAR, 7Z (max 100MB)</li>
            <li><strong>Code:</strong> PHP, JS, HTML, CSS, JSON (max 5MB)</li>
        </ul>
    </div>
</body>
</html>
