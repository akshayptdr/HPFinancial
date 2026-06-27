<?php $pending = max(0, $billed - $received); ?>
<div class="page-head">
  <div><div class="crumb"><a href="<?= url('/customers') ?>">Customers</a> · <?= e($c['firm_name'] ?: $c['name']) ?></div>
    <h1 class="flex items-center gap-12"><?= e($c['firm_name'] ?: $c['name']) ?> <?= status_pill($c['status']) ?></h1>
    <div class="muted" style="margin-top:4px"><?= e($c['name']) ?><?= $c['district']?' · '.e(trim(($c['district']).', '.($c['state']??''),', ')):'' ?></div></div>
  <div class="page-head__actions">
    <?php if (can('customers.edit')): ?><button class="btn btn--primary" onclick="document.getElementById('svcModal').style.display='grid'">Manage Services</button><?php endif; ?>
  </div>
</div>

<!-- shared capture-once fields -->
<div class="shared-box">
  <div class="shared-box__title">Shared Profile Fields · used across all services (edit on profile only)</div>
  <div class="shared-grid">
    <div><div class="k">Firm Name</div><div class="v"><?= e($c['firm_name'] ?: '—') ?></div></div>
    <div><div class="k">GST Number</div><div class="v mono"><?= e($c['gst_number'] ?: '—') ?></div></div>
    <div><div class="k">PAN</div><div class="v mono"><?= e($c['pan_number'] ?: '—') ?></div></div>
    <div><div class="k">Aadhaar</div><div class="v mono"><?= e($c['aadhaar_number'] ?: '—') ?></div></div>
  </div>
</div>

<div class="tabs" id="activity">
  <div class="tab active" data-tab="services" onclick="tab(this)">Services <span class="tag" style="margin-left:4px"><?= count($assigned) ?></span></div>
  <div class="tab" data-tab="activity" onclick="tab(this)">Activity <span class="tag" style="margin-left:4px"><?= count($activities) ?></span></div>
  <div class="tab" data-tab="profile" onclick="tab(this)">Profile</div>
  <div class="tab" data-tab="documents" onclick="tab(this)">Documents <span class="tag" style="margin-left:4px"><?= count($documents) ?></span></div>
</div>

<!-- ===== Services tab ===== -->
<div data-pane="services">
  <div class="grid grid--4" style="margin-bottom:18px">
    <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px"><?= money($billed) ?></div><div class="stat__label">Total Billed</div></div>
    <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px;color:var(--success)"><?= money($received) ?></div><div class="stat__label">Received</div></div>
    <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px;color:var(--danger)"><?= money($pending) ?></div><div class="stat__label">Outstanding</div></div>
    <div class="stat" style="padding:14px 16px"><div class="stat__val" style="font-size:20px"><?= count($assigned) ?></div><div class="stat__label">Active Services</div></div>
  </div>

  <?php if (!$assigned): ?>
    <div class="card"><div class="empty" style="padding:48px">
      <p>No services assigned yet.</p>
      <?php if (can('customers.edit')): ?><button class="btn btn--primary" style="margin-top:14px" onclick="document.getElementById('svcModal').style.display='grid'">Assign Services</button><?php endif; ?>
    </div></div>
  <?php endif; ?>

  <?php foreach ($services as $s): if (!in_array((int)$s['id'], $assigned, true)) continue; $code = $s['code']; $jobs = $grouped[$code] ?? []; ?>
    <div class="card" style="margin-bottom:18px">
      <div class="card__head">
        <h3><?= e($s['name']) ?></h3>
        <?php if (can('services.edit')): ?><a href="<?= url('/customers/'.$c['id'].'/jobs/'.str_replace('_','-',$code).'/create') ?>" class="btn btn--primary btn--sm">+ New Job</a><?php endif; ?>
      </div>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Sub-type / Detail</th><th>Period / FY</th><th>Due</th><th>Status</th><th>Fee</th><th>Received</th><th>Balance</th><th></th><th></th></tr></thead>
          <tbody>
          <?php if (!$jobs): ?><tr><td colspan="8" class="muted" style="padding:18px;text-align:center">No jobs yet.</td></tr>
          <?php else: foreach ($jobs as $j): $bal = (float)$j['fees_amount'] - (float)$j['received']; ?>
            <tr>
              <td class="cell-strong"><?= e($j['sub_type'] ? strtoupper($j['sub_type']) : ($j['title'] ?: $s['name'])) ?></td>
              <td class="mono"><?= e($j['period_label'] ?: $j['financial_year'] ?: '—') ?></td>
              <td><?= $j['due_date']?due_pill($j['due_date']):'—' ?></td>
              <td><?= $j['status_name']?status_pill($j['status_name']):'<span class="pill pill--gray">—</span>' ?></td>
              <td class="mono"><?= money($j['fees_amount']) ?></td>
              <td class="mono"><?= money($j['received']) ?></td>
              <td class="mono" style="color:<?= $bal>0?'var(--danger)':'var(--success)' ?>"><?= money(max(0,$bal)) ?></td>
              <td class="text-right"><a href="<?= url('/jobs/'.$j['id'].'/edit') ?>" class="btn btn--light btn--sm">Open</a></td>
              <td><?php if (can('services.edit')): ?><a href="<?= url('/jobs/'.$j['id'].'/copy') ?>" class="btn btn--outline btn--sm" title="Copy for next financial year">Renew</a><?php endif; ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- ===== Activity tab ===== -->
