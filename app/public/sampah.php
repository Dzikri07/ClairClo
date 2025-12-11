    <?php
    // sampah.php - Sampah (trash) page (cleaned)

    // Pindahkan session_start ke paling atas
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/connection.php';
    require_once __DIR__ . '/file_functions.php';

    $userId = $_SESSION['user_id'] ?? null;
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sampah - Clario</title>
        <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
        <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
        <!-- HANYA load style.css, hapus file-grid.css dan files.css yang conflict -->
        <link rel="stylesheet" href="assets/css/style.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    
    <style>
        /* CSS MINIMAL khusus untuk sampah.php - dengan !important untuk hindari conflict */

        /* Reset penting untuk filter - PAKAI !IMPORTANT */
        .file-item { 
            display: block !important;
        }
        .file-item.filtered-out { 
            display: none !important; 
        }

        /* View toggle simplified */
        .view-toggle { 
            display: flex !important; 
            border: 1px solid #dee2e6 !important; 
            border-radius: 6px !important; 
            background: #fff !important; 
        }
        .view-toggle .toggle-btn { 
            background: none !important; 
            border: 0 !important; 
            padding: 8px !important; 
            cursor: pointer !important; 
            color: #6c757d !important;
        }
        .view-toggle .toggle-btn.active { 
            background: #007bff !important; 
            color: #fff !important; 
        }

        /* Grid view - PAKAI !IMPORTANT */
        #file-grid.grid-view-mode { 
            display: grid !important; 
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)) !important; 
            gap: 16px !important; 
            padding: 12px 0 !important; 
        }

        /* List view - PAKAI !IMPORTANT */
        #file-grid.list-view-mode { 
            display: flex !important; 
            flex-direction: column !important; 
            gap: 10px !important; 
            padding: 12px 0 !important; 
        }

        /* File card basics */
        .file-card { 
            background: #fff !important; 
            border: 1px solid #e6e6e6 !important; 
            border-radius: 8px !important; 
            overflow: visible !important; 
        }
        .file-card-inner { 
            height: 110px !important; 
            display: flex !important; 
            align-items: center !important; 
            justify-content: center !important; 
            overflow: hidden !important; 
            border-radius: 8px 8px 0 0 !important; 
            background: #f5f5f5 !important; 
        }
        .file-card-inner img { 
            width: 100% !important; 
            height: 100% !important; 
            object-fit: cover !important; 
        }

        /* Card overlay - hanya untuk grid view */
        .card-overlay { 
            position: absolute !important; 
            top: 8px !important; 
            right: 8px !important; 
            display: flex !important; 
            flex-direction: row !important; 
            gap: 4px !important; 
            opacity: 1 !important; 
            transition: opacity 0.12s !important; 
            z-index: 1200 !important; 
            align-items: center !important;
            pointer-events: auto !important;
        }
        .file-item:hover .card-overlay { 
            opacity: 1 !important; 
        }
        .action-btn-group { 
            display: flex !important; 
            gap: 4px !important; 
            flex-direction: row !important; 
            align-items: center !important;
        }
        .action-btn-group .btn-sm { 
            font-size: 10px !important; 
            padding: 2px 4px !important; 
            min-width: 28px !important;
            width: 28px !important;
            height: 28px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 4px !important;
        }
        .action-btn-group .btn-sm i { 
            margin-right: 0 !important; 
            margin-left: 0 !important;
            font-size: 12px !important;
            line-height: 1 !important;
            width: 14px !important;
            height: 14px !important;
            display: inline-block !important;
            text-align: center !important;
        }
        /* Ensure thumbnail doesn't cover overlay */
        .file-card .file-thumbnail, .file-card-inner .file-thumbnail {
            position: relative !important;
            z-index: 0 !important;
        }

        /* Hover effects khusus */
        .restore-btn:hover { 
            background-color: #198754 !important; 
            border-color: #198754 !important; 
            color: white !important; 
        }
        .del-perm-btn:hover { 
            background-color: #dc3545 !important; 
            border-color: #dc3545 !important; 
            color: white !important; 
        }

        /* List view styles */
        .list-view-item { 
            display: flex !important; 
            align-items: center !important; 
            padding: 12px !important; 
            background: #fff !important; 
            border: 1px solid #e6e6e6 !important; 
            border-radius: 8px !important; 
            gap: 15px !important; 
        }
        .list-view-thumbnail { 
            width: 60px !important; 
            height: 60px !important; 
            display: flex !important; 
            align-items: center !important; 
            justify-content: center !important; 
            background: #f5f5f5 !important; 
            border-radius: 6px !important; 
            flex-shrink: 0 !important; 
        }
        .list-view-thumbnail img { 
            width: 100% !important; 
            height: 100% !important; 
            object-fit: cover !important; 
            border-radius: 6px !important; 
        }
        .list-view-content { 
            flex: 1 !important; 
            min-width: 0 !important; 
        }
        .list-view-actions { 
            display: flex !important; 
            gap: 8px !important; 
            flex-shrink: 0 !important; 
        }

        /* Stats section */
        .stats-section { 
            background: #fff !important; 
            border-radius: 8px !important; 
            padding: 15px !important; 
            margin-bottom: 20px !important; 
            border: 1px solid #e6e6e6 !important; 
        }

        /* No results message - PAKAI !IMPORTANT */
        .no-results { 
            text-align: center !important; 
            padding: 40px 20px !important; 
            color: #6c757d !important; 
            display: block !important;
            width: 100% !important;
        }

        /* Responsive */
        @media (max-width: 768px) { 
            #file-grid.grid-view-mode { 
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)) !important; 
            } 
            .file-card-inner { 
                height: 90px !important; 
            } 
            .list-view-item { 
                flex-direction: column !important; 
                align-items: flex-start !important; 
            }
            .list-view-actions { 
                align-self: flex-end !important; 
                margin-top: 10px !important;
            }
        }

        /* TAMBAHAN: Force hide filtered items dengan specificity tinggi */
        #file-grid .file-item.filtered-out {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0 !important;
            width: 0 !important;
            overflow: hidden !important;
            position: absolute !important;
        }

        /* TAMBAHAN: Ensure visible items are shown */
        #file-grid .file-item:not(.filtered-out) {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
