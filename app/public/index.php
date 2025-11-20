<?php
// index_fixed.php - Unified and cleaned version
// Pastikan file ini diletakkan di root project (ganti nama jika perlu)

session_start();
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/file_functions.php';

function extractCategory($mime) {
    if (!$mime) return 'other';
    if (strpos($mime, 'image/') === 0) return 'image';
    if (strpos($mime, 'video/') === 0) return 'video';
    if (strpos($mime, 'audio/') === 0) return 'audio';

    // Document check
    $docs = ['pdf', 'msword', 'vnd', 'text', 'presentation', 'spreadsheet'];
    foreach ($docs as $d) {
        if (strpos($mime, $d) !== false) return 'document';
    }

    // Archive
    if (strpos($mime, 'zip') !== false || strpos($mime,'rar') !== false || strpos($mime,'7z') !== false)
        return 'archive';

    return 'other';
}

// user and storage info
$userId = $_SESSION['user_id'] ?? null;
$user = null;
$storagePercent = 0;
$storageText = '';
if ($userId) {
    $user = fetchOne('SELECT username, email, full_name, avatar, storage_quota, storage_used FROM users WHERE id = ?', [$userId]);
    if ($user && !empty($user['storage_quota'])) {
        $storagePercent = round(($user['storage_used'] / $user['storage_quota']) * 100, 2);
        $used = isset($user['storage_used']) ? number_format($user['storage_used'] / 1024 / 1024, 2) . ' MB' : '0 B';
        $quota = isset($user['storage_quota']) ? number_format($user['storage_quota'] / 1024 / 1024, 2) . ' MB' : '0 B';
        $storageText = "$used dari $quota";
    }
}

// fetch up to 10 newest files for this user
$items = [];
if ($userId && file_exists(__DIR__ . '/../src/StorageManager.php')) {
    require_once __DIR__ . '/../src/StorageManager.php';
    $sm = new StorageManager();
    $dbItems = $sm->getUserFiles($userId);
    if ($dbItems) {
        foreach ($dbItems as $it) {
            $items[] = [
                'id' => $it['id'] ?? 0,
                'name' => $it['original_name'] ?? $it['filename'],
                'size' => $it['size'] ?? ($it['file_size'] ?? 0),
                'url' => 'uploads/' . ($it['thumbnail_path'] ?: $it['file_path']),
                'mime' => $it['mime'] ?? $it['mime_type'] ?? 'application/octet-stream',
                'is_favorite' => $it['is_favorite'] ?? 0,
            ];
        }
    }
}
usort($items, function($a,$b){ return intval($b['id'] ?? 0) - intval($a['id'] ?? 0); });
$items = array_slice($items, 0, 10);

