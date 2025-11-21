<?php
session_start();
require_once __DIR__ . '/../connection.php';

// Admin only
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit;
}

// If admin posts a manual log entry (for testing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_message'])) {
    $msg = trim($_POST['log_message']);
    if ($msg !== '') {
        log_activity('MANUAL_ENTRY', $msg, $_SESSION['id'] ?? null);
        header('Location: activity_log.php');
        exit;
    }
}

// Fetch recent logs from database
try {
    $sql = "SELECT al.*, u.username FROM activity_logs al 
            LEFT JOIN users u ON al.admin_id = u.id 
            ORDER BY al.created_at DESC LIMIT 100";
    $logs = fetchAll($sql);
} catch (Exception $e) {
    $logs = [];
    error_log("Failed to fetch activity logs: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Activity Log - Admin</title>
<!-- Tambahkan FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color:#f9f9f9;">
<div class="d-flex">
    <?php include __DIR__ . '/../sidebar.php'; ?>
    <div class="main flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold"><i class="fa fa-history me-2 text-primary"></i>Activity Log</h4>
            <small class="text-muted">Riwayat kejadian pada cloud server</small>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Tambahkan catatan (manual)</label>
                        <input type="text" name="log_message" class="form-control" placeholder="Contoh: Restart service X">
                    </div>
                    <button class="btn btn-primary">Tambahkan</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">Terbaru</div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p class="text-muted">Belum ada catatan.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Deskripsi</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><small><?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($log['username'] ?? '-'); ?></small></td>
                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                        <td><small><?php echo htmlspecialchars($log['description'] ?? '-'); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>