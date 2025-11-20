// favorite.js - unify favorite toggle behavior (placeholder)
document.addEventListener('click', function (e) {
    var fav = e.target.closest('.fav-btn');
    if (!fav) return;
    e.preventDefault();
    e.stopPropagation();
    var fileId = fav.dataset.fileId;
    var action = fav.dataset.favorite === '1' ? 'unfavorite' : 'favorite';
    fetch((window.BASE_URL || '') + '/favorite.php', { method: 'POST', body: new URLSearchParams({ id: fileId, action: action }) })
    .then(r => r.json())
    .then(j => {
        if (j.success) {
            fav.classList.toggle('active', j.is_favorite == 1);
            fav.dataset.favorite = j.is_favorite == 1 ? '1' : '0';
        } else {
            toastError('Favorit gagal', j.message || 'Terjadi kesalahan');
        }
    }).catch(err => { toastError('Network', err.message); });
});
