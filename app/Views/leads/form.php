<?php
$isEdit = (bool)$lead;
$act    = $isEdit ? url('/leads/'.$lead['id']) : url('/leads');
$selDistrict = $lead['district'] ?? old('district') ?? '';
$selTehsil   = $lead['tehsil']   ?? old('tehsil')   ?? '';
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
      <h3 style="font-size:15px;margin-bottom:14px">Location</h3>
      <div class="form-grid form-grid--4">

        <div class="field">
          <label>State</label>
          <input class="input" name="state" value="<?= e($lead['state']??'Madhya Pradesh') ?>" placeholder="State">
        </div>

        <div class="field">
          <label>District</label>
          <div class="select-wrap">
            <select class="select" name="district" id="sel-district">
              <option value="">— select —</option>
              <?php foreach ($districts as $d): ?>
                <option value="<?= e($d) ?>" <?= $selDistrict===$d?'selected':'' ?>><?= e($d) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label>Tehsil</label>
          <div class="select-wrap">
            <select class="select" name="tehsil" id="sel-tehsil">
              <option value="">— select district first —</option>
              <?php if ($selDistrict && isset($tehsilsMap[$selDistrict])): ?>
                <?php foreach ($tehsilsMap[$selDistrict] as $t): ?>
                  <option value="<?= e($t) ?>" <?= $selTehsil===$t?'selected':'' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label>Village</label>
          <input class="input" name="village" id="inp-village" value="<?= e($lead['village']??'') ?>" placeholder="Village name">
        </div>

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
  var tehsilsMap = <?= json_encode($tehsilsMap, JSON_UNESCAPED_UNICODE) ?>;
  var selDistrict = <?= json_encode($selDistrict) ?>;
  var selTehsil   = <?= json_encode($selTehsil) ?>;

  var distEl  = document.getElementById('sel-district');
  var tehsEl  = document.getElementById('sel-tehsil');

  function populateTehsils(district, currentTehsil) {
    tehsEl.innerHTML = '';
    var blank = document.createElement('option');
    blank.value = '';
    blank.textContent = district ? '— select tehsil —' : '— select district first —';
    tehsEl.appendChild(blank);
    var list = tehsilsMap[district] || [];
    list.forEach(function (t) {
      var o = document.createElement('option');
      o.value = t;
      o.textContent = t;
      if (t === currentTehsil) o.selected = true;
      tehsEl.appendChild(o);
    });
  }

  // Init on page load (edit mode)
  if (selDistrict) populateTehsils(selDistrict, selTehsil);

  distEl.addEventListener('change', function () {
    populateTehsils(this.value, '');
  });

  // Highlight cat-chip on checkbox change
  document.querySelectorAll('.cat-chip input[type=checkbox]').forEach(function (cb) {
    cb.addEventListener('change', function () {
      this.closest('.cat-chip').classList.toggle('cat-chip--on', this.checked);
    });
  });
})();
</script>
