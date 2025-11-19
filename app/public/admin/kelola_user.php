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

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    ob_start();
    $html = generatePDFContent();
    ob_end_clean();
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="storage_report_' . date('Y-m-d-His') . '.pdf"');
    echo $html;
    exit;
}

// Handle storage increase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'increase_storage') {
        $userId = $_POST['user_id'];
        $additionalGb = (int)$_POST['additional_gb'];

        if ($additionalGb > 0) {
            $additionalBytes = $additionalGb * 1024 * 1024 * 1024; // Convert GB to bytes

            // Update user storage quota
            $stmt = getDB()->prepare("UPDATE users SET storage_quota = storage_quota + ? WHERE id = ?");
            $stmt->execute([$additionalBytes, $userId]);

            $success = "Storage berhasil ditambahkan {$additionalGb} GB untuk user.";
        }
    }
}

// Get all users
$users = fetchAll("
    SELECT id, username, full_name, email, storage_quota, storage_used,
           last_login, created_at, is_active
    FROM users
    WHERE is_admin = 0
    ORDER BY created_at DESC
");

// Get all users including admin for total calculation
$allUsers = fetchAll("
    SELECT id, username, full_name, email, storage_quota, storage_used,
           last_login, created_at, is_active
    FROM users
    ORDER BY created_at DESC
");

// Calculate statistics
$totalUsedRow = fetchOne('SELECT COALESCE(SUM(storage_used),0) as total_used FROM users');
$totalUsed = intval($totalUsedRow['total_used'] ?? 0);
$totalQuota = 100 * 1024 * 1024 * 1024; // 100 GB in bytes
$totalUsers = count($allUsers);
$activeUsers = count(array_filter($allUsers, function($u) { return ($u['is_admin'] ?? 0) == 0 && ($u['is_active'] ?? 0) == 1; }));
$currentDate = date('d/m/Y H:i:s');

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function generatePDFContent() {
    global $totalUsed, $totalQuota, $totalUsers, $activeUsers, $currentDate, $allUsers;
    
    $availableSpace = $totalQuota - $totalUsed;
    $usedPercent = ($totalUsed / $totalQuota) * 100;
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 15px; }
        .title { font-size: 24px; font-weight: bold; color: #007bff; }
        .subtitle { font-size: 12px; color: #666; margin-top: 5px; }
        .stats-grid { display: table; width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        .stat-item { display: table-cell; width: 25%; border: 1px solid #ddd; padding: 15px; text-align: center; }
        .stat-value { font-size: 18px; font-weight: bold; color: #007bff; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        .chart-section { margin-bottom: 30px; }
        .section-title { font-size: 14px; font-weight: bold; color: #007bff; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #007bff; color: white; padding: 10px; text-align: left; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .progress-bar { width: 100%; height: 20px; background-color: #e9ecef; border-radius: 10px; overflow: hidden; margin: 5px 0; }
        .progress-fill { height: 100%; background-color: #28a745; display: flex; align-items: center; justify-content: center; color: white; font-size: 10px; font-weight: bold; }
        .progress-fill.warning { background-color: #ffc107; }
        .progress-fill.danger { background-color: #dc3545; }
        .footer { margin-top: 30px; font-size: 10px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>n. laln
<body>
    <div class="header">
          <div class="title">Laporan Storage & Chart</div>
        <div class="subtitle">Tanggal: ' . htmlspecialchars($currentDate) . '</div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-value">' . number_format($totalUsers) . '</div>
            <div class="stat-label">Total User</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">' . number_format($activeUsers) . '</div>
            <div class="stat-label">User Aktif</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">' . formatBytes($totalUsed) . '</div>
            <div class="stat-label">Penggunaan Total</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">' . round($usedPercent, 2) . '%</div>
            <div class="stat-label">Persentase Penggunaan</div>
        </div>
    </div>
    
    <div class="chart-section">
        <div class="section-title">Alokasi Storage</div>
        <table>
            <tr>
                <td style="width: 30%;">Total Alokasi</td>
                <td style="width: 70%;"><strong>' . formatBytes($totalQuota) . '</strong></div>
            </tr>
            <tr>
                <td>Terpakai</td>
                <td><strong>' . formatBytes($totalUsed) . '</strong> (' . round($usedPercent, 2) . '%)</td>
            </tr>
            <tr>
                <td>Tersedia</td>
                <td><strong>' . formatBytes($availableSpace) . '</strong> (' . round(100 - $usedPercent, 2) . '%)</td>
            </tr>
        </table>
        <div class="progress-bar">
            <div class="progress-fill ' . ($usedPercent > 90 ? 'danger' : ($usedPercent > 70 ? 'warning' : '')) . '" style="width: ' . min($usedPercent, 100) . '%;">' . round($usedPercent, 1) . '%</div>
        </div>
    </div>
    
    <div class="chart-section">
        <div class="section-title">Daftar User Terperinci</div>
        <table>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Terpakai</th>
                <th>Alokasi</th>
                <th>Status</th>
                <th>Tanggal Dibuat</th>
            </tr>';
    
    foreach ($allUsers as $user) {
        $usedPercent = $user['storage_quota'] > 0 ? ($user['storage_used'] / $user['storage_quota']) * 100 : 0;
        $status = $user['is_active'] ? 'Aktif' : 'Nonaktif';
        $created = date('d/m/Y', strtotime($user['created_at']));
        
        $html .= '<tr>
                <td>' . htmlspecialchars($user['username']) . '</td>
                <td>' . htmlspecialchars($user['email']) . '</td>
                <td>' . formatBytes($user['storage_used']) . ' (' . round($usedPercent, 1) . '%)</td>
                <td>' . formatBytes($user['storage_quota']) . '</td>
                <td>' . $status . '</td>
                <td>' . $created . '</td>
            </tr>';
    }
    
    $html .= '</table>
    </div>
    
    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh Sistem Manajemen Storage ClairoCloud</p>
        <p>© ' . date('Y') . ' - Semua Hak Dilindungi</p>
    </div>
</body>
</html>';
    
    return $html;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: #f9f9f9;">
<div class="d-flex">
    <?php include __DIR__ . '/../sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Storage & Chart</h4>
                <small class="text-muted">Tanggal: <?php echo $currentDate; ?></small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" onclick="exportPDF()">
                    <i class="fa fa-file-pdf me-1"></i> Export PDF
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                    <i class="fa fa-refresh me-1"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-users text-primary mb-2" style="font-size: 28px;"></i>
                        <h5 class="text-primary fw-bold"><?php echo $totalUsers; ?></h5>
                        <p class="text-muted small mb-0">Total User</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-user-check text-success mb-2" style="font-size: 28px;"></i>
                        <h5 class="text-success fw-bold"><?php echo $activeUsers; ?></h5>
                        <p class="text-muted small mb-0">User Aktif</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <i class="fas fa-database text-info mb-2" style="font-size: 28px;"></i>
                        <h5 class="text-info fw-bold"><?php echo formatBytes($totalUsed); ?></h5>
                        <p class="text-muted small mb-0">Penggunaan Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-pie text-warning mb-2" style="font-size: 28px;"></i>
                        <h5 class="text-warning fw-bold"><?php echo round(($totalUsed / $totalQuota) * 100, 2); ?>%</h5>
                        <p class="text-muted small mb-0">Persentase Penggunaan</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage Overview -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Grafik Penggunaan Storage</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="form-label small mb-0">Tampilan Grafik</label>
                                <select id="chartTypeSelect" class="form-select form-select-sm">
                                    <option value="doughnut">Donut</option>
                                    <option value="bar">Bar</option>
                                    <option value="line">Line (crypto style)</option>
                                </select>
                            </div>
                            <div class="text-end small text-muted">Total Alokasi: <?php echo formatBytes($totalQuota); ?></div>
                        </div>
                        <canvas id="storageChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="text-primary"><?php echo formatBytes($totalUsed); ?></h5>
                        <p class="text-muted mb-0">Total Terpakai</p>
                        <hr>
                        <h5 class="text-success"><?php echo formatBytes($totalQuota - $totalUsed); ?></h5>
                        <p class="text-muted mb-0">Total Tersedia</p>
                        <hr>
                        <h5 class="text-info"><?php echo formatBytes($totalQuota); ?></h5>
                        <p class="text-muted mb-0">Total Alokasi</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Daftar User (<?php echo count($users); ?> user)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Storage Terpakai</th>
                                <th>Storage Tersedia</th>
                                <th>Login Terakhir</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <?php
                            $usedPercent = $user['storage_quota'] > 0 ? ($user['storage_used'] / $user['storage_quota']) * 100 : 0;
                            $available = $user['storage_quota'] - $user['storage_used'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                            <div class="progress-bar bg-<?php echo $usedPercent > 90 ? 'danger' : ($usedPercent > 70 ? 'warning' : 'success'); ?>"
                                                 style="width: <?php echo min($usedPercent, 100); ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo formatBytes($user['storage_used']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo formatBytes($available); ?></td>
                                <td><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Belum pernah'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-outline-primary btn-sm" onclick="showStorageModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                        <i class="fa fa-plus me-1"></i> Tambah Storage
                                    </button>
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

<!-- Storage Increase Modal -->
<div class="modal fade" id="storageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Volume Storage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="increase_storage">
                    <input type="hidden" name="user_id" id="modalUserId">
                    <p>Tambah storage untuk user: <strong id="modalUsername"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Tambah Storage (GB)</label>
                        <input type="number" class="form-control" name="additional_gb" min="1" max="100" required>
                        <div class="form-text">Maksimal 100 GB per penambahan</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah Storage</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
// Prepare data for charts
const usersData = <?php echo json_encode($users); ?>;
const allUsersData = <?php echo json_encode($allUsers); ?>;
const stats = {
    totalUsed: <?php echo $totalUsed; ?>,
    totalQuota: <?php echo $totalQuota; ?>,
    totalUsers: <?php echo $totalUsers; ?>,
    activeUsers: <?php echo $activeUsers; ?>,
    currentDate: '<?php echo $currentDate; ?>'
};

// Sort users by storage used for chart
const topUsers = [...usersData].sort((a, b) => (b.storage_used || 0) - (a.storage_used || 0)).slice(0, 10);
const topLabels = topUsers.map(u => u.username || u.full_name || 'user');
const topValues = topUsers.map(u => parseInt(u.storage_used || 0));

// Chart.js for storage usage
const ctx = document.getElementById('storageChart').getContext('2d');
let storageChart = null;

function formatBytesJS(bytes) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let size = bytes;
    let unitIdx = 0;
    while (size > 1024 && unitIdx < units.length - 1) {
        size /= 1024;
        unitIdx++;
    }
    return (size.toFixed(2)) + ' ' + units[unitIdx];
}

function renderChart(type){
    if (storageChart) storageChart.destroy();
    
    if (type === 'doughnut'){
        storageChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Terpakai', 'Tersedia'],
                datasets: [{
                    data: [stats.totalUsed, Math.max(0, stats.totalQuota - stats.totalUsed)],
                    backgroundColor: ['#dc3545', '#28a745'],
                    borderColor: ['#c82333', '#1e7e34'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + formatBytesJS(context.parsed);
                            }
                        }
                    }
                }
            }
        });
    } else if (type === 'bar'){
        storageChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: topLabels,
                datasets: [{
                    label: 'Storage Terpakai',
                    data: topValues,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatBytesJS(value);
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Terpakai: ' + formatBytesJS(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
    } else if (type === 'line'){
        storageChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: topLabels.length ? topLabels : ['User 1', 'User 2', 'User 3'],
                datasets: [{
                    label: 'Penggunaan Storage Trend',
                    data: topValues.length ? topValues : [0, 0, 0],
                    fill: true,
                    backgroundColor: 'rgba(75, 192, 192, 0.15)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatBytesJS(value);
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Terpakai: ' + formatBytesJS(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
    }
}

// Initialize chart with default type
const select = document.getElementById('chartTypeSelect');
select.addEventListener('change', function(){ renderChart(this.value); });
renderChart(select.value || 'doughnut');

function showStorageModal(userId, username) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('storageModal')).show();
}

function exportPDF() {
    // Create PDF element
    const element = document.createElement('div');
    element.style.padding = '20px';
    element.style.fontFamily = 'Arial, sans-serif';
    
    const title = document.createElement('h2');
    title.textContent = 'Laporan Storage & Chart';
    title.style.textAlign = 'center';
    title.style.color = '#007bff';
    title.style.marginBottom = '20px';
    element.appendChild(title);
    
    const dateInfo = document.createElement('p');
    dateInfo.textContent = 'Tanggal: ' + stats.currentDate;
    dateInfo.style.textAlign = 'center';
    dateInfo.style.color = '#666';
    dateInfo.style.marginBottom = '20px';
    element.appendChild(dateInfo);
    
    // Statistics table
    const statsTable = document.createElement('table');
    statsTable.style.width = '100%';
    statsTable.style.borderCollapse = 'collapse';
    statsTable.style.marginBottom = '20px';
    statsTable.innerHTML = `
        <tr style="background-color: #007bff; color: white;">
            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Total User</th>
            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">User Aktif</th>
            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Penggunaan Total</th>
            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Persentase</th>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;">${stats.totalUsers}</td>
            <td style="padding: 10px; border: 1px solid #ddd;">${stats.activeUsers}</td>
            <td style="padding: 10px; border: 1px solid #ddd;">${formatBytesJS(stats.totalUsed)}</td>
            <td style="padding: 10px; border: 1px solid #ddd;">${(stats.totalUsed / stats.totalQuota * 100).toFixed(2)}%</td>
        </tr>
    `;
    element.appendChild(statsTable);
    
    // Allocation info
    const allocSection = document.createElement('h4');
    allocSection.textContent = 'Alokasi Storage';
    allocSection.style.color = '#007bff';
    allocSection.style.marginTop = '20px';
    element.appendChild(allocSection);
    
    const allocTable = document.createElement('table');
    allocTable.style.width = '100%';
    allocTable.style.borderCollapse = 'collapse';
    allocTable.style.marginBottom = '20px';
    allocTable.innerHTML = `
        <tr style="background-color: #f0f0f0;">
            <td style="padding: 8px; border: 1px solid #ddd; width: 30%;">Total Alokasi</td>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">${formatBytesJS(stats.totalQuota)}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;">Terpakai</td>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">${formatBytesJS(stats.totalUsed)} (${(stats.totalUsed / stats.totalQuota * 100).toFixed(2)}%)</td>
        </tr>
        <tr style="background-color: #f0f0f0;">
            <td style="padding: 8px; border: 1px solid #ddd;">Tersedia</td>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">${formatBytesJS(Math.max(0, stats.totalQuota - stats.totalUsed))} (${((stats.totalQuota - stats.totalUsed) / stats.totalQuota * 100).toFixed(2)}%)</td>
        </tr>
    `;
    element.appendChild(allocTable);
    
    // User list
    const userSection = document.createElement('h4');
    userSection.textContent = 'Daftar User';
    userSection.style.color = '#007bff';
    userSection.style.marginTop = '20px';
    element.appendChild(userSection);
    
    const userTable = document.createElement('table');
    userTable.style.width = '100%';
    userTable.style.borderCollapse = 'collapse';
    userTable.style.fontSize = '12px';
    userTable.innerHTML = '<tr style="background-color: #007bff; color: white;"><th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Username</th><th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Email</th><th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Terpakai</th><th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Alokasi</th><th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Status</th></tr>';
    
    allUsersData.forEach((user, idx) => {
        const row = userTable.insertRow();
        row.style.backgroundColor = idx % 2 === 0 ? '#f9f9f9' : '#fff';
        row.innerHTML = `
            <td style="padding: 8px; border: 1px solid #ddd;">${user.username}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${user.email}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${formatBytesJS(user.storage_used)} (${(user.storage_quota > 0 ? (user.storage_used / user.storage_quota * 100).toFixed(1) : 0)}%)</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${formatBytesJS(user.storage_quota)}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${user.is_active ? 'Aktif' : 'Nonaktif'}</td>
        `;
    });
    element.appendChild(userTable);
    
    // Footer
    const footer = document.createElement('p');
    footer.textContent = '© ' + new Date().getFullYear() + ' ClairoCloud Storage Management System - Laporan ini dibuat secara otomatis';
    footer.style.textAlign = 'center';
    footer.style.color = '#999';
    footer.style.fontSize = '10px';
    footer.style.marginTop = '30px';
    footer.style.borderTop = '1px solid #ddd';
    footer.style.paddingTop = '10px';
    element.appendChild(footer);
    
    // Generate PDF
    const opt = {
        margin: 10,
        filename: 'storage_report_' + new Date().toISOString().split('T')[0] + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
    };
    
    html2pdf().set(opt).from(element).save();
}
</script>
</body>
</html>
