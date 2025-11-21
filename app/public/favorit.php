<?php
// favorit.php - Favorit page
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
    <title>Favorit - Clario</title>
    <link href="https://fonts.googleapis.com/css2?family=Krona+One&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Additional styles for favorites page */
        .file-list-header {
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
            padding: 16px 24px;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            display: none;
            font-size: 14px;
        }
        
        .list-view-mode .file-list-header {
            display: flex;
        }
        
        .grid-view-mode .file-list-header {
            display: none;
        }
        
        .header-name { flex: 3; min-width: 200px; }
        .header-owner { flex: 2; min-width: 150px; }
        .header-date { flex: 2; min-width: 150px; }
        .header-size { flex: 1; text-align: right; min-width: 100px; }
        
        /* List view specific styles */
        .list-view-mode #file-grid {
            background: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .list-view-mode .file-item {
            display: flex;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s ease;
            min-height: 80px;
        }
        
        .list-view-mode .file-item:hover {
            background-color: #f8f9fa;
        }
        
        .list-view-mode .file-item:last-child {
            border-bottom: none;
        }
        
        .list-view-mode .file-card {
            display: flex;
            align-items: center;
            width: 100%;
            gap: 20px;
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
            position: relative;
        }
        
        .list-view-mode .file-thumbnail {
            width: 50px;
            height: 50px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .list-view-mode .file-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .list-view-mode .file-icon {
            font-size: 24px;
            color: #6c757d;
        }
        
        .list-view-mode .file-info {
            flex: 3;
            display: flex;
            flex-direction: column;
            text-align: left;
            gap: 6px;
            min-width: 200px;
        }
        
        .list-view-mode .file-name {
            font-weight: 600;
            margin: 0 !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 15px;
            color: #212529;
        }
        
        .list-view-mode .file-meta {
            display: none;
        }
        
        .list-view-mode .file-owner {
            flex: 2;
            color: #6c757d;
            font-size: 14px;
            min-width: 150px;
        }
        
        .list-view-mode .file-date {
            flex: 2;
            color: #6c757d;
            font-size: 14px;
            min-width: 150px;
        }
        
        .list-view-mode .file-size-list {
            flex: 1;
            text-align: right;
            color: #6c757d;
            font-size: 14px;
            margin: 0 !important;
            min-width: 100px;
            font-weight: 500;
        }
        
        .list-view-mode .card-overlay {
            position: static;
            display: flex;
            gap: 8px;
            margin-left: auto;
            opacity: 1;
            transform: none;
        }
        
        .list-view-mode .btn-action {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            color: #6c757d;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .list-view-mode .btn-action:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .list-view-mode .fav-btn.active {
            color: #ffc107 !important;
            background: #fff3cd !important;
            border-color: #ffc107 !important;
        }
        
        /* Grid view styles - PERBAIKAN UKURAN BOX */
        .grid-view-mode #file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 24px;
        }
        
        .grid-view-mode .file-item {
            display: block;
            height: 240px;
        }
        
        .grid-view-mode .file-card {
            display: flex;
            flex-direction: column;
            text-align: center;
            height: 100%;
            padding: 20px;
            position: relative;
            overflow: hidden;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .grid-view-mode .file-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border-color: #007bff;
        }
        
        .grid-view-mode .file-card .file-thumbnail {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            overflow: hidden;
            border-radius: 8px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }
        
        .grid-view-mode .file-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .grid-view-mode .file-card .file-icon {
            font-size: 48px;
            color: #6c757d;
        }
        
        .grid-view-mode .file-card .file-info {
            padding-top: 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .grid-view-mode .file-card .file-name {
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 12px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #212529;
            line-height: 1.4;
        }
        
        .grid-view-mode .file-card .file-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
        }
        
        .grid-view-mode .file-card .file-size {
            color: #6c757d;
            font-weight: 600;
            font-size: 12px;
        }
        
        .grid-view-mode .file-card .file-category {
            color: #007bff;
            background: #e7f1ff;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 11px;
        }
        
        .grid-view-mode .file-card .card-overlay {
            position: absolute;
            top: 16px;
            right: 16px;
            display: flex;
            flex-direction: column;
            z-index: 20;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s ease;
        }
        
        .grid-view-mode .file-card:hover .card-overlay {
            opacity: 1;
            transform: translateX(0);
        }
        
        .grid-view-mode .file-card .card-overlay .btn-action {
            margin-bottom: 8px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            color: #6c757d;
            transition: all 0.2s ease;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .grid-view-mode .file-card .card-overlay .btn-action:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
            transform: scale(1.05);
        }
        
        .grid-view-mode .file-owner,
        .grid-view-mode .file-date,
        .grid-view-mode .file-size-list {
            display: none !important;
        }
        
        /* Stats Section */
        .stats-section {
            margin-bottom: 32px;
        }
        
        .stats-grid {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            min-width: 240px;
            transition: transform 0.2s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            font-size: 28px;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #f8f9fa;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 6px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #6c757d;
        }
        
        /* Header Controls */
        .header-controls {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-filter-group {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        
        .search-wrapper {
            position: relative;
            width: 320px;
        }
        
        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 3;
        }
        
        .search-input {
            padding-left: 48px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            transition: all 0.2s ease;
            height: 44px;
            font-size: 14px;
        }
        
        .search-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.1);
        }
        
        .category-select {
            width: 180px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            height: 44px;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .category-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.1);
        }
        
        /* View Toggle */
        .view-toggle {
            display: flex;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background-color: #fff;
            position: relative;
            overflow: hidden;
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
            padding: 12px 16px;
            cursor: pointer;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            transition: all 0.2s ease;
            border-radius: 0;
            position: relative;
            z-index: 1;
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
            border-radius: 9px 0 0 9px;
        }
        
        .toggle-btn:last-child {
            border-radius: 0 9px 9px 0;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-image {
            max-width: 320px;
            opacity: 0.8;
            margin-bottom: 24px;
        }
        
        .empty-text {
            color: #6c757d;
            font-size: 18px;
            margin: 0;
        }
        
        /* Dark mode support */
        .dark-mode .file-list-header {
            background: #2d3748;
            color: #e2e8f0;
            border-bottom-color: #4a5568;
        }
        
        .dark-mode .list-view-mode #file-grid {
            background: #2d3748;
        }
        
        .dark-mode .list-view-mode .file-item {
            border-bottom-color: #4a5568;
        }
        
        .dark-mode .list-view-mode .file-item:hover {
            background-color: #4a5568;
        }
        
        .dark-mode .list-view-mode .file-thumbnail {
            background: #4a5568;
        }
        
        .dark-mode .list-view-mode .file-name {
            color: #f7fafc;
        }
        
        .dark-mode .stat-item {
            background: #2d3748;
            color: white;
        }
        
        .dark-mode .stat-icon {
            background: #4a5568;
            color: #cbd5e0;
        }
        
        .dark-mode .stat-number {
            color: #f7fafc;
        }
        
        .dark-mode .stat-label {
            color: #a0aec0;
        }
        
        .dark-mode .file-card {
            background: #2d3748;
        }
        
        .dark-mode .file-name {
            color: #f7fafc;
        }
        
        .dark-mode .file-thumbnail {
            background: #4a5568;
        }
        
        .dark-mode .file-icon {
            color: #cbd5e0;
        }
        
        .dark-mode .file-category {
            background: #2d5aa0;
            color: #e2e8f0;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .grid-view-mode #file-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .header-controls {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .search-filter-group {
                width: 100%;
            }
            
            .search-wrapper {
                flex: 1;
                min-width: 200px;
            }
            
            .stats-grid {
                gap: 16px;
            }
            
            .stat-item {
                min-width: calc(50% - 8px);
                flex: 1;
            }
            
            .grid-view-mode #file-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 20px;
            }
            
            .grid-view-mode .file-item {
                height: 220px;
            }
            
            .grid-view-mode .file-card .file-thumbnail {
                height: 100px;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                flex-direction: column;
            }
            
            .stat-item {
                min-width: 100%;
            }
            
            .search-filter-group {
                flex-direction: column;
                width: 100%;
            }
            
            .search-wrapper,
            .category-select {
                width: 100%;
            }
            
            .grid-view-mode #file-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 16px;
            }
            
            .grid-view-mode .file-item {
                height: 200px;
            }
            
            .grid-view-mode .file-card {
                padding: 16px;
            }
            
            .grid-view-mode .file-card .file-thumbnail {
                height: 90px;
            }
            
            .list-view-mode .file-item {
                padding: 12px 16px;
            }
            
            .list-view-mode .file-card {
                gap: 12px;
            }
            
            .list-view-mode .file-thumbnail {
                width: 40px;
                height: 40px;
            }
        }
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
            <h4 class="fw-bold">Favorit</h4>
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
                <p class="fs-5 mb-1">File <span class="text-info fw-semibold">Favorit</span> Anda</p>
                <h6 class="fw-bold mt-3">File yang ditandai favorit</h6>
                <p class="text-muted small">Lihat file yang kamu tandai sebagai favorit.</p>
            </div>
            <div class="view-toggle">
                <button class="toggle-btn active" id="grid-view" title="Tampilan Kotak"><span class="iconify" data-icon="mdi:view-grid-outline" data-width="18"></span></button>
                <button class="toggle-btn" id="list-view" title="Tampilan Daftar"><span class="iconify" data-icon="mdi:view-list-outline" data-width="18"></span></button>
            </div>
        </div>

        <?php
        if (!$userId) {
            echo '<div class="text-center py-5">';
            echo '<i class="fa fa-lock fa-3x text-muted mb-3" style="display: block;"></i>';
            echo '<h5 class="fw-bold mb-2">Login untuk Melihat Favorit</h5>';
            echo '<p class="text-muted mb-4">Silakan login untuk melihat file favorit Anda.</p>';
            echo '<a href="login.php" class="btn btn-primary">Login Sekarang</a>';
            echo '</div>';
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
                        'is_favorite' => $it['is_favorite'] ?? 0,
                        'created_at' => $it['created_at'] ?? $it['upload_date'] ?? date('Y-m-d H:i:s'),
                        'owner' => $it['owner_name'] ?? 'Anda'
                    ];
                }
            }

            if (empty($items)) {
                echo '<div class="empty-state">';
                echo '<img src="assets/image/defaultNotfound.png" alt="Tidak ada file favorit" class="empty-image">';
                echo '<p class="empty-text">Tidak ada file favorit.</p>';
                echo '</div>';
            } else {
                // show counts header
                $total = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0', [$userId]);
                $favorites = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0 AND is_favorite = 1', [$userId]);
                $trash = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 1', [$userId]);
                echo '<div class="stats-section">';
                echo '<div class="stats-grid">';
                echo '<div class="stat-item">';
                echo '<i class="fa fa-folder stat-icon"></i>';
                echo '<div class="stat-content">';
                echo '<div class="stat-number" id="total-files-count">' . intval($total['cnt'] ?? 0) . ' File</div>';
                echo '<div class="stat-label">Total file yang tersimpan</div>';
                echo '</div>';
                echo '</div>';
                echo '<div class="stat-item">';
                echo '<i class="fa fa-star stat-icon"></i>';
                echo '<div class="stat-content">';
                echo '<div class="stat-number" id="favorite-files-count">' . intval($favorites['cnt'] ?? 0) . ' Favorit</div>';
                echo '<div class="stat-label">File yang sering diakses</div>';
                echo '</div>';
                echo '</div>';
                echo '<div class="stat-item">';
                echo '<i class="fa fa-trash stat-icon"></i>';
                echo '<div class="stat-content">';
                echo '<div class="stat-number" id="trash-files-count">' . intval($trash['cnt'] ?? 0) . ' Sampah</div>';
                echo '<div class="stat-label">File siap hapus permanen</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';

                // File list header (only visible in list view)
                echo '<div class="file-list-header">';
                echo '<div class="header-name">Nama File</div>';
                echo '<div class="header-owner">Pemilik</div>';
                echo '<div class="header-date">Tanggal Diubah</div>';
                echo '<div class="header-size">Ukuran</div>';
                echo '</div>';

                // render grid
                echo '<div id="file-grid" data-page="favorites" class="file-grid-container">';
                foreach ($items as $it) {
                    $fileIdAttr = intval($it['id']);
                    $isFav = $it['is_favorite'] ? 'true' : 'false';
                    $favClass = !empty($it['is_favorite']) ? ' active' : '';
                    $fileCategory = getFileCategory($it['mime']);
                    $fileDate = date('d M Y', strtotime($it['created_at']));
                    $fileOwner = htmlspecialchars($it['owner'] ?? 'Anda');
                    
                    echo '<div class="file-item" data-file-id="' . $fileIdAttr . '" data-file-name="' . htmlspecialchars($it['name']) . '" data-file-mime="' . htmlspecialchars($it['mime']) . '" data-file-category="' . $fileCategory . '">';
                    echo '<div class="file-card">';
                    
                    // Thumbnail
                    echo '<div class="file-thumbnail">';
                    if (strpos($it['mime'], 'image/') === 0) {
                        echo '<img src="' . $it['url'] . '" alt="' . htmlspecialchars($it['name']) . '" loading="lazy">';
                    } else {
                        echo '<div class="file-icon">';
                        echo '<i class="fa ' . getFileIcon($it['mime']) . '"></i>';
                        echo '</div>';
                    }
                    echo '</div>';
                    
                    // File info
                    echo '<div class="file-info">';
                    echo '<h6 class="file-name">' . htmlspecialchars($it['name']) . '</h6>';
                    echo '<div class="file-meta">';
                    echo '<span class="file-size">' . human_filesize($it['size']) . '</span>';
                    echo '<span class="file-category">' . $fileCategory . '</span>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Additional info for list view
                    echo '<div class="file-owner">' . $fileOwner . '</div>';
                    echo '<div class="file-date">' . $fileDate . '</div>';
                    echo '<div class="file-size-list">' . human_filesize($it['size']) . '</div>';
                    
                    // Card overlay (actions)
                    echo '<div class="card-overlay">';
                    echo '<button class="btn btn-action fav-btn' . $favClass . '" data-file-id="' . $fileIdAttr . '" data-favorite="' . $isFav . '" title="' . ($it['is_favorite'] ? 'Hapus dari favorit' : 'Tambah ke favorit') . '">';
                    echo '<i class="fa fa-star"></i>';
                    echo '</button>';
                    echo '<button class="btn btn-action del-btn" title="Hapus">';
                    echo '<i class="fa fa-trash"></i>';
                    echo '</button>';
                    echo '<button class="btn btn-action more-btn" title="Lainnya">';
                    echo '<i class="fa fa-ellipsis-v"></i>';
                    echo '</button>';
                    echo '</div>';
                    
                    echo '</div></div>';
                }
                echo '</div>';
            }
        }
        
        // Helper function to get file category
        function getFileCategory($mimeType) {
            if (!$mimeType) return 'Lainnya';
            if (strpos($mimeType, 'image/') === 0) return 'Gambar';
            if (strpos($mimeType, 'video/') === 0) return 'Video';
            if (strpos($mimeType, 'audio/') === 0) return 'Audio';
            if (strpos($mimeType, 'application/pdf') !== false || 
                strpos($mimeType, 'word') !== false || 
                strpos($mimeType, 'sheet') !== false || 
                strpos($mimeType, 'presentation') !== false) return 'Dokumen';
            if (strpos($mimeType, 'zip') !== false || 
                strpos($mimeType, 'rar') !== false || 
                strpos($mimeType, '7z') !== false || 
                strpos($mimeType, 'compressed') !== false) return 'Arsip';
            return 'Lainnya';
        }
        
        // Helper function to get file icon
        function getFileIcon($mimeType) {
            if (strpos($mimeType, 'image/') === 0) return 'fa-image';
            if (strpos($mimeType, 'video/') === 0) return 'fa-video';
            if (strpos($mimeType, 'audio/') === 0) return 'fa-music';
            if (strpos($mimeType, 'application/pdf') !== false) return 'fa-file-pdf';
            if (strpos($mimeType, 'word') !== false) return 'fa-file-word';
            if (strpos($mimeType, 'sheet') !== false || strpos($mimeType, 'excel') !== false) return 'fa-file-excel';
            if (strpos($mimeType, 'presentation') !== false || strpos($mimeType, 'powerpoint') !== false) return 'fa-file-powerpoint';
            if (strpos($mimeType, 'zip') !== false || strpos($mimeType, 'rar') !== false) return 'fa-file-archive';
            return 'fa-file';
        }
        ?>
    </div>
