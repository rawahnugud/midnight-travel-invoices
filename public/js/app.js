(function () {
  'use strict';

  function byId(id) { return document.getElementById(id); }
  function all(sel, el) { return (el || document).querySelectorAll(sel); }
  function one(sel, el) { return (el || document).querySelector(sel); }

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
        var tr = document.createElement('tr');
        tr.className = 'line-item-row';
        tr.innerHTML =
          '<td><input type="text" name="items[' + idx + '][item_name]" placeholder="Item name">' +
          '<input type="text" name="items[' + idx + '][description]" placeholder="Description" class="input-desc"></td>' +
          '<td class="col-qty"><input type="number" name="items[' + idx + '][quantity]" min="0" step="0.01" value="1" class="input-qty"></td>' +
          '<td class="col-price"><input type="number" name="items[' + idx + '][unit_price]" min="0" step="0.01" value="0" class="input-price"></td>' +
          '<td class="col-total"><span class="line-total">0</span></td>' +
          '<td><button type="button" class="btn btn-text btn-sm remove-line">Remove</button></td>';
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
  var modalNewPassword = byId('modal-new-password');
  var modalPasswordGroup = byId('modal-password-group');
  var modalNewPasswordGroup = byId('modal-new-password-group');
  var modalRole = byId('modal-role');
  var userId = byId('user-id');
  var userMethod = byId('user-method');

  function showUserModal(edit) {
    if (!userModal) return;
    if (edit) {
      modalTitle.textContent = 'Edit User';
      userForm.action = '/users/' + edit.id;
      if (userMethod) userMethod.value = 'PUT';
      if (userId) userId.value = edit.id;
      if (modalUsername) modalUsername.value = edit.username || '';
      if (modalEmail) modalEmail.value = edit.email || '';
      if (modalPasswordGroup) modalPasswordGroup.style.display = 'none';
      if (modalNewPasswordGroup) modalNewPasswordGroup.style.display = 'block';
      if (modalPassword) modalPassword.removeAttribute('required');
      if (modalRole) modalRole.value = edit.role || 'staff';
    } else {
      modalTitle.textContent = 'Add User';
      userForm.action = '/users';
      if (userMethod) userMethod.value = 'POST';
      if (userId) userId.value = '';
      if (modalUsername) modalUsername.value = '';
      if (modalEmail) modalEmail.value = '';
      if (modalPasswordGroup) modalPasswordGroup.style.display = 'block';
      if (modalNewPasswordGroup) modalNewPasswordGroup.style.display = 'none';
      if (modalPassword) modalPassword.setAttribute('required', 'required');
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
