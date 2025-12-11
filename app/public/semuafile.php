<?php
// semuafile.php - Semua File page
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/file_functions.php';
require_once __DIR__ . '/../src/StorageManager.php';

$userId = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua File - Clario</title>
    <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/file-grid.css">
    <link rel="stylesheet" href="assets/css/files.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    /* Header card for a cleaner look */
    .header-card {
        background: linear-gradient(180deg, rgba(255,255,255,0.9), rgba(250,250,250,0.95));
        border-radius: 12px;
        padding: 18px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        border: 1px solid rgba(0,0,0,0.04);
    }

    .category-select {
        min-width: 160px !important;
        border-radius: 90px !important;
        padding-left: 12px !important;
        padding-right: 12px !important;
        background: #fff;
        border: 1px solid rgba(0,0,0,0.06);
        color: var(--text-primary);
    }

    /* Header and search bar styling */
    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .header-section input,
    .header-section select {
        min-width: 0 !important;
    }

    /* Ensure search bar is visible */
    #file-search-input {
        width: 260px !important;
        background-color: #f5f5f5 !important;
    }

    #category-filter {
        width: 150px !important;
        border-radius: 20px !important;
        border: 1px solid #dee2e6 !important;
    }

    #category-filter:hover {
        border-color: #00f5d4 !important;
    }

    #category-filter:focus {
        border-color: #00f5d4 !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 245, 212, 0.25) !important;
    }

    #file-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)) !important;
        gap: 20px !important;
        padding: 16px 0 !important;
    }

    #file-grid.list-view-mode {
        display: flex !important;
        flex-direction: column !important;
        grid-template-columns: unset !important;
        gap: 8px !important;
    }

    .file-item {
        display: block !important;
        min-width: 0 !important;
    }

    #file-grid.list-view-mode .file-item {
        display: block !important;
    }

    #file-grid.grid-view-mode .file-card {
        display: flex !important;
        flex-direction: column !important;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
        position: relative;
        height: 100% !important;
    }

    #file-grid.list-view-mode .file-card {
        display: flex !important;
        flex-direction: row !important;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        width: 100%;
        position: relative;
        height: auto;
    }

    #file-grid.grid-view-mode .file-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }

    #file-grid.grid-view-mode .file-card-inner {
        position: relative;
        width: 100%;
        height: 120px;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    #file-grid.grid-view-mode .file-card-inner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    #file-grid.grid-view-mode .file-thumbnail {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #file-grid.grid-view-mode .file-card-inner i {
        font-size: 48px;
        color: #ddd;
    }

    #file-grid.grid-view-mode .card-overlay {
        position: absolute;
        top: 8px;
        right: 8px;
        display: none;
        flex-direction: column;
        gap: 6px;
        opacity: 0;
        transition: opacity 0.2s;
        z-index: 20;
        pointer-events: none;
    }

    #file-grid.grid-view-mode .card-overlay .btn {
        padding: 6px 8px;
        font-size: 12px;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #ddd;
        color: #333;
        cursor: pointer;
        transition: all 0.2s ease;
        pointer-events: auto;
    }

    #file-grid.grid-view-mode .card-overlay .btn:hover {
        background: #fff;
        border-color: #bbb;
    }

    #file-grid.grid-view-mode .file-info {
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex: 1;
        min-width: 0;
    }

    #file-grid.grid-view-mode .file-name {
        margin: 0;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
        color: #222;
    }

    #file-grid.grid-view-mode .file-size {
        margin: 0;
        font-size: 11px;
        color: #999;
        margin-top: auto;
    }

    /* List View Mode */
    #file-grid.list-view-mode {
        display: flex !important;
        flex-direction: column !important;
        grid-template-columns: unset !important;
        gap: 8px !important;
    }

    #file-grid.list-view-mode .file-card {
        display: flex !important;
        flex-direction: row !important;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        width: 100%;
        position: relative;
        height: auto;
    }

    #file-grid.list-view-mode .file-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transform: none;
    }

    #file-grid.list-view-mode .file-card-inner {
        width: 50px;
        height: 50px;
        min-width: 50px;
        background: #f5f5f5;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    #file-grid.list-view-mode .file-card-inner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    #file-grid.list-view-mode .file-card-inner i {
        font-size: 24px;
        color: #ccc;
    }

    #file-grid.list-view-mode .file-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    #file-grid.list-view-mode .file-name {
        margin: 0;
        font-size: 14px;
        font-weight: 500;
        color: #222;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #file-grid.list-view-mode .file-size {
        margin: 0;
        font-size: 12px;
        color: #999;
    }

    #file-grid.list-view-mode .card-overlay {
        display: none !important;
        gap: 8px;
        align-items: center;
        opacity: 0;
        transition: opacity 0.2s;
        flex-shrink: 0;
        pointer-events: none;
    }

    #file-grid.list-view-mode .card-overlay .btn {
        padding: 6px 8px;
        font-size: 12px;
        border-radius: 4px;
        background: #f5f5f5;
        border: 1px solid #ddd;
        color: #333;
        cursor: pointer;
        transition: all 0.2s ease;
        pointer-events: auto;
    }

    #file-grid.list-view-mode .card-overlay .btn:hover {
        background: #e9ecef;
        border-color: #aaa;
    }

    .more-menu {
        position: fixed;
        min-width: 160px;
        background: #fff;
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.12);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        overflow: hidden;
        z-index: 10000;
        display: none;
        padding: 6px 0;
        transform-origin: top right;
    }

    .more-menu.show {
        display: block;
        animation: _menuPop 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes _menuPop {
        from {
            opacity: 0;
            transform: translateY(-8px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .more-menu .more-item {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 10px 14px;
        font-size: 13px;
        background: none;
        border: none;
        text-align: left;
        cursor: pointer;
        color: #222;
        transition: background-color 0.15s ease;
    }

    .more-menu .more-item:hover {
        background-color: #f0f6ff;
    }

    .more-menu .more-item i {
        width: 16px;
        text-align: center;
        color: #666;
        font-size: 13px;
    }

    .more-menu .more-item:hover i {
        color: #007bff;
    }

    /* Hide overlay for list view initially */
    #file-grid.list-view-mode .card-overlay {
        display: none !important;
    }

    .file-item.favorited {
        order: -1;
    }

    /* Filter visibility override */
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

    /* Responsive */
    @media (max-width: 768px) {
        #file-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 16px;
        }

        #file-grid.grid-view-mode .file-card-inner {
            height: 100px;
        }

        #file-grid.list-view-mode .file-card {
            padding: 10px 12px;
        }

        #file-grid.list-view-mode .file-card-inner {
            width: 44px;
            height: 44px;
        }

        .view-toggle {
            transform: scale(0.95);
        }

        .toggle-btn {
            padding: 6px 10px;
            font-size: 14px;
        }
    }

    @media (max-width: 480px) {
        .header-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        #file-grid {
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 12px;
        }

        .view-toggle {
            width: auto;
        }

        .toggle-btn {
            padding: 6px;
            font-size: 12px;
        }

        #file-grid.grid-view-mode .file-card-inner {
            height: 80px;
        }

        #file-grid.grid-view-mode .file-name {
            font-size: 11px;
        }

        #file-grid.grid-view-mode .file-size {
            font-size: 10px;
        }

        #file-grid.list-view-mode .file-card {
            padding: 8px 10px;
            gap: 8px;
        }

        #file-grid.list-view-mode .file-card-inner {
            width: 40px;
            height: 40px;
        }

        #file-grid.list-view-mode .file-name {
            font-size: 12px;
        }

        #file-grid.list-view-mode .file-size {
            font-size: 11px;
        }
    }
    </style>
