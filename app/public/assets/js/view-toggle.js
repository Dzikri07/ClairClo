// view-toggle.js - unified view toggle (placeholder)
document.addEventListener('click', function (e) {
    var t = e.target.closest('.toggle-btn');
    if (!t) return;
    var mode = t.dataset.view;
    var grid = document.getElementById('file-grid');
    if (!grid) return;
    grid.classList.toggle('grid-view-mode', mode === 'grid');
    grid.classList.toggle('list-view-mode', mode === 'list');
});
