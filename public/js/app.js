(function () {
  'use strict';

  function byId(id) { return document.getElementById(id); }
  function all(sel, el) { return (el || document).querySelectorAll(sel); }
  function one(sel, el) { return (el || document).querySelector(sel); }

  // Mobile sidebar toggle
  var sidebarToggle = byId('sidebar-toggle');
  var sidebarBackdrop = byId('sidebar-backdrop');
  var sidebar = byId('sidebar');
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function () {
      document.body.classList.toggle('sidebar-open');
    });
    if (sidebarBackdrop) {
      sidebarBackdrop.addEventListener('click', function () {
        document.body.classList.remove('sidebar-open');
      });
    }
    if (sidebar) {
      all('a', sidebar).forEach(function (link) {
        link.addEventListener('click', function () {
          document.body.classList.remove('sidebar-open');
        });
      });
    }
  }

  // Delete confirmation
  all('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // Line items: add row, remove row, recalc totals
  var form = one('#invoice-form');
  if (form) {
    var tbody = byId('line-items-tbody');
    var addBtn = byId('add-line');
    if (tbody && addBtn) {
      addBtn.addEventListener('click', function () {
        var idx = tbody.querySelectorAll('.line-item-row').length;
        var lab = form.getAttribute('data-label-item-desc') || 'Item / Description';
        var phItem = form.getAttribute('data-placeholder-item') || 'Item name';
        var phDesc = form.getAttribute('data-placeholder-desc') || 'Description';
        var labQty = form.getAttribute('data-label-qty') || 'Qty';
        var labPrice = form.getAttribute('data-label-unit-price') || 'Unit Price';
        var labAmount = form.getAttribute('data-label-amount') || 'Amount';
        var removeTxt = form.getAttribute('data-remove') || 'Remove';
        var tr = document.createElement('tr');
        tr.className = 'line-item-row';
        tr.innerHTML =
          '<td data-label="' + lab.replace(/"/g, '&quot;') + '"><input type="text" name="items[' + idx + '][item_name]" placeholder="' + phItem.replace(/"/g, '&quot;') + '">' +
          '<input type="text" name="items[' + idx + '][description]" placeholder="' + phDesc.replace(/"/g, '&quot;') + '" class="input-desc"></td>' +
          '<td class="col-qty" data-label="' + labQty.replace(/"/g, '&quot;') + '"><input type="number" name="items[' + idx + '][quantity]" min="0" step="0.01" value="1" class="input-qty"></td>' +
          '<td class="col-price" data-label="' + labPrice.replace(/"/g, '&quot;') + '"><input type="number" name="items[' + idx + '][unit_price]" min="0" step="0.01" value="0" class="input-price"></td>' +
          '<td class="col-total" data-label="' + labAmount.replace(/"/g, '&quot;') + '"><span class="line-total">0</span></td>' +
          '<td data-label=""><button type="button" class="btn btn-text btn-sm remove-line">' + removeTxt.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</button></td>';
        tbody.appendChild(tr);
        bindLineRow(tr);
      });
      tbody.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-line')) {
          var row = e.target.closest('tr');
          if (tbody.querySelectorAll('.line-item-row').length > 1) row.remove();
        }
      });
      function bindLineRow(row) {
        var qty = one('.input-qty', row);
        var price = one('.input-price', row);
        var totalSpan = one('.line-total', row);
        function update() {
          var q = parseFloat(qty && qty.value) || 0;
          var p = parseFloat(price && price.value) || 0;
          if (totalSpan) totalSpan.textContent = (q * p).toFixed(2);
        }
        if (qty) qty.addEventListener('input', update);
        if (price) price.addEventListener('input', update);
        update();
      }
      all('.line-item-row', tbody).forEach(bindLineRow);
    }
  }

  // User modal: add / edit
  var openAdd = byId('open-add-user');
  var userModal = byId('user-modal');
  var userForm = byId('user-form');
  var modalTitle = byId('user-modal-title');
  var modalUsername = byId('modal-username');
  var modalEmail = byId('modal-email');
  var modalPassword = byId('modal-password');
  var modalPasswordLabel = byId('modal-password-label');
  var modalPasswordHint = byId('modal-password-hint');
  var modalPasswordGroup = byId('modal-password-group');
  var modalRole = byId('modal-role');
  var userId = byId('user-id');
  var userMethod = byId('user-method');

  function showUserModal(edit) {
    if (!userModal) return;
    var titleAdd = userModal.getAttribute('data-title-add') || 'Add User';
    var titleEdit = userModal.getAttribute('data-title-edit') || 'Edit User';
    var labelPw = userModal.getAttribute('data-label-password') || 'Password';
    var labelNewPw = userModal.getAttribute('data-label-new-password') || 'New password';
    var storeUrl = (userForm && userForm.getAttribute('data-store-url')) || '/users';
    if (modalPassword) modalPassword.value = '';
    if (edit) {
      modalTitle.textContent = titleEdit;
      userForm.action = edit.url || ('/users/' + edit.id);
      if (userMethod) userMethod.value = 'PUT';
      if (userId) userId.value = edit.id;
      if (modalUsername) modalUsername.value = edit.username || '';
      if (modalEmail) modalEmail.value = edit.email || '';
      if (modalPasswordGroup) modalPasswordGroup.style.display = 'block';
      if (modalPasswordLabel) modalPasswordLabel.textContent = labelNewPw;
      if (modalPasswordHint) modalPasswordHint.style.display = 'block';
      if (modalPassword) {
        modalPassword.removeAttribute('disabled');
        modalPassword.removeAttribute('required');
        modalPassword.setAttribute('name', 'password');
      }
      if (modalRole) modalRole.value = edit.role || 'staff';
    } else {
      modalTitle.textContent = titleAdd;
      userForm.action = storeUrl;
      if (userMethod) userMethod.value = 'POST';
      if (userId) userId.value = '';
      if (modalUsername) modalUsername.value = '';
      if (modalEmail) modalEmail.value = '';
      if (modalPasswordGroup) modalPasswordGroup.style.display = 'block';
      if (modalPasswordLabel) modalPasswordLabel.textContent = labelPw;
      if (modalPasswordHint) modalPasswordHint.style.display = 'none';
      if (modalPassword) {
        modalPassword.removeAttribute('disabled');
        modalPassword.setAttribute('required', 'required');
        modalPassword.setAttribute('name', 'password');
      }
      if (modalRole) modalRole.value = 'staff';
    }
    userModal.style.display = 'flex';
  }

  function hideUserModal() {
    if (userModal) userModal.style.display = 'none';
  }

  if (openAdd) openAdd.addEventListener('click', function () { showUserModal(false); });
  all('.edit-user-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      showUserModal({
        id: btn.getAttribute('data-id'),
        url: btn.getAttribute('data-url'),
        username: btn.getAttribute('data-username'),
        email: btn.getAttribute('data-email') || '',
        role: btn.getAttribute('data-role'),
      });
    });
  });
  if (userModal) {
    one('.modal-backdrop', userModal).addEventListener('click', hideUserModal);
    one('.close-modal', userModal).addEventListener('click', hideUserModal);
  }
})();
