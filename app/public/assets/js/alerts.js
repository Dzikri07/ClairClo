// SweetAlert2 helper (placeholder)
function toastSuccess(title, html) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: title, html: html, showConfirmButton: false, timer: 2500 });
}

function toastError(title, html) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: title, html: html, showConfirmButton: false, timer: 4000 });
}

function confirmAction(options) {
    return Swal.fire(Object.assign({icon: 'question', showCancelButton: true}, options));
}
