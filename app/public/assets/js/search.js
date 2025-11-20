// search.js - simple search helper (placeholder)
function bindSearch(inputSelector, callback) {
    var el = document.querySelector(inputSelector);
    if (!el) return;
    el.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            callback && callback(el.value);
        }
    });
}
