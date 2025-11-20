// file-listing.js - shared listing utilities (placeholder)
function renderCategoryLabel(category) {
    if (!category) return '';
    return '<span class="badge bg-secondary ms-1">' + (category.name || category) + '</span>';
}
