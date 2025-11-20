/* Shared File Grid JavaScript Functions */
/* Used by: index.php, favorit.php, sampah.php, semuafile.php */

/**
 * Initialize file grid with view toggle, search, filter, and menu handlers
 * @param {Object} options - Configuration options
 */
function initFileGrid(options = {}) {
    const defaults = {
        gridSelector: '#file-grid',
        gridBtnSelector: '#grid-view',
        listBtnSelector: '#list-view',
        searchSelector: '#file-search-input',
        categorySelector: '#category-filter',
        defaultView: 'grid', // 'grid' or 'list'
        enableSearch: true,
        enableCategoryFilter: true
    };

    const config = { ...defaults, ...options };
    const grid = document.getElementById(config.gridSelector.replace('#', ''));
    const gridBtn = document.getElementById(config.gridBtnSelector.replace('#', ''));
    const listBtn = document.getElementById(config.listBtnSelector.replace('#', ''));
    const searchInput = document.getElementById(config.searchSelector.replace('#', ''));
    const categoryFilter = document.getElementById(config.categorySelector.replace('#', ''));

    if (!grid) return;

    // ============================================
    // VIEW TOGGLE HANDLERS
    // ============================================
    function setGridMode() {
        grid.classList.remove('list-view-mode');
        grid.classList.add('grid-view-mode');
        if (gridBtn) gridBtn.classList.add('active');
        if (listBtn) listBtn.classList.remove('active');
        localStorage.setItem('fileViewMode', 'grid');
        closeAllMenus();
    }

    function setListMode() {
        grid.classList.add('list-view-mode');
        grid.classList.remove('grid-view-mode');
        if (listBtn) listBtn.classList.add('active');
        if (gridBtn) gridBtn.classList.remove('active');
        localStorage.setItem('fileViewMode', 'list');
        closeAllMenus();
    }

    if (gridBtn) gridBtn.addEventListener('click', setGridMode);
    if (listBtn) listBtn.addEventListener('click', setListMode);

    // Initialize with saved view mode or default
    const savedViewMode = localStorage.getItem('fileViewMode') || config.defaultView;
    if (savedViewMode === 'list') {
        setListMode();
    } else {
        setGridMode();
    }

    // ============================================
    // SEARCH & CATEGORY FILTER
    // ============================================
    function getCategory(mimeType) {
        if (!mimeType) return 'other';
        if (mimeType.startsWith('image/')) return 'image';
        if (mimeType.startsWith('video/')) return 'video';
        if (mimeType.startsWith('audio/')) return 'audio';
        if (
            mimeType.includes('word') ||
            mimeType.includes('sheet') ||
            mimeType.includes('presentation') ||
            mimeType.includes('pdf')
        ) {
            return 'document';
        }
        if (
            mimeType.includes('zip') ||
            mimeType.includes('rar') ||
            mimeType.includes('7z') ||
            mimeType.includes('compressed')
        ) {
            return 'archive';
        }
        return 'other';
    }

    function filterItems() {
        const searchValue = config.enableSearch && searchInput ? searchInput.value.toLowerCase().trim() : '';
        const categoryValue = config.enableCategoryFilter && categoryFilter ? categoryFilter.value : '';
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

    if (config.enableSearch && searchInput) {
        searchInput.addEventListener('input', filterItems);
        searchInput.addEventListener('keypress', e => {
            if (e.key === 'Enter') e.preventDefault();
        });
    }

    if (config.enableCategoryFilter && categoryFilter) {
        categoryFilter.addEventListener('change', filterItems);
    }

    // ============================================
    // MORE MENU FUNCTIONS
    // ============================================
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

    function toggleMoreMenu(fileItem) {
        if (!fileItem) return;

        closeAllMenus(fileItem);

        const btn = fileItem.querySelector('.more-btn');
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

        fileItem.classList.add('menu-open');
    }

    function createMenuElement() {
        const menu = document.createElement('div');
        menu.className = 'more-menu';
        menu.role = 'menu';
        menu.innerHTML = `
            <button class="more-item download" title="Download"><i class="fa fa-download"></i> Download</button>
            <button class="more-item rename" title="Ganti nama"><i class="fa fa-pencil-alt"></i> Ganti nama</button>
            <button class="more-item share" title="Bagikan"><i class="fa fa-user-plus"></i> Bagikan</button>
            <button class="more-item favorite-menu" title="Tambahkan ke favorit"><i class="fa fa-star"></i> Favorit</button>
            <button class="more-item delete-menu" title="Hapus"><i class="fa fa-trash"></i> Hapus</button>
        `;
        return menu;
    }

    // ============================================
    // EVENT DELEGATION & MENU ACTIONS
    // ============================================
    function updateCounts(counts) {
        if (!counts) return;
        const totalEl = document.getElementById('total-files-count');
        const favEl = document.getElementById('favorite-files-count');
        const trashEl = document.getElementById('trash-files-count');
        if (totalEl && typeof counts.total !== 'undefined') totalEl.textContent = counts.total + ' File';
        if (favEl && typeof counts.favorites !== 'undefined') favEl.textContent = counts.favorites + ' Favorit';
        if (trashEl && typeof counts.trash !== 'undefined') trashEl.textContent = counts.trash + ' Sampah';
    }

    grid.addEventListener('click', e => {
        // More button toggle
        if (e.target.closest('.more-btn')) {
            e.stopPropagation();
            const item = e.target.closest('.file-item');
            toggleMoreMenu(item);
            return;
        }

        // Favorite button (overlay - grid only)
        if (e.target.closest('.fav-btn')) {
            const item = e.target.closest('.file-item');
            const fileId = item.dataset.fileId;
            toggleFavorite(fileId, item, updateCounts);
            return;
        }

        // Delete button (overlay - grid only)
        if (e.target.closest('.del-btn')) {
            const item = e.target.closest('.file-item');
            const fileId = item.dataset.fileId;
            doDelete(fileId, item, updateCounts, grid.dataset.page);
            return;
        }

        // Menu item clicks
        if (e.target.closest('.more-item')) {
            const action = e.target.closest('.more-item');
            const menu = action.closest('.more-menu');
            handleMenuAction(action, menu, updateCounts, grid.dataset.page);
            closeAllMenus();
            return;
        }
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('.more-btn') && !e.target.closest('.more-menu')) {
            closeAllMenus();
        }
    });

    // ============================================
    // PUBLIC API
    // ============================================
    return {
        closeAllMenus,
        toggleMoreMenu,
        setGridMode,
        setListMode,
        filterItems,
        updateCounts,
        getCategory
    };
}

