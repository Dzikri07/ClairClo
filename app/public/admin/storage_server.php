<?php
session_start();
require_once __DIR__ . '/../connection.php';

// Tambahkan ini:
$pdo = getDB();
// Admin only
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit;
}

// Gather statistics
$totalUsersRow = fetchOne('SELECT COUNT(*) as cnt FROM users');
$totalUsers = intval($totalUsersRow['cnt'] ?? 0);
$totalStorageUsedRow = fetchOne('SELECT COALESCE(SUM(storage_used),0) as total_used FROM users');
$totalStorageUsed = intval($totalStorageUsedRow['total_used'] ?? 0);

// Files stats
$totalFilesRow = fetchOne('SELECT COUNT(*) as cnt FROM files');
$totalFiles = intval($totalFilesRow['cnt'] ?? 0);

// Server info
$hostname = gethostname();
$os = php_uname();
$phpVersion = PHP_VERSION;
$serverTime = date('Y-m-d H:i:s');

// Disk usage for uploads folder
$uploadDir = __DIR__ . '/../uploads';
$diskTotal = @disk_total_space($uploadDir);
$diskFree = @disk_free_space($uploadDir);

function humanBytes($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = ['B','KB','MB','GB','TB'];
    $i = floor(log($bytes,1024));
    return round($bytes/pow(1024,$i),2) . ' ' . $units[$i];
}
// Storage limit (from settings)
$limitRow = fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'storage_limit'");
$storageLimit = isset($limitRow['setting_value']) ? intval($limitRow['setting_value']) : 0; // bytes

// Hitung sisa
$storageRemaining = ($storageLimit > 0)
    ? max($storageLimit - $totalStorageUsed, 0)
    : 0;

// Hitung persentase
$percentUsed = ($storageLimit > 0)
    ? ($totalStorageUsed / $storageLimit) * 100
    : 0;

// Display aturan persentase
if ($percentUsed > 0 && $percentUsed < 0.1) {
    $percentUsedDisplay = "0.1%";
} else {
    $percentUsedDisplay = round($percentUsed, 2) . "%";
}
// Update storage limit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['limit_value'], $_POST['limit_unit'])) {
    $value = intval($_POST['limit_value']);
    $unit = $_POST['limit_unit'];

    $bytes = ($unit === 'TB')
        ? $value * 1024 * 1024 * 1024 * 1024
        : $value * 1024 * 1024 * 1024;

    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'storage_limit'");
    $stmt->execute([$bytes]);

    header("Location: storage_server.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Storage Server - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color:#f9f9f9;">
<div class="d-flex">
    <?php include __DIR__ . '/../sidebar.php'; ?>
    <div class="main flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold"><i class="fa fa-server me-2 text-primary"></i>Storage Server</h4>
            <small class="text-muted">Status: <?php echo htmlspecialchars($serverTime); ?></small>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card p-3 shadow-sm">
                    <h6 class="mb-1">Total Users</h6>
                    <h3 class="fw-bold"><?php echo $totalUsers; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow-sm">
                    <h6 class="mb-1">Total Files</h6>
                    <h3 class="fw-bold"><?php echo $totalFiles; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow-sm">
                    <h6 class="mb-1">Storage Used</h6>
                    <h3 class="fw-bold"><?php echo humanBytes($totalStorageUsed); ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow-sm">
                    <h6 class="mb-1">Disk Free</h6>
                    <h3 class="fw-bold"><?php echo $diskTotal ? humanBytes($diskFree) : 'N/A'; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
    <div class="card p-3 shadow-sm">
        <h6 class="mb-1">Total Alokasi</h6>

        <div class="fw-bold">
            <?php 
                echo $storageLimit ? humanBytes($storageLimit) : "Unlimited";
            ?>
        </div>

        <small class="text-muted">
            Sisa: <?php echo $storageLimit ? humanBytes($storageRemaining) : "∞"; ?><br>
            Terpakai: <?php echo $percentUsedDisplay; ?>
        </small>
    </div>
</div>


        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">Server Info</div>
                    <div class="card-body">
                        <p><strong>Hostname:</strong> <?php echo htmlspecialchars($hostname); ?></p>
                        <p><strong>OS:</strong> <?php echo htmlspecialchars($os); ?></p>
                        <p><strong>PHP Version:</strong> <?php echo htmlspecialchars($phpVersion); ?></p>
                        <p><strong>Upload Folder:</strong> <?php echo htmlspecialchars($uploadDir); ?></p>
                        <p><strong>Disk Total:</strong> <?php echo $diskTotal ? humanBytes($diskTotal) : 'N/A'; ?></p>
                        <p><strong>Disk Free:</strong> <?php echo $diskTotal ? humanBytes($diskFree) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">Server Performance</div>
                    <div class="card-body">
                        <?php
                        // Attempts to show load averages on *nix systems
                        if (function_exists('sys_getloadavg')) {
                            $loads = sys_getloadavg();
                            echo '<p><strong>Load (1m,5m,15m):</strong> ' . implode(', ', $loads) . '</p>';
                        } else {
                            echo '<p><strong>Load:</strong> Not available on this system</p>';
                        }

                        // Uptime if available via proc (Linux)
                        if (is_readable('/proc/uptime')) {
                            $u = explode(' ', trim(file_get_contents('/proc/uptime')));
                            $uptimeSeconds = (int)$u[0];
                            $days = floor($uptimeSeconds/86400);
                            $hours = floor(($uptimeSeconds%86400)/3600);
                            echo '<p><strong>Uptime:</strong> ' . $days . ' days, ' . $hours . ' hours</p>';
                        } else {
                            echo '<p><strong>Uptime:</strong> Not available</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
<div class="col-lg-6 mb-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">
            Kelola Alokasi Storage
        </div>

        <div class="card-body">
            <form method="POST" action="">
                <label class="form-label">Tambah Storage</label>
                <div class="input-group mb-3">
                    <input type="number" name="limit_value" class="form-control" placeholder="Angka" required>
                    <select name="limit_unit" class="form-select">
                        <option value="GB">GB</option>
                        <option value="TB">TB</option>
                    </select>
                </div>

                <button class="btn btn-primary w-100">
                    <i class="fa fa-save me-1"></i> Tambah Alokasi
                </button>
            </form>
            <hr>

            <small class="text-muted">
                Total Alokasi Saat Ini:
                <strong><?php echo $storageLimit ? humanBytes($storageLimit) : "Unlimited"; ?></strong><br>
                Sisa:
                <strong><?php echo $storageLimit ? humanBytes($storageRemaining) : "∞"; ?></strong><br>
                Terpakai:
                <strong><?php echo $percentUsedDisplay; ?></strong>
            </small>
        </div>
    </div>
</div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">Recent Activity (Uploads)</div>
            <div class="card-body">
                <ul class="list-group">
                    <?php
                    $recent = fetchAll('SELECT f.id, f.original_name, f.size, f.uploaded_at, u.username FROM files f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.uploaded_at DESC LIMIT 10');
                    if ($recent) {
                        foreach ($recent as $r) {
                            echo '<li class="list-group-item small">' . htmlspecialchars($r['uploaded_at'] ?? 'N/A') . ' - <strong>' . htmlspecialchars($r['username'] ?? 'Unknown') . '</strong> uploaded <em>' . htmlspecialchars($r['original_name'] ?? $r['filename']) . '</em> (' . humanBytes(intval($r['size'] ?? 0)) . ')</li>';
                        }
                    } else {
                        echo '<li class="list-group-item small text-muted">No recent uploads</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>