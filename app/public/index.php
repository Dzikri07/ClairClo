<?php
// index_fixed.php - Unified and cleaned version
// Pastikan file ini diletakkan di root project (ganti nama jika perlu)

session_start();
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/file_functions.php';
// debug removed: var_dump(getenv('MYSQLHOST'));

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

    <!-- Centralized stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/file-grid.css">
    <link rel="stylesheet" href="assets/css/files.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Preview libraries: PDF.js, SheetJS (XLSX), Mammoth for DOCX -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script>if (window.pdfjsLib) pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.12/mammoth.browser.min.js"></script>

    <style>
        /* keep only minimal overrides for floating menus to avoid conflicts with shared CSS */
        .more-menu { position: fixed; min-width:150px; background:var(--bg-card,#fff); border-radius:8px; border:1px solid rgba(0,0,0,0.08); box-shadow:0 6px 18px rgba(0,0,0,0.08); overflow:hidden; z-index:99999; display:none; padding:6px 0; }
        .more-menu.show { display:block; }
        .more-menu .more-item { display:flex; align-items:center; gap:10px; width:100%; padding:8px 12px; font-size:14px; background:none; border:none; text-align:left; cursor:pointer; }
        .more-menu .more-item i { width:18px; text-align:center; }
    </style>
</head>
<body id="index-page">
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
                    <input type="text" id="search-input" class="form-control rounded-pill" placeholder="Telusuri file..." style="width:200px;">
                    <select id="category-filter" class="form-select rounded-pill" style="width:120px;">
                        <option value="">Semua</option>
                        <option value="image">Gambar</option>
                        <option value="video">Video</option>
                        <option value="audio">Audio</option>
                        <option value="document">Dokumen</option>
                        <option value="archive">Arsip</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <span class="iconify ms-3 fs-5 settings-btn" data-icon="mdi:settings" title="Pengaturan" style="cursor:pointer"></span>
                <button class="btn btn-link p-0 ms-3" data-bs-toggle="modal" data-bs-target="#profileModal" title="Akun"><i class="fa fa-user fs-5"></i></button>
            </div>
        </div>

        <div class="header-section d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="fs-5 mb-1">Selamat datang di <span class="text-info fw-semibold">Clario</span>!</p>
                <h6 class="fw-bold mt-3">Baru-baru ini diunggah</h6>
                <p class="text-muted small">Lihat file yang baru-baru ini diunggah.</p>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <div id="preview-mode-toggle" title="Tampilkan sebagai" style="display:flex; gap:6px; align-items:center;">
                    <button class="toggle-btn preview-btn active" id="thumb-view" title="Thumbnail"><i class="fa fa-image"></i></button>
                    <button class="toggle-btn preview-btn" id="icon-view" title="Icon"><i class="fa fa-square-full"></i></button>
                </div>
                <div class="view-toggle">
                    <button class="toggle-btn active" id="grid-view" title="Tampilan Kotak"><span class="iconify" data-icon="mdi:view-grid-outline" data-width="18"></span></button>
                    <button class="toggle-btn" id="list-view" title="Tampilan Daftar"><span class="iconify" data-icon="mdi:view-list-outline" data-width="18"></span></button>
                </div>
            </div>
        </div>

        <div id="file-grid" data-page="home" class="grid-view-mode">
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
                    if (strpos($mime,'image/')===0) $iconPath='assets/icons/img.png';
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
                    <!-- top-right small actions (fav / delete / more) -->
                    <div class="top-actions" style="position: absolute; top: 8px; right: 8px; display: flex; flex-direction: row; flex-wrap: nowrap; gap: 4px; z-index: 50; align-items: center;">
                        <button class="action-btn action-fav" title="Favorit" data-file-id="<?php echo $fileId; ?>" data-favorite="<?php echo ($it['is_favorite'] ? '1' : '0'); ?>" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                            <i class="<?php echo ($it['is_favorite'] ? 'fa fa-star' : 'fa fa-star-o'); ?>"></i>
                        </button>
                        <button class="action-btn action-delete" title="Hapus" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-trash"></i>
                        </button>
                        <button class="action-btn action-more" aria-label="Opsi" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;"><i class="fa fa-ellipsis-v"></i></button>
                    </div>

                    <div class="file-card-inner">
                        <div class="file-thumbnail">
                            <?php if (strpos($mime,'image/')===0): ?>
                                <img src="<?php echo $fileUrl; ?>" alt="<?php echo $fileName; ?>" style="max-width:100%; height:auto; border-radius:8px;">
                            <?php else: ?>
                                <img src="<?php echo $iconPath; ?>" alt="<?php echo $fileName; ?>" style="max-width: 60px; max-height: 60px;">
                            <?php endif; ?>
                        </div>
                    </div>

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
    console.log('DOM loaded - starting search functionality and UI handlers');

    // Elements
    const fileGrid = document.getElementById('file-grid');
    const searchInput = document.getElementById('search-input');
    const categorySelect = document.getElementById('category-filter');
    const globalMenu = document.getElementById('global-more-menu');

    // Inject hide CSS for search results (keeps things consistent)
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
            position: relative !important;
            height: auto !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
    `;
    document.head.appendChild(style);

    // Simple and robust search function
    function performSearchAndFilter() {
        if (!fileGrid) return;
        const searchTerm = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedCategory = (categorySelect ? categorySelect.value : '');

        const fileItems = fileGrid.querySelectorAll('.file-item');
        let visibleCount = 0;
        fileItems.forEach((item) => {
            const fileName = item.getAttribute('data-name') || '';
            const fileCategory = item.getAttribute('data-category') || '';
            const matchesSearch = searchTerm === '' || fileName.includes(searchTerm);
            const matchesCategory = selectedCategory === '' || fileCategory === selectedCategory;
            const shouldShow = matchesSearch && matchesCategory;
            if (shouldShow) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        // Manage no-results message
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
            }
        } else {
            if (existingNoResults) existingNoResults.remove();
        }
    }

    // Event listeners for search & filter
    if (searchInput) {
        searchInput.addEventListener('input', performSearchAndFilter);
    }
    if (categorySelect) {
        categorySelect.addEventListener('change', performSearchAndFilter);
    }
    // initial run
    setTimeout(performSearchAndFilter, 80);

    // View toggle
    const gridBtn = document.getElementById('grid-view');
    const listBtn = document.getElementById('list-view');
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

    // Preview mode (thumbnail vs icon)
    const thumbBtn = document.getElementById('thumb-view');
    const iconBtn = document.getElementById('icon-view');
    function determineIconPath(mime) {
        if (!mime) return 'assets/icons/file.png';
        if (mime.indexOf('image/') === 0) return 'assets/icons/img.png';
        if (mime.indexOf('video/') === 0) return 'assets/icons/vid.png';
        if (mime.indexOf('audio/') === 0) return 'assets/icons/music.png';
        if (mime.indexOf('pdf') !== -1) return 'assets/icons/pdf.png';
        if (mime.indexOf('zip') !== -1 || mime.indexOf('compressed') !== -1) return 'assets/icons/archive.png';
        return 'assets/icons/file.png';
    }

    function applyPreviewMode(mode) {
        try { localStorage.setItem('fileThumbnailMode', mode); } catch(e) {}
        if (thumbBtn) thumbBtn.classList.toggle('active', mode === 'thumb');
        if (iconBtn) iconBtn.classList.toggle('active', mode === 'icon');
        const items = fileGrid ? fileGrid.querySelectorAll('.file-item') : [];
        items.forEach(item => {
            const thumb = item.querySelector('.file-thumbnail');
            if (!thumb) return;
            const mime = (item.getAttribute('data-file-mime') || '').toLowerCase();
            const url = item.getAttribute('data-file-url') || '';
            const iconPath = determineIconPath(mime);
            if (mode === 'thumb') {
                // prefer preview image when available
                if (mime.indexOf('image/') === 0 && url) {
                    thumb.innerHTML = `<img src="${url}" alt="${item.getAttribute('data-file-name')||''}" style="max-width:100%; height:auto; border-radius:8px;">`;
                } else {
                    thumb.innerHTML = `<img src="${iconPath}" alt="icon" style="max-width:60px; max-height:60px;">`;
                }
            } else {
                // icon mode: always show the icon
                thumb.innerHTML = `<img src="${iconPath}" alt="icon" style="max-width:60px; max-height:60px;">`;
            }
        });
    }

    // wire toggle buttons
    if (thumbBtn) thumbBtn.addEventListener('click', function(e){ applyPreviewMode('thumb'); });
    if (iconBtn) iconBtn.addEventListener('click', function(e){ applyPreviewMode('icon'); });

    // apply saved mode
    const savedThumbMode = localStorage.getItem('fileThumbnailMode') || 'thumb';
    applyPreviewMode(savedThumbMode);

    /* ========================
       Double-click preview -> open modal
       - Opens a reusable modal with preview depending on mime
       - Reuses existing endpoints for actions (favorite/delete)
       ======================== */
    function showPreviewModalForItem(item) {
        if (!item) return;
        const id = item.getAttribute('data-file-id');
        const name = item.getAttribute('data-file-name') || '';
        const mime = (item.getAttribute('data-file-mime') || '').toLowerCase();
        const url = item.getAttribute('data-file-url') || '';

        const modal = document.getElementById('filePreviewModal');
        if (!modal) return;

        const titleEl = modal.querySelector('.preview-title');
        const bodyEl = modal.querySelector('.preview-body');
        const downloadBtn = modal.querySelector('.preview-download');
        const favBtn = modal.querySelector('.preview-fav');
        const deleteBtn = modal.querySelector('.preview-delete');

        titleEl.textContent = name;
        bodyEl.innerHTML = '';

        if (mime.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = url;
            img.alt = name;
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            img.loading = 'lazy';
            bodyEl.appendChild(img);

        } else if (mime.startsWith('video/')) {
            const v = document.createElement('video');
            v.src = url;
            v.controls = true;
            v.style.width = '100%';
            bodyEl.appendChild(v);

        } else if (mime.startsWith('audio/')) {
            const a = document.createElement('audio');
            a.src = url;
            a.controls = true;
            bodyEl.appendChild(a);

        } else if (mime.includes('pdf') || /\.pdf$/i.test(url)) {
            // PDF preview using PDF.js (render first page)
            bodyEl.innerHTML = '<div class="text-center">Loading PDF preview…</div><canvas id="pdf-preview-canvas" style="width:100%;"></canvas>';
            try {
                if (!window.pdfjsLib) throw new Error('PDF.js not loaded');
                const loadingTask = pdfjsLib.getDocument(url);
                loadingTask.promise.then(function(pdf) {
                    return pdf.getPage(1).then(function(page) {
                        const viewport = page.getViewport({scale: 1.0});
                        const canvas = document.getElementById('pdf-preview-canvas');
                        const ratio = Math.min(1.6, (bodyEl.clientWidth || 800) / viewport.width);
                        const scaled = page.getViewport({scale: ratio});
                        canvas.width = scaled.width;
                        canvas.height = scaled.height;
                        const ctx = canvas.getContext('2d');
                        page.render({canvasContext: ctx, viewport: scaled});
                    });
                }).catch(function(err){
                    bodyEl.innerHTML = '<div class="text-danger">Gagal memuat PDF.</div>';
                });
            } catch (err) {
                bodyEl.innerHTML = '<div class="text-danger">Preview PDF tidak tersedia.</div>';
            }

        } else if (mime.includes('sheet') || mime.includes('excel') || /\.(xlsx|xls|csv)$/i.test(url)) {
            // Excel preview using SheetJS
            bodyEl.innerHTML = '<div class="text-center">Loading spreadsheet preview…</div>';
            fetch(url).then(r => r.arrayBuffer()).then(ab => {
                try {
                    const data = new Uint8Array(ab);
                    const wb = XLSX.read(data, {type:'array'});
                    const first = wb.SheetNames[0];
                    const html = XLSX.utils.sheet_to_html(wb.Sheets[first]);
                    bodyEl.innerHTML = '<div style="max-height:480px; overflow:auto;">' + html + '</div>';
                } catch (e) {
                    bodyEl.innerHTML = '<div class="text-danger">Gagal merender spreadsheet.</div>';
                }
            }).catch(()=>{ bodyEl.innerHTML = '<div class="text-danger">Gagal memuat file spreadsheet.</div>'; });

        } else if (mime.includes('word') || /\.docx$/i.test(url)) {
            // DOCX preview using Mammoth
            bodyEl.innerHTML = '<div class="text-center">Loading document preview…</div>';
            fetch(url).then(r => r.arrayBuffer()).then(ab => {
                try {
                    mammoth.convertToHtml({arrayBuffer: ab}).then(function(result){
                        bodyEl.innerHTML = '<div style="max-height:560px; overflow:auto;">' + result.value + '</div>';
                    }).catch(()=>{ bodyEl.innerHTML = '<div class="text-danger">Gagal merender dokumen.</div>'; });
                } catch (e) {
                    bodyEl.innerHTML = '<div class="text-danger">Gagal merender dokumen.</div>';
                }
            }).catch(()=>{ bodyEl.innerHTML = '<div class="text-danger">Gagal memuat file dokumen.</div>'; });

        } else {
            const wrap = document.createElement('div');
            wrap.style.display = 'flex';
            wrap.style.alignItems = 'center';
            wrap.style.gap = '12px';
            const icon = document.createElement('i');
            icon.className = 'fa fa-file fa-3x';
            icon.style.color = '#6c757d';
            const info = document.createElement('div');
            info.innerHTML = `<div style="font-weight:600">${name}</div><div class="text-muted small">${mime || 'Tipe file tidak diketahui'}</div>`;
            wrap.appendChild(icon);
            wrap.appendChild(info);
            bodyEl.appendChild(wrap);
        }

        // wire download
        downloadBtn.onclick = function () { window.open(url, '_blank'); };

        // wire favorite toggle (optimistic UI)
        favBtn.onclick = function () {
            fetch('favorite.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ file_id: id })
            }).then(r => r.json()).then(j => {
                if (j.success) {
                    const localFav = document.querySelector('.file-item[data-file-id="' + id + '"] .fav-btn');
                    if (localFav) localFav.classList.toggle('active');
                    Swal.fire({ toast:true, position:'bottom-end', icon:'success', title:j.message || 'Updated', showConfirmButton:false, timer:1200 });
                    if (j.counts) updateCounts && updateCounts(j.counts);
                } else {
                    Swal.fire({ icon:'error', title:'Gagal', text: j.message || 'Gagal memperbarui favorit' });
                }
            }).catch(()=>{ Swal.fire({ icon:'error', title:'Network error' }); });
        };

        // wire delete (move to trash)
        deleteBtn.onclick = function () {
            if (!confirm(`Hapus "${name}"? File akan dipindahkan ke sampah.`)) return;
            fetch('delete.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: id }) })
            .then(r=>r.json()).then(j=>{
                if (j.success) {
                    const el = document.querySelector('.file-item[data-file-id="' + id + '"]');
                    if (el) el.remove();
                    const bs = bootstrap.Modal.getInstance(modal);
                    if (bs) bs.hide();
                    Swal.fire({ toast:true, position:'bottom-end', icon:'success', title:j.message || 'Terhapus', showConfirmButton:false, timer:1200 });
                    if (j.counts) updateCounts && updateCounts(j.counts);
                } else {
                    Swal.fire({ icon:'error', title:'Gagal', text: j.message || 'Gagal menghapus file' });
                }
            }).catch(()=>{ Swal.fire({ icon:'error', title:'Network error' }); });
        };

        // show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    if (fileGrid) {
        fileGrid.addEventListener('dblclick', function(e){
            const item = e.target.closest('.file-item');
            if (!item) return;
            showPreviewModalForItem(item);
        });
    }

    /* ========================
       Helper functions for favorite and delete actions
       ======================== */
    function toggleFavorite(fileId, fileItem) {
        if (!fileId || !fileItem) return;
        const favBtn = fileItem.querySelector('.action-fav');
        if (!favBtn) return;
        
        fetch('favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ file_id: fileId })
        })
        .then(r => r.json())
        .then(j => {
            if (j.success) {
                if (j.is_favorite == 1) {
                    favBtn.classList.add('active');
                    const icon = favBtn.querySelector('i');
                    if (icon) {
                        icon.className = 'fa fa-star';
                    }
                    fileItem.classList.add('favorited');
                    Swal.fire({icon: 'success', title: 'Ditambahkan ke favorit', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 2000});
                } else {
                    favBtn.classList.remove('active');
                    const icon = favBtn.querySelector('i');
                    if (icon) {
                        icon.className = 'fa fa-star-o';
                    }
                    fileItem.classList.remove('favorited');
                    Swal.fire({icon: 'success', title: 'Dihapus dari favorit', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 2000});
                }
            } else {
                Swal.fire({icon: 'error', title: 'Gagal', text: j.message || 'Gagal toggle favorit', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 2000});
            }
        })
        .catch(() => {
            Swal.fire({icon: 'error', title: 'Error', text: 'Network error', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 2000});
        });
    }

    function doDelete(fileId, fileItem) {
        const fileName = fileItem.getAttribute('data-file-name') || 'file ini';
        if (!confirm(`Hapus "${fileName}"? File akan dipindahkan ke sampah.`)) return;
        fileItem.style.opacity = '0.6';
        fetch('delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ file_id: fileId })
        })
        .then(r => r.json())
        .then(j => {
            if (j.success) {
                fileItem.remove();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'File berhasil dihapus',
                    position: 'bottom-right',
                    toast: true,
                    showConfirmButton: false,
                    timer: 3000
                });
            } else {
                fileItem.style.opacity = '1';
                alert(j.message || 'Gagal menghapus file');
            }
        })
        .catch(() => {
            fileItem.style.opacity = '1';
            alert('Network error');
        });
    }

    /* ========================
       Global more menu & delegated actions
       - clicking action-more opens positioned menu (global)
       ======================== */
    if (fileGrid && globalMenu) {
        fileGrid.addEventListener('click', function(e){
            // Handle action-fav button (favorite)
            if (e.target.closest('.action-fav')) {
                e.stopPropagation();
                const fileItem = e.target.closest('.file-item');
                if (fileItem) {
                    const fileId = fileItem.getAttribute('data-file-id');
                    toggleFavorite(fileId, fileItem);
                }
                return;
            }

            // Handle action-delete button (delete)
            if (e.target.closest('.action-delete')) {
                e.stopPropagation();
                const fileItem = e.target.closest('.file-item');
                if (fileItem) {
                    const fileId = fileItem.getAttribute('data-file-id');
                    doDelete(fileId, fileItem);
                }
                return;
            }

            const btn = e.target.closest('.action-more');
            if (!btn) return;
            e.stopPropagation();
            const fileItem = btn.closest('.file-item');
            if (!fileItem) return;
            // populate dataset for menu
            globalMenu.dataset.fileId = fileItem.getAttribute('data-file-id') || '';
            globalMenu.dataset.fileName = fileItem.getAttribute('data-file-name') || '';
            globalMenu.dataset.fileUrl = fileItem.getAttribute('data-file-url') || '';
            // show menu positioned near button
            globalMenu.classList.add('show');
            globalMenu.style.display = 'block';
            globalMenu.setAttribute('aria-hidden', 'false');
            const rect = btn.getBoundingClientRect();
            const menuW = globalMenu.offsetWidth || 180;
            const menuH = globalMenu.offsetHeight || 150;
            let left = rect.right - menuW;
            if (left < 8) left = 8;
            if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
            let top = rect.bottom + 6;
            if (top + menuH > window.innerHeight - 8) {
                top = rect.top - menuH - 6;
                if (top < 8) top = 8;
            }
            globalMenu.style.left = left + 'px';
            globalMenu.style.top = top + 'px';
            fileItem.classList.add('menu-open');
        });

        // Click outside closes menu
        document.addEventListener('click', function(ev) {
            if (!globalMenu.contains(ev.target) && !ev.target.closest('.action-more')) {
                globalMenu.classList.remove('show');
                globalMenu.style.display = 'none';
                globalMenu.setAttribute('aria-hidden','true');
                document.querySelectorAll('.file-item.menu-open').forEach(function(it){
                    it.classList.remove('menu-open');
                });
            }
        }, { passive: true });

        // Actions inside the menu
        globalMenu.addEventListener('click', function(e){
            const action = e.target.closest('.more-item');
            if (!action) return;
            e.stopPropagation();
            const fileId = globalMenu.dataset.fileId || null;
            const fileName = globalMenu.dataset.fileName || '';
            const fileItemEl = fileId ? document.querySelector('.file-item[data-file-id="' + fileId + '"]') : null;

            if (action.classList.contains('download')) {
                if (!fileId) {
                    Swal.fire({icon: 'error', title: 'Error', text: 'File ID tidak ditemukan', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000});
                    return;
                }
                const url = 'download.php?file_id=' + encodeURIComponent(fileId);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName || '';
                document.body.appendChild(a);
                a.click();
                a.remove();
                Swal.fire({icon: 'success', title: 'Berhasil', text: 'File berhasil diunduh: ' + fileName, position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000});
            } else if (action.classList.contains('rename')) {
                const current = fileName || (fileItemEl && fileItemEl.getAttribute('data-file-name')) || '';
                const newName = prompt('Ganti nama file menjadi:', current || '');
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
                        const msg = j.is_favorite == 1 ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit';
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
            // hide menu after action
            globalMenu.classList.remove('show');
            globalMenu.style.display='none';
            globalMenu.setAttribute('aria-hidden','true');
        });
    }

    // Close global menus on ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.more-menu.show').forEach(m => { m.classList.remove('show'); m.style.display='none'; });
        }
    });

    console.log('Search functionality & UI handlers initialized successfully');
});
</script>

<!-- File Preview Modal -->
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title preview-title">Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body preview-body" style="min-height:220px; display:flex; align-items:center; justify-content:center;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary preview-download"><i class="fa fa-download"></i> Download</button>
                <button type="button" class="btn btn-outline-warning preview-fav"><i class="fa fa-star"></i> Favorit</button>
                <button type="button" class="btn btn-outline-danger preview-delete"><i class="fa fa-trash"></i> Hapus</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

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
