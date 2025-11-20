<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Clario - Beranda</title>
    <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    /* Menu styling only */
    .more-btn { z-index: 9999; background:none; border:none; padding:6px; border-radius:6px; color:#6c757d; cursor:pointer; }
    .more-menu { position: fixed; min-width:150px; background:#fff; border-radius:8px; border:1px solid rgba(0,0,0,0.08); box-shadow:0 6px 18px rgba(0,0,0,0.08); overflow:hidden; z-index:99999; display:none; padding:6px 0; }
    .more-menu.show { display:block; }
    .more-menu .more-item { display:flex; align-items:center; gap:10px; width:100%; padding:8px 12px; font-size:14px; background:none; border:none; text-align:left; cursor:pointer; }
    .more-menu .more-item i { width:18px; text-align:center; }
    .more-menu .more-item:hover { background:#f5f9fb; }
    </style>
</head>
<body style="background-color:var(--bg-primary);">
<script>
// Load theme preference immediately to prevent flash
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-mode');
    document.body.classList.add('dark-mode');
}
</script>
<div class="d-flex">
    
<div class="sidebar p-3 d-flex flex-column" id="sidebar">
    <div class="d-flex align-items-center mb-4 logo-container">
        <img src="/ClairoCloud-fdb78ea278ebf602e9626d6a2e26712ef4955628/app/public/assets/image/clairo.png" alt="logo" class="me-2" style="width: 70px;">
        <div>
            <h4 class="fw-bold logo-text mb-0" style="font-family: 'Krona One', sans-serif;">
                <span class="text-teal">C</span>lario
            </h4>
                        <div class="small text-muted">Selamat datang, user</div>
                    </div>
    </div>

    <!-- Sidebar upload: hidden form + file input. Clicking the button opens file picker and submits to upload.php -->
    <!-- Hidden for admin users -->
        <form id="sidebar-upload-form" action="upload.php" method="post" enctype="multipart/form-data" style="display:none;">
        <input type="file" name="upload_file" id="sidebar-upload-input">
    </form>
    <button id="sidebar-upload-btn" class="upload-btn mb-4" type="button">
        <i class="fa fa-plus me-1"></i> Upload
    </button>
    
    <ul class="nav flex-column mb-4">
                <li class="nav-item"><a href="/ClairoCloud-fdb78ea278ebf602e9626d6a2e26712ef4955628/app/public/index.php" class="nav-link active"><i class="fa fa-home me-2"></i> Beranda</a></li>
        <li class="nav-item"><a href="/ClairoCloud-fdb78ea278ebf602e9626d6a2e26712ef4955628/app/public/semuafile.php" class="nav-link "><i class="fa fa-layer-group me-2"></i> Semua File</a></li>
        <li class="nav-item"><a href="/ClairoCloud-fdb78ea278ebf602e9626d6a2e26712ef4955628/app/public/favorit.php" class="nav-link "><i class="fa fa-star me-2"></i> Favorit</a></li>
        <li class="nav-item"><a href="/ClairoCloud-fdb78ea278ebf602e9626d6a2e26712ef4955628/app/public/sampah.php" class="nav-link "><i class="fa fa-trash me-2"></i> Sampah</a></li>
        <li class="nav-item"><a href="/ClairoCloud-fdb78ea278ebf602e9626d6a2e26712ef4955628/app/public/request_storage.php" class="nav-link "><i class="fa fa-plus-circle me-2"></i> Minta Storage</a></li>
            </ul>
    <div class="mt-auto">
                <div class="d-flex align-items-center mb-2">
          
           
        </div>
        
        <!-- Storage info hidden for admin users -->
                <div class="storage">
            <p class="fw-bold small mb-1">Penyimpanan</p>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <p class="small text-muted mt-1">0.20 MB dari 1,024.00 MB digunakan (0.02%)</p>
        </div>
        
        <!-- Theme toggle button -->
        <div class="mt-3 pt-3 border-top">
            <button class="btn btn-sm btn-outline-secondary w-100" onclick="toggleTheme()" title="Toggle Night Mode">
                <i class="fa fa-moon me-2"></i> <span id="theme-text">Night Mode</span>
            </button>
        </div>

        <!-- Logout button for admin users -->
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
const BASE_URL = '/ClairoCloud-fdb78ea278ebf602e9626d6a2e26712ef4955628/app/public';
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
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    
    // Update button text
    const themeText = document.getElementById('theme-text');
    if (themeText) {
        themeText.textContent = isDark ? 'Light Mode' : 'Night Mode';
    }
}

// Load theme preference on page load
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-mode');
    const themeText = document.getElementById('theme-text');
    if (themeText) {
        themeText.textContent = 'Light Mode';
    }
}
</script>
    <div class="main flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">Beranda</h4>
            <div class="d-flex align-items-center header-controls gap-3">
                <div class="search-bar d-flex align-items-center">
                    <input type="text" id="search-input" class="form-control rounded-pill" placeholder="Telusuri file..." style="background-color:#d4dedf; width:280px;">
                </div>
                <span class="iconify ms-3 fs-5 settings-btn" data-icon="mdi:settings" title="Pengaturan" style="cursor:pointer;"></span>
                <button class="btn btn-link p-0 ms-3" data-bs-toggle="modal" data-bs-target="#profileModal" title="Akun"><i class="fa fa-user fs-5"></i></button>
            </div>
        </div>

        <div class="header-section d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="fs-5 mb-1">Selamat datang di <span class="text-info fw-semibold">Clario</span>!</p>
                <h6 class="fw-bold mt-3">Baru-baru ini diunggah</h6>
                <p class="text-muted small">Lihat file yang baru-baru ini diunggah.</p>
            </div>
            <div class="view-toggle">
                <button class="toggle-btn active" id="grid-view" title="Tampilan Kotak"><span class="iconify" data-icon="mdi:view-grid-outline" data-width="18"></span></button>
                <button class="toggle-btn" id="list-view" title="Tampilan Daftar"><span class="iconify" data-icon="mdi:view-list-outline" data-width="18"></span></button>
            </div>
        </div>

        <div id="file-grid" data-page="home">
                        <div class="file-item" data-file-id="9" data-file-url="uploads/user_3\get_return_label_1763633096_691ee7c897739.pdf" data-file-name="get_return_label.pdf" data-file-mime="application/pdf">
                <div class="file-card">
                    <div class="file-card-inner">
                        <div class="card-overlay">
                            <button class="btn btn-sm btn-light fav-btn" title="Tambah ke favorit"><i class="fa fa-star"></i></button>
                            <button class="btn btn-sm btn-light del-btn" title="Hapus"><i class="fa fa-trash"></i></button>
                        </div>
                        <div class="file-thumbnail">
                                                            <img src="assets/icons/pdf.png" alt="get_return_label.pdf" style="max-width: 60px; max-height: 60px;">
                                                    </div>
                    </div>
                    <button class="more-btn" aria-label="Opsi"><i class="fa fa-ellipsis-v"></i></button>
                    <div class="file-info">
                        <p class="file-name file-name-multiline">get_return_label.pdf</p>
                        <p class="file-size">101.33 K</p>
                    </div>
                </div>
            </div>
                        <div class="file-item" data-file-id="8" data-file-url="uploads/user_3\users_export_2025-11-20_09-21-44__1__1763630739_691ede93eb036.csv" data-file-name="users_export_2025-11-20_09-21-44 (1).csv" data-file-mime="application/csv">
                <div class="file-card">
                    <div class="file-card-inner">
                        <div class="card-overlay">
                            <button class="btn btn-sm btn-light fav-btn" title="Tambah ke favorit"><i class="fa fa-star"></i></button>
                            <button class="btn btn-sm btn-light del-btn" title="Hapus"><i class="fa fa-trash"></i></button>
                        </div>
                        <div class="file-thumbnail">
                                                            <img src="assets/icons/file.png" alt="users_export_2025-11-20_09-21-44 (1).csv" style="max-width: 60px; max-height: 60px;">
                                                    </div>
                    </div>
                    <button class="more-btn" aria-label="Opsi"><i class="fa fa-ellipsis-v"></i></button>
                    <div class="file-info">
                        <p class="file-name file-name-multiline">users_export_2025-11-20_09-21-44 (1).csv</p>
                        <p class="file-size">292 B</p>
                    </div>
                </div>
            </div>
                    </div>

    </div>
