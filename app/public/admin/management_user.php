<?php
session_start();
require_once __DIR__ . '/../connection.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle add user action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $storageGb = intval($_POST['storage_gb'] ?? 0);

    // Validation
    $errors = [];
    if (empty($username)) $errors[] = 'Username tidak boleh kosong';
    if (strlen($username) < 3) $errors[] = 'Username minimal 3 karakter';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    if (empty($password)) $errors[] = 'Password tidak boleh kosong';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter';
    if ($password !== $passwordConfirm) $errors[] = 'Password tidak cocok';
    if ($storageGb < 1 || $storageGb > 1000) $errors[] = 'Storage harus antara 1-1000 GB';

    // Check if username exists
    if (empty($errors)) {
        $existing = fetchOne('SELECT id FROM users WHERE username = ?', [$username]);
        if ($existing) $errors[] = 'Username sudah terdaftar';
    }

    // Check if email exists
    if (empty($errors)) {
        $existing = fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) $errors[] = 'Email sudah terdaftar';
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'danger';
    } else {
        // Insert user
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $storageBytes = $storageGb * 1024 * 1024 * 1024;
        $now = date('Y-m-d H:i:s');

        $stmt = getDB()->prepare("
            INSERT INTO users (username, email, full_name, password, storage_quota, storage_used, is_active, is_admin, created_at)
            VALUES (?, ?, ?, ?, ?, 0, 1, 0, ?)
        ");
        
        if ($stmt->execute([$username, $email, $fullName, $hashedPassword, $storageBytes, $now])) {
            $message = "User '{$username}' berhasil ditambahkan dengan storage {$storageGb} GB";
            $messageType = 'success';
        } else {
            $message = 'Gagal menambahkan user: ' . $stmt->error;
            $messageType = 'danger';
        }
    }
}

// Handle delete user action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $userId = intval($_POST['user_id'] ?? 0);
    if ($userId > 0 && $userId != $_SESSION['user_id']) { // Prevent self-deletion
        $stmt = getDB()->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
        if ($stmt->execute([$userId])) {
            $message = 'User berhasil dihapus';
            $messageType = 'success';
        } else {
            $message = 'Gagal menghapus user';
            $messageType = 'danger';
        }
    } else {
        $message = 'Tidak dapat menghapus user ini';
        $messageType = 'danger';
    }
}

// Handle update user action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $userId = intval($_POST['user_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $isActive = intval($_POST['is_active'] ?? 0);

    if ($userId > 0) {
        $stmt = getDB()->prepare("
            UPDATE users SET email = ?, full_name = ?, is_active = ? WHERE id = ? AND is_admin = 0
        ");
        if ($stmt->execute([$email, $fullName, $isActive, $userId])) {
            $message = 'User berhasil diperbarui';
            $messageType = 'success';
        } else {
            $message = 'Gagal memperbarui user';
            $messageType = 'danger';
        }
    }
}

// Get all users
$users = fetchAll("
    SELECT id, username, full_name, email, storage_quota, storage_used,
           last_login, created_at, is_active, is_admin
    FROM users
    ORDER BY created_at DESC
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
    <title>Manajemen User - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    .table-responsive { border-radius: 0.5rem; }
    .badge { font-size: 0.8rem; padding: 0.35rem 0.65rem; }
    .btn-action { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
    .modal-header { background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; }
    .form-section { background: #f8f9fa; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body style="background-color: #f9f9f9;">
<div class="d-flex">
    <?php include __DIR__ . '/../sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0"><i class="fa fa-users text-primary me-2"></i>Manajemen User</h3>
                    <small class="text-muted">Kelola pengguna sistem</small>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fa fa-user-plus me-2"></i>Tambah User Baru
                </button>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Users Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">Daftar Semua User (<?php echo count($users); ?> user)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 12%;">Username</th>
                                    <th style="width: 15%;">Nama Lengkap</th>
                                    <th style="width: 20%;">Email</th>
                                    <th style="width: 12%;">Storage Quota</th>
                                    <th style="width: 12%;">Terpakai</th>
                                    <th style="width: 12%;">Tipe</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 15%; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <?php
                                $usedPercent = $user['storage_quota'] > 0 ? ($user['storage_used'] / $user['storage_quota']) * 100 : 0;
                                $available = max(0, $user['storage_quota'] - $user['storage_used']);
                                $barColor = $usedPercent > 90 ? 'danger' : ($usedPercent > 70 ? 'warning' : 'success');
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['full_name'] ?: '-'); ?></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($user['email']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo formatBytes($user['storage_quota']); ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="progress flex-grow-1" style="height: 6px; background-color: #e9ecef;">
                                                <div class="progress-bar bg-<?php echo $barColor; ?>" style="width: <?php echo $usedPercent; ?>%;"></div>
                                            </div>
                                        </div>
                                        <small><?php echo formatBytes($user['storage_used']); ?> / <?php echo formatBytes($user['storage_quota']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($user['is_admin'] ?? 0): ?>
                                            <span class="badge bg-info">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active'] ?? 0): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                                data-user-id="<?php echo $user['id']; ?>" 
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-fullname="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-is-active="<?php echo $user['is_active'] ?? 0; ?>"
                                                title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                title="Hapus">
                                            <i class="fa fa-trash"></i>
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
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-user-plus me-2"></i>Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="full_name" name="full_name">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">Konfirmasi Password</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="storage_gb" class="form-label">Storage Quota (GB)</label>
                        <input type="number" class="form-control" id="storage_gb" name="storage_gb" value="10" min="1" max="1000" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-edit me-2"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_is_active" class="form-label">Status</label>
                        <select class="form-select" id="edit_is_active" name="is_active" required>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-danger">
                <h5 class="modal-title text-danger"><i class="fa fa-trash me-2"></i>Hapus User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    
                    <p class="mb-0">Apakah Anda yakin ingin menghapus user <strong id="delete_username"></strong>?</p>
                    <p class="text-danger small mt-2"><i class="fa fa-exclamation-triangle me-2"></i>Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Edit modal population
document.getElementById('editUserModal').addEventListener('show.bs.modal', function(e) {
    const button = e.relatedTarget;
    document.getElementById('edit_user_id').value = button.dataset.userId;
    document.getElementById('edit_username').value = button.dataset.username;
    document.getElementById('edit_email').value = button.dataset.email;
    document.getElementById('edit_full_name').value = button.dataset.fullname;
    document.getElementById('edit_is_active').value = button.dataset.isActive;
});

// Delete modal population
document.getElementById('deleteUserModal').addEventListener('show.bs.modal', function(e) {
    const button = e.relatedTarget;
    document.getElementById('delete_user_id').value = button.dataset.userId;
    document.getElementById('delete_username').textContent = button.dataset.username;
});
</script>

</body>
</html>