<div data-pane="activity" style="display:none">
  <div class="grid" style="grid-template-columns:1fr 1.5fr;max-width:1000px">
    <?php if (can('customers.edit')): ?>
    <div class="card" style="height:fit-content">
      <div class="card__head"><h3>Add Activity</h3></div>
      <div class="card__body">
        <form method="post" action="<?= url('/customers/'.$c['id'].'/activity') ?>">
          <?= csrf_field() ?>
          <div class="field"><label>Type</label>
            <div class="select-wrap"><select class="select" name="type">
              <option value="note">Note</option>
              <option value="call">Call</option>
              <option value="meeting">Meeting</option>
            </select></div>
          </div>
          <div class="field"><label>Description</label>
            <textarea class="input" name="description" rows="3" placeholder="Add a note, log a call or meeting…"></textarea>
          </div>
          <div class="field"><label>Follow-up Date <span class="muted small">(optional)</span></label>
            <input class="input" type="date" name="follow_up_at">
          </div>
          <button class="btn btn--primary w-full">Add Activity</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card__head"><h3>Timeline</h3></div>
      <div class="card__body">
        <?php if (!$activities): ?>
          <div class="muted" style="padding:20px 0;text-align:center">No activity recorded yet.</div>
        <?php else: ?>
        <div class="timeline">
          <?php foreach ($activities as $a): ?>
            <div class="tl-item <?= e($a['type']) ?>">
              <div class="when"><?= e(date('d M Y, H:i', strtotime($a['created_at']))) ?> · <?= e($a['user_name'] ?: 'System') ?></div>
              <div class="what"><?= e(ucfirst($a['type'])) ?><?= $a['follow_up_at'] ? ' · follow-up ' . date_h($a['follow_up_at']) : '' ?></div>
              <?php if ($a['description']): ?><div class="muted small" style="margin-top:3px"><?= nl2br(e($a['description'])) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ===== Profile tab ===== -->
<div data-pane="profile" style="display:none">
  <div class="card" style="max-width:900px">
    <div class="card__head"><h3>Profile</h3></div>
    <div class="card__body">
      <form method="post" action="<?= url('/customers/'.$c['id']) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field"><label>Customer Name <span class="req">*</span></label><input class="input" name="name" value="<?= e($c['name']) ?>" required></div>
          <div class="field"><label>Firm Name</label><input class="input" name="firm_name" value="<?= e($c['firm_name']) ?>"></div>
          <div class="field"><label>Mobile <span class="req">*</span></label><input class="input" name="mobile" value="<?= e($c['mobile']) ?>" required></div>
          <div class="field"><label>Email</label><input class="input" name="email" value="<?= e($c['email']) ?>"></div>
          <div class="field"><label>PAN</label><input class="input" name="pan_number" value="<?= e($c['pan_number']) ?>"></div>
          <div class="field"><label>GST Number</label><input class="input" name="gst_number" value="<?= e($c['gst_number']) ?>"></div>
          <div class="field"><label>Aadhaar</label><input class="input" name="aadhaar_number" value="<?= e($c['aadhaar_number']) ?>"></div>
          <div class="field"><label>Bank / Passbook</label><input class="input" name="bank_details" value="<?= e($c['bank_details']) ?>"></div>
          <div class="field"><label>Contact Person</label><input class="input" name="contact_person" value="<?= e($c['contact_person']) ?>"></div>
          <div class="field"><!-- spacer --></div>
          <div class="field"><label>State</label><input class="input" name="state" value="<?= e($c['state'] ?: 'Madhya Pradesh') ?>" placeholder="State"></div>
          <div class="field"><label>District</label>
            <div class="select-wrap"><select class="select" name="district" id="cust-sel-district">
              <option value="">— select —</option>
              <?php foreach ($mpDistricts as $d): ?><option value="<?= e($d) ?>" <?= ($c['district']===$d)?'selected':'' ?>><?= e($d) ?></option><?php endforeach; ?>
            </select></div></div>
          <div class="field"><label>Tehsil</label>
            <div class="select-wrap"><select class="select" name="tehsil" id="cust-sel-tehsil">
              <option value="">— select district first —</option>
              <?php if (!empty($c['district']) && isset($mpTehsilsMap[$c['district']])): ?>
                <?php foreach ($mpTehsilsMap[$c['district']] as $t): ?><option value="<?= e($t) ?>" <?= ($c['tehsil']===$t)?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?>
              <?php endif; ?>
            </select></div></div>
          <div class="field"><label>Village</label><input class="input" name="village" value="<?= e($c['village']) ?>" placeholder="Village name"></div>
        </div>
        <div class="text-right"><button class="btn btn--primary" <?= can('customers.edit')?'':'disabled' ?>>Save Profile</button></div>
      </form>
    </div>
  </div>
