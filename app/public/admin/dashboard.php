<?php
session_start();
require_once __DIR__ . '/../connection.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../src/StorageManager.php';
$storageManager = new StorageManager();

// Handle export users CSV
if (isset($_GET['export']) && $_GET['export'] === 'users') {
    $users = fetchAll("SELECT id, username, email, full_name, storage_quota, storage_used, is_active, created_at FROM users WHERE is_admin = 0 ORDER BY created_at DESC");
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Username', 'Email', 'Full Name', 'Storage Quota (GB)', 'Storage Used (GB)', 'Active', 'Created At']);
    
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['username'],
            $user['email'],
            $user['full_name'] ?? '-',
            round($user['storage_quota'] / (1024 * 1024 * 1024), 2),
            round($user['storage_used'] / (1024 * 1024 * 1024), 2),
            ($user['is_active'] ? 'Yes' : 'No'),
            $user['created_at']
        ]);
    }
    fclose($output);
    log_activity('EXPORT_USERS', 'Exported ' . count($users) . ' user records to CSV');
    exit;
}

// Handle export logs
if (isset($_GET['export']) && $_GET['export'] === 'logs') {
    $logs = fetchAll("SELECT al.*, u.username FROM activity_logs al LEFT JOIN users u ON al.admin_id = u.id ORDER BY al.created_at DESC LIMIT 1000");
    
    // Check if logs are empty
    if (empty($logs)) {
        $_SESSION['export_error'] = 'Tidak ada data log untuk diekspor. Database log kosong.';
        header('Location: dashboard.php');
        exit;
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Admin', 'Action', 'Description', 'IP Address', 'Timestamp']);
    
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['username'] ?? '-',
            $log['action'],
            $log['description'] ?? '-',
            $log['ip_address'] ?? '-',
            $log['created_at']
        ]);
    }
    fclose($output);
    log_activity('EXPORT_LOGS', 'Exported activity logs to CSV');
    exit;
}

// Get all users (non-admin)
$allUsers = fetchAll("
    SELECT id, username, full_name, email, storage_quota, storage_used,
           last_login, created_at, is_active
    FROM users
    WHERE is_admin = 0
    ORDER BY created_at DESC
");

// Calculate statistics
$totalUsedRow = fetchOne('SELECT COALESCE(SUM(storage_used),0) as total_used FROM users WHERE is_admin = 0');
$totalUsed = intval($totalUsedRow['total_used'] ?? 0);
$storageLimitRow = fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'storage_limit'");
$totalQuota = intval($storageLimitRow['setting_value'] ?? 0);
$totalUsers = count($allUsers);
$totalFiles = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE is_trashed = 0');
$totalFiles = intval($totalFiles['cnt'] ?? 0);
$pendingRequests = fetchOne('SELECT COUNT(*) as cnt FROM storage_requests WHERE status = "pending"');
$pendingRequests = intval($pendingRequests['cnt'] ?? 0);
$safeQuota = ($totalQuota > 0) ? $totalQuota : 1;

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --bg-primary: #f9f9f9;
            --bg-card: #ffffff;
            --text-primary: #333;
            --text-muted: #999;
            --border-color: #e0e0e0;
        }
        body.dark-mode {
            --bg-primary: #1a1a1a;
            --bg-card: #2a2a2a;
            --text-primary: #f0f0f0;
            --text-muted: #999;
            --border-color: #444;
        }
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
        }
        .card {
            background-color: var(--bg-card) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            border-color: #0dcaf0;
        }
        body.dark-mode .stat-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
        }
        .stat-card h3 {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-card p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 0;
        }
        table {
            color: var(--text-primary) !important;
        }
        thead {
            background-color: var(--bg-primary) !important;
            color: var(--text-primary) !important;
        }
    </style>
