document.addEventListener('DOMContentLoaded', function () {
  const grid = document.getElementById('file-grid');
  if (!grid) return;

  const searchInput = document.getElementById('file-search-input');
  const categoryFilter = document.getElementById('category-filter');
  const gridBtn = document.getElementById('grid-view');
  const listBtn = document.getElementById('list-view');

  function setGridMode() {
    grid.classList.remove('list-view-mode');
    grid.classList.add('grid-view-mode');
    if (gridBtn) gridBtn.classList.add('active');
    if (listBtn) listBtn.classList.remove('active');
    localStorage.setItem('fileViewMode', 'grid');
  }
  function setListMode() {
    grid.classList.add('list-view-mode');
    grid.classList.remove('grid-view-mode');
    if (listBtn) listBtn.classList.add('active');
    if (gridBtn) gridBtn.classList.remove('active');
    localStorage.setItem('fileViewMode', 'list');
  }

  // Initialize view mode from localStorage or default to list
  const mode = localStorage.getItem('fileViewMode') || 'list';
  if (mode === 'grid') setGridMode(); else setListMode();

  if (gridBtn) gridBtn.addEventListener('click', setGridMode);
  if (listBtn) listBtn.addEventListener('click', setListMode);

  function getCategoryFromMime(mime) {
    if (!mime) return 'other';
    if (mime.startsWith('image/')) return 'image';
    if (mime.startsWith('video/')) return 'video';
    if (mime.startsWith('audio/')) return 'audio';
    if (mime.includes('pdf') || mime.includes('word') || mime.includes('sheet') || mime.includes('presentation')) return 'document';
    if (mime.includes('zip') || mime.includes('rar') || mime.includes('7z')) return 'archive';
    return 'other';
  }

  function filterItems() {
    const q = (searchInput && searchInput.value || '').toLowerCase().trim();
    const cat = (categoryFilter && categoryFilter.value) || '';
    const items = grid.querySelectorAll('.file-item');
    items.forEach(item => {
      const name = (item.dataset.fileName || '').toLowerCase();
      const mime = item.dataset.fileMime || '';
      const itemCat = getCategoryFromMime(mime);
      const matchesSearch = q === '' || name.indexOf(q) !== -1;
      const matchesCat = cat === '' || itemCat === cat;
      item.style.display = (matchesSearch && matchesCat) ? '' : 'none';
    });
  }

  if (searchInput) searchInput.addEventListener('input', filterItems);
  if (categoryFilter) categoryFilter.addEventListener('change', filterItems);

  // Delegate more-button clicks to open global menu (if exists) or emit custom event
  grid.addEventListener('click', function (e) {
    const more = e.target.closest('.file-more');
    if (!more) return;
    const item = more.closest('.file-item');
    if (!item) return;
    const fileId = item.dataset.fileId || '';
    const fileName = item.dataset.fileName || '';
    // If a global menu element exists, position and show it
    const globalMenu = document.getElementById('global-more-menu');
    if (globalMenu) {
      globalMenu.dataset.fileId = fileId;
      globalMenu.dataset.fileName = fileName;
      // position near button
      const rect = more.getBoundingClientRect();
      globalMenu.style.left = rect.right - 8 + 'px';
      globalMenu.style.top = rect.bottom + window.scrollY + 'px';
      globalMenu.style.display = 'block';
      globalMenu.classList.add('show');
      globalMenu.setAttribute('aria-hidden', 'false');
    } else {
      // fallback: open prompt with actions
      const action = prompt('Action for ' + fileName + ': (download|rename|favorite|delete)');
      if (!action) return;
      // simple actions using endpoints
      if (action === 'download') window.location = 'download.php?file_id=' + encodeURIComponent(fileId);
      if (action === 'delete') fetch('delete.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId }) }).then(r=>r.json()).then(j=>{ if (j.success) item.remove(); alert(j.message || 'Deleted'); });
    }
  });

  // Close global menu on outside click
  document.addEventListener('click', function (e) {
    const globalMenu = document.getElementById('global-more-menu');
    if (!globalMenu) return;
    if (!globalMenu.contains(e.target) && !e.target.closest('.file-more')) {
      globalMenu.classList.remove('show');
      globalMenu.style.display = 'none';
      globalMenu.setAttribute('aria-hidden', 'true');
    }
  });
});