</div>

<!-- ===== Documents tab ===== -->
<div data-pane="documents" style="display:none">
  <div class="grid" style="grid-template-columns:1fr 1.4fr;max-width:1000px">
    <div class="card">
      <div class="card__head"><h3>Add Document</h3></div>
      <div class="card__body">
        <form method="post" action="<?= url('/customers/'.$c['id'].'/documents') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="field"><label>Type</label>
            <div class="select-wrap"><select class="select" name="doc_type">
              <option value="pan">PAN</option><option value="aadhaar">Aadhaar</option>
              <option value="bank_passbook">Bank Passbook</option><option value="other">Other</option></select></div></div>
          <div class="field"><label>Number / Text value</label><input class="input" name="text_value" placeholder="e.g. document number"></div>
          <div class="field"><label>File (pdf/jpg/png)</label><input class="input" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png"></div>
          <button class="btn btn--primary w-full" <?= can('customers.edit')?'':'disabled' ?>>Upload</button>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card__head"><h3>Documents</h3></div>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Type</th><th>Value</th><th>File</th><th>Uploaded</th></tr></thead>
          <tbody>
          <?php if (!$documents): ?><tr><td colspan="4" class="muted" style="padding:18px;text-align:center">No documents.</td></tr>
          <?php else: foreach ($documents as $d): ?>
            <tr><td><span class="tag"><?= e(ucwords(str_replace('_',' ',$d['doc_type']))) ?></span></td>
              <td class="mono"><?= e($d['text_value'] ?: '—') ?></td>
              <td><?= $d['file_path']?'<a href="'.url('/documents/'.$d['id']).'" target="_blank">View</a>':'—' ?></td>
              <td class="cell-sub"><?= e(date('d M Y', strtotime($d['uploaded_at']))) ?></td></tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Manage services modal -->
<div class="modal-backdrop" id="svcModal" style="display:none">
  <form method="post" action="<?= url('/customers/'.$c['id'].'/services') ?>" class="modal">
    <?= csrf_field() ?>
    <div class="modal__head"><h3>Manage Services</h3><button type="button" class="icon-btn" onclick="document.getElementById('svcModal').style.display='none'">✕</button></div>
    <div class="modal__body">
      <p class="muted small" style="margin-bottom:14px">Selected services appear as sections on the profile.</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <?php foreach ($services as $s): $on = in_array((int)$s['id'],$assigned,true); ?>
          <label class="chip-check <?= $on?'on':'' ?>"><input type="checkbox" name="services[]" value="<?= $s['id'] ?>" <?= $on?'checked':'' ?>> <?= e($s['name']) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn--light" onclick="document.getElementById('svcModal').style.display='none'">Cancel</button>
      <button class="btn btn--primary">Save</button>
    </div>
  </form>
</div>

<script>
function tab(el){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('[data-pane]').forEach(p=>p.style.display='none');
  document.querySelector('[data-pane="'+el.dataset.tab+'"]').style.display='';
}
// Auto-open tab from URL hash
(function(){
  var hash = location.hash.replace('#','');
  if(hash){var el=document.querySelector('.tab[data-tab="'+hash+'"]');if(el)tab(el);}
})();
document.querySelectorAll('.chip-check input').forEach(i=>i.addEventListener('change',e=>e.target.closest('.chip-check').classList.toggle('on',e.target.checked)));

// Cascading District → Tehsil for customer profile
(function(){
  var tehsilsMap = <?= json_encode($mpTehsilsMap, JSON_UNESCAPED_UNICODE) ?>;
  var distEl = document.getElementById('cust-sel-district');
  var tehsEl = document.getElementById('cust-sel-tehsil');
  if (!distEl || !tehsEl) return;
  var currentTehsil = <?= json_encode($c['tehsil'] ?? '') ?>;

  function populateTehsils(district, sel) {
    tehsEl.innerHTML = '';
    var blank = document.createElement('option');
    blank.value = '';
    blank.textContent = district ? '— select tehsil —' : '— select district first —';
    tehsEl.appendChild(blank);
    (tehsilsMap[district] || []).forEach(function(t){
      var o = document.createElement('option');
      o.value = t; o.textContent = t;
      if (t === sel) o.selected = true;
      tehsEl.appendChild(o);
    });
  }

  if (distEl.value) populateTehsils(distEl.value, currentTehsil);
  distEl.addEventListener('change', function(){ populateTehsils(this.value, ''); });
})();
</script>