</head>
<body>
<script>
// Load theme preference immediately to prevent flash
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-mode');
    document.body.classList.add('dark-mode');
}
</script>
<div class="d-flex">
    <?php include __DIR__ . '/../sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <?php if (isset($_SESSION['export_error'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_SESSION['export_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['export_error']); ?>
        <?php endif; ?>
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-0">Dashboard</h2>
                    <small class="text-muted">Ringkasan Sistem Clario - Kontrol Admin</small>
                </div>
                <div class="text-end">
                    <span class="small text-muted">Capacity: <?php echo formatBytes($totalQuota); ?> | Used: <?php echo formatBytes($totalUsed); ?></span>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="row mb-4 g-3">
            <div class="col-lg-3">
                <div class="stat-card" onclick="window.location.href='management_user.php'" role="button" tabindex="0">
                    <h6 style="color: var(--text-muted); margin-bottom: 5px;">Total Pengguna</h6>
                    <h3><?php echo $totalUsers; ?></h3>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="stat-card" onclick="window.location.href='../semuafile.php'" role="button" tabindex="0">
                    <h6 style="color: var(--text-muted); margin-bottom: 5px;">Total File</h6>
                    <h3><?php echo $totalFiles; ?></h3>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="stat-card" onclick="window.location.href='storage_server.php'" role="button" tabindex="0">
                    <h6 style="color: var(--text-muted); margin-bottom: 5px;">Penyimpanan Terpakai</h6>
                  <?php
                $percentUsed = ($totalQuota > 0)
                    ? round(($totalUsed / $totalQuota) * 100, 1)
                    : 0;
                ?>

                <h3><?php echo $percentUsed; ?>%</h3>

                <p>
                    <?php echo formatBytes($totalUsed); ?> /
                    <?php echo ($totalQuota > 0 ? formatBytes($totalQuota) : "Unlimited"); ?>
                </p>

                </div>
            </div>
            <div class="col-lg-3">
                <div class="stat-card" onclick="window.location.href='permintaan_storage.php'" role="button" tabindex="0">
                    <h6 style="color: var(--text-muted); margin-bottom: 5px;">Permintaan Pending</h6>
                    <h3><?php echo $pendingRequests; ?></h3>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- Chart -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                        <h6 class="mb-0 fw-bold">Grafik Penggunaan Storage</h6>
                        <small style="color: var(--text-muted);">Pemakaian (GB) per bulan</small>
                    </div>
                    <div class="card-body">
                        <canvas id="storageChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                        <h6 class="mb-0 fw-bold">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="?export=users" class="btn btn-outline-primary">Export Users CSV</a>
                            <a href="?export=logs" class="btn btn-outline-info">Export Logs</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User List -->
        <div class="card shadow-sm">
            <div class="card-header" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                <h6 class="mb-0 fw-bold">Daftar User (<?php echo count($allUsers); ?> user)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Nama User</th>
                                <th>Email</th>
                                <th>Storage Quota</th>
                                <th>Terpakai</th>
                                <th>Progress</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $user): ?>
                            <?php
                            $usedPercent = $user['storage_quota'] > 0 ? ($user['storage_used'] / $user['storage_quota']) * 100 : 0;
                            $barColor = $usedPercent > 90 ? 'danger' : ($usedPercent > 70 ? 'warning' : 'success');
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if ($user['full_name']): ?>
                                        <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($user['full_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo htmlspecialchars($user['email']); ?></small></td>
                                <td><?php echo formatBytes($user['storage_quota']); ?></td>
                                <td><?php echo formatBytes($user['storage_used']); ?></td>
                                <td>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-<?php echo $barColor; ?>" style="width: <?php echo $usedPercent; ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <a href="management_user.php?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Theme toggle function
function toggleTheme() {
    document.documentElement.classList.toggle('dark-mode');
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    
    // Update button text if it exists
    const themeText = document.getElementById('theme-text');
    if (themeText) {
        themeText.textContent = isDark ? 'Light Mode' : 'Night Mode';
    }
}

// Load theme preference on page load and update button text
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-mode');
    document.body.classList.add('dark-mode');
    const themeText = document.getElementById('theme-text');
    if (themeText) {
        themeText.textContent = 'Light Mode';
    }
}

// Add keyboard navigation for stat cards
document.addEventListener('DOMContentLoaded', function() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
});

// Storage chart
const storageCtx = document.getElementById('storageChart').getContext('2d');
new Chart(storageCtx, {
    type: 'line',
    data: {
        labels: ['May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Pemakaian (GB)',
            data: [150, 170, 190, 220, 250, 290, 320, 350],
            borderColor: '#0dcaf0',
            backgroundColor: 'rgba(13, 202, 240, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#0dcaf0',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
</body>
</html>