</head>
<body style="background-color: #f9f9f9;">
<div class="d-flex">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main flex-grow-1 p-4">
        <div class="header-section d-flex justify-content-between align-items-center mb-4 header-card">
            <div class="welcome-text">
                <p class="fs-5 mb-1">Semua File</p>
                <h6 class="fw-bold mt-3">File yang tersimpan</h6>
                <p class="text-muted small">Lihat semua file yang telah diunggah.</p>
            </div>
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <div style="display:flex; gap:8px; align-items:center;">
                    <div class="input-group" style="width:320px;">
                        <input id="file-search" type="text" class="form-control form-control-sm" placeholder="Cari file...">
                        <button id="file-search-btn" class="btn btn-primary btn-sm" type="button" title="Cari"><i class="fa fa-search"></i></button>
                    </div>
                    <select id="category-filter" class="form-select form-select-sm category-select" aria-label="Filter Kategori">
                        <option value="">Semua Kategori</option>
                        <option value="image">Gambar</option>
                        <option value="video">Video</option>
                        <option value="audio">Audio</option>
                        <option value="document">Dokumen</option>
                        <option value="archive">Arsip</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                <div class="view-toggle">
                    <button class="toggle-btn preview-btn" id="thumb-view" title="Thumbnail"><i class="fa fa-image"></i></button>
                    <button class="toggle-btn preview-btn" id="icon-view" title="Icon"><i class="fa fa-file"></i></button>
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
        </div>

        <?php
            $searchQuery = trim($_GET['q'] ?? '');
            if (!$userId) {
            echo '<div class="alert alert-warning">Silakan login untuk melihat file Anda.</div>';
        } else {
            $sm = new StorageManager();
            $filters = [];
            if (!empty($searchQuery)) $filters['search'] = $searchQuery;
            $dbItems = $sm->getUserFiles($userId, $filters);

            $items = [];
            if ($dbItems) {
                foreach ($dbItems as $it) {
                    $url = 'uploads/' . ($it['thumbnail_path'] ?: $it['file_path']);
                    $items[] = [
                        'id' => $it['id'] ?? 0,
                        'name' => $it['original_name'] ?? $it['filename'],
                        'size' => $it['size'] ?? ($it['file_size'] ?? 0),
                        'url' => $url,
                        'mime' => $it['mime'] ?? $it['mime_type'] ?? (@mime_content_type(__DIR__ . '/uploads/' . ($it['file_path'] ?? '')) ?: 'application/octet-stream'),
                        'is_favorite' => $it['is_favorite'] ?? 0
                    ];
                }
            }

            if (empty($items)) {
                echo '<div class="text-center mt-4">';
                echo '<img src="assets/image/defaultNotfound.png" alt="Tidak ada file" style="max-width:260px; opacity:0.95;">';
                echo '<p class="text-muted small mt-2">Tidak ada file.</p>';
                echo '</div>';
            } else {
                // show counts header (with IDs for JS updates)
                $total = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0', [$userId]);
                $favorites = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0 AND is_favorite = 1', [$userId]);
                $trash = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 1', [$userId]);
                echo '<div class="row mb-3">';
                echo '<div class="col-auto d-flex gap-4 align-items-center">';
                echo '<div class="text-center"><i class="fa fa-folder fa-2x text-dark"></i><div class="fw-bold" id="total-files-count">' . intval($total['cnt'] ?? 0) . ' File</div><div class="small text-muted">Total file yang tersimpan</div></div>';
                echo '<div class="text-center"><i class="fa fa-star fa-2x text-warning"></i><div class="fw-bold" id="favorite-files-count">' . intval($favorites['cnt'] ?? 0) . ' Favorit</div><div class="small text-muted">File yang sering diakses</div></div>';
                echo '<div class="text-center"><i class="fa fa-trash fa-2x text-danger"></i><div class="fw-bold" id="trash-files-count">' . intval($trash['cnt'] ?? 0) . ' Sampah</div><div class="small text-muted">File siap hapus permanen</div></div>';
                echo '</div></div>';

                // render grid (contains header for list mode + items)
                echo '<div id="file-grid" data-page="all">';

                // determine owner name for this user's files
                $ownerRow = fetchOne('SELECT username, full_name FROM users WHERE id = ?', [$userId]);
                $ownerName = $ownerRow['full_name'] ?? $ownerRow['username'] ?? '-';

                // file list header (visible only in list mode via CSS)
                echo '<div class="file-list-header">';
                echo '<div class="col col-name"><i class="fa fa-file"></i> Nama File</div>';
                echo '<div class="col col-owner"><i class="fa fa-user"></i> Pemilik</div>';
                echo '<div class="col col-modified"><i class="fa fa-clock"></i> Tanggal Diubah</div>';
                echo '<div class="col col-size"><i class="fa fa-hdd"></i> Ukuran</div>';
                echo '<div class="col col-actions"><i class="fa fa-ellipsis-h"></i></div>';
                echo '</div>';
                foreach ($items as $it) {
                    $fileIdAttr = intval($it['id']);
                    $isFav = $it['is_favorite'] ? 'true' : 'false';
                    $favClass = !empty($it['is_favorite']) ? ' active' : '';
                    $fileUrl = htmlspecialchars($it['url'], ENT_QUOTES);
                    $fileNameEsc = htmlspecialchars($it['name'], ENT_QUOTES);
                    $fileSizeStr = human_filesize($it['size']);
                    // owner and modified date
                    $owner = htmlspecialchars($ownerName, ENT_QUOTES);
                    $modifiedRaw = $it['updated_at'] ?? $it['modified_at'] ?? $it['created_at'] ?? '';
                    $modified = $modifiedRaw ? date('d M Y', strtotime($modifiedRaw)) : '-';
                    $mime = htmlspecialchars($it['mime'], ENT_QUOTES);

                    // Determine icon path based on mime type
                    $iconPath = 'assets/icons/file.png';
                    if (strpos($it['mime'], 'image/') === 0) {
                        $iconPath = '';  // Use actual image
                    } elseif (strpos($it['mime'], 'audio/') === 0) {
                        $iconPath = 'assets/icons/music.png';
                    } elseif (strpos($it['mime'], 'video/') === 0) {
                        $iconPath = 'assets/icons/vid.png';
                    }

                                        // Using HEREDOC for clarity - render item with columns for list mode
                                        $iconAttr = strpos($it['mime'], 'image/') === 0 ? "<img src=\"{$fileUrl}\" alt=\"{$fileNameEsc}\" />" : "<img src=\"{$iconPath}\" class=\"icon-fallback\" alt=\"{$fileNameEsc}\" />";

                                        echo <<<HTML
<div class="file-item" data-file-id="{$fileIdAttr}" data-file-url="{$fileUrl}" data-file-name="{$fileNameEsc}" data-file-mime="{$mime}"
    data-name="{$fileNameEsc}"
    data-category="{$mime}">
    <div class="file-card">
        <!-- top-right small actions (fav / delete / more) -->
        <div class="top-actions" style="position: absolute; top: 8px; right: 8px; display: flex; flex-direction: row; flex-wrap: nowrap; gap: 4px; z-index: 50; align-items: center;">
            <button class="action-btn action-fav" title="Favorit" data-file-id="{$fileIdAttr}" data-favorite="{$isFav}" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                <i class="fa fa-star{$favClass}"></i>
            </button>
            <button class="action-btn action-delete" title="Hapus" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                <i class="fa fa-trash"></i>
            </button>
            <button class="action-btn action-more" aria-label="Opsi" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;"><i class="fa fa-ellipsis-v"></i></button>
        </div>

        <div class="file-card-inner">
            {$iconAttr}
        </div>

        <div class="file-row-columns">
            <div class="col col-name"><p class="file-name">{$fileNameEsc}</p></div>
            <div class="col col-owner">{$owner}</div>
            <div class="col col-modified">{$modified}</div>
            <div class="col col-size">{$fileSizeStr}</div>
            <div class="col col-actions">
                <button class="action-btn action-more" aria-label="Opsi" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;"><i class="fa fa-ellipsis-v"></i></button>
            </div>
        </div>
    </div><!-- .file-card -->
</div><!-- .file-item -->
HTML;
                }
                echo '</div>'; // end file-grid
            }
        }
        ?>
    <script>
    // Wire search input to filter results in real-time
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('file-search');
        const searchBtn = document.getElementById('file-search-btn');
        const grid = document.getElementById('file-grid');
        
        if (!searchInput || !grid) return;
        
        // Handle category filter dropdown
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', function () {
                // Trigger input event to filter items
                searchInput.dispatchEvent(new Event('input'));
            });
        }

            // Add search button click event to trigger filtering
            if (searchBtn) {
                searchBtn.addEventListener('click', function () {
                    searchInput.dispatchEvent(new Event('input'));
                });
            }
        
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
            const searchValue = searchInput.value.toLowerCase().trim();
            const categoryFilter = document.getElementById('category-filter');
            const selectedCategory = categoryFilter ? categoryFilter.value : '';
            const gridItems = grid.querySelectorAll('.file-item');

            gridItems.forEach(item => {
                const fileName = (item.dataset.fileName || '').toLowerCase();
                const mimeType = item.dataset.fileMime || '';
                const itemCategory = getCategory(mimeType);

                const matchesSearch = searchValue === '' || fileName.includes(searchValue);
                const matchesCategory = selectedCategory === '' || itemCategory === selectedCategory;

                if (matchesSearch && matchesCategory) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        }
        
        searchInput.addEventListener('input', filterItems);
    });
    </script>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const grid = document.getElementById('file-grid');
        const gridBtn = document.getElementById('grid-view');
        const listBtn = document.getElementById('list-view');

        if (!grid) return;

        // Initialize grid view mode by default
        grid.classList.add('grid-view-mode');
        grid.classList.remove('list-view-mode');
        if (gridBtn) gridBtn.classList.add('active');
        if (listBtn) listBtn.classList.remove('active');

        // View toggle handlers
        if (gridBtn) {
            gridBtn.addEventListener('click', function() {
                grid.classList.remove('list-view-mode');
                grid.classList.add('grid-view-mode');
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
                closeAllMenus();
            });
        }

        if (listBtn) {
            listBtn.addEventListener('click', function() {
                grid.classList.add('list-view-mode');
                grid.classList.remove('grid-view-mode');
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
                closeAllMenus();
            });
        }

        // Close all menus when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.action-more') && !e.target.closest('.more-menu')) {
                closeAllMenus();
            }
        });

        // Thumbnail / Icon preview toggle (persisted)
        const thumbBtn = document.getElementById('thumb-view');
        const iconBtn = document.getElementById('icon-view');

        function determineIconPath(mime) {
            if (!mime) return 'assets/icons/file.png';
            if (mime.startsWith('image/')) return 'assets/icons/img.png';
            if (mime.startsWith('audio/')) return 'assets/icons/music.png';
            if (mime.startsWith('video/')) return 'assets/icons/vid.png';
            if (mime.includes('pdf')) return 'assets/icons/pdf.png';
            if (mime.includes('zip') || mime.includes('archive')) return 'assets/icons/archive.png';
            return 'assets/icons/file.png';
        }

        function applyPreviewMode(mode) {
            const items = document.querySelectorAll('#file-grid .file-item');
            items.forEach(it => {
                const mime = (it.dataset.fileMime || '').toLowerCase();
                const url = it.dataset.fileUrl || (it.querySelector('img') ? it.querySelector('img').src : '');
                const thumbTarget = it.querySelector('.file-card-inner') || it.querySelector('.file-thumbnail') || it.querySelector('.list-view-thumbnail');
                if (!thumbTarget) return;
                if (mode === 'thumb' && mime.startsWith('image/') && url) {
                    thumbTarget.innerHTML = `<img src="${url}" alt="${it.dataset.fileName || ''}" />`;
                } else {
                    const icon = determineIconPath(mime);
                    thumbTarget.innerHTML = `<img src="${icon}" class="icon-fallback" alt="icon">`;
                }
            });
            try { localStorage.setItem('fileThumbnailMode', mode); } catch (e) {}
            if (thumbBtn) thumbBtn.classList.toggle('active', mode === 'thumb');
            if (iconBtn) iconBtn.classList.toggle('active', mode === 'icon');
        }

        // Initialize saved mode
        const savedThumbMode = localStorage.getItem('fileThumbnailMode') || 'thumb';
        applyPreviewMode(savedThumbMode);

        if (thumbBtn) thumbBtn.addEventListener('click', function () { applyPreviewMode('thumb'); });
        if (iconBtn) iconBtn.addEventListener('click', function () { applyPreviewMode('icon'); });

        // Delegated click handlers for all actions
        grid.addEventListener('click', function (e) {
            // Favorite button (overlay - grid only)
            if (e.target.closest('.action-fav')) {
                const item = e.target.closest('.file-item');
                const fileId = item.dataset.fileId;
                toggleFavorite(fileId, item);
                return;
            }

            // Delete button (overlay - grid only)
            if (e.target.closest('.action-delete')) {
                const item = e.target.closest('.file-item');
                const fileId = item.dataset.fileId;
                doDelete(fileId, item);
                return;
            }

            // More button toggle
            if (e.target.closest('.action-more')) {
                e.stopPropagation();
                const item = e.target.closest('.file-item');
                toggleMoreMenu(item);
                return;
            }

            // Menu item clicks
            if (e.target.closest('.more-item')) {
                handleMenuAction(e);
                closeAllMenus();
                return;
            }
        });

        // Double-click handler for preview
        grid.addEventListener('dblclick', function (e) {
            if (e.target.closest('.file-item')) {
                const item = e.target.closest('.file-item');
                const fileId = item.dataset.fileId;
                const fileName = item.dataset.fileName;
                showThumbnailPreview(fileId, fileName);
            }
        });

        function toggleMoreMenu(fileItem) {
            if (!fileItem) return;
            
            closeAllMenus(fileItem);
            
            const btn = fileItem.querySelector('.action-more');
            if (!btn) return;
            
            let menu = fileItem.querySelector('.more-menu');
            if (!menu) {
                menu = createMenuElement();
                fileItem.appendChild(menu);
            }

            menu.classList.add('show');
            
            const rect = btn.getBoundingClientRect();
            const menuW = menu.offsetWidth;
            const menuH = menu.offsetHeight;
            
            let left = rect.right - menuW;
            if (left < 8) left = 8;
            if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
            
            let top = rect.bottom + 6;
            if (top + menuH > window.innerHeight - 8) {
                top = rect.top - menuH - 6;
                if (top < 8) top = 8;
            }
            
            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
            menu.dataset.fileId = fileItem.dataset.fileId;
            menu.dataset.fileUrl = fileItem.dataset.fileUrl;
            menu.dataset.fileName = fileItem.dataset.fileName;
        }

        function createMenuElement() {
            const menu = document.createElement('div');
            menu.className = 'more-menu';
            menu.role = 'menu';
            menu.innerHTML = `
                <button class="more-item download" title="Download"><i class="fa fa-download"></i> Download</button>
                <button class="more-item rename" title="Ganti nama"><i class="fa fa-pencil-alt"></i> Ganti nama</button>
                <button class="more-item share" title="Bagikan"><i class="fa fa-user-plus"></i> Bagikan</button>
                <button class="more-item thumbnail" title="Lihat Thumbnail"><i class="fa fa-image"></i> Lihat Thumbnail</button>
                <button class="more-item favorite-menu" title="Tambahkan ke favorit"><i class="fa fa-star"></i> Favorit</button>
                <button class="more-item delete-menu" title="Hapus"><i class="fa fa-trash"></i> Hapus</button>
            `;
            return menu;
        }

        function handleMenuAction(e) {
            const action = e.target.closest('.more-item');
            if (!action) return;
            
            const menu = action.closest('.more-menu');
            const fileId = menu.dataset.fileId;
            const fileName = menu.dataset.fileName || '';
            const fileItem = document.querySelector(`.file-item[data-file-id="${fileId}"]`);

            if (action.classList.contains('download')) {
                if (!fileId) return;
                const url = 'download.php?file_id=' + encodeURIComponent(fileId);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName || '';
                document.body.appendChild(a);
                a.click();
                a.remove();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'File berhasil diunduh: ' + fileName,
                    position: 'bottom-right',
                    toast: true,
                    showConfirmButton: false,
                    timer: 3000
                });
            } else if (action.classList.contains('rename')) {
                const currentName = fileName || '';
                const newName = prompt('Ganti nama file menjadi:', currentName);
                if (newName !== null && newName.trim() !== '') {
                    fetch('rename.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ file_id: fileId, new_name: newName.trim() })
                    }).then(r => r.json()).then(j => {
                        if (j.success) {
                            if (fileItem) {
                                const nameEl = fileItem.querySelector('.file-name');
                                if (nameEl) nameEl.textContent = newName.trim();
                                fileItem.dataset.fileName = newName.trim();
                            }
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Nama file berhasil diubah menjadi: ' + j.new_name,
                                position: 'bottom-right',
                                toast: true,
                                showConfirmButton: false,
                                timer: 3000
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: j.message || 'Gagal mengganti nama',
                                position: 'bottom-right',
                                toast: true,
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    }).catch(() => {
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
            } else if (action.classList.contains('share')) {
                Swal.fire({
                    icon: 'info',
                    title: 'Info',
                    text: 'Fungsi bagikan belum diimplementasikan di demo ini.',
                    position: 'bottom-right',
                    toast: true,
                    showConfirmButton: false,
                    timer: 3000
                });
            } else if (action.classList.contains('thumbnail')) {
                showThumbnailPreview(fileId, fileName);
            } else if (action.classList.contains('favorite-menu')) {
                toggleFavorite(fileId, fileItem);
            } else if (action.classList.contains('delete-menu')) {
                doDelete(fileId, fileItem);
            }
        }

        function closeAllMenus(exceptItem) {
            document.querySelectorAll('.more-menu.show').forEach(menu => {
                if (exceptItem && exceptItem.contains(menu)) return;
                menu.classList.remove('show');
                menu.style.left = '';
                menu.style.top = '';
            });
            document.querySelectorAll('.file-item.menu-open').forEach(item => {
                if (!exceptItem || !exceptItem.contains(item)) {
                    item.classList.remove('menu-open');
                }
            });
        }

        function toggleFavorite(fileId, itemEl) {
            if (!fileId || !itemEl) return;
            const favBtn = itemEl.querySelector('.action-fav');
            if (!favBtn) return;
            
            fetch('favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ file_id: fileId })
            }).then(r => r.json()).then(j => {
                if (j.success) {
                    if (j.is_favorite == 1) {
                        favBtn.classList.add('active');
                        const icon = favBtn.querySelector('i');
                        if (icon) icon.className = 'fa fa-star';
                        itemEl.classList.add('favorited');
                        Swal.fire({
                            icon: 'success',
                            title: 'Ditambahkan ke favorit',
                            position: 'bottom-right',
                            toast: true,
                            showConfirmButton: false,
                            timer: 2000
                        });
                    } else {
                        favBtn.classList.remove('active');
                        const icon = favBtn.querySelector('i');
                        if (icon) icon.className = 'fa fa-star-o';
                        itemEl.classList.remove('favorited');
                        Swal.fire({
                            icon: 'success',
                            title: 'Dihapus dari favorit',
                            position: 'bottom-right',
                            toast: true,
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                    updateCounts(j.counts);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: j.message || 'Gagal',
                        position: 'bottom-right',
                        toast: true,
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            }).catch(() => {
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

        function doDelete(fileId, itemEl) {
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
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('delete.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ file_id: fileId })
                    }).then(r => r.json()).then(j => {
                        if (j.success) {
                            if (itemEl) itemEl.remove();
                            updateCounts(j.counts || {});
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
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: j.message || 'Gagal',
                                position: 'bottom-right',
                                toast: true,
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    }).catch(() => {
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
        }

        function updateCounts(c) {
            if (!c) return;
            const t = document.getElementById('total-files-count');
            const f = document.getElementById('favorite-files-count');
            const tr = document.getElementById('trash-files-count');
            if (t && typeof c.total !== 'undefined') t.textContent = c.total + ' File';
            if (f && typeof c.favorites !== 'undefined') f.textContent = c.favorites + ' Favorit';
            if (tr && typeof c.trash !== 'undefined') tr.textContent = c.trash + ' Sampah';
        }

        function getFileIcon(mimeType) {
            if (!mimeType) return 'assets/icons/file.png';
            if (mimeType.startsWith('image/')) return 'assets/icons/img.png';
            if (mimeType.startsWith('audio/')) return 'assets/icons/music.png';
            if (mimeType.startsWith('video/')) return 'assets/icons/vid.png';
            return 'assets/icons/file.png';
        }

        function showThumbnailPreview(fileId, fileName) {
            const fileItem = document.querySelector(`.file-item[data-file-id="${fileId}"]`);
            if (!fileItem) return;

            const fileUrl = fileItem.dataset.fileUrl;
            const mimeType = fileItem.dataset.fileMime;
            let content = '';
            let width = '600px';
            let height = '800px';

            if (mimeType && mimeType.startsWith('image/')) {
                content = `<img src="${fileUrl}" style="max-width: 100%; max-height: 100%; border-radius: 8px;">`;
                width = 'auto';
                height = 'auto';
            } else if (mimeType === 'application/pdf') {
                content = `<iframe src="${fileUrl}" style="width: 100%; height: 100%; border: none;"></iframe>`;
            } else if (mimeType.includes('spreadsheet') || mimeType.includes('excel') || mimeType.includes('sheet')) {
                // Use Google Docs viewer for Excel files
                const googleViewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(window.location.origin + '/' + fileUrl)}&embedded=true`;
                content = `<iframe src="${googleViewerUrl}" style="width: 100%; height: 100%; border: none;"></iframe>`;
            } else if (mimeType.includes('word') || mimeType.includes('document')) {
                // Use Google Docs viewer for Word files
                const googleViewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(window.location.origin + '/' + fileUrl)}&embedded=true`;
                content = `<iframe src="${googleViewerUrl}" style="width: 100%; height: 100%; border: none;"></iframe>`;
            } else if (mimeType && mimeType.startsWith('video/')) {
                content = `<video controls style="max-width: 100%; max-height: 100%;"><source src="${fileUrl}" type="${mimeType}">Your browser does not support the video tag.</video>`;
            } else if (mimeType && mimeType.startsWith('audio/')) {
                content = `<audio controls style="width: 100%;"><source src="${fileUrl}" type="${mimeType}">Your browser does not support the audio element.</audio>`;
            } else {
                const iconPath = getFileIcon(mimeType);
                content = `<div style="text-align: center;"><img src="${iconPath}" style="width: 120px; height: 120px; border-radius: 8px;"><p style="margin-top: 10px;">${fileName}</p><p style="color: #666;">Preview not available for this file type.</p></div>`;
            }

            Swal.fire({
                title: fileName,
                html: content,
                width: width,
                height: height,
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    popup: 'preview-modal'
                }
            });
        }
    });
</script>
</body>
</html>
