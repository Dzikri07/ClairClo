<?php
session_start();
require_once __DIR__ . '/../connection.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit;
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $requestId = $_POST['request_id'];
    $adminId = $_SESSION['user_id'];

    if ($_POST['action'] === 'approve') {
        // Get request details
        $request = fetchOne("SELECT * FROM storage_requests WHERE id = ?", [$requestId]);

        if ($request && $request['status'] === 'pending') {
            // Update user storage quota
            $stmt = getDB()->prepare("UPDATE users SET storage_quota = ? WHERE id = ?");
            $stmt->execute([$request['requested_quota'], $request['user_id']]);

            // Update request status
            $stmt = getDB()->prepare("UPDATE storage_requests SET status = 'approved', admin_id = ?, processed_at = NOW() WHERE id = ?");
            $stmt->execute([$adminId, $requestId]);

            $success = "Permintaan storage berhasil disetujui.";
        }
    } elseif ($_POST['action'] === 'reject') {
        $adminNotes = $_POST['admin_notes'] ?? '';

        // Update request status
        $stmt = getDB()->prepare("UPDATE storage_requests SET status = 'rejected', admin_id = ?, admin_notes = ?, processed_at = NOW() WHERE id = ?");
        $stmt->execute([$adminId, $adminNotes, $requestId]);

        $success = "Permintaan storage berhasil ditolak.";
    }
}

// Get all storage requests with user details
$requests = fetchAll("
    SELECT sr.*, u.username, u.full_name, u.email,
           au.username as admin_username
    FROM storage_requests sr
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN users au ON sr.admin_id = au.id
    ORDER BY sr.created_at DESC
");

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
    <title>Permintaan Storage - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: #f9f9f9;">
<div class="d-flex">
    <?php include __DIR__ . '/../sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold"><i class="fa fa-database text-primary me-2"></i>Permintaan Storage</h4>
            <div class="d-flex gap-2">
                <span class="badge bg-info"><i class="fa fa-inbox me-2"></i>Total: <?php echo count($requests); ?> permintaan</span>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Requests Table -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Daftar Permintaan Storage</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($requests)): ?>
                <div class="text-center py-5">
                    <i class="fa fa-database fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada permintaan storage</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Permintaan</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Diajukan</th>
                                <th>Diproses</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($request['username']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo formatBytes($request['requested_quota']); ?></strong><br>
                                        <small class="text-muted">dari <?php echo formatBytes($request['current_quota']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($request['reason'] ?: '-'); ?></td>
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
                                <td><?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <?php if ($request['processed_at']): ?>
                                        <?php echo date('d/m/Y H:i', strtotime($request['processed_at'])); ?><br>
                                        <small class="text-muted">oleh <?php echo htmlspecialchars($request['admin_username']); ?></small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <div class="btn-group btn-group-sm">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm"
                                                    onclick="return confirm('Apakah Anda yakin ingin menyetujui permintaan ini?')">
                                                <i class="fa fa-check"></i> Setujui
                                            </button>
                                        </form>
                                        <button class="btn btn-outline-danger btn-sm" onclick="showRejectModal(<?php echo $request['id']; ?>)">
                                            <i class="fa fa-times"></i> Tolak
                                        </button>
                                    </div>
                                    <?php else: ?>
                                        <?php if ($request['status'] === 'rejected' && $request['admin_notes']): ?>
                                        <button class="btn btn-outline-info btn-sm" onclick="showNotes('<?php echo htmlspecialchars($request['admin_notes']); ?>')">
                                            <i class="fa fa-comment"></i> Catatan
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
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

<!-- Reject Modal -->
        <div class="modal fade" id="rejectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tolak Permintaan Storage</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="request_id" id="rejectRequestId">
                            <div class="mb-3">
                                <label class="form-label">Catatan Admin (Opsional)</label>
                                <textarea class="form-control" name="admin_notes" rows="3" placeholder="Berikan alasan penolakan..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Tolak Permintaan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Notes Modal -->
        <div class="modal fade" id="notesModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Catatan Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p id="notesContent"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showRejectModal(requestId) {
    document.getElementById('rejectRequestId').value = requestId;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function showNotes(notes) {
    document.getElementById('notesContent').textContent = notes;
    new bootstrap.Modal(document.getElementById('notesModal')).show();
}
</script>

</body>
</html>
