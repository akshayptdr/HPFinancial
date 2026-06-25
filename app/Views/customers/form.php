<div class="page-head" style="max-width:900px">
  <div><div class="crumb"><a href="<?= url('/customers') ?>">Customers</a> · New</div><h1>Add New Customer</h1></div>
  <div class="page-head__actions"><a href="<?= url('/customers') ?>" class="btn btn--ghost">Cancel</a></div>
</div>
<div class="card" style="max-width:900px">
  <div class="card__body">
    <form method="post" action="<?= url('/customers') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field"><label>Customer Name <span class="req">*</span></label><input class="input" name="name" value="<?= e(old('name')) ?>" required></div>
        <div class="field"><label>Firm Name</label><input class="input" name="firm_name" value="<?= e(old('firm_name')) ?>"></div>
        <div class="field"><label>Mobile <span class="req">*</span></label><input class="input" name="mobile" value="<?= e(old('mobile')) ?>" required></div>
        <div class="field"><label>Email</label><input class="input" name="email" value="<?= e(old('email')) ?>"></div>
        <div class="field"><label>PAN</label><input class="input" name="pan_number" value="<?= e(old('pan_number')) ?>" placeholder="ABCDE1234F"></div>
        <div class="field"><label>GST Number</label><input class="input" name="gst_number" value="<?= e(old('gst_number')) ?>"></div>
        <div class="field"><label>Aadhaar</label><input class="input" name="aadhaar_number" value="<?= e(old('aadhaar_number')) ?>"></div>
        <div class="field"><label>Assign To</label>
          <div class="select-wrap"><select class="select" name="assigned_to"><option value="">— me —</option>
            <?php foreach ($employees as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select></div></div>
      </div>
      <div class="divider"></div>
      <div class="form-grid form-grid--3">
        <div class="field"><label>State</label><input class="input" name="state" value="<?= e(old('state')) ?>"></div>
        <div class="field"><label>District</label><input class="input" name="district" value="<?= e(old('district')) ?>"></div>
        <div class="field"><label>Tehsil</label><input class="input" name="tehsil" value="<?= e(old('tehsil')) ?>"></div>
        <div class="field"><label>Village</label><input class="input" name="village" value="<?= e(old('village')) ?>"></div>
        <div class="field col-span-2" style="grid-column:span 2"><label>Bank / Passbook details</label><input class="input" name="bank_details" value="<?= e(old('bank_details')) ?>"></div>
      </div>
      <div class="flex between items-center">
        <span class="muted small"><span class="req">*</span> Required</span>
        <div class="flex gap-12"><a href="<?= url('/customers') ?>" class="btn btn--light">Cancel</a><button class="btn btn--primary">Save Customer</button></div>
      </div>
    </form>
  </div>
</div>
