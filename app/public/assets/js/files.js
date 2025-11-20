document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('file-grid');
    if(!grid) return;

    const searchInput = document.getElementById('file-search');
    const chips = document.querySelectorAll('.category-chip');
    const viewBtns = document.querySelectorAll('.view-toggle button');
    const sortButtons = document.querySelectorAll('[data-sort]');
    let currentSort = { field: 'name', dir: 'asc' };

    function getItems(){ return Array.from(grid.querySelectorAll('.file-item')); }

    function applySearchAndFilter(){
        const q = (searchInput?.value||'').toLowerCase().trim();
        const activeChip = document.querySelector('.category-chip.active')?.dataset.filter || '';
        getItems().forEach(item=>{
            const name = (item.dataset.name||'').toLowerCase();
            const category = (item.dataset.category||'').toLowerCase();
            let match = true;
            if(q) {
                match = name.includes(q);
            }
            if(activeChip) {
                match = match && category === activeChip;
            }
            if(match) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
        applySort();
    }

    function applySort(){
        const items = getItems().filter(i => !i.classList.contains('hidden'));
        const comp = (a,b)=>{
            const fa = a.dataset[currentSort.field]||'';
            const fb = b.dataset[currentSort.field]||'';
            if(currentSort.field === 'size') {
                return (parseInt(fa)||0) - (parseInt(fb)||0);
            }
            if(currentSort.field === 'date'|| currentSort.field === 'created') {
                return new Date(fa) - new Date(fb);
            }
            return fa.localeCompare(fb);
        };
        items.sort((a,b)=>currentSort.dir === 'asc' ? comp(a,b) : -comp(a,b));
        // append in order
        items.forEach(i => grid.appendChild(i));
    }

    searchInput?.addEventListener('input', applySearchAndFilter);
    chips.forEach(ch=>{
        ch.addEventListener('click', () => {
            chips.forEach(c=>c.classList.remove('active'));
            if (!ch.classList.contains('active')) ch.classList.add('active');
            if(ch.classList.contains('active') && ch.dataset.filter==='') chips.forEach(c=>c.classList.remove('active'));
            applySearchAndFilter();
        });
    });

    viewBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            viewBtns.forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
            grid.classList.toggle('grid-view-mode', btn.dataset.view==='grid');
            grid.classList.toggle('list-view-mode', btn.dataset.view==='list');
        });
    });

    sortButtons.forEach(btn=>{
        btn.addEventListener('click', () => {
            const field = btn.dataset.sort;
            if(currentSort.field === field) currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
            else { currentSort.field = field; currentSort.dir = 'asc'; }
            sortButtons.forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
            applySort();
        });
    });

    // action delegation for more buttons (download, rename, favorite, delete)
    grid.addEventListener('click', (e) => {
        const action = e.target.closest('[data-action]')?.dataset.action;
        const fileId = e.target.closest('.file-item')?.dataset.id;
        if(!action || !fileId) return;
        if(action === 'download') { window.location = `download.php?id=${fileId}`; }
        if(action === 'favorite') {
            fetch('favorite.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId }) })
              .then(r=>r.json()).then(json=> location.reload());
        }
        if(action === 'delete') {
            if(!confirm('Hapus file?')) return;
            fetch('delete.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId, action: 'delete' }) })
              .then(r=>r.json()).then(json=> location.reload());
        }
        if(action === 'rename') {
            const newName = prompt('Nama file baru:');
            if(!newName) return;
            fetch('rename.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ file_id: fileId, new_name: newName }) })
              .then(r=>r.json()).then(json=> location.reload());
        }
    });

    // initial
    applySearchAndFilter();
});