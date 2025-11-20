// favorite.js - unify favorite toggle behavior (placeholder)
document.addEventListener('click', function (e) {
    var fav = e.target.closest('.fav-btn');
    if (!fav) return;
    e.preventDefault();
    e.stopPropagation();

    // Try to get file id from button dataset or from closest .file-item
    var fileId = fav.dataset.fileId || (fav.closest('.file-item') && fav.closest('.file-item').dataset.fileId) || null;
    if (!fileId) {
        console.warn('favorite.js: file id not found for fav button');
        toastError('Favorit gagal', 'File ID tidak ditemukan');
        return;
    }

    // POST JSON body expected by favorite.php
    var url = (window.BASE_URL || '') + '/favorite.php';
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ file_id: fileId })
    }).then(function (res) {
        return res.json();
    }).then(function (j) {
        if (j && j.success) {
            // Update UI: toggle active class on button and favorited class on item
            fav.classList.toggle('active', j.is_favorite == 1);
            fav.dataset.favorite = j.is_favorite == 1 ? '1' : '0';
            var item = fav.closest('.file-item');
            if (item) {
                item.classList.toggle('favorited', j.is_favorite == 1);
                if (j.is_favorite == 1) {
                    // move to top for visibility
                    var grid = document.getElementById('file-grid');
                    if (grid && grid.dataset.page !== 'favorites') grid.insertBefore(item, grid.firstChild);
                } else {
                    // if on favorites page, remove item
                    var grid = document.getElementById('file-grid');
                    if (grid && grid.dataset.page === 'favorites') item.remove();
                }
            }
            if (j.counts) {
                // update counts if provided
                var t = document.getElementById('total-files-count');
                var f = document.getElementById('favorite-files-count');
                var tr = document.getElementById('trash-files-count');
                if (t && typeof j.counts.total !== 'undefined') t.textContent = j.counts.total + ' File';
                if (f && typeof j.counts.favorites !== 'undefined') f.textContent = j.counts.favorites + ' Favorit';
                if (tr && typeof j.counts.trash !== 'undefined') tr.textContent = j.counts.trash + ' Sampah';
            }
            toastSuccess('Berhasil', j.is_favorite == 1 ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit');
        } else {
            toastError('Favorit gagal', (j && j.message) ? j.message : 'Terjadi kesalahan');
        }
    }).catch(function (err) {
        console.error('favorite.js error', err);
        toastError('Network', err.message || 'Terjadi kesalahan jaringan');
    });
});
