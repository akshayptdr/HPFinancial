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
$bal = $fees - (float)$received;
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

<div class="grid" style="grid-template-columns:1.5fr 1fr">
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
            $opts = $f[3] ?? null; $subs = $f[4] ?? null;
            $v = $val($key);
            $attr = $subs ? 'data-subs="'.implode(',', $subs).'"' : '';
            $hide = $subs && $curSub && !in_array($curSub, $subs, true);
          ?>
            <div class="field jobfield" <?= $attr ?> style="<?= $hide?'display:none':'' ?>">
              <label><?= e($label) ?></label>
              <?php if ($type==='select'): ?>
                <div class="select-wrap"><select class="select" name="<?= e($key) ?>"><option value="">— select —</option>
                  <?php foreach (($opts??[]) as $ov=>$ol): ?><option value="<?= e($ov) ?>" <?= (string)$v===(string)$ov?'selected':'' ?>><?= e($ol) ?></option><?php endforeach; ?>
                </select></div>
              <?php elseif ($type==='textarea'): ?>
                <textarea class="input" name="<?= e($key) ?>" rows="2"><?= e($v) ?></textarea>
              <?php elseif ($type==='date'): ?>
                <input class="input" type="date" name="<?= e($key) ?>" value="<?= e($v) ?>">
              <?php elseif ($type==='number'): ?>
                <input class="input mono" type="number" step="0.01" name="<?= e($key) ?>" value="<?= e($v) ?>">
              <?php elseif ($type==='fy'): ?>
                <input class="input" name="<?= e($key) ?>" value="<?= e($v) ?>" placeholder="2025-26">
              <?php else: ?>
                <input class="input" name="<?= e($key) ?>" value="<?= e($v) ?>">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <div class="field"><label>Due Date</label><input class="input" type="date" name="due_date" value="<?= e($val('due_date')) ?>"></div>
          <div class="field"><label>File Status</label>
            <div class="select-wrap"><select class="select" name="file_status_id"><option value="">— select —</option>
              <?php foreach ($fileStatuses as $fs): ?><option value="<?= $fs['id'] ?>" <?= ($job['file_status_id']??'')==$fs['id']?'selected':'' ?>><?= e($fs['name']) ?></option><?php endforeach; ?></select></div></div>
          <div class="field"><label>Assign To</label>
            <div class="select-wrap"><select class="select" name="assigned_to">
              <?php foreach ($employees as $u): ?><option value="<?= $u['id'] ?>" <?= ($job['assigned_to']??auth_user()['id'])==$u['id']?'selected':'' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div></div>
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
          <div class="divider"></div>
          <div class="field"><label>Insurance Type (select one or more)</label>
            <div class="flex gap-8" style="flex-wrap:wrap">
              <?php foreach (['health'=>'Health','term'=>'Term','vehicle'=>'Vehicle','claim_assist'=>'Claim Assist'] as $k=>$lbl): $on=isset($selItems[$k]); ?>
                <label class="chip-check <?= $on?'on':'' ?>"><input type="checkbox" name="ins_types[]" value="<?= $k ?>" <?= $on?'checked':'' ?>> <?= $lbl ?></label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php elseif ($special==='credentials'): ?>
          <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:16px;margin:8px 0">
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

        <div class="field" style="margin-top:14px"><label>Comment</label><textarea class="input" name="comment" rows="2"><?= e($val('comment')) ?></textarea></div>
        <div class="field"><label>Agreed Fee (₹)</label><input class="input mono" type="number" step="0.01" name="fees_amount" value="<?= e($fees ?: '') ?>"></div>
        <div class="flex between items-center">
          <span class="muted small">Fields adjust to the selected sub-type.</span>
          <div class="flex gap-12"><a href="<?= url('/customers/'.$c['id']) ?>" class="btn btn--light">Cancel</a><button class="btn btn--primary"><?= $isEdit?'Update Job':'Save Job' ?></button></div>
        </div>
      </form>
    </div>
  </div>

  <!-- Fees panel -->
  <div class="card" style="align-self:start">
    <div class="card__head"><h3>Fees &amp; Payments</h3></div>
    <div class="card__body">
      <div class="kpi-row" style="background:var(--n-50);border-radius:10px;padding:14px;margin-bottom:16px">
        <div class="kpi"><div class="v mono"><?= money($fees) ?></div><div class="l">Agreed</div></div>
        <div class="kpi"><div class="v mono" style="color:var(--success)"><?= money($received) ?></div><div class="l">Received</div></div>
        <div class="kpi"><div class="v mono" style="color:<?= $bal>0?'var(--danger)':'var(--success)' ?>"><?= money(max(0,$bal)) ?></div><div class="l">Balance</div></div>
      </div>
      <?php if (!$isEdit): ?>
        <p class="muted small">Save the job first to record payments.</p>
      <?php else: ?>
        <?php if (can('payments.record')): ?>
        <form method="post" action="<?= url('/jobs/'.$job['id'].'/payments') ?>" style="background:var(--n-50);border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:14px">
          <?= csrf_field() ?>
          <div class="flex gap-8" style="margin-bottom:8px">
            <input class="input mono" type="number" step="0.01" name="amount" placeholder="Amount" required>
            <div class="select-wrap"><select class="select" name="payment_mode"><option value="cash">Cash</option><option value="bank">Bank</option></select></div>
          </div>
          <div class="flex gap-8">
            <input class="input" type="date" name="received_date" value="<?= date('Y-m-d') ?>">
            <button class="btn btn--primary btn--sm">Record</button>
          </div>
        </form>
        <?php endif; ?>
        <table class="tbl" style="font-size:13px"><tbody>
          <?php if (!$payments): ?><tr><td class="muted" style="padding:14px;text-align:center">No payments yet.</td></tr>
          <?php else: foreach ($payments as $p): ?>
            <tr><td><div class="cell-strong mono"><?= money($p['amount']) ?></div>
              <div class="cell-sub"><?= e(date('d M', strtotime($p['received_date']))) ?> · by <?= e($p['recorded_name']) ?></div></td>
              <td class="text-right"><span class="pill pill--<?= $p['payment_mode']==='cash'?'green':'blue' ?>"><?= ucfirst($p['payment_mode']) ?></span></td></tr>
          <?php endforeach; endif; ?>
        </tbody></table>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function syncSub(){
  var sub=(document.querySelector('input[name=sub_type]:checked')||{}).value||'';
  document.querySelectorAll('.chip-check input[name=sub_type]').forEach(i=>i.closest('.chip-check').classList.toggle('on',i.checked));
  document.querySelectorAll('.jobfield[data-subs]').forEach(function(el){
    var subs=el.getAttribute('data-subs').split(',');
    el.style.display = (!sub || subs.indexOf(sub)>=0) ? '' : 'none';
  });
}
document.querySelectorAll('.chip-check input[type=checkbox]').forEach(i=>i.addEventListener('change',e=>e.target.closest('.chip-check').classList.toggle('on',e.target.checked)));
syncSub();
</script>
