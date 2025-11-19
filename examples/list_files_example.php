<?php
/**
 * Example: List User Files with Categories
 * Demonstrates how to retrieve and display user files
 */

require_once __DIR__ . '/../app/src/StorageManager.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

$userId = $_SESSION['user_id'];
$storageManager = new StorageManager();

// Get filters from query string
$filters = [];
if (isset($_GET['category'])) {
    $filters['category_id'] = $_GET['category'];
}
if (isset($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['favorites'])) {
    $filters['is_favorite'] = 1;
}

// Get user files
$files = $storageManager->getUserFiles($userId, $filters);

// Get all categories for filter
$categories = fetchAll("SELECT * FROM file_categories WHERE is_active = 1 ORDER BY name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Files</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .header { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .filters { background: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .category-filter { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .category-btn { padding: 8px 15px; border: 1px solid #ddd; border-radius: 20px; background: white; cursor: pointer; text-decoration: none; color: #333; }
        .category-btn:hover, .category-btn.active { background: #4CAF50; color: white; border-color: #4CAF50; }
        .search-box { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 10px; }
        .files-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .file-card { background: white; border-radius: 8px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .file-card:hover { transform: translateY(-5px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .file-icon { font-size: 48px; text-align: center; margin-bottom: 10px; }
        .file-name { font-weight: bold; margin-bottom: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-info { font-size: 12px; color: #666; margin-bottom: 5px; }
        .file-category { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; color: white; margin-top: 5px; }
        .file-actions { margin-top: 10px; display: flex; gap: 5px; }
        .btn { padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; }
        .btn-download { background: #4CAF50; color: white; }
        .btn-delete { background: #f44336; color: white; }
        .no-files { text-align: center; padding: 40px; background: white; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-folder"></i> My Files</h1>
        <p>Total Files: <?= count($files) ?></p>
    </div>

    <div class="filters">
        <h3>Filter by Category</h3>
        <div class="category-filter">
            <a href="?" class="category-btn <?= !isset($_GET['category']) ? 'active' : '' ?>">
                <i class="fas fa-th"></i> All Files
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= $cat['id'] ?>" 
                   class="category-btn <?= isset($_GET['category']) && $_GET['category'] == $cat['id'] ? 'active' : '' ?>"
                   style="<?= isset($_GET['category']) && $_GET['category'] == $cat['id'] ? 'background: ' . $cat['color'] : '' ?>">
                    <i class="<?= $cat['icon'] ?>"></i> <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <form method="GET">
            <input type="text" name="search" class="search-box" 
                   placeholder="Search files..." 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </form>
    </div>

    <?php if (empty($files)): ?>
        <div class="no-files">
            <i class="fas fa-folder-open" style="font-size: 64px; color: #ddd;"></i>
            <h2>No files found</h2>
            <p>Upload your first file to get started!</p>
        </div>
    <?php else: ?>
        <div class="files-grid">
            <?php foreach ($files as $file): ?>
                <div class="file-card">
                    <div class="file-icon" style="color: <?= $file['category_color'] ?? '#666' ?>">
                        <i class="<?= $file['category_icon'] ?? 'fa-file' ?>"></i>
                    </div>
                    <div class="file-name" title="<?= htmlspecialchars($file['original_name']) ?>">
                        <?= htmlspecialchars($file['original_name']) ?>
                    </div>
                    <div class="file-info">
                        <i class="fas fa-hdd"></i> <?= formatFileSize($file['size']) ?>
                    </div>
                    <div class="file-info">
                        <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($file['uploaded_at'])) ?>
                    </div>
                    <?php if ($file['category_name']): ?>
                        <span class="file-category" style="background: <?= $file['category_color'] ?>">
                            <?= htmlspecialchars($file['category_name']) ?>
                        </span>
                    <?php endif; ?>
                    <div class="file-actions">
                        <button class="btn btn-download" onclick="downloadFile(<?= $file['id'] ?>)">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <button class="btn btn-delete" onclick="deleteFile(<?= $file['id'] ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <script>
        function downloadFile(fileId) {
            window.location.href = 'download.php?id=' + fileId;
        }

        function deleteFile(fileId) {
            if (confirm('Are you sure you want to delete this file?')) {
                window.location.href = 'delete.php?id=' + fileId;
            }
        }
    </script>
</body>
</html>

<?php
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
