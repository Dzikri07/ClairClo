<?php
// index.php - Homepage showing 10 newest items (icon-only) with global more-menu
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/file_functions.php';

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
// fetch up to 10 newest files for this user (if logged in) using StorageManager when available
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
if (empty($items)) {
    $items = [
        ["id"=>0, "name" => "Foto_dibali.jpg", "size" => "5 MB", 'mime'=>'image/jpeg'],
        ["id"=>0, "name" => "dadali.mp3", "size" => "3,1 MB", 'mime'=>'audio/mpeg'],
        ["id"=>0, "name" => "laporan_pkl.docx", "size" => "488 KB", 'mime'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ["id"=>0, "name" => "gustracing.mp4", "size" => "50 MB", 'mime'=>'video/mp4']
    ];
}
usort($items, function($a,$b){ return intval($b['id'] ?? 0) - intval($a['id'] ?? 0); });
$items = array_slice($items, 0, 10);
?>
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
    .card-overlay { pointer-events: none; }
    .card-overlay .btn { pointer-events: auto; }
    .more-btn { z-index: 9999; background:none; border:none; padding:6px; border-radius:6px; color:#6c757d; }
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
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">Beranda</h4>
            <div class="d-flex align-items-center header-controls">
                <div class="search-bar d-flex align-items-center">
                    <input type="text" class="form-control rounded-pill" placeholder="Telusuri file..." style="background-color:#d4dedf; width:280px;">
                </div>
                <span class="iconify ms-3 fs-5" data-icon="mdi:settings" title="Pengaturan"></span>
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

        <div class="row g-3 mt-3" id="file-grid" data-page="home">
            <?php foreach ($items as $it):
                $fileId = intval($it['id'] ?? 0);
                $fileName = htmlspecialchars($it['name'] ?? ($it['filename'] ?? 'file'), ENT_QUOTES);
                $fileSizeStr = is_numeric($it['size'] ?? null) ? human_filesize($it['size']) : htmlspecialchars($it['size'] ?? '');
                $mime = $it['mime'] ?? '';
                $iconClass = 'fa-file';
                if (strpos($mime,'image/')===0) $iconClass='fa-file-image';
                elseif (strpos($mime,'video/')===0) $iconClass='fa-file-video';
                elseif (strpos($mime,'audio/')===0) $iconClass='fa-file-audio';
                elseif (strpos($mime,'pdf')!==false) $iconClass='fa-file-pdf';
                elseif (strpos($mime,'zip')!==false || strpos($mime,'compressed')!==false) $iconClass='fa-file-archive';
                $fileUrl = htmlspecialchars($it['url'] ?? '', ENT_QUOTES);
            ?>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2 file-item" data-file-id="<?php echo $fileId; ?>" data-file-url="<?php echo $fileUrl; ?>" data-file-name="<?php echo $fileName; ?>">
                <div class="file-card text-center p-3 shadow-sm position-relative">
                    <div class="file-thumb mb-2"><i class="fa <?php echo $iconClass; ?> fa-2x text-info"></i></div>
                    <p class="mb-1 fw-semibold small"><?php echo $fileName; ?></p>
                    <p class="text-muted small"><?php echo $fileSizeStr; ?></p>
                    <button class="more-btn position-absolute" style="right:8px; top:8px;" aria-label="Opsi"><i class="fa fa-ellipsis-v"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
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
// view toggle (visual only)
const gridBtn = document.getElementById('grid-view');
const listBtn = document.getElementById('list-view');
gridBtn && gridBtn.addEventListener('click', ()=>{ gridBtn.classList.add('active'); listBtn && listBtn.classList.remove('active'); document.getElementById('file-grid').classList.remove('list-view-mode'); document.getElementById('file-grid').classList.add('grid-view-mode'); });
listBtn && listBtn.addEventListener('click', ()=>{ listBtn.classList.add('active'); gridBtn && gridBtn.classList.remove('active'); document.getElementById('file-grid').classList.add('list-view-mode'); document.getElementById('file-grid').classList.remove('grid-view-mode'); });

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

<!-- Theme toggle function (global) -->
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