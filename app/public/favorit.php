<?php
// favorit.php - Favorit page
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/file_functions.php';
require_once __DIR__ . '/../src/StorageManager.php';

$userId = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="id"><!--  -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorit - Clario</title>
    <link href="https://fonts.googlea   pis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background-color: #f9f9f9;">
<div class="d-flex">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main flex-grow-1 p-4">
        <div class="header-section d-flex justify-content-between align-items-center mb-4">
            <div class="welcome-text">
                <p class="fs-5 mb-1">Favorit</p>
                <h6 class="fw-bold mt-3">File favorit</h6>
                <p class="text-muted small">Lihat file yang kamu tandai sebagai favorit.</p>
            </div>
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <div style="display:flex; gap:8px; align-items:center;">
                    <input id="file-search-input" type="text" class="form-control form-control-sm" placeholder="Cari file..." style="width:260px;">
                    <select id="category-filter" class="form-select form-select-sm" style="width:150px;">
                        <option value="">Semua Kategori</option>
                        <option value="image">Gambar</option>
                        <option value="video">Video</option>
                        <option value="audio">Audio</option>
                        <option value="document">Dokumen</option>
                        <option value="archive">Arsip</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div class="view-toggle">
                <button class="toggle-btn active" id="grid-view" title="Tampilan Kotak">
                    <span class="iconify" data-icon="mdi:view-grid-outline" data-width="18"></span>
                </button>
                <button class="toggle-btn" id="list-view" title="Tampilan Daftar">
                    <span class="iconify" data-icon="mdi:view-list-outline" data-width="18"></span>
                </button>
                </div>
            </div>
        </div>

        <?php
        if (!$userId) {
            echo '<div class="alert alert-warning">Silakan login untuk melihat file favorit Anda.</div>';
        } else {
            $sm = new StorageManager();
            $dbItems = $sm->getUserFiles($userId, ['is_favorite' => 1]);

            $items = [];
            if ($dbItems) {
                foreach ($dbItems as $it) {
                    $url = 'uploads/' . ($it['thumbnail_path'] ?: $it['file_path']);
                    $items[] = [
                        'id' => $it['id'] ?? 0,
                        'name' => $it['original_name'] ?? $it['filename'],
                        'size' => $it['size'] ?? ($it['file_size'] ?? 0),
                        'url' => $url,
                        'mime' => $it['mime'] ?? $it['mime_type'] ?? mime_content_type(__DIR__ . '/uploads/' . ($it['file_path'] ?? '')),
                        'is_favorite' => $it['is_favorite'] ?? 0
                    ];
                }
            }

            if (empty($items)) {
                echo '<div class="text-center mt-4">';
                echo '<img src="assets/image/defaultNotfound.png" alt="Tidak ada file favorit" style="max-width:260px; opacity:0.95;">';
                echo '<p class="text-muted small mt-2">Tidak ada file favorit.</p>';
                echo '</div>';
            } else {
                // show counts header
                $total = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0', [$userId]);
                $favorites = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0 AND is_favorite = 1', [$userId]);
                $trash = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 1', [$userId]);
                echo '<div class="row mb-3">';
                echo '<div class="col-auto d-flex gap-4 align-items-center">';
                echo '<div class="text-center"><i class="fa fa-folder fa-2x text-dark"></i><div class="fw-bold">' . intval($total['cnt'] ?? 0) . ' File</div><div class="small text-muted">Total file yang tersimpan</div></div>';
                echo '<div class="text-center"><i class="fa fa-star fa-2x text-warning"></i><div class="fw-bold">' . intval($favorites['cnt'] ?? 0) . ' Favorit</div><div class="small text-muted">File yang sering diakses</div></div>';
                echo '<div class="text-center"><i class="fa fa-trash fa-2x text-danger"></i><div class="fw-bold">' . intval($trash['cnt'] ?? 0) . ' Sampah</div><div class="small text-muted">File siap hapus permanen</div></div>';
                echo '</div></div>';

                // render grid
                echo '<div id="file-grid" data-page="favorites">';
                foreach ($items as $it) {
                    $fileIdAttr = intval($it['id']);
                    $isFav = $it['is_favorite'] ? 'true' : 'false';
                    $favClass = !empty($it['is_favorite']) ? ' active' : '';
                    echo '<div class="file-item" data-file-id="' . $fileIdAttr . '">';
                    echo '<div class="file-card">';
                    echo '<div class="file-card-inner">';
                    echo '<div class="card-overlay">';
                    echo '<button class="btn btn-sm btn-light fav-btn' . $favClass . '" data-file-id="' . $fileIdAttr . '" data-favorite="' . $isFav . '" title="Tambah ke favorit"><i class="fa fa-star"></i></button>';
                    echo '<button class="btn btn-sm btn-light del-btn" title="Hapus"><i class="fa fa-trash"></i></button>';
                    echo '</div>';
                    if (strpos($it['mime'], 'image/') === 0) {
                        echo '<div class="file-thumbnail"><img src="' . $it['url'] . '" alt="' . htmlspecialchars($it['name']) . '"></div>';
                    } else {
                        echo '<div class="file-thumbnail"><i class="fa fa-file"></i></div>';
                    }
                    echo '</div>';
                    // Inline favorite button (visible under more area)
                    echo '<button class="fav-inline fav-btn' . $favClass . ' btn btn-sm" data-file-id="' . $fileIdAttr . '" data-favorite="' . $isFav . '" title="Favorit" style="position:absolute; right:12px; top:48px; background:transparent; border:none;">';
                    echo '<i class="fa fa-star"></i>';
                    echo '</button>';
                    echo '<div class="file-info">';
                    echo '<p class="file-name">' . htmlspecialchars($it['name']) . '</p>';
                    echo '<p class="file-size">' . human_filesize($it['size']) . '</p>';
                    echo '</div>';
                    echo '</div></div>';
                }
                echo '</div>';
            }
        }
        ?>
    </div>
