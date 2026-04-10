<!-- ================= EDIT PROFILE MODAL ================= -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editProfileForm" action="includes/update_profile.php" method="post">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Chỉnh sửa thông tin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="editProfileAlert" class="alert d-none"></div>

          <div class="mb-3">
            <label class="form-label">Tên đăng nhập</label>
            <input class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label">Họ và tên</label>
            <input class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
          <button class="btn btn-primary" id="saveProfileBtn">Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= CHANGE PASSWORD MODAL ================= -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="changePasswordForm" action="includes/change_password.php" method="post">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-key me-2"></i>Đổi mật khẩu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="changePasswordAlert" class="alert d-none"></div>

          <input class="form-control mb-2" type="password" name="current_password" placeholder="Mật khẩu hiện tại" required>
          <input class="form-control mb-2" type="password" name="new_password" placeholder="Mật khẩu mới" required>
          <input class="form-control" type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
          <button class="btn btn-success" id="changePasswordBtn">Đổi mật khẩu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

function ajaxForm(form, alertBox, btn, successReload=true) {
    form.addEventListener('submit', e => {
        e.preventDefault();
        alertBox.className = 'alert d-none';
        btn.disabled = true;

        fetch(form.action, { method:'POST', body:new FormData(form) })
        .then(r=>r.json())
        .then(d=>{
            alertBox.classList.remove('d-none');
            alertBox.classList.add(d.success?'alert-success':'alert-danger');
            alertBox.textContent = d.success ? d.message : d.error;
            if(d.success && successReload) setTimeout(()=>location.reload(),800);
        })
        .finally(()=>{
            btn.disabled=false;
        });
    });
}

ajaxForm(
    document.getElementById('editProfileForm'),
    document.getElementById('editProfileAlert'),
    document.getElementById('saveProfileBtn')
);

ajaxForm(
    document.getElementById('changePasswordForm'),
    document.getElementById('changePasswordAlert'),
    document.getElementById('changePasswordBtn'),
    false
);

});
</script>
