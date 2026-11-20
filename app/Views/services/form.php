<?php
$prefill = $prefill ?? [];
$data    = $job && $job['data'] ? json_decode($job['data'], true) : [];
$colKeys = \App\Support\ServiceConfig::columnKeys();
$val = function($key) use ($job, $data, $colKeys, $prefill) {
    if ($job && in_array($key, $colKeys, true)) return $job[$key] ?? '';
    if ($job) return $data[$key] ?? '';
    return $prefill[$key] ?? $data[$key] ?? '';
};
$curSub = $job['sub_type'] ?? $prefill['sub_type'] ?? '';
$isEdit = (bool)$job;
$action = $isEdit ? url('/jobs/'.$job['id']) : url('/customers/'.$c['id'].'/jobs/'.str_replace('_','-',$code));
$fees = (float)($job['fees_amount'] ?? 0);
$special = $cfg['special'] ?? null;
$selItems = [];
foreach ($items as $it) { $selItems[$it['item_type']] = $it; }
?>
<div class="page-head">
  <div><div class="crumb"><a href="<?= url('/customers') ?>">Customers</a> · <a href="<?= url('/customers/'.$c['id']) ?>"><?= e($c['firm_name'] ?: $c['name']) ?></a> · <?= e($cfg['label']) ?></div>
    <h1><?= e($cfg['label']) ?> Job</h1></div>
  <div class="page-head__actions"><a href="<?= url('/customers/'.$c['id']) ?>" class="btn btn--ghost">Back to profile</a></div>
</div>

<div class="shared-box">
  <div class="shared-box__title">Customer (read-only)</div>
  <div class="shared-grid">
    <div><div class="k">Firm Name</div><div class="v"><?= e($c['firm_name'] ?: '—') ?></div></div>
    <div><div class="k">GST Number</div><div class="v mono"><?= e($c['gst_number'] ?: '—') ?></div></div>
    <div><div class="k">PAN</div><div class="v mono"><?= e($c['pan_number'] ?: '—') ?></div></div>
    <div><div class="k">Aadhaar</div><div class="v mono"><?= e($c['aadhaar_number'] ?: '—') ?></div></div>
  </div>
</div>

