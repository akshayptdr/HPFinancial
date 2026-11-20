<style>
  /* service card headers: tinted band, icon badge and title aligned on one line */
  .card__head.svc-head{background:#eff6ff;border-bottom:1px solid #dbeafe;border-radius:14px 14px 0 0;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px}
  .svc-head h3{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:0;font-size:18px;font-weight:700;line-height:1.2;color:#14306b}
  .svc-head .svc-ico{width:38px;height:38px;border-radius:10px;background:#2563eb;color:#fff;display:inline-flex;align-items:center;justify-content:center;flex:none;box-shadow:0 2px 6px rgba(37,99,235,.25)}
  .svc-head .svc-ico svg{width:21px;height:21px;display:block}
  .svc-head.is-stopped{background:#f1f5f9;border-bottom-color:#e2e8f0}
  .svc-head.is-stopped h3{color:#475569}
  .svc-head.is-stopped .svc-ico{background:#94a3b8;box-shadow:none}
</style>
<div class="page-head">
  <div><div class="crumb"><a href="<?= url('/customers') ?>">Customers</a> · <?= e($c['firm_name'] ?: $c['name']) ?></div>
    <h1 class="flex items-center gap-12"><?= e($c['firm_name'] ?: $c['name']) ?> <?= status_pill($c['status']) ?></h1>
    <div class="muted" style="margin-top:4px"><?= e($c['name']) ?><?= $c['address']?' · '.e($c['address']):'' ?></div></div>
  <div class="page-head__actions">
    <?php if (sees_money() && can('invoices.manage')): ?><a class="btn btn--outline" href="<?= url('/customers/'.$c['id'].'/invoices/new') ?>">Create Invoice</a><?php endif; ?>
    <?php if (sees_money() && can('payments.record')): ?><button class="btn btn--outline" onclick="openPay()">Receive Payment</button><?php endif; ?>
    <?php if (can('customers.edit')): ?><button class="btn btn--primary" onclick="document.getElementById('svcModal').style.display='grid'">Manage Services</button><?php endif; ?>
  </div>
</div>

<?php include APP_PATH . '/Views/families/_box.php'; ?>

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
  <?php if (sees_money()): ?><div class="tab" data-tab="payments" onclick="tab(this)">Payments <span class="tag" style="margin-left:4px"><?= count($history) ?></span></div><?php endif; ?>
  <?php if (sees_money() && can('invoices.manage')): ?><div class="tab" data-tab="invoices" onclick="tab(this)">Invoices <span class="tag" style="margin-left:4px"><?= count($invoices) ?></span></div><?php endif; ?>
  <div class="tab" data-tab="activity" onclick="tab(this)">Activity <span class="tag" style="margin-left:4px"><?= count($activities) ?></span></div>
  <div class="tab" data-tab="profile" onclick="tab(this)">Profile</div>
  <div class="tab" data-tab="documents" onclick="tab(this)">Documents <span class="tag" style="margin-left:4px"><?= count($documents) ?></span></div>
</div>

<?php ob_start(); ?>
<?php $isAdvance = $outstanding < -0.004; ?>
<div class="grid grid--4" style="margin-bottom:18px">
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px"><?= money($billed) ?></div><div class="stat__label">Total Billed</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px;color:var(--success)"><?= money($received) ?></div><div class="stat__label">Received</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px;color:var(--warning-fg)"><?= money($discount) ?></div><div class="stat__label">Discount / Written off</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px;color:<?= $isAdvance ? 'var(--success)' : ($outstanding > 0.004 ? 'var(--danger)' : 'var(--success)') ?>"><?= money(abs($outstanding)) ?></div><div class="stat__label"><?= $isAdvance ? 'Advance (paid ahead)' : 'Outstanding' ?></div></div>
</div>
<?php $statsHtml = ob_get_clean(); if (!sees_money()) $statsHtml = ''; ?>
<!-- ===== Services tab ===== -->
<div data-pane="services">
  <?php echo $statsHtml; ?>

  <?php if (!$assigned): ?>
    <div class="card"><div class="empty" style="padding:48px">
      <p>No services assigned yet.</p>
      <?php if (can('customers.edit')): ?><button class="btn btn--primary" style="margin-top:14px" onclick="document.getElementById('svcModal').style.display='grid'">Assign Services</button><?php endif; ?>
    </div></div>
  <?php endif; ?>

  <?php
    $svcIcons = [   // one simple line icon per service (24x24, drawn with currentColor)
      'income_tax'         => '<path d="M7 3h8l4 4v14H7z"/><path d="M15 3v4h4"/><path d="M10 13h6M10 17h4"/>',
      'gst'                => '<path d="M4 7h16M4 12h16M4 17h10"/><path d="m17 16 2 2 3-4"/>',
      'accounting'         => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 11h2M12 11h2M8 15h2M12 15h2"/>',
      'loan_subsidy'       => '<path d="M3 20h18"/><path d="M5 20V10l7-5 7 5v10"/><path d="M9 20v-6h6v6"/>',
      'mutual_fund'        => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="m7 15 4-4 3 3 5-6"/>',
      'insurance'          => '<path d="M12 3l8 3v6c0 4.5-3.300 7.500-8 9-4.700-1.500-8-4.500-8-9V6z"/><path d="M12 8v5M9.500 10.500h5"/>',
      'certificate'        => '<circle cx="12" cy="9" r="5"/><path d="m9 13-1.500 8L12 18.500 16.500 21 15 13"/>',
      'llp_compliance'     => '<circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.500"/><path d="M3 20c0-3 2.200-5 5-5s5 2 5 5"/><path d="M15 15c2.500-.5 5 1 5 4"/>',
      'company_compliance' => '<rect x="4" y="9" width="16" height="11" rx="1.500"/><path d="M9 9V5h6v4M4 14h16"/>',
    ];
    $svcIconDefault = '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 12h8"/>';
  ?>
  <?php foreach ($services as $s): if (!in_array((int)$s['id'], $assigned, true)) continue; $code = $s['code']; $jobs = $grouped[$code] ?? []; ?>
    <div class="card" style="margin-bottom:18px">
      <?php $stp = $stopped[(int)$s['id']] ?? null; ?>
      <div class="card__head svc-head<?= $stp ? ' is-stopped' : '' ?>">
        <h3><span class="svc-ico"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $svcIcons[$code] ?? $svcIconDefault ?></svg></span><?= e($s['name']) ?> <?php if ($stp): ?><span class="pill pill--gray">Stopped · <?= date_h($stp['stopped_on']) ?></span><?php endif; ?></h3>
        <div class="flex gap-8 items-center">
          <?php if ($stp): ?>
            <?php if (\App\Core\Auth::isAdmin()): ?>
              <form method="post" action="<?= url('/customers/'.$c['id'].'/services/'.$s['id'].'/resume') ?>" style="display:inline" onsubmit="return confirm('Resume <?= e($s['name']) ?>? Automatic jobs will start again from the next period.')">
                <?= csrf_field() ?><button class="btn btn--outline btn--sm">Resume service</button></form>
            <?php endif; ?>
          <?php else: ?>
            <?php if (\App\Core\Auth::isAdmin()): ?><button type="button" class="btn btn--sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5" onclick="openStop(<?= (int)$s['id'] ?>, '<?= e(addslashes($s['name'])) ?>')">Stop service</button><?php endif; ?>
            <?php if (can('services.edit')): ?><a href="<?= url('/customers/'.$c['id'].'/jobs/'.str_replace('_','-',$code).'/create') ?>" class="btn btn--primary btn--sm">+ New Job</a><?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($stp): ?>
        <div class="alert alert--info" style="margin:0;border-radius:0;border-left:0;border-right:0"><b>Stopped.</b> Reason: <?= e($stp['stop_reason']) ?>. No automatic jobs are created and open jobs were marked Discontinued. Any outstanding amount stays on the customer's account.</div>
      <?php endif; ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Sub-type / Detail</th><th>Period / FY</th><th>Due</th><th>Status</th><?php if (sees_money()): ?><th>Fee</th><?php endif; ?><th></th></tr></thead>
          <tbody>
          <?php if (!$jobs): ?><tr><td colspan="6" class="muted" style="padding:18px;text-align:center">No jobs yet.</td></tr>
          <?php else: foreach ($jobs as $j): ?>
            <tr>
              <td class="cell-strong"><?= e($j['sub_type'] ? strtoupper($j['sub_type']) : ($j['title'] ?: $s['name'])) ?><?= e(job_kind_label($j)) ?></td>
              <td class="mono"><?= e($j['period_label'] ?: $j['financial_year'] ?: '—') ?></td>
              <td><?= $j['due_date']?due_pill($j['due_date']):'—' ?><?= !empty($j['due_date_2']) ? '<div style="margin-top:3px">'.due_pill($j['due_date_2']).'</div>' : '' ?></td>
              <td><?= $j['status_name']?status_pill($j['status_name']):'<span class="pill pill--gray">—</span>' ?></td>
              <?php if (sees_money()): ?><td class="mono"><?= money($j['fees_amount']) ?></td><?php endif; ?>
              <td class="text-right" style="white-space:nowrap">
                <a href="<?= url('/jobs/'.$j['id'].'/edit') ?>" class="btn btn--light btn--sm">Open</a>
                <?php if (can('services.edit')): ?>
                  <form method="post" action="<?= url('/jobs/'.$j['id'].'/delete') ?>" style="display:inline" onsubmit="return confirm('Delete this job? This cannot be undone.')">
                    <?= csrf_field() ?>
                    <button class="btn btn--sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5">Delete</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (sees_money()): ?>
<!-- ===== Payments tab ===== -->
<div data-pane="payments" style="display:none" id="paymentsPane">
  <?php echo $statsHtml; ?>
  <div class="flex gap-8" style="margin-bottom:14px;flex-wrap:wrap">
    <?php if (can('payments.record')): ?><button class="btn btn--primary" onclick="openPay()">Receive Payment</button><?php endif; ?>
    <?php if (can('payments.discount')): ?><button class="btn btn--outline" onclick="document.getElementById('discModal').style.display='grid'">Give Discount / Write-off</button><?php endif; ?>
  </div>
  <div class="card">
    <div class="card__head"><h3>Payment &amp; discount history</h3></div>
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th>Date</th><th>Type</th><th>Mode</th><th>Amount</th><th>Comment</th><th>By</th><th></th></tr></thead>
        <tbody>
        <?php if (!$history): ?><tr><td colspan="7" class="muted" style="padding:22px;text-align:center">Nothing recorded yet. Use “Receive Payment” when the customer pays.</td></tr>
        <?php else: foreach ($history as $h): $isDisc = $h['kind'] === 'discount'; ?>
          <tr>
            <td><?= date_h($h['date']) ?></td>
            <td><span class="pill pill--<?= $isDisc ? 'amber' : 'green' ?>"><?= $isDisc ? 'Discount' : 'Payment' ?></span></td>
            <td><?= $isDisc ? '—' : e(pay_mode_label($h['mode'])) ?></td>
            <td class="mono cell-strong" style="color:<?= $isDisc ? 'var(--warning-fg)' : 'var(--success)' ?>"><?= money($h['amount']) ?></td>
            <td class="small"><?= $h['remarks'] ? nl2br(e($h['remarks'])) : '<span class="muted">—</span>' ?></td>
            <td class="cell-sub"><?= e($h['by'] ?: '—') ?></td>
            <td class="text-right">
              <?php if (can('payments.record') && (!$isDisc || can('payments.discount'))): ?>
                <form method="post" action="<?= url($h['source'] === 'ledger' ? '/ledger/'.$h['id'].'/delete' : '/payments/'.$h['id'].'/delete') ?>" style="display:inline" onsubmit="return confirm('Remove this entry? The totals will update.')">
                  <?= csrf_field() ?><button class="btn btn--sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;padding:2px 8px" title="Remove">✕</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php endif; /* payments tab: Admin and Manager only */ ?>

<?php if (sees_money() && can('invoices.manage')): ?>
<!-- ===== Invoices tab ===== -->
<div data-pane="invoices" style="display:none">
  <div class="flex gap-8" style="margin-bottom:14px"><a class="btn btn--primary" href="<?= url('/customers/'.$c['id'].'/invoices/new') ?>">+ Create Invoice</a></div>
  <div class="card">
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th>Bill No</th><th>Date</th><th>Total</th><th></th></tr></thead>
        <tbody>
        <?php if (!$invoices): ?><tr><td colspan="4" class="muted" style="padding:22px;text-align:center">No invoices yet. Click “Create Invoice”, tick the jobs to bill and print it.</td></tr>
        <?php else: foreach ($invoices as $iv): ?>
          <tr>
            <td class="mono cell-strong"><a href="<?= url('/invoices/'.$iv['id']) ?>"><?= e($iv['invoice_no']) ?></a><?= !empty($iv['group_id']) ? ' <span class="pill pill--sky">Group</span>' : '' ?></td>
            <td><?= date_h($iv['invoice_date']) ?></td>
            <td class="mono cell-strong"><?= money($iv['total']) ?></td>
            <td class="text-right" style="white-space:nowrap">
              <a class="btn btn--light btn--sm" href="<?= url('/invoices/'.$iv['id']) ?>">View / Print</a>
              <form method="post" action="<?= url('/invoices/'.$iv['id'].'/delete') ?>" style="display:inline" onsubmit="return confirm('Delete invoice <?= e($iv['invoice_no']) ?>? This cannot be undone.')">
                <?= csrf_field() ?><button class="btn btn--sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5">Delete</button></form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

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
          <div class="field"><label>Contact Person Mobile</label><input class="input" name="contact_person_mobile" value="<?= e($c['contact_person_mobile'] ?? '') ?>"></div>
          <div class="field" style="grid-column:span 2"><label>Address</label>
            <textarea class="input" name="address" rows="2" placeholder="Village, Tehsil, District, State…"><?= e($c['address'] ?? '') ?></textarea></div>
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

<?php if (sees_money() && can('payments.record')): ?>
<!-- Receive payment modal -->
<div class="modal-backdrop" id="payModal" style="display:none">
  <form method="post" action="<?= url('/customers/'.$c['id'].'/payments') ?>" class="modal">
    <?= csrf_field() ?>
    <div class="modal__head"><h3>Receive Payment</h3><button type="button" class="icon-btn" onclick="document.getElementById('payModal').style.display='none'">✕</button></div>
    <div class="modal__body">
      <p class="muted small" style="margin-bottom:12px">Outstanding now: <b class="mono"><?= $isAdvance ? '₹0 (advance '.money(abs($outstanding)).')' : money($outstanding) ?></b> — recorded against the customer's total, not a single job.</p>
      <div class="form-grid">
        <div class="field"><label>Amount received <span class="req">*</span></label><input class="input mono" type="number" step="0.01" min="0.01" name="amount" id="payAmount" required></div>
        <div class="field"><label>Date</label><input class="input" type="date" name="entry_date" value="<?= date('Y-m-d') ?>"></div>
        <div class="field" style="grid-column:span 2"><label>Collected by <span class="req">*</span></label>
          <div class="select-wrap"><select class="select" name="payment_mode" required>
            <?php foreach ($payModes as $mk => $ml): ?><option value="<?= e($mk) ?>"><?= e($ml) ?></option><?php endforeach; ?>
          </select></div></div>
        <div class="field" style="grid-column:span 2"><label>Comment</label><textarea class="input" name="remarks" rows="2" placeholder="e.g. UPI ref, paid by son, part payment…"></textarea></div>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn--light" onclick="document.getElementById('payModal').style.display='none'">Cancel</button>
      <button class="btn btn--primary">Save payment</button>
    </div>
  </form>
</div>
<?php endif; ?>
<?php if (sees_money() && can('payments.discount')): ?>
<!-- Discount / write-off modal -->
<div class="modal-backdrop" id="discModal" style="display:none">
  <form method="post" action="<?= url('/customers/'.$c['id'].'/discounts') ?>" class="modal">
    <?= csrf_field() ?>
    <div class="modal__head"><h3>Discount / Write-off</h3><button type="button" class="icon-btn" onclick="document.getElementById('discModal').style.display='none'">✕</button></div>
    <div class="modal__body">
      <p class="muted small" style="margin-bottom:12px">Reduces the amount owed without any money received. Cannot be more than the outstanding (<b class="mono"><?= money(max(0, $outstanding)) ?></b>).</p>
      <div class="form-grid">
        <div class="field"><label>Discount amount <span class="req">*</span></label><input class="input mono" type="number" step="0.01" min="0.01" name="amount" required></div>
        <div class="field"><label>Date</label><input class="input" type="date" name="entry_date" value="<?= date('Y-m-d') ?>"></div>
        <div class="field" style="grid-column:span 2"><label>Remark <span class="req">*</span> <span class="muted small">(why is this written off?)</span></label><textarea class="input" name="remarks" rows="2" required></textarea></div>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn--light" onclick="document.getElementById('discModal').style.display='none'">Cancel</button>
      <button class="btn btn--primary">Save discount</button>
    </div>
  </form>
</div>
<?php endif; ?>

<?php if (\App\Core\Auth::isAdmin()): ?>
<!-- Stop service modal (admin only) -->
<div class="modal-backdrop" id="stopModal" style="display:none">
  <form method="post" id="stopForm" action="" class="modal">
    <?= csrf_field() ?>
    <div class="modal__head"><h3 id="stopTitle">Stop service</h3><button type="button" class="icon-btn" onclick="document.getElementById('stopModal').style.display='none'">✕</button></div>
    <div class="modal__body">
      <p class="muted small" style="margin-bottom:12px">All <b>open jobs</b> of this service are marked <b>Discontinued</b> and leave the Work Board, and <b>no more jobs are created automatically</b>.
        Completed jobs and history are kept. <b>Outstanding money is not written off</b> — collect it, or use “Give Discount / Write-off” on the Payments tab.</p>
      <div class="form-grid">
        <div class="field"><label>Stopped from</label><input class="input" type="date" name="stopped_on" value="<?= date('Y-m-d') ?>"></div>
        <div class="field" style="grid-column:span 2"><label>Reason <span class="req">*</span></label><textarea class="input" name="reason" rows="2" required placeholder="e.g. Customer left, moved to another CA, business closed…"></textarea></div>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn--light" onclick="document.getElementById('stopModal').style.display='none'">Cancel</button>
      <button class="btn btn--primary" style="background:#dc2626;border-color:#dc2626">Stop service</button>
    </div>
  </form>
</div>
<?php endif; ?>

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
function openStop(serviceId, name){
  document.getElementById('stopForm').action = '<?= url('/customers/'.$c['id'].'/services/') ?>' + serviceId + '/stop';
  document.getElementById('stopTitle').textContent = 'Stop ' + name;
  document.getElementById('stopModal').style.display = 'grid';
}
function openPay(){
  var el = document.querySelector('.tab[data-tab="payments"]'); if (el) tab(el);
  var m = document.getElementById('payModal'); if (m) { m.style.display = 'grid'; var a = document.getElementById('payAmount'); if (a) a.focus(); }
}
// ?pay=1 (e.g. from the group page) opens the payment form straight away
if (/[?&]pay=1\b/.test(location.search)) { window.addEventListener('load', openPay); }
// Auto-open tab from URL hash
(function(){
  var hash = location.hash.replace('#','');
  if(hash){var el=document.querySelector('.tab[data-tab="'+hash+'"]');if(el)tab(el);}
})();
document.querySelectorAll('.chip-check input').forEach(i=>i.addEventListener('change',e=>e.target.closest('.chip-check').classList.toggle('on',e.target.checked)));

</script>