</div>

<!-- Global floating menu -->
<div id="global-more-menu" class="more-menu" role="menu" aria-hidden="true" style="display:none;">
    <button class="more-item download" title="Download"><i class="fa fa-download"></i> Download</button>
    <button class="more-item rename" title="Ganti nama"><i class="fa fa-pencil-alt"></i> Ganti nama</button>
    <button class="more-item share" title="Bagikan"><i class="fa fa-user-plus"></i> Bagikan</button>
    <button class="more-item favorite-menu" title="Hapus dari favorit"><i class="fa fa-star"></i> Hapus dari Favorit</button>
    <button class="more-item delete-menu" title="Hapus"><i class="fa fa-trash"></i> Hapus</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('file-grid');
    const gridBtn = document.getElementById('grid-view');
    const listBtn = document.getElementById('list-view');
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    
    if (!grid) return;
    
    // View toggle handlers
    function updateViewMode(mode) {
        if (mode === 'list') {
            grid.classList.add('list-view-mode');
            grid.classList.remove('grid-view-mode');
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
        } else {
            grid.classList.add('grid-view-mode');
            grid.classList.remove('list-view-mode');
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
        }
        localStorage.setItem('fileViewMode', mode);
    }

    if (gridBtn) {
        gridBtn.addEventListener('click', function() {
            updateViewMode('grid');
        });
    }
    
    if (listBtn) {
        listBtn.addEventListener('click', function() {
            updateViewMode('list');
        });
    }
    
    // Load saved view mode
    const savedViewMode = localStorage.getItem('fileViewMode') || 'grid';
    updateViewMode(savedViewMode);
    
    // Search and category filter
    function filterItems() {
        const searchValue = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const categoryValue = categoryFilter ? categoryFilter.value : '';
        const items = grid.querySelectorAll('.file-item');
        let visibleCount = 0;
        
        items.forEach(item => {
            const fileName = (item.dataset.fileName || '').toLowerCase();
            const fileCategory = item.dataset.fileCategory || '';
            
            const matchesSearch = searchValue === '' || fileName.includes(searchValue);
            const matchesCategory = categoryValue === '' || fileCategory === categoryValue;
            
            if (matchesSearch && matchesCategory) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        const noResults = document.getElementById('no-results');
        if (visibleCount === 0 && items.length > 0) {
            if (!noResults) {
                const noResultsMsg = document.createElement('div');
                noResultsMsg.id = 'no-results';
                noResultsMsg.className = 'no-results text-center py-5';
                noResultsMsg.innerHTML = `
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>Tidak ada file yang cocok</h5>
                    <p class="text-muted">Coba ubah kata kunci pencarian atau filter kategori</p>
                `;
                grid.parentNode.insertBefore(noResultsMsg, grid);
            }
        } else if (noResults) {
            noResults.remove();
        }
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterItems);
        // Clear search on escape key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                filterItems();
            }
        });
    }
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterItems);
    }
    
    // Event delegation for favorite and delete buttons
    grid.addEventListener('click', function (e) {
        const fav = e.target.closest('.fav-btn');
        if (fav) {
            const item = fav.closest('.file-item');
            const fileId = item.dataset.fileId;
            
            // Toggle visual state immediately for better UX
            fav.classList.toggle('active');
            const isCurrentlyFavorite = fav.classList.contains('active');
            fav.title = isCurrentlyFavorite ? 'Hapus dari favorit' : 'Tambah ke favorit';
            
            fetch('favorite.php', { 
                method: 'POST', 
                headers: { 'Content-Type':'application/json' }, 
                body: JSON.stringify({ file_id: fileId }) 
            })
            .then(r => r.json())
            .then(j => {
                if (j.success) {
                    // If we're on the favorites page and user un-favorited, remove the item
                    if (grid.dataset.page === 'favorites' && j.is_favorite == 0) {
                        item.style.opacity = '0';
                        setTimeout(() => {
                            item.remove();
                            updateCounts(j.counts);
                            // Check if no items left
                            if (grid.querySelectorAll('.file-item').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    } else {
                        updateCounts(j.counts);
                    }
                } else {
                    // Revert visual state on error
                    fav.classList.toggle('active');
                    fav.title = !isCurrentlyFavorite ? 'Hapus dari favorit' : 'Tambah ke favorit';
                    alert(j.message || 'Gagal');
                }
            })
            .catch(() => {
                // Revert visual state on network error
                fav.classList.toggle('active');
                fav.title = !isCurrentlyFavorite ? 'Hapus dari favorit' : 'Tambah ke favorit';
                alert('Network error');
            });
            return;
        }
        
        const del = e.target.closest('.del-btn');
        if (del) {
            const item = del.closest('.file-item');
            const fileId = item.dataset.fileId;
            const fileName = item.dataset.fileName || 'file ini';
            
            if (!confirm(`Hapus "${fileName}"? File akan dipindahkan ke sampah.`)) return;
            
            // Visual feedback
            item.style.opacity = '0.5';
            
            fetch('delete.php', { 
                method: 'POST', 
                headers: { 'Content-Type':'application/json' }, 
                body: JSON.stringify({ file_id: fileId }) 
            })
            .then(r => r.json())
            .then(j => { 
                if (j.success) { 
                    item.style.opacity = '0';
                    setTimeout(() => {
                        item.remove();
                        updateCounts(j.counts || {});
                        // Check if no items left
                        if (grid.querySelectorAll('.file-item').length === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    item.style.opacity = '1';
                    alert(j.message || 'Gagal'); 
                }
            })
            .catch(() => {
                item.style.opacity = '1';
                alert('Network error');
            });
            return;
        }
    });
    
    function updateCounts(c) { 
        if(!c) return; 
        const totalEl = document.getElementById('total-files-count');
        const favEl = document.getElementById('favorite-files-count');
        const trashEl = document.getElementById('trash-files-count');
        
        if(totalEl && typeof c.total !== 'undefined') totalEl.textContent = c.total + ' File'; 
        if(favEl && typeof c.favorites !== 'undefined') favEl.textContent = c.favorites + ' Favorit'; 
        if(trashEl && typeof c.trash !== 'undefined') trashEl.textContent = c.trash + ' Sampah'; 
    }
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Focus search on Ctrl+K or Cmd+K
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
            }
        }
    });
});
</script>

<!-- Profile Modal -->
<!-- Tambahkan kode profile modal dari index.php jika diperlukan -->

</body>
</html>