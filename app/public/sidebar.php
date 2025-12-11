<?php
// sidebar.php - extracted sidebar markup with active link detection
$current = basename($_SERVER['SCRIPT_NAME']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Compute a base URL that points to the public folder so links work
// even when this sidebar is included from an admin subfolder.
$baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// If included from an admin folder, move up one level to public root
if (basename($baseUrl) === 'admin') {
    $baseUrl = dirname($baseUrl);
}
// Ensure no trailing slash
$baseUrl = rtrim($baseUrl, '/');

// Ensure DB helpers are available
if (!function_exists('fetchOne')) {
    require_once __DIR__ . '/connection.php';
}

$userId = $_SESSION['user_id'] ?? null;
$user = null;
$storagePercent = 0;
$storageText = '';
if ($userId) {
    $user = fetchOne('SELECT username, email, full_name, storage_quota, storage_used, avatar FROM users WHERE id = ?', [$userId]);
    if ($user) {
        if (!empty($user['storage_quota'])) {
            $storagePercent = ($user['storage_used'] / $user['storage_quota']) * 100;
            $storagePercent = round($storagePercent, 2);
        }
        $used = isset($user['storage_used']) ? number_format($user['storage_used'] / 1024 / 1024, 2) . ' MB' : '0 B';
        $quota = isset($user['storage_quota']) ? number_format($user['storage_quota'] / 1024 / 1024, 2) . ' MB' : '0 B';
        $storageText = "$used dari $quota";
    }
}
?>

<div class="sidebar p-3 d-flex flex-column" id="sidebar">
    <div class="d-flex align-items-center mb-4 logo-container">
        <img src="<?php echo $baseUrl; ?>/assets/image/clairo.png" alt="logo" class="me-2" style="width: 70px;">
        <div>
            <h4 class="fw-bold logo-text mb-0" style="font-family: 'Krona One', sans-serif;">
                <span class="text-teal">C</span>lario
            </h4>
            <?php if ($user): ?>
            <div class="small text-muted">Selamat datang, <?php echo htmlspecialchars($user['username'] ?: $user['full_name']); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar upload: hidden form + file input. Clicking the button opens file picker and submits to upload.php -->
    <!-- Hidden for admin users -->
    <?php if (!(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1)): ?>
    <form id="sidebar-upload-form" action="upload.php" method="post" enctype="multipart/form-data" style="display:none;">
        <input type="file" name="upload_file" id="sidebar-upload-input">
    </form>
    <button id="sidebar-upload-btn" class="upload-btn mb-4" type="button">
        <i class="fa fa-plus me-1"></i> Upload
    </button>
    <?php endif; ?>

    <ul class="nav flex-column mb-4">
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
        <li class="nav-item mt-3">
            <hr class="my-3">
            <small class="text-muted fw-bold px-3">ADMIN PANEL</small>
        </li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/admin/dashboard.php" class="nav-link <?php echo ($current === 'dashboard.php') ? 'active' : ''; ?>"><i class="fa fa-chart-line me-2"></i> Dashboard</a></li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/admin/management_user.php" class="nav-link <?php echo ($current === 'management_user.php') ? 'active' : ''; ?>"><i class="fa fa-users me-2"></i> Manajemen User</a></li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/admin/permintaan_storage.php" class="nav-link <?php echo ($current === 'permintaan_storage.php') ? 'active' : ''; ?>"><i class="fa fa-database me-2"></i> Permintaan Storage</a></li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/admin/storage_server.php" class="nav-link <?php echo ($current === 'storage_server.php') ? 'active' : ''; ?>"><i class="fa fa-server me-2"></i> Storage Server</a></li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/admin/file_internal.php" class="nav-link <?php echo ($current === 'file_internal.php') ? 'active' : ''; ?>"><i class="fa fa-folder-open me-2"></i> File Internal</a></li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/admin/activity_log.php" class="nav-link <?php echo ($current === 'activity_log.php') ? 'active' : ''; ?>"><i class="fa fa-history me-2"></i> Activity Log</a></li>
        <?php else: ?>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/index.php" class="nav-link <?php echo ($current === 'index.php') ? 'active' : ''; ?>"><i class="fa fa-home me-2"></i> Beranda</a></li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/semuafile.php" class="nav-link <?php echo ($current === 'semuafile.php') ? 'active' : ''; ?>"><i class="fa fa-layer-group me-2"></i> Semua File</a></li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/favorit.php" class="nav-link <?php echo ($current === 'favorit.php') ? 'active' : ''; ?>"><i class="fa fa-star me-2"></i> Favorit</a></li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/sampah.php" class="nav-link <?php echo ($current === 'sampah.php') ? 'active' : ''; ?>"><i class="fa fa-trash me-2"></i> Sampah</a></li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/request_storage.php" class="nav-link <?php echo ($current === 'request_storage.php') ? 'active' : ''; ?>"><i class="fa fa-plus-circle me-2"></i> Minta Storage</a></li>
        <li class="nav-item"><a href="<?php echo $baseUrl; ?>/backup.php" class="nav-link <?php echo ($current === 'backup.php') ? 'active' : ''; ?>"><i class="fa fa-download me-2"></i> Backup</a></li>
        <?php endif; ?>
    </ul>
    <div class="mt-auto">
        <?php if ($user): ?>
        <div class="d-flex align-items-center mb-2">
          
           
        </div>
        <?php endif; ?>

        <!-- Storage info hidden for admin users -->
        <?php if (!(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1)): ?>
        <div class="storage">
            <p class="fw-bold small mb-1">Penyimpanan</p>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo intval($storagePercent); ?>%;" aria-valuenow="<?php echo intval($storagePercent); ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <p class="small text-muted mt-1"><?php echo htmlspecialchars($storageText ?: '0 B dari 0 B'); ?> digunakan (<?php echo $storagePercent; ?>%)</p>
        </div>
        <?php endif; ?>

        <!-- Theme toggle button -->
        <div class="mt-3 pt-3 border-top">
            <button class="btn btn-sm btn-outline-secondary w-100" onclick="toggleTheme()" title="Toggle Night Mode">
                <i class="fa fa-moon me-2"></i> <span id="theme-text">Night Mode</span>
            </button>
        </div>

        <!-- Logout button for admin users -->
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
        <div class="mt-2">
            <a href="<?php echo $baseUrl; ?>/logout.php" class="btn btn-sm btn-logout w-100" style="background-color: #e9ecef; color: #495057; border: 1px solid #dee2e6;">
                <i class="fa fa-sign-out me-2"></i> Logout
            </a>
        </div>  
        <?php endif; ?>
        <style>
          .btn-logout:hover {
            background-color: #D2EAEC !important;
            border-color: #D2EAEC !important;
            color: #495057 !important;
          }
        </style>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Expose BASE_URL for client-side scripts
const BASE_URL = '<?php echo $baseUrl; ?>';
// Sidebar upload button behaviour: AJAX upload with SweetAlert2 notifications
document.addEventListener('DOMContentLoaded', function () {
    var uploadBtn = document.getElementById('sidebar-upload-btn');
    var fileInput = document.getElementById('sidebar-upload-input');
    var form = document.getElementById('sidebar-upload-form');

    if (uploadBtn && fileInput && form) {
        uploadBtn.addEventListener('click', function () {
            fileInput.click();
        });

        fileInput.addEventListener('change', function () {
            if (fileInput.files.length > 0) {
                // AJAX upload instead of form submit
                uploadFileAjax(fileInput.files[0]);
                // Reset input for next upload
                fileInput.value = '';
            }
        });
    }

    // AJAX file upload with SweetAlert2 notifications
    function uploadFileAjax(file) {
        var formData = new FormData();
        formData.append('upload_file', file);

        // Show loading notification
        Swal.fire({
            title: 'Mengunggah...',
            html: '<p class="mb-0">Sedang mengunggah file: <strong>' + escapeHtml(file.name) + '</strong></p>',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });

        // Send upload request
        fetch(BASE_URL + '/upload.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Always attempt to read response body (may contain JSON error message)
            return response.text().then(text => ({ ok: response.ok, status: response.status, text }));
        })
        .then(obj => {
            var data = null;
            try {
                data = obj.text ? JSON.parse(obj.text) : null;
            } catch (e) {
                data = null;
            }

            if (obj.ok) {
                if (data && data.success) {
                    Swal.fire({
                        title: 'Berhasil!',
                        html: '<p class="mb-0">File <strong>' + escapeHtml(file.name) + '</strong> berhasil diunggah.</p>',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Gagal!',
                        html: '<p class="mb-0">' + escapeHtml((data && data.message) ? data.message : 'Terjadi kesalahan saat mengunggah file.') + '</p>',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                }
            } else {
                // Non-OK HTTP status — prefer server message if present
                var msg = (data && data.message) ? data.message : ('Terjadi kesalahan jaringan: HTTP ' + obj.status);
                Swal.fire({
                    title: 'Gagal!',
                    html: '<p class="mb-0">' + escapeHtml(msg) + '</p>',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            }
        })
        .catch(error => {
            // Network or other error
            Swal.fire({
                title: 'Gagal!',
                html: '<p class="mb-0">Terjadi kesalahan jaringan: ' + escapeHtml(error.message) + '</p>',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        });
    }

    // Utility function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Mobile sidebar toggle
    var sidebar = document.getElementById('sidebar');
    var menuToggle = document.createElement('button');
    menuToggle.innerHTML = '<i class="fa fa-bars"></i>';
    menuToggle.style.cssText = 'position: fixed; top: 10px; left: 10px; z-index: 1001; background: #007364; color: white; border: none; padding: 8px 12px; border-radius: 4px; display: none;';
    menuToggle.id = 'menu-toggle';
    document.body.appendChild(menuToggle);

    if (window.innerWidth <= 768) {
        menuToggle.style.display = 'block';
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }

    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            menuToggle.style.display = 'block';
        } else {
            menuToggle.style.display = 'none';
            sidebar.classList.remove('open');
        }
    });
});

// Initialize Bootstrap tooltips if available
document.addEventListener('DOMContentLoaded', function () {
    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            var triggers = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            triggers.forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        }
    } catch (e) {
        // ignore — fallback to native title tooltip
    }
});

// Theme toggle function
function toggleTheme() {
    document.documentElement.classList.toggle('dark-mode');
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    
    // Update button text
    const themeText = document.getElementById('theme-text');
    if (themeText) {
        themeText.textContent = isDark ? 'Light Mode' : 'Night Mode';
    }

    // Also explicitly set CSS variables on :root so any stylesheet ordering
    // or hardcoded selectors are overridden by these runtime values.
    applyThemeVariables(isDark);
}

// Load theme preference on page load
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-mode');
    document.body.classList.add('dark-mode');
    const themeText = document.getElementById('theme-text');
    if (themeText) {
        themeText.textContent = 'Light Mode';
    }
}