<div class="grid" style="grid-template-columns:<?= sees_money() ? '1.5fr 1fr' : '1fr' ?>">
  <div class="card">
    <div class="card__head"><h3>Job Details</h3></div>
    <div class="card__body">
      <form method="post" action="<?= $action ?>" id="jobForm">
        <?= csrf_field() ?>
        <?php if (!empty($cfg['subtypes'])): ?>
          <div class="field"><label>Sub-type <span class="req">*</span></label>
            <div class="flex gap-8" style="flex-wrap:wrap">
              <?php foreach ($cfg['subtypes'] as $k=>$lbl): ?>
                <label class="chip-check <?= $curSub===$k?'on':'' ?>"><input type="radio" name="sub_type" value="<?= e($k) ?>" <?= $curSub===$k?'checked':'' ?> onchange="syncSub()"> <?= e($lbl) ?></label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="form-grid">
          <?php foreach ($cfg['fields'] as $f):
            [$key,$label,$type] = [$f[0],$f[1],$f[2]];
            if ($key === 'form8_status_id') continue;   // rendered next to the Form 8 due date
            $opts = $f[3] ?? null; $subs = $f[4] ?? null; $when = $f[6] ?? null;
            $v = $val($key);
            $attr = ($subs ? 'data-subs="'.implode(',', $subs).'"' : '') . ($when ? ' data-when="'.e($when).'"' : '');
            $whenHide = false;
            if ($when) { [$wk, $wv] = explode('=', $when, 2); $whenHide = (string)$val($wk) !== $wv; }
            $hide = $subs && $curSub && !in_array($curSub, $subs, true);
          ?>
            <div class="field jobfield" <?= $attr ?> style="<?= ($hide || $whenHide)?'display:none':'' ?>">
              <label><?= e($label) ?></label>
              <?php if ($type==='select'): ?>
                <div class="select-wrap"><select class="select" name="<?= e($key) ?>"><option value="">— select —</option>
                  <?php foreach (($opts??[]) as $ov=>$ol): ?><option value="<?= e($ov) ?>" <?= (string)$v===(string)$ov?'selected':'' ?>><?= e($ol) ?></option><?php endforeach; ?>
                </select></div>
              <?php elseif ($type==='textarea'): ?>
                <textarea class="input" name="<?= e($key) ?>" rows="2"><?= e($v) ?></textarea>
              <?php elseif ($type==='filestatus'): ?>
                <div class="select-wrap"><select class="select" name="<?= e($key) ?>"><option value="">— select —</option>
                  <?php foreach ($fileStatuses as $fs): ?><option value="<?= (int)$fs['id'] ?>" <?= (string)$v===(string)$fs['id']?'selected':'' ?>><?= e($fs['name']) ?></option><?php endforeach; ?>
                </select></div>
              <?php elseif ($type==='month'): ?>
                <input class="input" type="month" name="<?= e($key) ?>" value="<?= e($v) ?>">
              <?php elseif ($type==='date'): ?>
                <input class="input" type="date" name="<?= e($key) ?>" value="<?= e($v) ?>">
              <?php elseif ($type==='number'): ?>
                <input class="input mono" type="number" step="0.01" name="<?= e($key) ?>" value="<?= e($v) ?>">
              <?php elseif ($type==='fy'):
                $curFyBase = (int)date('n') >= 4 ? (int)date('Y') : (int)date('Y') - 1;
                $defaultFy = $curFyBase.'-'.str_pad(($curFyBase+1)%100,2,'0',STR_PAD_LEFT);
                $selFy = $v ?: $defaultFy;
              ?>
                <div class="select-wrap"><select class="select" name="<?= e($key) ?>">
                  <option value="">— select FY —</option>
                  <?php for($fy=2018;$fy<=2040;$fy++):
                    $fyLabel=$fy.'-'.str_pad(($fy+1)%100,2,'0',STR_PAD_LEFT); ?>
                    <option value="<?= $fyLabel ?>" <?= $selFy===$fyLabel?'selected':'' ?>><?= $fyLabel ?></option>
                  <?php endfor; ?>
                </select></div>
              <?php else: ?>
                <input class="input" name="<?= e($key) ?>" value="<?= e($v) ?>">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <div class="field jobfield" id="f24gBox" data-subs="tds" style="display:none">
            <label>Form 24G Due Date</label>
            <input class="input" value="10th of every month (auto)" readonly style="background:var(--n-50)">
            <div class="muted small" style="margin-top:4px">Monthly job, no fee. Each month's return is added to the Work Board 10 days before its due date (the 10th of the next month), and continues every financial year.</div>
          </div>
          <div class="field"><label><span id="dueLbl">Due Date</span> <span class="req">*</span></label><input class="input" type="date" name="due_date" value="<?= e($val('due_date')) ?>" required></div>
          <?php ob_start(); ?>
          <div class="field jobfield" id="due2Box" data-subs="returns" style="display:none">
            <label id="due2Lbl">GSTR-3B Due Date</label>
            <input class="input" type="date" name="due_date_2" value="<?= e($val('due_date_2')) ?>">
          </div>
          <?php $due2Html = ob_get_clean(); ?>
          <?php if ($code !== 'llp_compliance') echo $due2Html; ?>
          <div class="field"><label>File Status</label>
            <div class="select-wrap"><select class="select" name="file_status_id"><option value="">— select —</option>
              <?php foreach ($fileStatuses as $fs): ?><option value="<?= $fs['id'] ?>" <?= ($job['file_status_id']??'')==$fs['id']?'selected':'' ?>><?= e($fs['name']) ?></option><?php endforeach; ?></select></div></div>
          <?php if ($code === 'llp_compliance'): echo $due2Html; ?>
          <div class="field jobfield" data-subs="annual_filing" style="<?= ($curSub && $curSub !== 'annual_filing') ? 'display:none' : '' ?>">
            <label>Form 8 Status</label>
            <div class="select-wrap"><select class="select" name="form8_status_id"><option value="">— select —</option>
              <?php foreach ($fileStatuses as $fs): ?><option value="<?= (int)$fs['id'] ?>" <?= (string)$val('form8_status_id') === (string)$fs['id'] ? 'selected' : '' ?>><?= e($fs['name']) ?></option><?php endforeach; ?></select></div>
          </div>
          <?php endif; ?>
          <div class="field"><label>Assign To <span class="req">*</span></label>
            <div class="select-wrap"><select class="select" name="assigned_to" required>
              <option value="">— Select a team member —</option>
              <?php foreach ($employees as $u): ?><option value="<?= $u['id'] ?>" <?= (string)($job['assigned_to'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div></div>
        </div>

        <?php if ($special==='investments'): ?>
          <div class="divider"></div><b style="font-size:14px">Investments</b>
          <table class="tbl" style="margin:10px 0"><thead><tr><th>Type</th><th>Target (₹)</th><th>Achieved (₹)</th></tr></thead><tbody>
            <?php foreach (['sip'=>'SIP','lumpsum'=>'Lump Sum','transfer'=>'Transfer'] as $k=>$lbl): $it=$selItems[$k]??null; ?>
              <tr><td><span class="tag"><?= $lbl ?></span><input type="hidden" name="inv_type[]" value="<?= $k ?>"></td>
                <td><input class="input mono" name="inv_target[]" value="<?= e($it['target_amount']??'') ?>" style="height:34px"></td>
                <td><input class="input mono" name="inv_achieved[]" value="<?= e($it['achieved_amount']??'') ?>" style="height:34px"></td></tr>
            <?php endforeach; ?>
          </tbody></table>
        <?php elseif ($special==='insurance_types'): ?>
          <div id="insTypesBox" style="<?= $curSub==='claim' ? 'display:none' : '' ?>">
          <div class="divider"></div>
          <div class="field"><label>Insurance Type (select one or more)</label>
            <div class="flex gap-8" style="flex-wrap:wrap">
              <?php foreach (['health'=>'Health','term'=>'Term','vehicle'=>'Vehicle','other'=>'Other'] as $k=>$lbl): $on=isset($selItems[$k]); ?>
                <label class="chip-check <?= $on?'on':'' ?>"><input type="checkbox" name="ins_types[]" value="<?= $k ?>" <?= $on?'checked':'' ?>> <?= $lbl ?></label>
              <?php endforeach; ?>
            </div>
          </div>
          </div>
        <?php elseif ($special==='credentials'): ?>
          <div id="portal-creds-box" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:16px;margin:8px 0;<?= in_array($curSub,['audit','gst'])?'display:none':'' ?>">
            <div class="flex items-center gap-8" style="margin-bottom:12px"><b style="color:#92400e;font-size:13px">Portal Credentials</b><span class="pill pill--amber" style="margin-left:auto">🔒 Encrypted at rest</span></div>
            <div class="form-grid">
              <div class="field" style="margin-bottom:8px"><label class="small">TRACES ID</label><input class="input" name="traces_id" value="<?= e($creds['traces']['username']??'') ?>"></div>
              <div class="field" style="margin-bottom:8px"><label class="small">TRACES Password</label><input class="input" type="password" name="traces_pw" value="<?= e($creds['traces']['password']??'') ?>"></div>
              <div class="field" style="margin-bottom:8px"><label class="small">IT Username</label><input class="input" name="it_user" value="<?= e($creds['it_portal']['username']??'') ?>"></div>
              <div class="field" style="margin-bottom:8px"><label class="small">IT Password</label><input class="input" type="password" name="it_pw" value="<?= e($creds['it_portal']['password']??'') ?>"></div>
              <div class="field" style="margin-bottom:8px"><label class="small">AIN-24Q ID</label><input class="input" name="ain24q_id" value="<?= e($creds['ain_24q']['username']??'') ?>"></div>
              <div class="field" style="margin-bottom:8px"><label class="small">AIN-24Q Password</label><input class="input" type="password" name="ain24q_pw" value="<?= e($creds['ain_24q']['password']??'') ?>"></div>
              <div class="field" style="margin-bottom:0"><label class="small">AIN-26Q ID</label><input class="input" name="ain26q_id" value="<?= e($creds['ain_26q']['username']??'') ?>"></div>
              <div class="field" style="margin-bottom:0"><label class="small">AIN-26Q Password</label><input class="input" type="password" name="ain26q_pw" value="<?= e($creds['ain_26q']['password']??'') ?>"></div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($code === 'insurance' && $isEdit && !empty($job['completed_on'])): ?>
          <div class="muted small" style="margin-top:12px">Completed on <b><?= e(date('d M Y', strtotime($job['completed_on']))) ?></b> · the renewal job for the next year is added to the Work Board one month before <b><?= e(date('d M Y', strtotime($job['completed_on'].' +1 year'))) ?></b>.</div>
        <?php endif; ?>
        <div class="field" style="margin-top:14px"><label>Comment</label><textarea class="input" name="comment" rows="2"><?= e($val('comment')) ?></textarea></div>
        <?php if (!sees_money()): ?>
        <?php elseif (!in_array($code, ['mutual_fund', 'insurance'], true)): ?>
        <div class="field"><label>Agreed Fee (₹)</label><input class="input mono" type="number" step="0.01" name="fees_amount" value="<?= e($fees ?: '') ?>"></div>
        <?php elseif ($code === 'insurance'): ?>
        <div class="field" id="insFeeBox" style="display:none"><label>Agreed Fee (₹)</label><input class="input mono" type="number" step="0.01" name="fees_amount" value="<?= e($fees ?: '') ?>"></div>
        <?php endif; ?>
        <div class="flex between items-center">
          <span class="muted small">Fields adjust to the selected sub-type.</span>
          <div class="flex gap-12"><a href="<?= url('/customers/'.$c['id']) ?>" class="btn btn--light">Cancel</a><button class="btn btn--primary"><?= $isEdit?'Update Job':'Save Job' ?></button></div>
        </div>
      </form>
    </div>
  </div>

  <!-- Fees panel: payments are collected per customer, not per job (Admin and Manager only) -->
  <?php if (sees_money()): ?>
  <div class="card" style="align-self:start">
    <div class="card__head"><h3>Fee</h3></div>
    <div class="card__body">
      <div class="kpi-row" style="background:var(--n-50);border-radius:10px;padding:14px;margin-bottom:14px">
        <div class="kpi"><div class="v mono"><?= money($fees) ?></div><div class="l">Agreed fee</div></div>
      </div>
      <p class="muted small" style="margin:0">Payments are received on the customer's page against their total outstanding, not on individual jobs.
        <a href="<?= url('/customers/'.$c['id'].'#payments') ?>" style="font-weight:600">Open Payments →</a></p>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
window.syncExtras = [];
function syncSub(){
  var sub=(document.querySelector('input[name=sub_type]:checked')||{}).value||'';
  document.querySelectorAll('.chip-check input[name=sub_type]').forEach(i=>i.closest('.chip-check').classList.toggle('on',i.checked));
  document.querySelectorAll('.jobfield[data-subs]').forEach(function(el){
    var subs=el.getAttribute('data-subs').split(',');
    el.style.display = (!sub || subs.indexOf(sub)>=0) ? '' : 'none';
  });
  // Hide Portal Credentials when Audit is selected (govt_dept)
  var credsBox = document.getElementById('portal-creds-box');
  if (credsBox) credsBox.style.display = (sub === 'audit' || sub === 'gst') ? 'none' : '';
  (window.syncExtras || []).forEach(function(fn){ fn(); });
}
document.querySelectorAll('.chip-check input[type=checkbox]').forEach(i=>i.addEventListener('change',e=>e.target.closest('.chip-check').classList.toggle('on',e.target.checked)));
syncSub();

// GST returns: due dates follow Return Type + Filing Type (editable). The period itself is automatic.
(function(){
  var DAYS = <?= json_encode(\App\Support\ServiceConfig::GST_DUE_DAYS) ?>;
  var DAYSQ = <?= json_encode(\App\Support\ServiceConfig::GST_DUE_DAYS_QUARTERLY) ?>;
  var FIXED = <?= json_encode(\App\Support\ServiceConfig::GST_FIXED_FILING) ?>;
  function el(n){ return document.querySelector('[name=' + n + ']'); }
  var rt = el('return_type'), ft = el('filing_type'), due = el('due_date'), due2 = el('due_date_2');
  var box2 = document.getElementById('due2Box'), lbl = document.getElementById('dueLbl');
  if (!rt || !ft || !due || !due2) { if (box2) box2.style.display = 'none'; return; }
  function sub(){ return (document.querySelector('input[name=sub_type]:checked')||{}).value || ''; }
  function iso(y, m, d){ return y + '-' + ('0' + m).slice(-2) + '-' + ('0' + d).slice(-2); }
  function datesFor(y, m){            // y-m = last month of the period
    var days = (ft.value === 'quarterly' && DAYSQ[rt.value]) || DAYS[rt.value]; if (!days) return null;
    var dy = y, dm = m + 1; if (dm > 12) { dm = 1; dy++; }
    return [iso(dy, dm, days[0]), days[1] ? iso(dy, dm, days[1]) : ''];
  }
  function upcoming(){                // first period whose due date is today or later
    if (!DAYS[rt.value] || !ft.value) return null;
    var t = new Date(); t.setHours(0,0,0,0);
    var today = iso(t.getFullYear(), t.getMonth() + 1, t.getDate());
    var y = t.getFullYear(), m = t.getMonth() + 1 - 4; while (m < 1) { m += 12; y--; }
    for (var i = 0; i < 12; i++) {
      if (!(ft.value === 'quarterly' && [3,6,9,12].indexOf(m) < 0)) {
        var d = datesFor(y, m); if (d && d[0] >= today) return d;
      }
      m++; if (m > 12) { m = 1; y++; }
    }
    return null;
  }
  function fromDue(v){                // period implied by a typed due date: the month before it
    var p = /^(\d{4})-(\d{2})-\d{2}$/.exec(v); if (!p) return null;
    var y = +p[1], m = +p[2] - 1; if (m < 1) { m = 12; y--; }
    if (ft.value === 'quarterly') while ([3,6,9,12].indexOf(m) < 0) { m--; if (m < 1) { m = 12; y--; } }
    return datesFor(y, m);
  }
  // Composite is quarterly only, GSTR-7 is monthly only: hide the other option and select the allowed one
  function enforce(){
    var fixed = FIXED[rt.value] || null;
    Array.prototype.forEach.call(ft.options, function(o){
      if (!o.value) return;
      var off = !!fixed && o.value !== fixed;
      o.disabled = off; o.hidden = off;
    });
    if (fixed && ft.value !== fixed) ft.value = fixed;
  }
  var auto1 = null, auto2 = null;
  function layout(){
    if (sub() !== 'returns') {            // leaving Returns: drop the due dates we auto-filled for it
      if (auto1 && due.value === auto1) due.value = '';
      if (auto2 && due2.value === auto2) due2.value = '';
      auto1 = auto2 = null;
    }
    var combined = sub() === 'returns' && rt.value === 'gstr1_3b';
    box2.style.display = combined ? '' : 'none';
    lbl.textContent = combined ? 'GSTR-1 Due Date' : 'Due Date';
  }
  function apply(){
    enforce();
    layout();
    if (sub() !== 'returns' || !rt.value || !ft.value) return;
    var d;
    if (due.value && due.value !== auto1) d = fromDue(due.value);       // user typed a due date: only fill the 2nd
    else { d = upcoming(); if (d && (!due.value || due.value === auto1)) { due.value = d[0]; auto1 = d[0]; } }
    if (d && rt.value === 'gstr1_3b' && d[1] && (!due2.value || due2.value === auto2)) { due2.value = d[1]; auto2 = d[1]; }
  }
  [rt, ft].forEach(function(x){ x.addEventListener('change', apply); });
  due.addEventListener('change', apply);
  window.syncExtras.push(layout);
  enforce();
  var u = upcoming(); if (u) { if (due.value === u[0]) auto1 = u[0]; if (due2.value === u[1]) auto2 = u[1]; }
  layout();
})();

// LLP Annual Filing: two returns - Form 11 (due 30 May) and Form 8 (due 30 Oct) - each with its own date and status
(function(){
  var fs8 = document.querySelector('select[name=form8_status_id]'), st = document.querySelector('select[name=file_status_id]'),
      due = document.querySelector('input[name=due_date]'), due2 = document.querySelector('input[name=due_date_2]'),
      fy = document.querySelector('select[name=financial_year]'),
      box2 = document.getElementById('due2Box'), lbl = document.getElementById('dueLbl'), lbl2 = document.getElementById('due2Lbl');
  if (!fs8 || !st || !due || !due2 || !fy || !box2) return;
  var stLbl = st.closest('.field') ? st.closest('.field').querySelector('label') : null;
  var stOrig = stLbl ? stLbl.textContent : '', lbl2Orig = lbl2 ? lbl2.textContent : '';
  function sub(){ return (document.querySelector('input[name=sub_type]:checked')||{}).value || ''; }
  function dates(){
    var m = /^(\d{4})-\d{2}$/.exec(fy.value); if (!m) return null;
    var y = +m[1] + 1; return [y + '-05-30', y + '-10-30'];
  }
  var a1 = null, a2 = null;
  function apply(){
    var on = sub() === 'annual_filing';
    box2.style.display = on ? '' : 'none';
    if (lbl2) lbl2.textContent = on ? 'Form 8 Due Date' : lbl2Orig;
    if (lbl) lbl.textContent = on ? 'Form 11 Due Date' : 'Due Date';
    if (stLbl) stLbl.textContent = on ? 'Form 11 Status' : stOrig;
    if (!on) {
      if (a1 && due.value === a1) due.value = '';
      if (a2 && due2.value === a2) due2.value = '';
      a1 = a2 = null; return;
    }
    var d = dates(); if (!d) return;
    if (!due.value || due.value === a1)   { due.value = d[0];  a1 = d[0]; }
    if (!due2.value || due2.value === a2) { due2.value = d[1]; a2 = d[1]; }
  }
  fy.addEventListener('change', apply);
  window.syncExtras.push(apply);
  var d0 = dates(); if (d0) { if (due.value === d0[0]) a1 = d0[0]; if (due2.value === d0[1]) a2 = d0[1]; }
  apply();
})();

// Insurance: tick-boxes only for "Insurance"; Agreed Fee only for a "Claim" of type Third Party
(function(){
  var box = document.getElementById('insTypesBox'), fee = document.getElementById('insFeeBox'),
      ct = document.querySelector('select[name=claim_type]');
  if (!box && !fee) return;
  function run(){
    var sub = (document.querySelector('input[name=sub_type]:checked')||{}).value || '';
    if (box) box.style.display = (sub === 'claim') ? 'none' : '';
    if (fee) fee.style.display = (sub === 'claim' && ct && ct.value === 'third_party') ? '' : 'none';
  }
  window.syncExtras.push(run);
  if (ct) ct.addEventListener('change', run);
  document.querySelectorAll('input[name=sub_type]').forEach(function(i){ i.addEventListener('change', function(){ setTimeout(run, 0); }); });
  run();
})();

// Conditional fields (data-when="field=value"): shown only while that field has that value
(function(){
  function run(){
    document.querySelectorAll('[data-when]').forEach(function(el){
      var p = el.getAttribute('data-when').split('='), c = document.querySelector('[name=' + p[0] + ']');
      el.style.display = (c && c.value === p[1]) ? '' : 'none';
    });
  }
  document.querySelectorAll('select').forEach(function(sl){ sl.addEventListener('change', run); });
  run();
})();

// Internal Audit: monthly / quarterly -> due date is the 10th after the period ends (period + financial year automatic)
(function(){
  var fq = document.querySelector('select[name=filing_type]'), due = document.querySelector('input[name=due_date]'),
      fy = document.querySelector('select[name=financial_year]');
  if (!fq || !due || !fy || !document.querySelector('input[name=sub_type][value=internal_audit]')) return;
  var DAY = <?= (int)\App\Support\ServiceConfig::INTERNAL_AUDIT_DUE_DAY ?>;
  function sub(){ return (document.querySelector('input[name=sub_type]:checked')||{}).value || ''; }
  function iso(y, m, d){ return y + '-' + ('0' + m).slice(-2) + '-' + ('0' + d).slice(-2); }
  function dueFor(y, m){ var dy = y, dm = m + 1; if (dm > 12) { dm = 1; dy++; } return iso(dy, dm, DAY); }
  function periodic(){ return sub() === 'internal_audit' && (fq.value === 'monthly' || fq.value === 'quarterly'); }
  function upcoming(){
    var t = new Date(); t.setHours(0,0,0,0);
    var today = iso(t.getFullYear(), t.getMonth() + 1, t.getDate());
    var y = t.getFullYear(), m = t.getMonth() + 1 - 4; while (m < 1) { m += 12; y--; }
    for (var i = 0; i < 12; i++) {
      if (!(fq.value === 'quarterly' && [3,6,9,12].indexOf(m) < 0)) { var d = dueFor(y, m); if (d >= today) return d; }
      m++; if (m > 12) { m = 1; y++; }
    }
    return null;
  }
  function endFromDue(v){
    var p = /^(\d{4})-(\d{2})-\d{2}$/.exec(v); if (!p) return null;
    var y = +p[1], m = +p[2] - 1; if (m < 1) { m = 12; y--; }
    if (fq.value === 'quarterly') while ([3,6,9,12].indexOf(m) < 0) { m--; if (m < 1) { m = 12; y--; } }
    return [y, m];
  }
  var auto = null;
  function apply(){
    if (periodic()) {
      if (!due.value || due.value === auto) { var d = upcoming(); if (d) { due.value = d; auto = d; } }
      var e = endFromDue(due.value);                                   // financial year follows the period (read-only)
      if (e) { var s0 = e[1] >= 4 ? e[0] : e[0] - 1; fy.value = s0 + '-' + ('0' + ((s0 + 1) % 100)).slice(-2); }
      fy.disabled = true; fy.style.background = 'var(--n-50)';
    } else {
      fy.disabled = false; fy.style.background = '';
      if (auto && due.value === auto) { due.value = ''; auto = null; }
    }
  }
  fq.addEventListener('change', apply); due.addEventListener('change', apply);
  window.syncExtras.push(apply);
  apply();
})();

// Accounting: monthly / quarterly -> due date is the 5th after the period ends (period itself is automatic)
(function(){
  var fq = document.querySelector('select[name=frequency]'), due = document.querySelector('input[name=due_date]'),
      fy = document.querySelector('select[name=financial_year]');
  if (!fq || !due || !fy) return;
  var DAY = <?= (int)\App\Support\ServiceConfig::ACCOUNTING_DUE_DAY ?>;
  function iso(y, m, d){ return y + '-' + ('0' + m).slice(-2) + '-' + ('0' + d).slice(-2); }
  function dueFor(y, m){ var dy = y, dm = m + 1; if (dm > 12) { dm = 1; dy++; } return iso(dy, dm, DAY); }
  function periodic(){ return fq.value === 'monthly' || fq.value === 'quarterly'; }
  function upcoming(){
    var t = new Date(); t.setHours(0,0,0,0);
    var today = iso(t.getFullYear(), t.getMonth() + 1, t.getDate());
    var y = t.getFullYear(), m = t.getMonth() + 1 - 4; while (m < 1) { m += 12; y--; }
    for (var i = 0; i < 12; i++) {
      if (!(fq.value === 'quarterly' && [3,6,9,12].indexOf(m) < 0)) { var d = dueFor(y, m); if (d >= today) return d; }
      m++; if (m > 12) { m = 1; y++; }
    }
    return null;
  }
  function endFromDue(v){             // [year, month] of the last month of the period implied by a due date
    var p = /^(\d{4})-(\d{2})-\d{2}$/.exec(v); if (!p) return null;
    var y = +p[1], m = +p[2] - 1; if (m < 1) { m = 12; y--; }
    if (fq.value === 'quarterly') while ([3,6,9,12].indexOf(m) < 0) { m--; if (m < 1) { m = 12; y--; } }
    return [y, m];
  }
  var auto = null;
  function apply(){
    var wrap = fy.closest('.jobfield');
    if (periodic()) {
      if (!due.value || due.value === auto) { var d = upcoming(); if (d) { due.value = d; auto = d; } }
      var e = endFromDue(due.value);                                   // financial year follows the period (read-only)
      if (e) { var s0 = e[1] >= 4 ? e[0] : e[0] - 1; fy.value = s0 + '-' + ('0' + ((s0 + 1) % 100)).slice(-2); }
      fy.disabled = true; fy.style.background = 'var(--n-50)';
      if (wrap) wrap.title = 'Automatic — follows the period';
    } else {
      fy.disabled = false; fy.style.background = '';
      if (wrap) wrap.title = '';
      if (auto && due.value === auto) { due.value = ''; auto = null; }
    }
  }
  fq.addEventListener('change', apply);
  due.addEventListener('change', apply);
  window.syncExtras.push(apply);
  if (periodic() && due.value === upcoming()) auto = due.value;
  apply();
})();

// Form 24G: show the auto due-date box only when "Form 24G Applicable" = Yes (TDS only)
(function(){
  var sel = document.querySelector('select[name=form_24g]'), box = document.getElementById('f24gBox');
  if (!box) return;
  if (!sel) { box.style.display = 'none'; return; }   // not a TDS-capable form
  function t(){
    var sub=(document.querySelector('input[name=sub_type]:checked')||{}).value||'';
    box.style.display = (sub==='tds' && sel.value==='yes') ? '' : 'none';
  }
  sel.addEventListener('change', t);
  document.querySelectorAll('input[name=sub_type]').forEach(function(i){ i.addEventListener('change', function(){ setTimeout(t,0); }); });
  t();
})();

// TDS: due date follows Financial Year + Quarter (editable; we only overwrite our own auto value)
(function(){
  var MAP = <?= json_encode(\App\Support\ServiceConfig::TDS_DUE) ?>;
  var due = document.querySelector('input[name=due_date]');
  var q = document.querySelector('select[name=quarter]');
  var fy = document.querySelector('select[name=financial_year]');
  if (!due || !q || !fy) return;
  var lastAuto = null;
  function calc(){
    var m = /^(\d{4})-\d{2}$/.exec(fy.value);
    if (!m || !MAP[q.value]) return null;
    return (parseInt(m[1],10) + MAP[q.value][1]) + '-' + MAP[q.value][0];
  }
  function apply(){
    var sub=(document.querySelector('input[name=sub_type]:checked')||{}).value||'';
    var d = calc();
    if (sub !== 'tds' || !d) return;
    if (!due.value || due.value === lastAuto) { due.value = d; lastAuto = d; }
  }
  q.addEventListener('change', apply); fy.addEventListener('change', apply);
  document.querySelectorAll('input[name=sub_type]').forEach(function(i){ i.addEventListener('change', apply); });
  if (due.value && due.value === calc()) lastAuto = due.value;
})();
</script>
