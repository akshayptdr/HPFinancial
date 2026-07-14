<?php
$isEdit = (bool)$lead;
$act    = $isEdit ? url('/leads/'.$lead['id']) : url('/leads');
?>
<div class="page-head" style="max-width:960px">
  <div><div class="crumb"><a href="<?= url('/leads') ?>">Leads</a> · <?= $isEdit?'Edit':'New' ?></div>
    <h1><?= $isEdit?'Edit Lead':'Add New Lead' ?></h1></div>
  <div class="page-head__actions"><a href="<?= url('/leads') ?>" class="btn btn--ghost">Cancel</a></div>
</div>

<div class="card" style="max-width:960px">
  <div class="card__body">
    <form method="post" action="<?= $act ?>">
      <?= csrf_field() ?>

      <!-- Basic Info -->
      <div class="form-grid">
        <div class="field">
          <label>Lead Type</label>
          <div class="select-wrap">
            <select class="select" name="lead_type_id">
              <option value="">— select —</option>
              <?php foreach ($types as $t): ?>
                <option value="<?= $t['id'] ?>" <?= ($lead['lead_type_id']??old('lead_type_id'))==$t['id']?'selected':'' ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label>Full Name <span class="req">*</span></label>
          <input class="input" name="name" value="<?= e($lead['name']??old('name')) ?>" required>
        </div>

        <div class="field">
          <label>Mobile Number <span class="req">*</span></label>
          <input class="input" name="mobile" value="<?= e($lead['mobile']??old('mobile')) ?>" required>
        </div>

        <div class="field">
          <label>Contact Person</label>
          <input class="input" name="contact_person" value="<?= e($lead['contact_person']??'') ?>">
        </div>

        <div class="field">
          <label>Follow-up Date</label>
          <input class="input" type="date" name="follow_up_date" value="<?= e($lead['follow_up_date']??'') ?>">
        </div>
      </div>

      <!-- Interested Categories (multi-select checkboxes) -->
      <div class="divider"></div>
      <div class="field">
        <label style="margin-bottom:10px;display:block">Interested Category <span class="muted small">(select all that apply)</span></label>
        <div class="cat-grid">
          <?php foreach ($categories as $cat): ?>
            <label class="cat-chip <?= in_array($cat['id'], $selectedCategories)?'cat-chip--on':'' ?>">
              <input type="checkbox" name="category_ids[]" value="<?= $cat['id'] ?>"
                <?= in_array($cat['id'], $selectedCategories)?'checked':'' ?>>
              <?= e($cat['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Location -->
      <div class="divider"></div>
      <div class="field">
        <label>Address</label>
        <textarea class="input" name="address" rows="2" placeholder="Village, Tehsil, District, State…"><?= e($lead['address'] ?? '') ?></textarea>
      </div>

      <div class="field">
        <label>Referred By</label>
        <input class="input" name="referred_by" value="<?= e($lead['referred_by'] ?? '') ?>" placeholder="Referral person name">
      </div>

      <!-- Notes -->
      <div class="field"><label>Notes</label><textarea class="input" name="notes" rows="3"><?= e($lead['notes']??'') ?></textarea></div>

      <div class="flex between items-center">
        <span class="muted small"><span class="req">*</span> Required</span>
        <div class="flex gap-12">
          <a href="<?= url('/leads') ?>" class="btn btn--light">Cancel</a>
          <button class="btn btn--primary">Save Lead</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  document.querySelectorAll('.cat-chip input[type=checkbox]').forEach(function (cb) {
    cb.addEventListener('change', function () {
      this.closest('.cat-chip').classList.toggle('cat-chip--on', this.checked);
    });
  });
})();
</script>
