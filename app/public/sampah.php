<?php
// sampah.php - Sampah (trash) page (cleaned)
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
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    /* Minimal page-specific styles (kept compact and safe) */
    .view-toggle { display:flex; border:1px solid #dee2e6; border-radius:6px; background:#fff; }
    .view-toggle .toggle-btn{ background:none; border:0; padding:8px; cursor:pointer; }
    .view-toggle .toggle-btn.active{ background:#007bff; color:#fff; }
    #file-grid.grid-view-mode{ display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:16px; padding:12px 0; }
    #file-grid.list-view-mode{ display:flex; flex-direction:column; gap:10px; padding:12px 0; }
    .file-card{ background:#fff; border:1px solid #e6e6e6; border-radius:8px; overflow:visible; }
    .file-card-inner{ height:110px; display:flex; align-items:center; justify-content:center; overflow:hidden; border-radius:8px 8px 0 0; background:#f5f5f5; }
    .file-card-inner img{ width:100%; height:100%; object-fit:cover; }
    .card-overlay{ position:absolute; top:8px; right:8px; display:flex; flex-direction:column; gap:6px; opacity:0; transition:opacity .12s; z-index:10; }
    .file-item{ position:relative; }
    .file-item:hover .card-overlay{ opacity:1; }
    .action-btn-group{ display:flex; gap:6px; flex-direction:column; }
    .action-btn-group .btn-sm{ font-size:11px; padding:6px 8px; }
    @media (max-width:768px){ #file-grid.grid-view-mode{ grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); } .file-card-inner{ height:90px; } }
    @media (max-width:480px){ .header-section{ flex-direction:column; gap:12px; } .view-toggle{ width:96px; } }
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
            <div class="view-toggle">
                <button class="toggle-btn active" id="grid-view" title="Tampilan Kotak">
                    <span class="iconify" data-icon="mdi:view-grid-outline" data-width="18"></span>
                </button>
                <button class="toggle-btn" id="list-view" title="Tampilan Daftar">
                    <span class="iconify" data-icon="mdi:view-list-outline" data-width="18"></span>
                </button>
            </div>
        </div>

        <?php
        if (session_status() === PHP_SESSION_NONE) session_start();
        require_once __DIR__ . '/connection.php';
        require_once __DIR__ . '/file_functions.php';

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            echo '<div class="alert alert-warning">Silakan login untuk melihat sampah Anda.</div>';
        } else {
            $rows = fetchAll('SELECT * FROM file_storage_paths WHERE user_id = ? AND is_deleted = 1 ORDER BY deleted_at DESC', [$userId]);
            $items = [];
            if ($rows) {
                foreach ($rows as $r) {
                    $url = 'uploads/' . ($r['thumbnail_path'] ?: $r['file_path']);
                    $items[] = [
                        'id' => $r['file_id'] ?? 0,
                        'name' => $r['original_filename'] ?? $r['stored_filename'],
                        'size' => $r['file_size'] ?? 0,
                        'url' => $url,
                        'mime' => $r['mime_type'] ?? 'application/octet-stream'
                    ];
                }
            }

            if (empty($items)) {
                echo '<div class="text-center mt-4">';
                echo '<img src="assets/image/defaultNotfound.png" alt="Tidak ada sampah" style="max-width:260px; opacity:0.95;">';
                echo '<p class="text-muted small mt-2">Tidak ada sampah.</p>';
                echo '</div>';
            } else {
                // show counts
                $total = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0', [$userId]);
                $favorites = fetchOne('SELECT COUNT(*) as cnt FROM files WHERE user_id = ? AND is_trashed = 0 AND is_favorite = 1', [$userId]);
                $trash = fetchOne('SELECT COUNT(*) as cnt FROM file_storage_paths WHERE user_id = ? AND is_deleted = 1', [$userId]);
                echo '<div class="row mb-3">';
                echo '<div class="col-auto d-flex gap-4 align-items-center">';
                echo '<div class="text-center"><i class="fa fa-folder fa-2x text-dark"></i><div class="fw-bold">' . intval($total['cnt'] ?? 0) . ' File</div><div class="small text-muted">Total file yang tersimpan</div></div>';
                echo '<div class="text-center"><i class="fa fa-star fa-2x text-warning"></i><div class="fw-bold">' . intval($favorites['cnt'] ?? 0) . ' Favorit</div><div class="small text-muted">File yang sering diakses</div></div>';
                echo '<div class="text-center"><i class="fa fa-trash fa-2x text-danger"></i><div class="fw-bold">' . intval($trash['cnt'] ?? 0) . ' Sampah</div><div class="small text-muted">File siap hapus permanen</div></div>';
                echo '</div></div>';

                echo '<div id="file-grid" data-page="trash">';
                foreach ($items as $it) {
                    $fileIdAttr = intval($it['id']);
                    echo '<div class="file-item" data-file-id="' . $fileIdAttr . '">';
                    echo '<div class="file-card position-relative">';
                    echo '<div class="file-card-inner">';
                    echo '<div class="card-overlay">';
                    echo '<div class="action-btn-group">';
                    echo '<button class="btn btn-sm btn-light share-btn" title="Bagikan" aria-label="Bagikan">';
                    echo '<i class="fa fa-share-alt me-1"></i> Bagikan';
                    echo '</button>';
                    echo '<button class="btn btn-sm btn-warning fav-btn" title="Favorit" aria-label="Favorit">';
                    echo '<i class="fa fa-star"></i>';
                    echo '</button>';
                    echo '<button class="btn btn-sm btn-primary rename-btn" title="Ganti nama" aria-label="Ganti nama">';
                    echo '<i class="fa fa-pencil-alt me-1"></i> Ganti';
                    echo '</button>';
                    echo '<button class="btn btn-sm btn-success restore-btn" title="Kembalikan file" aria-label="Kembalikan file">';
                    echo '<i class="fa fa-undo me-1"></i> Pulihkan';
                    echo '</button>';
                    echo '<button class="btn btn-sm btn-danger del-perm-btn" title="Hapus permanen" aria-label="Hapus permanen">';
                    echo '<i class="fa fa-trash"></i> Hapus';
                    echo '</button>';
                    echo '</div>';
                    echo '</div>';
                    if (strpos($it['mime'], 'image/') === 0) {
                        echo '<div class="file-thumbnail"><img src="' . $it['url'] . '" alt="' . htmlspecialchars($it['name']) . '"></div>';
                    } else {
                        echo '<div class="file-thumbnail"><i class="fa fa-file"></i></div>';
                    }
                    echo '</div>';
                    echo '<div class="file-info p-2">';
                    echo '<p class="file-name mb-1">' . htmlspecialchars($it['name']) . '</p>';
                    echo '<p class="file-size text-muted small mb-0">' . human_filesize($it['size']) . '</p>';
                    echo '</div>';
                    echo '</div></div>';
                }
                echo '</div>';
            }
        }
        ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('file-grid');
    const gridBtn = document.getElementById('grid-view');
    const listBtn = document.getElementById('list-view');
    if (!grid) return;

    function setGridMode(){ grid.classList.remove('list-view-mode'); grid.classList.add('grid-view-mode'); gridBtn && gridBtn.classList.add('active'); listBtn && listBtn.classList.remove('active'); }
    function setListMode(){ grid.classList.add('list-view-mode'); grid.classList.remove('grid-view-mode'); listBtn && listBtn.classList.add('active'); gridBtn && gridBtn.classList.remove('active'); }

    if (gridBtn) gridBtn.addEventListener('click', setGridMode);
    if (listBtn) listBtn.addEventListener('click', setListMode);

    // default
    setGridMode();

    function updateCounts(c){ if(!c) return; var t=document.getElementById('total-files-count'); var f=document.getElementById('favorite-files-count'); var tr=document.getElementById('trash-files-count'); if(t&&typeof c.total!=='undefined') t.textContent = c.total + ' File'; if(f&&typeof c.favorites!=='undefined') f.textContent = c.favorites + ' Favorit'; if(tr&&typeof c.trash!=='undefined') tr.textContent = c.trash + ' Sampah'; }

    // delegate clicks for restore, permanent delete, favorite, rename, share
    grid.addEventListener('click', function (e) {
        // Handle restore button
        const restoreBtn = e.target.closest('.restore-btn');
        if (restoreBtn) {
            const item = restoreBtn.closest('.file-item');
            if (!item) return;
            const fileId = item.dataset.fileId;
            const fileName = item.querySelector('.file-name')?.textContent || 'file';
            
            Swal.fire({title: 'Kembalikan File?', text: 'Kembalikan file "' + fileName + '" ke lokasi aslinya?', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Kembalikan', confirmButtonColor: '#28a745', cancelButtonText: 'Batal', position: 'bottom-right'}).then(result=>{
                if (result.isConfirmed) {
                    fetch('delete.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify({ file_id: fileId, action: 'restore' }) })
                    .then(r => r.json())
                    .then(j => {
                        if (j && j.success) {
                            item.style.opacity = '0'; item.style.transform = 'scale(0.95)'; setTimeout(() => item.remove(), 300);
                            updateCounts(j.counts || {});
                            Swal.fire({icon: 'success', title: 'Berhasil', text: 'File telah dipulihkan', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000});
                            setTimeout(() => { if (grid.children.length === 0) location.reload(); }, 500);
                        } else Swal.fire({icon: 'error', title: 'Gagal', text: (j && j.message) ? j.message : 'Gagal memulihkan file', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000});
                    }).catch(err => { console.error(err); Swal.fire({icon: 'error', title: 'Error', text: 'Network error', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}); });
                }
            });
            return;
        }

        // Handle favorite toggle
        const favBtn = e.target.closest('.fav-btn');
        if (favBtn) {
            const item = favBtn.closest('.file-item'); if (!item) return;
            const fileId = item.dataset.fileId;
            fetch('favorite.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId }) })
            .then(r=>r.json()).then(j=>{
                if (j && j.success) {
                    item.classList.toggle('favorited', j.is_favorite == 1);
                    if (favBtn) favBtn.dataset.favorite = j.is_favorite == 1 ? 'true' : 'false';
                    updateCounts(j.counts || {});
                    var msg = j.is_favorite == 1 ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit';
                    Swal.fire({icon: 'success', title: 'Berhasil', text: msg, position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000});
                } else Swal.fire({icon: 'error', title: 'Gagal', text: j.message||'Gagal', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000});
            }).catch(()=>Swal.fire({icon: 'error', title: 'Error', text: 'Network error', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}));
            return;
        }

        // Handle rename (auto-append extension if user omits it)
        const renameBtn = e.target.closest('.rename-btn');
        if (renameBtn) {
            const item = renameBtn.closest('.file-item'); if (!item) return;
            const fileId = item.dataset.fileId;
            const currentFull = item.querySelector('.file-name')?.textContent || '';
            // prefill prompt without extension for convenience
            const currentBase = (currentFull || '').replace(/\.[^/.]+$/, '');
            const userInput = prompt('Ganti nama file menjadi (ekstensi akan ditangani otomatis):', currentBase || '');
            if (userInput !== null && userInput.trim() !== '') {
                const newNameProvided = userInput.trim();
                fetch('rename.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId, new_name: newNameProvided }) })
                .then(r=>r.json()).then(j=>{
                    if (j && j.success) {
                        const final = j.new_name || (newNameProvided);
                        const n = item.querySelector('.file-name'); if (n) n.textContent = final; item.dataset.fileName = final;
                        Swal.fire({icon: 'success', title: 'Berhasil', text: 'Nama file berhasil diubah menjadi: ' + final, position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000});
                    } else Swal.fire({icon: 'error', title: 'Gagal', text: j.message||'Gagal', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000});
                }).catch(()=>Swal.fire({icon: 'error', title: 'Error', text: 'Network error', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000}));
            }
            return;
        }

        // Handle share (copy download link)
        const shareBtn = e.target.closest('.share-btn');
        if (shareBtn) {
            const item = shareBtn.closest('.file-item'); if (!item) return;
            const fileId = item.dataset.fileId; const fileName = item.querySelector('.file-name')?.textContent || '';
            const url = 'download.php?file_id=' + encodeURIComponent(fileId);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(()=>{
                    Swal.fire({icon: 'success', title: 'Berhasil', text: 'Link unduhan disalin ke clipboard', position: 'bottom-right', toast: true, showConfirmButton: false, timer: 3000});
                }).catch(()=>{ prompt('Salin link berikut:', url); });
            } else { prompt('Salin link berikut:', url); }
            return;
        }        // Handle permanent delete button
        const delBtn = e.target.closest('.del-perm-btn');
        if (!delBtn) return;
        const item = delBtn.closest('.file-item'); if (!item) return;
        const fileId = item.dataset.fileId; const fileName = item.querySelector('.file-name')?.textContent || 'file';
        if (!confirm('Hapus permanen file "' + fileName + '"? Tindakan ini tidak dapat dibatalkan.')) return;
        fetch('delete.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify({ file_id: fileId, permanent: 1 }) })
        .then(r => r.json())
        .then(j => {
            if (j && j.success) {
                const alertDiv = document.createElement('div'); alertDiv.className = 'alert alert-info alert-dismissible fade show'; alertDiv.role = 'alert'; alertDiv.innerHTML = '<i class="fa fa-trash me-2"></i><strong>Dihapus!</strong> File telah dihapus secara permanen. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>'; document.querySelector('.main')?.insertBefore(alertDiv, document.querySelector('.header-section')?.nextElementSibling);
                item.style.opacity = '0'; item.style.transform = 'scale(0.95)'; setTimeout(() => item.remove(), 300);
                updateCounts(j.counts || {});
                setTimeout(() => { if (grid.children.length === 0) location.reload(); }, 500);
            } else alert((j && j.message) ? j.message : 'Gagal menghapus');
        }).catch(err => { console.error(err); alert('Network error'); });
    });
});
</script>

</body>
</html>
    height: 180px;