</div>
</body>
</html>

<style>
/* View Switcher Styles */
.view-toggle {
    display: flex;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background-color: #fff;
    position: relative;
}

.view-toggle::after {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 1px;
    height: 60%;
    background-color: #dee2e6;
}

.toggle-btn {
    background: none;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    transition: all 0.2s ease;
    border-radius: 0;
}

.toggle-btn:hover {
    background-color: #f8f9fa;
    color: #495057;
}

.toggle-btn.active {
    background-color: #007bff;
    color: white;
}

.toggle-btn.active:hover {
    background-color: #0056b3;
}

.toggle-btn:first-child {
    border-radius: 5px 0 0 5px;
}

.toggle-btn:last-child {
    border-radius: 0 5px 5px 0;
}

/* File Grid View */
#file-grid.grid-view-mode {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 16px;
}

#file-grid.grid-view-mode .file-item {
    display: block;
    height: 180px;
}

#file-grid.grid-view-mode .file-card {
    display: flex;
    flex-direction: column;
    text-align: center;
    height: 100%;
    padding: 12px !important;
    position: relative;
    overflow: hidden;
}

#file-grid.grid-view-mode .file-card .card-overlay {
    position: absolute !important;
    top: 8px;
    right: 8px;
    display: flex;
    flex-direction: column;
    z-index: 20;
}

#file-grid.grid-view-mode .file-card .card-overlay button {
    margin-bottom: 6px;
    flex-shrink: 0;
}

#file-grid.grid-view-mode .file-card img,
#file-grid.grid-view-mode .file-card i.fa-file {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 8px;
    flex-shrink: 0;
}

#file-grid.grid-view-mode .file-card p {
    margin: 0 !important;
    flex-shrink: 0;
}

#file-grid.grid-view-mode .file-card p.fw-semibold {
    font-size: 12px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 4px !important;
}

#file-grid.grid-view-mode .file-card p.text-muted {
    font-size: 11px;
    margin-top: 2px !important;
}

/* File List View */
#file-grid.list-view-mode {
    display: block !important;
}

#file-grid.list-view-mode .file-item {
    display: block;
    margin-bottom: 0;
}

#file-grid.list-view-mode .file-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 15px !important;
    text-align: left;
    border-radius: 0;
    border-bottom: 1px solid #dee2e6;
}

#file-grid.list-view-mode .file-card:hover {
    background-color: #f8f9fa;
}

#file-grid.list-view-mode .file-card img,
#file-grid.list-view-mode .file-card i.fa-file {
    flex-shrink: 0;
    margin: 0 !important;
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

#file-grid.list-view-mode .file-card i.fa-file {
    color: #0dcaf0;
}

#file-grid.list-view-mode .file-card p {
    margin: 0 !important;
}

#file-grid.list-view-mode .file-card .file-content {
    flex-grow: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

#file-grid.list-view-mode .file-card .card-overlay {
    position: static !important;
    display: flex;
    gap: 5px;
    flex-shrink: 0;
}

#file-grid.list-view-mode .file-card .card-overlay button {
    margin: 0 !important;
}

