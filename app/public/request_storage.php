<?php
session_start();
require_once __DIR__ . '/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle storage request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $requestedGb = (int)$_POST['requested_gb'];
    $reason = $_POST['reason'] ?? '';

    if ($requestedGb > 0 && $requestedGb <= 100) { // Max 100GB per request
        $requestedBytes = $requestedGb * 1024 * 1024 * 1024;

        // Get current quota
        $user = fetchOne("SELECT storage_quota FROM users WHERE id = ?", [$userId]);
        $currentQuota = $user['storage_quota'];

        // Check if there's already a pending request
        $existingRequest = fetchOne("SELECT id FROM storage_requests WHERE user_id = ? AND status = 'pending'", [$userId]);

        if ($existingRequest) {
            $error = "Anda sudah memiliki permintaan storage yang sedang diproses.";
        } else {
            // Insert new request
            insert('storage_requests', [
                'user_id' => $userId,
                'requested_quota' => $requestedBytes,
                'current_quota' => $currentQuota,
                'reason' => $reason
            ]);

            $success = "Permintaan storage berhasil diajukan. Admin akan memproses dalam 1-2 hari kerja.";
        }
    } else {
        $error = "Jumlah storage yang diminta tidak valid (1-100 GB).";
    }
}

// Get current user storage info
$user = fetchOne("SELECT storage_quota, storage_used FROM users WHERE id = ?", [$_SESSION['user_id']]);
$currentQuota = $user['storage_quota'];
$currentUsed = $user['storage_used'];
$available = $currentQuota - $currentUsed;

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
    <title>Minta Tambah Storage - Clario</title>
    <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background-color: #f9f9f9;">
<div class="d-flex">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">Minta Tambah Storage</h4>
        </div>

        <!-- Current Storage Info -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fa fa-hdd fa-2x text-primary mb-2"></i>
                        <h5><?php echo formatBytes($currentQuota); ?></h5>
                        <p class="text-muted mb-0">Kuota Saat Ini</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fa fa-database fa-2x text-success mb-2"></i>
                        <h5><?php echo formatBytes($currentUsed); ?></h5>
                        <p class="text-muted mb-0">Terpakai</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fa fa-plus-circle fa-2x text-info mb-2"></i>
                        <h5><?php echo formatBytes($available); ?></h5>
                        <p class="text-muted mb-0">Tersedia</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Request Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Ajukan Permintaan Tambah Storage</h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jumlah Storage yang Diminta (GB)</label>
                                <input type="number" class="form-control" name="requested_gb" min="1" max="100" required>
                                <div class="form-text">Maksimal 100 GB per permintaan</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Alasan Permintaan</label>
                                <textarea class="form-control" name="reason" rows="3" placeholder="Jelaskan mengapa Anda membutuhkan tambahan storage..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-paper-plane me-2"></i>Ajukan Permintaan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Request History -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Riwayat Permintaan</h6>
            </div>
            <div class="card-body p-0">
                <?php
                $requests = fetchAll("
                    SELECT * FROM storage_requests
                    WHERE user_id = ?
                    ORDER BY created_at DESC
                    LIMIT 10
                ", [$_SESSION['user_id']]);

                if (empty($requests)): ?>
                <div class="text-center py-4">
                    <i class="fa fa-history fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">Belum ada riwayat permintaan</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Permintaan</th>
                                <th>Status</th>
                                <th>Catatan Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?></td>
                                <td><?php echo formatBytes($request['requested_quota']); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $request['status'] === 'approved' ? 'success' :
                                             ($request['status'] === 'rejected' ? 'danger' : 'warning');
                                    ?>">
                                        <?php
                                        echo $request['status'] === 'approved' ? 'Disetujui' :
                                             ($request['status'] === 'rejected' ? 'Ditolak' : 'Menunggu');
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($request['admin_notes'] ?: '-'); ?></td>
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