// Apply CSS variable values according to current theme
function applyThemeVariables(isDark) {
    const root = document.documentElement;
    if (isDark) {
        root.style.setProperty('--bg-primary', '#1a1a1a');
        root.style.setProperty('--bg-card', '#2a2a2a');
        root.style.setProperty('--text-primary', '#f0f0f0');
        root.style.setProperty('--text-muted', '#9aa4b2');
        root.style.setProperty('--border-color', '#444');
        root.style.setProperty('--sidebar-bg', '#1f1f1f');
        root.style.setProperty('--sidebar-border', '#444');
    } else {
        root.style.setProperty('--bg-primary', '#f9f9f9');
        root.style.setProperty('--bg-card', '#ffffff');
        root.style.setProperty('--text-primary', '#333333');
        root.style.setProperty('--text-muted', '#999999');
        root.style.setProperty('--border-color', '#e0e0e0');
        root.style.setProperty('--sidebar-bg', '#f4f4f4');
        root.style.setProperty('--sidebar-border', '#ddd');
    }
}

// Ensure variables are applied at load according to persisted preference
document.addEventListener('DOMContentLoaded', function () {
    const isDark = localStorage.getItem('theme') === 'dark' || document.body.classList.contains('dark-mode');
    applyThemeVariables(isDark);
    console.debug('[theme] applied variables, dark=', isDark, ' --sidebar-bg=', getComputedStyle(document.documentElement).getPropertyValue('--sidebar-bg'));
});
</script>
