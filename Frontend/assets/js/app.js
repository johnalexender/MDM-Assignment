// Simple client-side demo app using localStorage
(function(){
  // constants
  const PAGE_SIZE = 5;

  // util
  function uid(prefix='id'){
    return prefix + '_' + Math.random().toString(36).slice(2,9);
  }

  function now(){ return new Date().toISOString(); }

  // storage helpers (namespaced per user)
  function read(key){
    const v = localStorage.getItem(key);
    return v ? JSON.parse(v) : [];
  }
  function write(key, val){ localStorage.setItem(key, JSON.stringify(val)); }

  // users
  function getUsers(){ return read('mdm_users'); }
  function saveUsers(u){ write('mdm_users', u); }

  function findUserByEmail(email){ return getUsers().find(x=> x.email.toLowerCase()===email.toLowerCase()); }

  function setCurrentUser(user){ localStorage.setItem('mdm_current_user', JSON.stringify(user)); }
  function getCurrentUser(){ const v = localStorage.getItem('mdm_current_user'); return v? JSON.parse(v): null; }
  function logout(){ localStorage.removeItem('mdm_current_user'); location.href = 'index.html'; }

  // per-user data keys
  function keyFor(entity){
    const u = getCurrentUser();
    if(!u) return null;
    return `mdm_${entity}_${u.email}`; // users data isolated by email
  }

  // generic CRUD for arrays
  function all(entity){ const k = keyFor(entity); return k ? read(k) : []; }
  function saveAll(entity, arr){ const k = keyFor(entity); if(k) write(k, arr); }

  // bootstrap helpers for modal
  function hideModal(id){ const m = bootstrap.Modal.getInstance(document.getElementById(id)); if(m) m.hide(); }

  // init functions for pages
  window.initAuthUI = function(){
    // seed admin if not present
    if(!getUsers().length){
      saveUsers([{id:uid('u'),first:'Super',last:'Admin',email:'admin@mdm.test',password:'admin123',is_admin:true}]);
    }

    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const alerts = document.getElementById('alerts');

    function showAlert(msg,type='danger'){
      alerts.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
      setTimeout(()=> alerts.innerHTML = '', 3500);
    }

    loginForm.addEventListener('submit', (e)=>{
      e.preventDefault();
      const email = document.getElementById('loginEmail').value.trim();
      const password = document.getElementById('loginPassword').value;
      const user = findUserByEmail(email);
      if(!user || user.password !== password){ showAlert('Invalid credentials'); return; }
      setCurrentUser(user);
      location.href = 'dashboard.html';
    });

    registerForm.addEventListener('submit', (e)=>{
      e.preventDefault();
      const first = document.getElementById('regFirst').value.trim();
      const last = document.getElementById('regLast').value.trim();
      const email = document.getElementById('regEmail').value.trim();
      const password = document.getElementById('regPassword').value;
      const is_admin = document.getElementById('regIsAdmin').checked;
      if(findUserByEmail(email)){ showAlert('User already exists'); return; }
      const users = getUsers();
      users.push({id:uid('u'),first,last,email,password,is_admin:is_admin||false,created: now()});
      saveUsers(users);
      showAlert('Registered — you can now log in', 'success');
      registerForm.reset();
    });
  };

  window.initDashboardUI = function(){
    const user = getCurrentUser(); if(!user) return logout();
    document.getElementById('welcomeUser').textContent = `${user.first} ${user.last}`;
    document.getElementById('userInfo').innerHTML = `<p><strong>${user.email}</strong><br>Admin: ${user.is_admin? 'Yes': 'No'}</p>`;
    document.getElementById('btnLogout').addEventListener('click', logout);
    if(user.is_admin) document.getElementById('adminLink').style.display = 'inline-block';
  };

  // Generic pagination + table rendering helper
  function paginate(arr, page, pageSize){
    const total = arr.length; const pages = Math.max(1, Math.ceil(total / pageSize));
    const p = Math.min(Math.max(1, page||1), pages);
    const start = (p-1)*pageSize;
    return {items: arr.slice(start, start+pageSize), page:p, pages, total};
  }

  // Export CSV simple
  function exportCSV(filename, rows){
    if(!rows.length) return alert('No data to export');
    const cols = Object.keys(rows[0]);
    const csv = [cols.join(',')].concat(rows.map(r => cols.map(c=> '\"'+String(r[c]||'').replace(/\"/g,'\"\"')+'\"').join(','))).join('\\n');
    const blob = new Blob([csv], {type:'text/csv'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = filename; a.click(); URL.revokeObjectURL(url);
  }

  // Brands UI
  window.initBrandsUI = function(){
    const user = getCurrentUser(); if(!user) return logout();
    const listEl = document.getElementById('brandsList');
    const form = document.getElementById('brandForm');
    const inputCode = document.getElementById('brandCode');
    const inputName = document.getElementById('brandName');
    const inputActive = document.getElementById('brandActive');
    const inputId = document.getElementById('brandId');
    const search = document.getElementById('brandSearch');
    const statusFilter = document.getElementById('brandStatusFilter');
    let page = 1;

    function load(){
      let arr = all('brands');
      const q = (search.value||'').trim().toLowerCase();
      if(q) arr = arr.filter(b => b.code.toLowerCase().includes(q) || b.name.toLowerCase().includes(q));
      const status = statusFilter.value;
      if(status !== 'all') arr = arr.filter(b => b.status === status);
      const p = paginate(arr, page, PAGE_SIZE);
      renderTable(p.items, p.page, p.pages, p.total);
    }

    function renderTable(items, curPage, pages, total){
      let html = `<div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead><tbody>`;
      if(!items.length) html += `<tr><td colspan="4" class="text-center">No brands found</td></tr>`;
      items.forEach(b => {
        html += `<tr><td>${b.code}</td><td>${b.name}</td><td>${b.status}</td>
          <td class="table-actions">
            <button class="btn btn-sm btn-primary" data-id="${b.id}" data-act="edit">Edit</button>
            <button class="btn btn-sm btn-danger" data-id="${b.id}" data-act="delete">Delete</button>
          </td></tr>`;
      });
      html += `</tbody></table></div>`;
      // pagination
      html += `<div class="d-flex justify-content-between align-items-center">
        <div>Showing ${items.length} of ${total}</div><nav><ul class="pagination mb-0">`;
      for(let i=1;i<=pages;i++) html += `<li class="page-item ${i===curPage? 'active':''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
      html += `</ul></nav></div>`;
      listEl.innerHTML = html;

      // attach events
      listEl.querySelectorAll('button[data-act]').forEach(btn=> btn.addEventListener('click', (e)=>{
        const id = e.currentTarget.getAttribute('data-id'); const act = e.currentTarget.getAttribute('data-act');
        if(act==='edit') editBrand(id); else confirmDelete('brands', id, 'brand');
      }));
      listEl.querySelectorAll('a.page-link').forEach(a=> a.addEventListener('click', (e)=>{ e.preventDefault(); page = +e.currentTarget.getAttribute('data-page'); load(); }));
    }

    function save(){
      const code = inputCode.value.trim(); const name = inputName.value.trim(); const status = inputActive.checked ? 'Active' : 'Inactive';
      if(!code || !name) return alert('Code and name required');
      const arr = all('brands');
      if(inputId.value){ // update
        const idx = arr.findIndex(x=> x.id===inputId.value);
        if(idx>=0){ arr[idx].code = code; arr[idx].name = name; arr[idx].status = status; arr[idx].updated = now(); }
      } else {
        arr.push({id: uid('b'), code, name, status, created: now()});
      }
      saveAll('brands', arr); hideModal('brandModal'); load();
    }

    form.addEventListener('submit', (e)=>{ e.preventDefault(); save(); });

    function editBrand(id){ const arr = all('brands'); const b = arr.find(x=> x.id===id); if(!b) return; inputId.value = b.id; inputCode.value = b.code; inputName.value = b.name; inputActive.checked = b.status==='Active'; const m = new bootstrap.Modal(document.getElementById('brandModal')); m.show(); }

    function confirmDelete(entity, id, label){ const msgEl = document.getElementById('confirmDeleteMsg'); msgEl.textContent = `Delete this ${label}? This action cannot be undone.`; const btn = document.getElementById('confirmDeleteBtn'); const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal')); modal.show(); btn.onclick = function(){ const arr = all(entity).filter(x=> x.id !== id); saveAll(entity, arr); modal.hide(); load(); } }

    // search/filter events
    search.addEventListener('input', ()=> { page =1; load(); });
    statusFilter.addEventListener('change', ()=> { page=1; load(); });

    document.getElementById('exportBrands').addEventListener('click', ()=>{ exportCSV('brands.csv', all('brands')); });

    load();
  };

  // Categories UI (similar to brands) - reuse code with small changes
  window.initCategoriesUI = function(){
    const listEl = document.getElementById('categoriesList');
    const form = document.getElementById('categoryForm');
    const inputCode = document.getElementById('categoryCode');
    const inputName = document.getElementById('categoryName');
    const inputActive = document.getElementById('categoryActive');
    const inputId = document.getElementById('categoryId');
    const search = document.getElementById('categorySearch');
    const statusFilter = document.getElementById('categoryStatusFilter');
    let page = 1;

    function load(){
      let arr = all('categories');
      const q = (search.value||'').trim().toLowerCase();
      if(q) arr = arr.filter(b => b.code.toLowerCase().includes(q) || b.name.toLowerCase().includes(q));
      const status = statusFilter.value;
      if(status !== 'all') arr = arr.filter(b => b.status === status);
      const p = paginate(arr, page, PAGE_SIZE);
      renderTable(p.items, p.page, p.pages, p.total);
    }

    function renderTable(items, curPage, pages, total){
      let html = `<div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead><tbody>`;
      if(!items.length) html += `<tr><td colspan="4" class="text-center">No categories found</td></tr>`;
      items.forEach(b => {
        html += `<tr><td>${b.code}</td><td>${b.name}</td><td>${b.status}</td>
          <td class="table-actions">
            <button class="btn btn-sm btn-primary" data-id="${b.id}" data-act="edit">Edit</button>
            <button class="btn btn-sm btn-danger" data-id="${b.id}" data-act="delete">Delete</button>
          </td></tr>`;
      });
      html += `</tbody></table></div>`;
      html += `<div class="d-flex justify-content-between align-items-center">
        <div>Showing ${items.length} of ${total}</div><nav><ul class="pagination mb-0">`;
      for(let i=1;i<=pages;i++) html += `<li class="page-item ${i===curPage? 'active':''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
      html += `</ul></nav></div>`;
      listEl.innerHTML = html;
      listEl.querySelectorAll('button[data-act]').forEach(btn=> btn.addEventListener('click', (e)=>{
        const id = e.currentTarget.getAttribute('data-id'); const act = e.currentTarget.getAttribute('data-act');
        if(act==='edit') editCategory(id); else confirmDelete('categories', id, 'category');
      }));
      listEl.querySelectorAll('a.page-link').forEach(a=> a.addEventListener('click', (e)=>{ e.preventDefault(); page = +e.currentTarget.getAttribute('data-page'); load(); }));
    }

    function save(){
      const code = inputCode.value.trim(); const name = inputName.value.trim(); const status = inputActive.checked ? 'Active' : 'Inactive';
      if(!code || !name) return alert('Code and name required');
      const arr = all('categories');
      if(inputId.value){ const idx = arr.findIndex(x=> x.id===inputId.value); if(idx>=0){ arr[idx].code = code; arr[idx].name = name; arr[idx].status = status; arr[idx].updated = now(); } }
      else arr.push({id: uid('c'), code, name, status, created: now()});
      saveAll('categories', arr); hideModal('categoryModal'); load();
    }

    form.addEventListener('submit', (e)=>{ e.preventDefault(); save(); });
    function editCategory(id){ const arr = all('categories'); const b = arr.find(x=> x.id===id); if(!b) return; inputId.value = b.id; inputCode.value = b.code; inputName.value = b.name; inputActive.checked = b.status==='Active'; const m = new bootstrap.Modal(document.getElementById('categoryModal')); m.show(); }
    function confirmDelete(entity, id, label){ const msgEl = document.getElementById('confirmDeleteMsg'); msgEl.textContent = `Delete this ${label}? This action cannot be undone.`; const btn = document.getElementById('confirmDeleteBtn'); const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal')); modal.show(); btn.onclick = function(){ const arr = all(entity).filter(x=> x.id !== id); saveAll(entity, arr); modal.hide(); load(); } }

    search.addEventListener('input', ()=> { page =1; load(); });
    statusFilter.addEventListener('change', ()=> { page=1; load(); });
    document.getElementById('exportCategories').addEventListener('click', ()=>{ exportCSV('categories.csv', all('categories')); });
    load();
  };

  // Items UI
  window.initItemsUI = function(){
    const listEl = document.getElementById('itemsList');
    const form = document.getElementById('itemForm');
    const inputId = document.getElementById('itemId');
    const inputBrand = document.getElementById('itemBrand');
    const inputCategory = document.getElementById('itemCategory');
    const inputCode = document.getElementById('itemCode');
    const inputName = document.getElementById('itemName');
    const inputFile = document.getElementById('itemFile');
    const inputActive = document.getElementById('itemActive');
    const search = document.getElementById('itemSearch');
    const statusFilter = document.getElementById('itemStatusFilter');
    let page = 1;

    function populateSelects(){
      const brands = all('brands');
      const cats = all('categories');
      inputBrand.innerHTML = '<option value="">-- Choose brand --</option>' + brands.map(b=> `<option value="${b.id}">${b.name} (${b.code})</option>`).join('');
      inputCategory.innerHTML = '<option value="">-- Choose category --</option>' + cats.map(c=> `<option value="${c.id}">${c.name} (${c.code})</option>`).join('');
    }

    function load(){
      populateSelects();
      let arr = all('items');
      const q = (search.value||'').trim().toLowerCase();
      if(q) arr = arr.filter(b => b.code.toLowerCase().includes(q) || b.name.toLowerCase().includes(q));
      const status = statusFilter.value; if(status !== 'all') arr = arr.filter(b => b.status === status);
      const p = paginate(arr, page, PAGE_SIZE);
      renderTable(p.items, p.page, p.pages, p.total);
    }

    function renderTable(items, curPage, pages, total){
      let html = `<div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>Code</th><th>Name</th><th>Brand</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead><tbody>`;
      if(!items.length) html += `<tr><td colspan="6" class="text-center">No items found</td></tr>`;
      const brands = all('brands'); const cats = all('categories');
      items.forEach(it => {
        const b = brands.find(x=> x.id===it.brand_id) || {}; const c = cats.find(x=> x.id===it.category_id) || {};
        html += `<tr><td>${it.code}</td><td>${it.name}</td><td>${b.name||'-'}</td><td>${c.name||'-'}</td><td>${it.status}</td>
          <td class="table-actions">
            <button class="btn btn-sm btn-primary" data-id="${it.id}" data-act="edit">Edit</button>
            <button class="btn btn-sm btn-danger" data-id="${it.id}" data-act="delete">Delete</button>
          </td></tr>`;
      });
      html += `</tbody></table></div>`;
      html += `<div class="d-flex justify-content-between align-items-center">
        <div>Showing ${items.length} of ${total}</div><nav><ul class="pagination mb-0">`;
      for(let i=1;i<=pages;i++) html += `<li class="page-item ${i===curPage? 'active':''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
      html += `</ul></nav></div>`;
      listEl.innerHTML = html;
      listEl.querySelectorAll('button[data-act]').forEach(btn=> btn.addEventListener('click', (e)=>{
        const id = e.currentTarget.getAttribute('data-id'); const act = e.currentTarget.getAttribute('data-act');
        if(act==='edit') editItem(id); else confirmDelete('items', id, 'item');
      }));
      listEl.querySelectorAll('a.page-link').forEach(a=> a.addEventListener('click', (e)=>{ e.preventDefault(); page = +e.currentTarget.getAttribute('data-page'); load(); }));
    }

    function save(){
      const brand_id = inputBrand.value; const category_id = inputCategory.value; const code = inputCode.value.trim(); const name = inputName.value.trim(); const status = inputActive.checked? 'Active' : 'Inactive';
      if(!brand_id || !category_id || !code || !name) return alert('All fields required');
      const arr = all('items');
      // file meta
      const fileMeta = inputFile.files && inputFile.files[0] ? {name: inputFile.files[0].name, size: inputFile.files[0].size, type: inputFile.files[0].type} : null;
      if(inputId.value){ const idx = arr.findIndex(x=> x.id===inputId.value); if(idx>=0){ arr[idx] = {...arr[idx], brand_id, category_id, code, name, status, file: fileMeta, updated: now() }; } }
      else arr.push({id: uid('it'), brand_id, category_id, code, name, status, file: fileMeta, created: now()});
      saveAll('items', arr); hideModal('itemModal'); load();
    }

    form.addEventListener('submit', (e)=>{ e.preventDefault(); save(); });

    function editItem(id){ const arr = all('items'); const it = arr.find(x=> x.id===id); if(!it) return; inputId.value = it.id; inputCode.value = it.code; inputName.value = it.name; inputActive.checked = it.status==='Active'; populateSelects(); inputBrand.value = it.brand_id; inputCategory.value = it.category_id; const m = new bootstrap.Modal(document.getElementById('itemModal')); m.show(); }

    function confirmDelete(entity, id, label){ const msgEl = document.getElementById('confirmDeleteMsg'); msgEl.textContent = `Delete this ${label}? This action cannot be undone.`; const btn = document.getElementById('confirmDeleteBtn'); const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal')); modal.show(); btn.onclick = function(){ const arr = all(entity).filter(x=> x.id !== id); saveAll(entity, arr); modal.hide(); load(); } }

    search.addEventListener('input', ()=> { page =1; load(); });
    statusFilter.addEventListener('change', ()=> { page=1; load(); });
    document.getElementById('exportItems').addEventListener('click', ()=>{ exportCSV('items.csv', all('items')); });
    load();
  };

  // Admin UI: view all users and their data
  window.initAdminUI = function(){
    const user = getCurrentUser(); if(!user || !user.is_admin) return logout();
    const area = document.getElementById('adminArea'); 
    const users = getUsers();
    let html = '<div class="accordion" id="usersAcc">';
    users.forEach(u=>{
      const brands = read(`mdm_brands_${u.email}`) || [];
      const categories = read(`mdm_categories_${u.email}`) || [];
      const items = read(`mdm_items_${u.email}`) || [];
      html += `<div class="accordion-item"><h2 class="accordion-header" id="h_${u.id}"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c_${u.id}">${u.first} ${u.last} — ${u.email} ${u.is_admin? '(admin)':''}</button></h2>
        <div id="c_${u.id}" class="accordion-collapse collapse" data-bs-parent="#usersAcc"><div class="accordion-body">
        <h6>Brands (${brands.length})</h6><pre>${JSON.stringify(brands, null, 2)}</pre>
        <h6>Categories (${categories.length})</h6><pre>${JSON.stringify(categories, null, 2)}</pre>
        <h6>Items (${items.length})</h6><pre>${JSON.stringify(items, null, 2)}</pre>
        </div></div></div>`;
    });
    html += '</div>';
    area.innerHTML = html;
  };

})();