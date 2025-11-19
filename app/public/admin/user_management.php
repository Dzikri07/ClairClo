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
    <title>Kelola User - Admin Panel</title>
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
            <h4 class="fw-bold"><i class="fa fa-users me-2 text-primary"></i>Kelola User</h4>
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
                                        <div class="progress flex-grow-1" style="height: 5px; min-width: 60px;">
                                            <div class="progress-bar bg-<?php echo $barColor; ?>" style="width: <?php echo min($usedPercent, 100); ?>%"></div>
                                        </div>
                                        <small class="text-muted text-nowrap"><?php echo formatBytes($user['storage_used']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['is_admin'] ? 'danger' : 'secondary'; ?>">
                                        <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!$user['is_admin']): ?>
                                    <button class="btn btn-sm btn-outline-warning btn-action" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-action" onclick="showDeleteModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Hapus">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="text-muted small">-</span>
                                    <?php endif; ?>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-user-plus me-2 text-primary"></i>Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addUserForm">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body">
                    <!-- User Credentials Section -->
                    <div class="form-section">
                        <h6 class="fw-bold mb-3"><i class="fa fa-user-circle me-2"></i>Data User</h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" placeholder="Masukkan username" required minlength="3">
                                <small class="form-text text-muted">Minimal 3 karakter, hanya alphanumeric dan underscore</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" placeholder="user@example.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="full_name" placeholder="Masukkan nama lengkap">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" placeholder="Masukkan password" required minlength="6">
                                <small class="form-text text-muted">Minimal 6 karakter</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password_confirm" placeholder="Konfirmasi password" required minlength="6">
                            </div>
                        </div>
                    </div>

                    <!-- Storage Section -->
                    <div class="form-section">
                        <h6 class="fw-bold mb-3"><i class="fa fa-hdd me-2"></i>Alokasi Storage</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Storage Quota (GB) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="storage_gb" value="10" min="1" max="1000" required>
                                <span class="input-group-text">GB</span>
                            </div>
                            <small class="form-text text-muted">Alokasi storage untuk user (1-1000 GB)</small>
                        </div>

                        <div class="alert alert-info alert-sm mb-0">
                            <small><i class="fa fa-info-circle me-1"></i>Storage dapat ditambah kemudian melalui menu kelola user</small>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-2"></i>Tambah User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-edit me-2 text-warning"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="editUsername" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="editEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="full_name" id="editFullName">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="is_active" id="editIsActive">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fa fa-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa fa-trash me-2"></i>Hapus User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Menghapus user akan menghapus semua data user termasuk file-nya.
                    </div>
                    <p>Anda yakin ingin menghapus user <strong id="deleteUsername"></strong>?</p>
                    <p class="text-muted small">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-trash me-2"></i>Hapus User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showEditModal(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editFullName').value = user.full_name || '';
    document.getElementById('editIsActive').value = user.is_active ? '1' : '0';
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function showDeleteModal(userId, username) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}

// Form validation
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    const password = document.querySelector('input[name="password"]').value;
    const passwordConfirm = document.querySelector('input[name="password_confirm"]').value;
    if (password !== passwordConfirm) {
        e.preventDefault();
        alert('Password tidak cocok!');
        return false;
    }
});
</script>
</body>
</html>