/**
 * Toggle favorite status for a file
 */
function toggleFavorite(fileId, itemEl, updateCountsCallback) {
    if (!fileId) return;
    fetch('favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ file_id: fileId })
    })
        .then(r => r.json())
        .then(j => {
            if (j.success) {
                if (itemEl) {
                    itemEl.classList.toggle('favorited', j.is_favorite == 1);
                    const favBtn = itemEl.querySelector('.fav-btn');
                    if (favBtn) favBtn.dataset.favorite = j.is_favorite == 1 ? 'true' : 'false';
                }
                if (updateCountsCallback) updateCountsCallback(j.counts);
                const msg = j.is_favorite == 1 ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: msg,
                        position: 'bottom-right',
                        toast: true,
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            } else {
                if (typeof Swal !== 'undefined') {
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
            }
        })
        .catch(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Network error',
                    position: 'bottom-right',
                    toast: true,
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        });
}

/**
 * Delete or restore file
 */
function doDelete(fileId, itemEl, updateCountsCallback, page) {
    if (!fileId) return;

    const fileName = itemEl ? (itemEl.querySelector('.file-name')?.textContent || 'file') : 'file';
    const isTrashPage = page === 'trash';

    const swalConfig = {
        title: isTrashPage ? 'Hapus Permanen?' : 'Hapus File?',
        text: isTrashPage
            ? `File "${fileName}" akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.`
            : `File ini akan dipindahkan ke sampah`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: isTrashPage ? 'Ya, Hapus Permanen' : 'Ya, Hapus',
        confirmButtonColor: isTrashPage ? '#dc3545' : '#d33',
        cancelButtonText: 'Batal',
        position: 'bottom-right'
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire(swalConfig).then(result => {
            if (result.isConfirmed) {
                fetch('delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        file_id: fileId,
                        permanent: isTrashPage ? 1 : 0
                    })
                })
                    .then(r => r.json())
                    .then(j => {
                        if (j && j.success) {
                            if (itemEl) {
                                itemEl.style.opacity = '0';
                                itemEl.style.transform = 'scale(0.95)';
                                setTimeout(() => itemEl.remove(), 300);
                            }
                            if (updateCountsCallback) updateCountsCallback(j.counts || {});
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: isTrashPage ? 'File telah dihapus permanen' : 'File berhasil dihapus',
                                position: 'bottom-right',
                                toast: true,
                                showConfirmButton: false,
                                timer: 3000
                            });
                            setTimeout(() => {
                                const grid = document.getElementById('file-grid');
                                if (grid && grid.querySelectorAll('.file-item:not([style*="display: none"])').length === 0) {
                                    location.reload();
                                }
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
                    })
                    .catch(err => {
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
    }
}

/**
 * Handle menu actions (download, rename, share, etc)
 */
function handleMenuAction(action, menu, updateCountsCallback, page) {
    if (!action || !menu) return;

    const fileId = menu.dataset.fileId;
    const fileName = menu.dataset.fileName || '';
    const fileUrl = menu.dataset.fileUrl || '';
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
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'File berhasil diunduh: ' + fileName,
                position: 'bottom-right',
                toast: true,
                showConfirmButton: false,
                timer: 3000
            });
        }
    } else if (action.classList.contains('rename')) {
        const currentName = fileName || '';
        const newName = prompt('Ganti nama file menjadi:', currentName);
        if (newName !== null && newName.trim() !== '') {
            fetch('rename.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ file_id: fileId, new_name: newName.trim() })
            })
                .then(r => r.json())
                .then(j => {
                    if (j.success) {
                        if (fileItem) {
                            const nameEl = fileItem.querySelector('.file-name');
                            if (nameEl) nameEl.textContent = newName.trim();
                            fileItem.dataset.fileName = newName.trim();
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Nama file berhasil diubah menjadi: ' + (j.new_name || newName.trim()),
                                position: 'bottom-right',
                                toast: true,
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
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
                    }
                })
                .catch(() => {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Network error',
                            position: 'bottom-right',
                            toast: true,
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
        }
    } else if (action.classList.contains('share')) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Info',
                text: 'Fungsi bagikan belum diimplementasikan di demo ini.',
                position: 'bottom-right',
                toast: true,
                showConfirmButton: false,
                timer: 3000
            });
        }
    } else if (action.classList.contains('favorite-menu')) {
        toggleFavorite(fileId, fileItem, updateCountsCallback);
    } else if (action.classList.contains('delete-menu')) {
        doDelete(fileId, fileItem, updateCountsCallback, page);
    } else if (action.classList.contains('restore-btn')) {
        restoreFile(fileId, fileItem, updateCountsCallback);
    }
}

/**
 * Restore file from trash
 */
function restoreFile(fileId, itemEl, updateCountsCallback) {
    if (!fileId) return;

    const fileName = itemEl ? (itemEl.querySelector('.file-name')?.textContent || 'file') : 'file';

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Kembalikan File?',
            text: `Kembalikan file "${fileName}" ke lokasi aslinya?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Kembalikan',
            confirmButtonColor: '#28a745',
            cancelButtonText: 'Batal',
            position: 'bottom-right'
        }).then(result => {
            if (result.isConfirmed) {
                fetch('delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ file_id: fileId, action: 'restore' })
                })
                    .then(r => r.json())
                    .then(j => {
                        if (j && j.success) {
                            if (itemEl) {
                                itemEl.style.opacity = '0';
                                itemEl.style.transform = 'scale(0.95)';
                                setTimeout(() => itemEl.remove(), 300);
                            }
                            if (updateCountsCallback) updateCountsCallback(j.counts || {});
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
                                const grid = document.getElementById('file-grid');
                                if (
                                    grid &&
                                    grid.querySelectorAll('.file-item:not([style*="display: none"])').length === 0
                                ) {
                                    location.reload();
                                }
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
                    })
                    .catch(err => {
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
    }
}