</style>
    </head>
    <body style="background-color: #f9f9f9;">
    <div class="d-flex">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main flex-grow-1 p-4">
            <div class="header-section d-flex justify-content-between align-items-center mb-4">
                <div class="welcome-text">
                    <p class="fs-5 mb-1">Sampah</p>
                    <h6 class="fw-bold mt-3">File yang dihapus</h6>
                    <p class="text-muted small">File yang dihapus akan muncul di sini.</p>
                </div>
                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
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
                        <button class="btn btn-danger btn-sm" id="delete-all-btn">
                            <i class="fa fa-trash me-1"></i> Hapus Semua
                        </button>
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
                echo '<div class="alert alert-warning">Silakan login untuk melihat sampah Anda.</div>';
            } else {
                $rows = fetchAll('SELECT fsp.*, f.is_favorite FROM file_storage_paths fsp LEFT JOIN files f ON fsp.file_id = f.id WHERE fsp.user_id = ? AND fsp.is_deleted = 1 ORDER BY fsp.deleted_at DESC', [$userId]);
                $items = [];
                if ($rows) {
                    foreach ($rows as $r) {
                        $url = 'uploads/' . ($r['thumbnail_path'] ?: $r['file_path']);
                        $category = get_file_category($r['mime_type'] ?? '');
                        $items[] = [
                            'id' => $r['file_id'] ?? 0,
                            'name' => $r['original_filename'] ?? $r['stored_filename'],
                            'size' => $r['file_size'] ?? 0,
                            'url' => $url,
                            'mime' => $r['mime_type'] ?? 'application/octet-stream',
                            'category' => $category
                        ];
                    }
                }

                if (empty($items)) {
                    echo '<div class="text-center mt-4">';
                    echo '<img src="assets/image/defaultNotfound.png" alt="Tidak ada sampah" style="max-width:260px; opacity:0.95;">';
                    echo '<p class="text-muted small mt-2">Tidak ada sampah.</p>';
                    echo '</div>';
                } else {
                    // Show simplified stats
                    $trash = fetchOne('SELECT COUNT(*) as cnt FROM file_storage_paths WHERE user_id = ? AND is_deleted = 1', [$userId]);
             // Ganti bagian stats menjadi:
                    echo '<div class="stats-section" id="trash-stats">';
                    echo '<div class="row align-items-center">';
                    echo '<div class="col">';
                    echo '<div class="d-flex align-items-center gap-3">';
                    echo '<div class="text-center">';
                    // Removed large trash icon to reduce visual clutter
                    echo '<div class="fw-bold mt-1"><span id="trash-count">' . intval($trash['cnt'] ?? 0) . '</span> File di Sampah</div>';
                    echo '</div>';
                    echo '<div class="text-muted small">File akan dihapus permanen setelah 30 hari</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';

                    echo '<div id="file-grid" data-page="trash">';
                    
                    // Grid View
                    foreach ($items as $it) {
                        $fileIdAttr = intval($it['id']);
                        echo '<div class="file-item" data-file-id="' . $fileIdAttr . '" data-file-url="' . htmlspecialchars($it['url']) . '" data-file-name="' . htmlspecialchars($it['name']) . '" data-file-mime="' . htmlspecialchars($it['mime']) . '" data-file-category="' . htmlspecialchars($it['category']) . '">';
                        echo '<div class="file-card position-relative">';
                        echo '<div class="file-card-inner">';
                        echo '<div class="card-overlay">';
                        echo '<div class="action-btn-group">';
                        echo '<button class="btn btn-sm btn-success restore-btn" title="Kembalikan file" aria-label="Kembalikan file">';
                        echo '<i class="fa fa-undo"></i>';
                        echo '</button>';
                        echo '<button class="btn btn-sm btn-danger del-perm-btn" title="Hapus permanen" aria-label="Hapus permanen">';
                        echo '<i class="fa fa-trash"></i>';
                        echo '</button>';
                        echo '</div>';
                        echo '</div>';
                        if (strpos($it['mime'], 'image/') === 0) {
                            echo '<div class="file-thumbnail"><img src="' . $it['url'] . '" alt="' . htmlspecialchars($it['name']) . '"></div>';
                        } else {
                            echo '<div class="file-thumbnail"><i class="fa fa-file fa-2x text-muted"></i></div>';
                        }
                        echo '</div>';
                        echo '<div class="file-info p-2">';
                        echo '<p class="file-name mb-1 small text-truncate" title="' . htmlspecialchars($it['name']) . '">' . htmlspecialchars($it['name']) . '</p>';
                        echo '<p class="file-size text-muted small mb-0">' . human_filesize($it['size']) . '</p>';
                        echo '</div>';
                        echo '</div></div>';
                    }
                    
                    echo '</div>';
                }
            }

            // Helper function to get file category
            function get_file_category($mimeType) {
                if (strpos($mimeType, 'image/') === 0) return 'image';
                if (strpos($mimeType, 'video/') === 0) return 'video';
                if (strpos($mimeType, 'audio/') === 0) return 'audio';
                if (strpos($mimeType, 'application/pdf') !== false || 
                    strpos($mimeType, 'word') !== false || 
                    strpos($mimeType, 'sheet') !== false || 
                    strpos($mimeType, 'presentation') !== false) return 'document';
                if (strpos($mimeType, 'zip') !== false || 
                    strpos($mimeType, 'rar') !== false || 
                    strpos($mimeType, '7z') !== false || 
                    strpos($mimeType, 'compressed') !== false) return 'archive';
                return 'other';
            }
            ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const grid = document.getElementById('file-grid');
        const gridBtn = document.getElementById('grid-view');
        const listBtn = document.getElementById('list-view');
        const searchInput = document.getElementById('file-search-input');
        const categoryFilter = document.getElementById('category-filter');
        const deleteAllBtn = document.getElementById('delete-all-btn');
        
        if (!grid) return;

        let currentViewMode = 'grid';

        function setGridMode(){ 
            grid.classList.remove('list-view-mode'); 
            grid.classList.add('grid-view-mode'); 
            gridBtn.classList.add('active'); 
            listBtn.classList.remove('active');
            currentViewMode = 'grid';
            convertToListView(false);
        }
        
        function setListMode(){ 
            grid.classList.add('list-view-mode'); 
            grid.classList.remove('grid-view-mode'); 
            listBtn.classList.add('active'); 
            gridBtn.classList.remove('active');
            currentViewMode = 'list';
            convertToListView(true);
        }

        function convertToListView(isListView) {
            const items = grid.querySelectorAll('.file-item:not(.filtered-out)');
            
            items.forEach(item => {
                if (isListView && !item.classList.contains('list-view-converted')) {
                    const fileId = item.dataset.fileId;
                    const fileName = item.dataset.fileName || '';
                    const fileSize = item.querySelector('.file-size')?.textContent || '';
                    const fileMime = item.dataset.fileMime || '';
                    const isImage = fileMime.startsWith('image/');
                    const thumbnailSrc = item.querySelector('img')?.src || '';
                    
                    item.classList.add('list-view-item', 'list-view-converted');
                    item.innerHTML = `
                        <div class="list-view-thumbnail">
                            ${isImage ? 
                                `<img src="${thumbnailSrc}" alt="${fileName}">` : 
                                `<i class="fa fa-file fa-lg text-muted"></i>`
                            }
                        </div>
                        <div class="list-view-content">
                            <div class="fw-bold text-truncate">${fileName}</div>
                            <div class="text-muted small">${fileSize}</div>
                        </div>
                        <div class="list-view-actions">
                            <button class="btn btn-success btn-sm restore-btn">
                                <i class="fa fa-undo me-1"></i> Pulihkan
                            </button>
                            <button class="btn btn-danger btn-sm del-perm-btn">
                                <i class="fa fa-trash me-1"></i> Hapus
                            </button>
                        </div>
                    `;
                    item.dataset.fileId = fileId;
                    item.dataset.fileName = fileName;
                    item.dataset.fileMime = fileMime;
                    
                } else if (!isListView && item.classList.contains('list-view-converted')) {
                    location.reload();
                }
            });
        }

        gridBtn.addEventListener('click', setGridMode);
        listBtn.addEventListener('click', setListMode);

        // Default
        setGridMode();

        // Thumbnail / Icon preview toggle - add small UI and behavior
        (function () {
            // add preview toggle UI near existing view-toggle (if present)
            const header = document.querySelector('.header-section div[style*="display:flex"]');
            if (header) {
                const previewWrap = document.createElement('div');
                previewWrap.className = 'view-toggle';
                previewWrap.style.marginRight = '8px';
                previewWrap.innerHTML = `
                    <button class="toggle-btn preview-btn" id="thumb-view" title="Thumbnail"><i class="fa fa-image"></i></button>
                    <button class="toggle-btn preview-btn" id="icon-view" title="Icon"><i class="fa fa-file"></i></button>
                `;
                // insert before the existing view-toggle group
                const existingView = header.querySelector('.view-toggle');
                if (existingView) existingView.parentNode.insertBefore(previewWrap, existingView);
            }

            const thumbBtn = document.getElementById('thumb-view');
            const iconBtn = document.getElementById('icon-view');

            function determineIconPath(mime) {
                if (!mime) return 'assets/icons/file.png';
                if (mime.startsWith('image/')) return 'assets/icons/img.png';
                if (mime.startsWith('audio/')) return 'assets/icons/music.png';
                if (mime.startsWith('video/')) return 'assets/icons/vid.png';
                if (mime.includes('pdf')) return 'assets/icons/pdf.png';
                return 'assets/icons/file.png';
            }

            function applyPreviewMode(mode) {
                const items = document.querySelectorAll('#file-grid .file-item');
                items.forEach(it => {
                    const mime = (it.dataset.fileMime || '').toLowerCase();
                    const url = it.dataset.fileUrl || (it.querySelector('img') ? it.querySelector('img').src : '');
                    // Prefer the dedicated thumbnail element so we don't overwrite the overlay
                    const thumbnailElement = it.querySelector('.file-thumbnail') || it.querySelector('.list-view-thumbnail');
                    const fileCardInner = it.querySelector('.file-card-inner');
                    const target = thumbnailElement || fileCardInner;
                    if (!target) return;

                    if (mode === 'thumb' && mime.startsWith('image/') && url) {
                        if (thumbnailElement) {
                            thumbnailElement.innerHTML = `<img src="${url}" alt="${it.dataset.fileName || ''}">`;
                        } else {
                            // fallback to inner when no dedicated thumbnail element exists
                            target.innerHTML = `<img src="${url}" alt="${it.dataset.fileName || ''}">`;
                        }
                    } else {
                        const icon = determineIconPath(mime);
                        if (thumbnailElement) {
                            thumbnailElement.innerHTML = `<i class="fa fa-file fa-2x text-muted"></i>`;
                        } else {
                            target.innerHTML = `<i class="fa fa-file fa-2x text-muted"></i>`;
                        }
                        // fallback icon image if present
                        // thumbnailElement && (thumbnailElement.innerHTML = `<img src="${icon}" class="icon-fallback">`);
                    }
                });
                try { localStorage.setItem('fileThumbnailMode', mode); } catch (e) {}
                if (thumbBtn) thumbBtn.classList.toggle('active', mode === 'thumb');
                if (iconBtn) iconBtn.classList.toggle('active', mode === 'icon');
            }

            const saved = localStorage.getItem('fileThumbnailMode') || 'thumb';
            applyPreviewMode(saved);
            if (thumbBtn) thumbBtn.addEventListener('click', () => applyPreviewMode('thumb'));
            if (iconBtn) iconBtn.addEventListener('click', () => applyPreviewMode('icon'));
        })();

        // Search and category filter - FIXED VERSION
        function filterItems() {
            const searchValue = searchInput.value.toLowerCase().trim();
            const categoryValue = categoryFilter.value;
            const items = grid.querySelectorAll('.file-item');
            
            let visibleCount = 0;
            
            items.forEach(item => {
                const fileName = (item.dataset.fileName || '').toLowerCase();
                const fileCategory = item.dataset.fileCategory || '';
                
                const matchesSearch = searchValue === '' || fileName.includes(searchValue);
                const matchesCategory = categoryValue === '' || fileCategory === categoryValue;
                
                if (matchesSearch && matchesCategory) {
                    item.classList.remove('filtered-out');
                    visibleCount++;
                } else {
                    item.classList.add('filtered-out');
                }
            });
            
            // Show no results message
            const noResultsMsg = document.getElementById('no-results-message');
            if (visibleCount === 0 && items.length > 0) {
                if (!noResultsMsg) {
                    const message = document.createElement('div');
                    message.id = 'no-results-message';
                    message.className = 'no-results';
                    message.innerHTML = `
                        <i class="fa fa-search fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Tidak ada file yang sesuai dengan pencarian</p>
                        <button class="btn btn-sm btn-outline-secondary mt-2" onclick="clearFilters()">Hapus Filter</button>
                    `;
                    grid.appendChild(message);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }
        
        // Clear filters function
        window.clearFilters = function() {
            searchInput.value = '';
            categoryFilter.value = '';
            filterItems();
        };
        
        searchInput.addEventListener('input', function() {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(filterItems, 300);
        });
        
        categoryFilter.addEventListener('change', filterItems);

        // Delete All functionality
        deleteAllBtn.addEventListener('click', function() {
            const visibleItems = grid.querySelectorAll('.file-item:not(.filtered-out)');
            
            if (visibleItems.length === 0) {
                Swal.fire({
                    icon: 'info', 
                    title: 'Tidak ada file', 
                    text: 'Tidak ada file yang sesuai dengan filter untuk dihapus', 
                    position: 'bottom-right', 
                    toast: true, 
                    showConfirmButton: false, 
                    timer: 3000
                });
                return;
            }
            
            Swal.fire({
                title: 'Hapus Semua File?',
                text: `Anda akan menghapus permanen ${visibleItems.length} file. Tindakan ini tidak dapat dibatalkan!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus Semua',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Batal',
                position: 'center'
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Sedang menghapus file',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    const fileIds = Array.from(visibleItems).map(item => item.dataset.fileId);
                    
                    fetch('delete.php', { 
                        method: 'POST', 
                        headers: { 'Content-Type':'application/json' }, 
                        body: JSON.stringify({ 
                            file_ids: fileIds, 
                            delete_all: true,
                            action: 'delete_permanent_all'
                        }) 
                    })
                    .then(r => r.json())
                    .then(j => {
                        Swal.close();
                        if (j && j.success) {
                            visibleItems.forEach((item, index) => {
                                setTimeout(() => {
                                    item.style.opacity = '0';
                                    item.style.transform = 'scale(0.95)';
                                    setTimeout(() => item.remove(), 300);
                                }, index * 50);
                            });
                            
                            Swal.fire({
                                icon: 'success', 
                                title: 'Berhasil', 
                                text: `${visibleItems.length} file telah dihapus permanen`, 
                                position: 'bottom-right', 
                                toast: true, 
                                showConfirmButton: false, 
                                timer: 3000
                            });
                            
                            setTimeout(() => {
                                const remainingItems = grid.querySelectorAll('.file-item');
                                if (remainingItems.length === 0) location.reload();
                            }, 1000);
                        } else {
                            Swal.fire({
                                icon: 'error', 
                                title: 'Gagal', 
                                text: (j && j.message) ? j.message : 'Gagal menghapus file', 
                                position: 'bottom-right', 
                                toast: true, 
                                showConfirmButton: false, 
                                timer: 3000
                            });
                        }
                    }).catch(err => { 
                        console.error(err); 
                        Swal.fire({
                            icon: 'error', 
                            title: 'Error', 
                            text: 'Terjadi kesalahan jaringan', 
                            position: 'bottom-right', 
                            toast: true, 
                            showConfirmButton: false, 
                            timer: 3000
                        }); 
                    });
                }
            });
        });

        // Delegate clicks for restore and permanent delete
        grid.addEventListener('click', function (e) {
            const restoreBtn = e.target.closest('.restore-btn');
            if (restoreBtn) {
                const item = restoreBtn.closest('.file-item');
                if (!item) return;
                const fileId = item.dataset.fileId;
                const fileName = item.dataset.fileName || 'file';
                
                Swal.fire({
                    title: 'Kembalikan File?', 
                    text: 'Kembalikan file "' + fileName + '" ke lokasi aslinya?', 
                    icon: 'question', 
                    showCancelButton: true, 
                    confirmButtonText: 'Ya, Kembalikan', 
                    confirmButtonColor: '#28a745', 
                    cancelButtonText: 'Batal', 
                    position: 'bottom-right'
                }).then(result=>{
                    if (result.isConfirmed) {
                        fetch('delete.php', { 
                            method: 'POST', 
                            headers: { 'Content-Type':'application/json' }, 
                            body: JSON.stringify({ file_id: fileId, action: 'restore' }) 
                        })
                        .then(r => r.json())
                        .then(j => {
                            if (j && j.success) {
                                item.style.opacity = '0'; 
                                item.style.transform = 'scale(0.95)'; 
                                setTimeout(() => item.remove(), 300);
                                Swal.fire({
                                    icon: 'success', 
                                    title: 'Berhasil', 
                                    text: 'File telah dipulihkan', 
                                    position: 'bottom-right', 
                                    toast: true, 
                                    showConfirmButton: false, 
                                    timer: 3000
                                });
                                setTimeout(() => { 
                                    const remainingItems = grid.querySelectorAll('.file-item:not(.filtered-out)');
                                    if (remainingItems.length === 0) location.reload(); 
                                }, 500);
                            } else {
                                Swal.fire({
                                    icon: 'error', 
                                    title: 'Gagal', 
                                    text: (j && j.message) ? j.message : 'Gagal memulihkan file', 
                                    position: 'bottom-right', 
                                    toast: true, 
                                    showConfirmButton: false, 
                                    timer: 3000
                                });
                            }
                        }).catch(err => { 
                            console.error(err); 
                            Swal.fire({
                                icon: 'error', 
                                title: 'Error', 
                                text: 'Network error', 
                                position: 'bottom-right', 
                                toast: true, 
                                showConfirmButton: false, 
                                timer: 3000
                            }); 
                        });
                    }
                });
                return;
            }
            
            const delBtn = e.target.closest('.del-perm-btn');
            if (!delBtn) return;
            const item = delBtn.closest('.file-item'); 
            if (!item) return;
            const fileId = item.dataset.fileId; 
            const fileName = item.dataset.fileName || 'file';
            
            Swal.fire({
                title: 'Hapus Permanen?',
                text: 'File "' + fileName + '" akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus Permanen',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Batal',
                position: 'bottom-right'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('delete.php', { 
                        method: 'POST', 
                        headers: { 'Content-Type':'application/json' }, 
                        body: JSON.stringify({ file_id: fileId, permanent: 1 }) 
                    })
                    .then(r => r.json())
                    .then(j => {
                        if (j && j.success) {
                            item.style.opacity = '0'; 
                            item.style.transform = 'scale(0.95)'; 
                            setTimeout(() => item.remove(), 300);
                            Swal.fire({
                                icon: 'success', 
                                title: 'Berhasil', 
                                text: 'File telah dihapus permanen', 
                                position: 'bottom-right', 
                                toast: true, 
                                showConfirmButton: false, 
                                timer: 3000
                            });
                            setTimeout(() => { 
                                const remainingItems = grid.querySelectorAll('.file-item:not(.filtered-out)');
                                if (remainingItems.length === 0) location.reload(); 
                            }, 500);
                        } else {
                            Swal.fire({
                                icon: 'error', 
                                title: 'Gagal', 
                                text: (j && j.message) ? j.message : 'Gagal menghapus', 
                                position: 'bottom-right', 
                                toast: true, 
                                showConfirmButton: false, 
                                timer: 3000
                            });
                        }
                    }).catch(err => { 
                        console.error(err); 
                        Swal.fire({
                            icon: 'error', 
                            title: 'Error', 
                            text: 'Network error', 
                            position: 'bottom-right', 
                            toast: true, 
                            showConfirmButton: false, 
                            timer: 3000
                        }); 
                    });
                }
            });
        });
    });
    </script>

    </body>
    </html>