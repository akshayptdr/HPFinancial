<div class="page-head">
  <div><div class="crumb">Administration · Employees</div><h1>Employees</h1></div>
  <div class="page-head__actions">
    <button class="btn btn--primary" onclick="document.getElementById('addEmp').style.display='grid'">+ Add Employee</button>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Name</th><th>Mobile (login)</th><th>Type</th><th>Last Login</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($employees as $emp): ?>
        <tr>
          <td class="flex items-center gap-12"><span class="avatar-sm"><?= e(initials($emp['name'])) ?></span>
            <div><div class="cell-strong"><?= e($emp['name']) ?></div><div class="cell-sub"><?= e($emp['email'] ?: '—') ?></div></div></td>
          <td class="mono"><?= e($emp['mobile']) ?></td>
          <td><span class="pill pill--<?= $emp['role_slug']==='admin'?'blue':'gray' ?>"><?= e($emp['role_name']) ?></span></td>
          <td class="cell-sub"><?= $emp['last_login_at'] ? e(date('d M, H:i', strtotime($emp['last_login_at']))) : 'Never' ?></td>
          <td><?= status_pill($emp['status']) ?></td>
          <td class="text-right">
            <button class="btn btn--light btn--sm" onclick='editEmp(<?= json_encode($emp, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
            <form method="post" action="<?= url('/employees/'.$emp['id'].'/reset') ?>" style="display:inline" onsubmit="return confirm('Reset password for this employee?')"><?= csrf_field() ?><button class="btn btn--light btn--sm">Reset PW</button></form>
            <?php if ((int)$emp['id'] !== (int)auth_user()['id']): ?>
            <form method="post" action="<?= url('/employees/'.$emp['id'].'/status') ?>" style="display:inline"><?= csrf_field() ?><button class="btn btn--light btn--sm"><?= $emp['status']==='active'?'Deactivate':'Activate' ?></button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit modal -->
<div class="modal-backdrop" id="addEmp" style="display:none">
  <div class="modal">
    <form method="post" id="empForm" action="<?= url('/employees') ?>">
      <?= csrf_field() ?>
      <div class="modal__head"><h3 id="empTitle">Add Employee</h3><button type="button" class="icon-btn" onclick="document.getElementById('addEmp').style.display='none'">✕</button></div>
      <div class="modal__body">
        <div class="form-grid">
          <div class="field col-span-2" style="grid-column:span 2"><label>Full Name <span class="req">*</span></label><input class="input" name="name" id="f_name" required></div>
          <div class="field"><label>Mobile (login) <span class="req">*</span></label><input class="input" name="mobile" id="f_mobile" required></div>
          <div class="field"><label>Email</label><input class="input" name="email" id="f_email"></div>
          <div class="field"><label>Employee Type</label>
            <div class="select-wrap"><select class="select" name="type" id="f_type"><option value="employee">Employee</option><option value="admin">Admin</option></select></div></div>
          <div class="field"><label>Status</label>
            <div class="select-wrap"><select class="select" name="status" id="f_status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div></div>
        </div>
        <p class="muted small" id="genNote">A temporary password will be generated and shown after saving. The employee must change it on first login.</p>
      </div>
      <div class="modal__foot">
        <button type="button" class="btn btn--light" onclick="document.getElementById('addEmp').style.display='none'">Cancel</button>
        <button class="btn btn--primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
<script>
function editEmp(e){
  var f=document.getElementById('empForm');
  f.action='<?= url('/employees') ?>/'+e.id+'/update';
  document.getElementById('empTitle').textContent='Edit Employee';
  document.getElementById('f_name').value=e.name;
  document.getElementById('f_mobile').value=e.mobile;
  document.getElementById('f_email').value=e.email||'';
  document.getElementById('f_type').value=(e.role_slug==='admin'?'admin':'employee');
  document.getElementById('genNote').style.display='none';
  document.getElementById('addEmp').style.display='grid';
}
</script>