// NOTE: human_filesize() should live in file_functions.php only.
// If file_functions.php doesn't define it, add it there (with function_exists guard).
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Clario - Beranda (fixed)</title>
    <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    /* Minimal extra styles retained from original */
    .more-btn { z-index: 9999; background:none; border:none; padding:6px; border-radius:6px; color:#6c757d; cursor:pointer; }
    .more-menu { position: fixed; min-width:150px; background:#fff; border-radius:8px; border:1px solid rgba(0,0,0,0.08); box-shadow:0 6px 18px rgba(0,0,0,0.08); overflow:hidden; z-index:99999; display:none; padding:6px 0; }
    .more-menu.show { display:block; }
    .more-menu .more-item { display:flex; align-items:center; gap:10px; width:100%; padding:8px 12px; font-size:14px; background:none; border:none; text-align:left; cursor:pointer; }
    .more-menu .more-item i { width:18px; text-align:center; }
    .more-menu .more-item:hover { background:#f5f9fb; }

    /* file-grid defaults (simple) */
    #file-grid.grid-view-mode { display:grid; grid-template-columns: repeat(auto-fill, minmax(160px,1fr)); gap: 12px; }
    #file-grid.list-view-mode { display:block; }
    .file-item { background:transparent; }
    .file-card { padding:10px; border-radius:10px; background:#fff; display:flex; flex-direction:column; align-items:center; gap:8px; }
    .file-info { text-align:center; }
    
    /* New styles for search and filter functionality */
    .file-item.hidden { display: none !important; }
    </style>
</head>
<body id="index-page" style="background-color:var(--bg-primary);">
<script>
// Load theme preference immediately to prevent flash
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-mode');
    document.body.classList.add('dark-mode');
}
</script>
<div class="d-flex">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">Beranda</h4>
            <div class="d-flex align-items-center header-controls gap-3">
                <div class="search-bar d-flex align-items-center gap-2">
                    <input type="text" id="search-input" class="form-control rounded-pill" placeholder="Telusuri file..." style="background-color:#d4dedf; width:200px;">
                    <select id="category-filter" class="form-select rounded-pill" style="background-color:#d4dedf; width:120px;">
                        <option value="">Semua</option>
                        <option value="image">Gambar</option>
                        <option value="video">Video</option>
                        <option value="audio">Audio</option>
                        <option value="document">Dokumen</option>
                        <option value="archive">Arsip</option>
                        <option value="other">Lainnya</option>
                    </select>
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
            <?php if (!$userId): ?>
                <div class="text-center py-5">
                    <i class="fa fa-lock fa-3x text-muted mb-3" style="display: block;"></i>
                    <h5 class="fw-bold mb-2">Login untuk Menyimpan Data</h5>
                    <p class="text-muted mb-4">Silakan login untuk melihat dan mengelola file Anda.</p>
                    <a href="login.php" class="btn btn-primary">Login Sekarang</a>
                </div>
            <?php elseif (empty($items)): ?>
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-3x text-muted mb-3" style="display: block;"></i>
                    <h5 class="fw-bold mb-2">Tidak Ada File</h5>
                    <p class="text-muted mb-4">Mulai dengan mengupload file pertama Anda.</p>
                    <a href="#" class="btn btn-primary" onclick="document.querySelector('.upload-btn')?.click(); return false;">Upload File</a>
                </div>
            <?php else: ?>
                <?php foreach ($items as $it):
                    $fileId = intval($it['id'] ?? 0);
                    $fileNameRaw = $it['name'] ?? ($it['filename'] ?? 'file');
                    $fileName = htmlspecialchars($fileNameRaw, ENT_QUOTES);
                    $fileSizeStr = is_numeric($it['size'] ?? null) ? human_filesize($it['size']) : htmlspecialchars($it['size'] ?? '');
                    $mime = $it['mime'] ?? '';
                    $iconPath = 'assets/icons/file.png';
                    if (strpos($mime,'image/')===0) $iconPath='assets/icons/image.png';
                    elseif (strpos($mime,'video/')===0) $iconPath='assets/icons/vid.png';
                    elseif (strpos($mime,'audio/')===0) $iconPath='assets/icons/music.png';
                    elseif (strpos($mime,'pdf')!==false) $iconPath='assets/icons/pdf.png';
                    elseif (strpos($mime,'zip')!==false || strpos($mime,'compressed')!==false) $iconPath='assets/icons/archive.png';
                    $fileUrl = htmlspecialchars($it['url'] ?? '', ENT_QUOTES);
                    $categoryAttr = extractCategory($mime);
                ?>
              <div class="file-item"
     data-file-id="<?php echo $fileId; ?>"
     data-file-url="<?php echo $fileUrl; ?>"
     data-file-name="<?php echo $fileName; ?>"
     data-file-mime="<?php echo htmlspecialchars($mime, ENT_QUOTES); ?>"
     data-category="<?php echo $categoryAttr; ?>"
     data-name="<?php echo htmlspecialchars(strtolower($fileNameRaw), ENT_QUOTES); ?>">

    <div class="file-card">
        <div class="file-card-inner">
            <div class="file-thumbnail">
                <?php if (strpos($mime,'image/')===0): ?>
                    <img src="<?php echo $fileUrl; ?>" alt="<?php echo $fileName; ?>" style="max-width:100%; height:auto; border-radius:8px;">
                <?php else: ?>
                    <img src="<?php echo $iconPath; ?>" alt="<?php echo $fileName; ?>" style="max-width: 60px; max-height: 60px;">
                <?php endif; ?>
            </div>
        </div>

        <button class="more-btn" aria-label="Opsi"><i class="fa fa-ellipsis-v"></i></button>

        <div class="file-info">
            <p class="file-name file-name-multiline"><?php echo $fileName; ?></p>
            <p class="file-size"><?php echo $fileSizeStr; ?></p>
        </div>
    </div>

</div>

                <?php endforeach; ?>
            <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM loaded - starting search functionality');
    
    // Elements
    const fileGrid = document.getElementById('file-grid');
    const searchInput = document.getElementById('search-input');
    const categorySelect = document.getElementById('category-filter');

    // Add CSS for hiding
    const style = document.createElement('style');
    style.textContent = `
        .file-item.hidden { 
            display: none !important; 
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0 !important;
            width: 0 !important;
            overflow: hidden !important;
            position: absolute !important;
        }
        .file-item:not(.hidden) {
            display: block !important;
        }
    `;
    document.head.appendChild(style);

    // Simple and robust search function
    function performSearchAndFilter() {
        console.log('=== PERFORMING SEARCH ===');
        
        if (!fileGrid) {
            console.error('fileGrid not found!');
            return;
        }

        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedCategory = categorySelect.value;
        
        console.log('Search term:', searchTerm);
        console.log('Selected category:', selectedCategory);

        const fileItems = fileGrid.querySelectorAll('.file-item');
        console.log('Total file items found:', fileItems.length);

        let visibleCount = 0;

        fileItems.forEach((item, index) => {
            // Get file data
            const fileName = item.getAttribute('data-name') || '';
            const fileCategory = item.getAttribute('data-category') || '';
            
            console.log(`File ${index}: "${fileName}" [${fileCategory}]`);

            // Check matches
            const matchesSearch = searchTerm === '' || fileName.includes(searchTerm);
            const matchesCategory = selectedCategory === '' || fileCategory === selectedCategory;
            
            const shouldShow = matchesSearch && matchesCategory;
            
            console.log(`  Search match: ${matchesSearch}, Category match: ${matchesCategory}, Show: ${shouldShow}`);

            if (shouldShow) {
                item.classList.remove('hidden');
                item.style.display = '';
                item.style.visibility = 'visible';
                item.style.opacity = '1';
                visibleCount++;
            } else {
                item.classList.add('hidden');
                item.style.display = 'none';
                item.style.visibility = 'hidden';
                item.style.opacity = '0';
            }
        });

        console.log('Visible items:', visibleCount);

        // Handle no results
        const existingNoResults = document.getElementById('no-results-message');
        if (visibleCount === 0) {
            if (!existingNoResults) {
                const noResultsMsg = document.createElement('div');
                noResultsMsg.id = 'no-results-message';
                noResultsMsg.className = 'text-center py-5';
                noResultsMsg.innerHTML = `
                    <i class="fa fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold mb-2">Tidak Ada Hasil</h5>
                    <p class="text-muted">Tidak ada file yang sesuai dengan pencarian Anda.</p>
                `;
                fileGrid.appendChild(noResultsMsg);
                console.log('No results message added');
            }
        } else {
            if (existingNoResults) {
                existingNoResults.remove();
                console.log('No results message removed');
            }
        }
    }

    // Event listeners
    if (searchInput && categorySelect) {
        console.log('Adding event listeners');
        
        searchInput.addEventListener('input', function() {
            console.log('Search input changed');
            performSearchAndFilter();
        });
        
        categorySelect.addEventListener('change', function() {
            console.log('Category changed');
            performSearchAndFilter();
        });

        // Test the function immediately
        setTimeout(() => {
            console.log('Initial test of search function');
            performSearchAndFilter();
        }, 100);
    } else {
        console.error('Search elements not found!');
    }

    // Keep your existing view toggle and global menu code here
    const gridBtn = document.getElementById('grid-view');
    const listBtn = document.getElementById('list-view');
    const globalMenu = document.getElementById('global-more-menu');

    // View toggle (persisted) - your existing code
    if (fileGrid && gridBtn && listBtn) {
        const saved = localStorage.getItem('fileViewMode') || 'grid';
        if (saved === 'list') {
            fileGrid.classList.add('list-view-mode');
            fileGrid.classList.remove('grid-view-mode');
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
        } else {
            fileGrid.classList.add('grid-view-mode');
            fileGrid.classList.remove('list-view-mode');
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
        }

        gridBtn.addEventListener('click', function(e){ 
            e.preventDefault(); 
            gridBtn.classList.add('active'); 
            listBtn.classList.remove('active'); 
            fileGrid.classList.remove('list-view-mode'); 
            fileGrid.classList.add('grid-view-mode'); 
            localStorage.setItem('fileViewMode', 'grid'); 
        });
        
        listBtn.addEventListener('click', function(e){ 
            e.preventDefault(); 
            listBtn.classList.add('active'); 
            gridBtn.classList.remove('active'); 
            fileGrid.classList.add('list-view-mode'); 
            fileGrid.classList.remove('grid-view-mode'); 
            localStorage.setItem('fileViewMode', 'list'); 
        });
    }

    // Global menu logic - your existing code
    if (fileGrid && globalMenu) {
        fileGrid.addEventListener('click', function(e){
            var btn = e.target.closest('.more-btn');
            if (btn) {
                e.stopPropagation();
                var fileItem = btn.closest('.file-item');
                if (!fileItem) return;
                globalMenu.dataset.fileId = fileItem.getAttribute('data-file-id') || '';
                globalMenu.dataset.fileName = fileItem.getAttribute('data-file-name') || '';
                globalMenu.dataset.fileUrl = fileItem.getAttribute('data-file-url') || '';
                globalMenu.classList.add('show'); 
                globalMenu.style.display='block'; 
                globalMenu.setAttribute('aria-hidden','false');
                var rect = btn.getBoundingClientRect();
                var menuW = globalMenu.offsetWidth, menuH = globalMenu.offsetHeight;
                var left = rect.right - menuW; 
                if (left < 8) left = 8; 
                if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
                var top = rect.bottom + 6; 
                if (top + menuH > window.innerHeight - 8) { 
                    top = rect.top - menuH - 6; 
                    if (top < 8) top = 8; 
                }
                globalMenu.style.left = left + 'px'; 
                globalMenu.style.top = top + 'px';
                fileItem.classList.add('menu-open');
            }
        });

        document.addEventListener('click', function(ev){ 
            if (!globalMenu.contains(ev.target) && !ev.target.closest('.more-btn')) { 
                globalMenu.classList.remove('show'); 
                globalMenu.style.display='none'; 
                globalMenu.setAttribute('aria-hidden','true'); 
                document.querySelectorAll('.file-item.menu-open').forEach(function(it){ 
                    it.classList.remove('menu-open'); 
                }); 
            } 
        }, { passive: true });

        globalMenu.addEventListener('click', function(e){ 
            var action = e.target.closest('.more-item'); 
            if (!action) return; 
            e.stopPropagation(); 
            var fileId = globalMenu.dataset.fileId || null; 
            var fileName = globalMenu.dataset.fileName || ''; 
            var fileItemEl = fileId ? document.querySelector('.file-item[data-file-id="' + fileId + '"]') : null; 
            
            if (action.classList.contains('download')) { 
                if (!fileId) { 
                    Swal.fire({icon: 'error', title: 'Error', text: 'File ID tidak ditemukan', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); 
                    return; 
                } 
                var url = 'download.php?file_id=' + encodeURIComponent(fileId); 
                var a = document.createElement('a'); 
                a.href = url; 
                a.download = fileName || ''; 
                document.body.appendChild(a); 
                a.click(); 
                a.remove(); 
                Swal.fire({icon: 'success', title: 'Berhasil', text: 'File berhasil diunduh: ' + fileName, position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); 
            } else if (action.classList.contains('rename')) { 
                var current = fileName || (fileItemEl && fileItemEl.getAttribute('data-file-name')) || ''; 
                var newName = prompt('Ganti nama file menjadi:', current || ''); 
                if (newName !== null && newName.trim() !== '') { 
                    fetch('rename.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId, new_name: newName.trim() }) })
                    .then(r=>r.json())
                    .then(j=>{ 
                        if (j.success) { 
                            if (fileItemEl) { 
                                fileItemEl.setAttribute('data-file-name', j.new_name);
                                fileItemEl.setAttribute('data-name', j.new_name.toLowerCase());
                                const fileNameElement = fileItemEl.querySelector('.file-name');
                                if (fileNameElement) {
                                    fileNameElement.textContent = j.new_name;
                                }
                            } 
                            Swal.fire({icon: 'success', title: 'Berhasil', text: 'Nama file berhasil diubah menjadi: ' + j.new_name, position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); 
                        } else {
                            Swal.fire({icon: 'error', title: 'Gagal', text: j.message||'Gagal mengubah nama file', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); 
                        }
                    })
                    .catch(()=>Swal.fire({icon: 'error', title: 'Error', text: 'Network error', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000})); 
                } 
            } else if (action.classList.contains('share')) { 
                Swal.fire({icon: 'info', title: 'Info', text: 'Fungsi bagikan belum diimplementasikan di demo ini.', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); 
            } else if (action.classList.contains('favorite-menu')) { 
                if (!fileId) return; 
                fetch('favorite.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId }) })
                .then(r=>r.json())
                .then(j=>{ 
                    if (j.success) { 
                        if (fileItemEl) fileItemEl.classList.toggle('favorited', j.is_favorite == 1); 
                        var msg = j.is_favorite == 1 ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit'; 
                        Swal.fire({icon: 'success', title: 'Berhasil', text: msg, position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); 
                    } else {
                        Swal.fire({icon: 'error', title: 'Gagal', text: j.message||'Gagal', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); 
                    }
                })
                .catch(()=>Swal.fire({icon: 'error', title: 'Error', text: 'Network error', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000})); 
            } else if (action.classList.contains('delete-menu')) { 
                if (!fileId) return; 
                Swal.fire({
                    title: 'Hapus File?', 
                    text: 'File ini akan dipindahkan ke sampah', 
                    icon: 'warning', 
                    showCancelButton: true, 
                    confirmButtonText: 'Ya, Hapus', 
                    confirmButtonColor: '#d33', 
                    cancelButtonText: 'Batal', 
                    position: 'bottom-right'
                }).then(result=>{ 
                    if (result.isConfirmed) { 
                        fetch('delete.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId }) })
                        .then(r=>r.json())
                        .then(j=>{ 
                            if (j.success) { 
                                if (fileItemEl) fileItemEl.remove(); 
                                Swal.fire({icon: 'success', title: 'Berhasil', text: 'File berhasil dihapus', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); 
                            } else {
                                Swal.fire({icon: 'error', title: 'Gagal', text: j.message||'Gagal', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); 
                            }
                        })
                        .catch(()=>Swal.fire({icon: 'error', title: 'Error', text: 'Network error', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000})); 
                    } 
                }); 
            } 
            globalMenu.classList.remove('show'); 
            globalMenu.style.display='none'; 
            globalMenu.setAttribute('aria-hidden','true'); 
        });
    }

    console.log('Search functionality initialized successfully');
    // Cek apakah class hidden berfungsi
document.querySelectorAll('.file-item').forEach(item => {
    console.log(item.className, item.style.display);
});

// Force hide manual
document.querySelectorAll('.file-item').forEach(item => {
    item.style.display = 'none !important';
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
                <?php if ($user): ?>
                <div class="d-flex align-items-center mb-3">
                    <div style="width:64px; height:64px; border-radius:50%; overflow:hidden; background:#e9ecef; display:flex; align-items:center; justify-content:center;">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="avatar" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <i class="fa fa-user fa-2x text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <div class="ms-3 flex-grow-1">
                        <div class="fw-bold"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                </div>

                <div class="storage mb-3">
                        <p class="fw-bold small mb-1">Penyimpanan</p>
                        <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo intval($storagePercent); ?>%;" aria-valuenow="<?php echo intval($storagePercent); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="small text-muted mt-1"><?php echo htmlspecialchars($storageText ?: '0 B dari 0 B'); ?> digunakan (<?php echo $storagePercent; ?>%)</p>
                </div>

                <?php else: ?>
                <p class="text-muted">Anda belum masuk. <a href="login.php">Masuk</a></p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <?php if ($user): ?>
                    <a href="request_storage.php" class="btn btn-outline-primary">Dapatkan penyimpanan</a>
                    <a href="logout.php" class="btn btn-outline-secondary">Log out</a>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>