.card-overlay .btn { box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
.file-item { transition: transform .12s ease; }
.file-item.favorited { order: -1; }
</style>

<style>
    

/* Responsive tweaks */
@media (max-width:768px){
    #file-grid.grid-view-mode{ grid-template-columns: repeat(auto-fill, minmax(120px,1fr)); gap:12px; }
    .file-card-inner{ height:100px; }
    .view-toggle{ transform: scale(.95); }
    .toggle-btn{ padding:6px 10px; }
}
@media (max-width:480px){
    .header-section{ flex-direction:column; align-items:flex-start; gap:12px; }
    .view-toggle{ width:96px; }
    .toggle-btn{ padding:6px; }
    #file-grid.grid-view-mode{ grid-template-columns: repeat(auto-fill, minmax(110px,1fr)); }
    #file-grid.grid-view-mode .file-card-inner{ height:90px; }
    #file-grid.grid-view-mode .file-name{ font-size:11px; }
    #file-grid.grid-view-mode .file-size{ font-size:10px; }
    #file-grid.list-view-mode .file-card-inner{ width:40px; height:40px; min-width:40px; }
    #file-grid.list-view-mode .file-name{ font-size:13px; }
    #file-grid.list-view-mode .file-size{ font-size:11px; }
}
</style>
<script>
// Reuse the same JS handlers as semuafile by delegating clicks on #file-grid
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('file-grid');
    const gridBtn = document.getElementById('grid-view');
    const listBtn = document.getElementById('list-view');
    const searchInput = document.getElementById('file-search-input');
    const categoryFilter = document.getElementById('category-filter');
    
    if (!grid) return;
    
    // View toggle handlers - just toggle classes, no DOM manipulation
    if (gridBtn) {
        gridBtn.addEventListener('click', function() {
            grid.classList.remove('list-view-mode');
            grid.classList.add('grid-view-mode');
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
        });
    }
    
    if (listBtn) {
        listBtn.addEventListener('click', function() {
            grid.classList.add('list-view-mode');
            grid.classList.remove('grid-view-mode');
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
        });
    }
    
    // Initialize grid view mode
    grid.classList.add('grid-view-mode');
    
    // Search and category filter
    function getCategory(mimeType) {
        if (!mimeType) return 'other';
        if (mimeType.startsWith('image/')) return 'image';
        if (mimeType.startsWith('video/')) return 'video';
        if (mimeType.startsWith('audio/')) return 'audio';
        if (mimeType.includes('word') || mimeType.includes('sheet') || mimeType.includes('presentation') || mimeType.includes('pdf')) return 'document';
        if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('7z') || mimeType.includes('compressed')) return 'archive';
        return 'other';
    }
    
    function filterItems() {
        const searchValue = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const categoryValue = categoryFilter ? categoryFilter.value : '';
        const items = grid.querySelectorAll('.file-item');
        
        items.forEach(item => {
            const fileName = (item.dataset.fileName || '').toLowerCase();
            const mimeType = item.dataset.fileMime || '';
            const itemCategory = getCategory(mimeType);
            
            const matchesSearch = searchValue === '' || fileName.includes(searchValue);
            const matchesCategory = categoryValue === '' || itemCategory === categoryValue;
            
            if (matchesSearch && matchesCategory) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    if (searchInput) searchInput.addEventListener('input', filterItems);
    if (categoryFilter) categoryFilter.addEventListener('change', filterItems);
    
    grid.addEventListener('click', function (e) {
        var fav = e.target.closest('.fav-btn');
        if (fav) {
            var item = fav.closest('.file-item');
            var fileId = item.dataset.fileId;
            fetch('favorite.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify({ file_id: fileId }) })
            .then(r=>r.json()).then(j=>{
                if (j.success) {
                    // If we're on the favorites page and user un-favorited, remove the item
                    if (grid.dataset.page === 'favorites' && j.is_favorite == 0) {
                        item.remove();
                    } else {
                        item.classList.toggle('favorited', j.is_favorite==1);
                        if (j.is_favorite==1) grid.insertBefore(item, grid.firstChild);
                    }
                    updateCounts(j.counts);
                } else {
                    alert(j.message||'Gagal');
                }
            }).catch(()=>alert('Network error'));
            return;
        }
        var del = e.target.closest('.del-btn');
        if (del) {
            var item = del.closest('.file-item');
            var fileId = item.dataset.fileId;
            if (!confirm('Hapus file ini?')) return;
            fetch('delete.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify({ file_id: fileId }) })
            .then(r=>r.json()).then(j=>{ if (j.success) { item.remove(); updateCounts(j.counts||{}); } else alert(j.message||'Gagal'); }).catch(()=>alert('Network error'));
            return;
        }
    });
    function updateCounts(c){ if(!c) return; var t=document.getElementById('total-files-count'); var f=document.getElementById('favorite-files-count'); var tr=document.getElementById('trash-files-count'); if(t&&typeof c.total!=='undefined') t.textContent = c.total + ' File'; if(f&&typeof c.favorites!=='undefined') f.textContent = c.favorites + ' Favorit'; if(tr&&typeof c.trash!=='undefined') tr.textContent = c.trash + ' Sampah'; }
});
</script>