</div>

<!-- Global floating menu -->
<div id="global-more-menu" class="more-menu" role="menu" aria-hidden="true" style="display:none;">
    <button class="more-item download" title="Download"><i class="fa fa-download"></i> Download</button>
    <button class="more-item rename" title="Ganti nama"><i class="fa fa-pencil-alt"></i> Ganti nama</button>
    <button class="more-item share" title="Bagikan"><i class="fa fa-user-plus"></i> Bagikan</button>
    <button class="more-item favorite-menu" title="Tambahkan ke favorit"><i class="fa fa-star"></i> Favorit</button>
    <button class="more-item delete-menu" title="Hapus"><i class="fa fa-trash"></i> Hapus</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // view toggle (with state persistence)
    const gridBtn = document.getElementById('grid-view');
    const listBtn = document.getElementById('list-view');
    const fileGrid = document.getElementById('file-grid');

    if (fileGrid && gridBtn && listBtn) {
        // Initialize with grid view mode by default
        fileGrid.classList.add('grid-view-mode');
        fileGrid.classList.remove('list-view-mode');
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');

        // Grid view button
        gridBtn.addEventListener('click', function(e){
            e.preventDefault();
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
            fileGrid.classList.remove('list-view-mode');
            fileGrid.classList.add('grid-view-mode');
            localStorage.setItem('fileViewMode', 'grid');
        });

        // List view button
        listBtn.addEventListener('click', function(e){
            e.preventDefault();
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
            fileGrid.classList.add('list-view-mode');
            fileGrid.classList.remove('grid-view-mode');
            localStorage.setItem('fileViewMode', 'list');
        });
    }

    // Search functionality for home page
    const searchInput = document.getElementById('search-input');
    if (searchInput && fileGrid) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            const fileItems = fileGrid.querySelectorAll('.file-item');
            
            fileItems.forEach(item => {
                const fileName = (item.dataset.fileName || '').toLowerCase();
                if (query === '' || fileName.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});

// global menu logic
(function(){
    var grid = document.getElementById('file-grid');
    var globalMenu = document.getElementById('global-more-menu');
    if (!grid || !globalMenu) return;

    grid.addEventListener('click', function(e){
        var btn = e.target.closest('.more-btn');
        if (btn) {
            e.stopPropagation();
            var fileItem = btn.closest('.file-item');
            if (!fileItem) return;
            // attach data
            globalMenu.dataset.fileId = fileItem.dataset.fileId || '';
            globalMenu.dataset.fileName = fileItem.dataset.fileName || '';
            globalMenu.dataset.fileUrl = fileItem.dataset.fileUrl || '';
            // show and position
            globalMenu.classList.add('show'); globalMenu.style.display='block'; globalMenu.setAttribute('aria-hidden','false');
            var rect = btn.getBoundingClientRect();
            var menuW = globalMenu.offsetWidth, menuH = globalMenu.offsetHeight;
            var left = rect.right - menuW; if (left < 8) left = 8; if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
            var top = rect.bottom + 6; if (top + menuH > window.innerHeight - 8) { top = rect.top - menuH - 6; if (top < 8) top = 8; }
            globalMenu.style.left = left + 'px'; globalMenu.style.top = top + 'px';
            fileItem.classList.add('menu-open');
        }
    });

    document.addEventListener('click', function(ev){ if (!globalMenu.contains(ev.target) && !ev.target.closest('.more-btn')) { globalMenu.classList.remove('show'); globalMenu.style.display='none'; globalMenu.setAttribute('aria-hidden','true'); document.querySelectorAll('.file-item.menu-open').forEach(function(it){ it.classList.remove('menu-open'); }); } }, { passive: true });

    globalMenu.addEventListener('click', function(e){ var action = e.target.closest('.more-item'); if (!action) return; e.stopPropagation(); var fileId = globalMenu.dataset.fileId || null; var fileName = globalMenu.dataset.fileName || ''; var fileItemEl = fileId ? document.querySelector('.file-item[data-file-id=\"' + fileId + '\"]') : null; if (action.classList.contains('download')) { if (!fileId) { Swal.fire({icon: 'error', title: 'Error', text: 'File ID tidak ditemukan', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); return; } var url = 'download.php?file_id=' + encodeURIComponent(fileId); var a = document.createElement('a'); a.href = url; a.download = fileName || ''; document.body.appendChild(a); a.click(); a.remove(); Swal.fire({icon: 'success', title: 'Berhasil', text: 'File berhasil diunduh: ' + fileName, position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); } else if (action.classList.contains('rename')) { var current = fileName || (fileItemEl && fileItemEl.dataset.fileName) || ''; var newName = prompt('Ganti nama file menjadi:', current || ''); if (newName !== null && newName.trim() !== '') { fetch('rename.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId, new_name: newName.trim() }) }).then(r=>r.json()).then(j=>{ if (j.success) { if (fileItemEl) { fileItemEl.dataset.fileName = j.new_name; } Swal.fire({icon: 'success', title: 'Berhasil', text: 'Nama file berhasil diubah menjadi: ' + j.new_name, position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); } else Swal.fire({icon: 'error', title: 'Gagal', text: j.message||'Gagal mengubah nama file', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); }).catch(()=>Swal.fire({icon: 'error', title: 'Error', text: 'Network error', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000})); } } else if (action.classList.contains('share')) { Swal.fire({icon: 'info', title: 'Info', text: 'Fungsi bagikan belum diimplementasikan di demo ini.', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); } else if (action.classList.contains('favorite-menu')) { if (!fileId) return; fetch('favorite.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId }) }).then(r=>r.json()).then(j=>{ if (j.success) { if (fileItemEl) fileItemEl.classList.toggle('favorited', j.is_favorite == 1); var msg = j.is_favorite == 1 ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit'; Swal.fire({icon: 'success', title: 'Berhasil', text: msg, position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); } else Swal.fire({icon: 'error', title: 'Gagal', text: j.message||'Gagal', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); }).catch(()=>Swal.fire({icon: 'error', title: 'Error', text: 'Network error', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000})); } else if (action.classList.contains('delete-menu')) { if (!fileId) return; Swal.fire({title: 'Hapus File?', text: 'File ini akan dipindahkan ke sampah', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus', confirmButtonColor: '#d33', cancelButtonText: 'Batal', position: 'bottom-right'}).then(result=>{ if (result.isConfirmed) { fetch('delete.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId }) }).then(r=>r.json()).then(j=>{ if (j.success) { if (fileItemEl) fileItemEl.remove(); Swal.fire({icon: 'success', title: 'Berhasil', text: 'File berhasil dihapus', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); } else Swal.fire({icon: 'error', title: 'Gagal', text: j.message||'Gagal', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); }).catch(()=>Swal.fire({icon: 'error', title: 'Error', text: 'Network error', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000})); } }); } globalMenu.classList.remove('show'); globalMenu.style.display='none'; globalMenu.setAttribute('aria-hidden','true'); });

})();
</script>

// Theme toggle function (global)
<script>
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

// Search functionality for home page
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const fileGrid = document.getElementById('file-grid');
    
    if (!searchInput || !fileGrid) return;
    
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const fileItems = fileGrid.querySelectorAll('.file-item');
        
        fileItems.forEach(item => {
            const fileName = (item.dataset.fileName || '').toLowerCase();
            if (query === '' || fileName.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
});
</script>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Akun</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                                <div class="d-flex align-items-center mb-3">
                    <div style="width:64px; height:64px; border-radius:50%; overflow:hidden; background:#e9ecef; display:flex; align-items:center; justify-content:center;">
                                                    <i class="fa fa-user fa-2x text-muted"></i>
                                            </div>
                    <div class="ms-3 flex-grow-1">
                        <div class="fw-bold">dzikri</div>
                        <div class="text-muted small">user@clairocloud.local</div>
                    </div>
                </div>

                <div class="storage mb-3">
                        <p class="fw-bold small mb-1">Penyimpanan</p>
                        <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="small text-muted mt-1">0.20 MB dari 1,024.00 MB digunakan (0.02%)</p>
                </div>

                            </div>
            <div class="modal-footer">
                                    <a href="request_storage.php" class="btn btn-outline-primary">Dapatkan penyimpanan</a>
                    <a href="logout.php" class="btn btn-outline-secondary">Log out</a>
                            </div>
        </div>
    </div>
</div>
</body>
</html>