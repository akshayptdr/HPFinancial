<?php
$canPay = sees_money() && can('payments.record');
$canEdit = can('customers.edit');
$payerId = (int)($f['payer_customer_id'] ?? 0);
$contactKey = ($f['contact_type'] && $f['contact_id']) ? $f['contact_type'].':'.$f['contact_id'] : '';
?>
<div class="page-head">
  <div><div class="crumb"><a href="<?= url('/families') ?>">Groups</a> · <?= e($f['name']) ?></div>
    <h1><?= e($f['name']) ?></h1>
    <div class="muted" style="margin-top:4px">
      Contact: <b><?= $contact ? e($contact['name']).' ('.e($contact['mobile']).')' : '—' ?></b>
      · Usually pays: <b><?php $pn='—'; foreach ($customers as $c) if ((int)$c['id']===$payerId) $pn=$c['name']; echo e($pn); ?></b>
    </div></div>
  <div class="page-head__actions">
    <?php if (can('invoices.manage')): ?><a class="btn btn--primary" href="<?= url('/families/'.$f['id'].'/invoices/new') ?>">Create group invoice</a><?php endif; ?>
    <?php if ($canEdit): ?><button class="btn btn--light" onclick="document.getElementById('roleModal').style.display='grid'">Edit group &amp; roles</button><?php endif; ?>
  </div>
</div>

<?php if (sees_money()): ?>
<div class="grid grid--4" style="margin-bottom:12px">
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px"><?= money($summary['billed']) ?></div><div class="stat__label">Total Billed</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px;color:var(--success)"><?= money($summary['received']) ?></div><div class="stat__label">Received</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px;color:var(--warning-fg)"><?= money($summary['discount']) ?></div><div class="stat__label">Discount / Written off</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:20px;color:var(--danger)"><?= money($summary['outstanding']) ?></div><div class="stat__label">To collect from group</div></div>
</div>
<p class="muted small" style="margin:0 0 18px">Payments and discounts are recorded on <b>each member's own page</b>. This page only adds them up.
  <?php if ($summary['advance'] > 0.004): ?> Members who paid ahead hold <b class="mono"><?= money($summary['advance']) ?></b> in advance (not netted against others).<?php endif; ?></p>
<?php endif; ?>

<!-- Members -->
<div class="card">
  <div class="card__head"><h3>Members</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Name</th><th>Type</th><th>Mobile</th><th>Roles</th><?php if (sees_money()): ?><th>Billed</th><th>Received</th><th>Discount</th><th>Outstanding</th><?php endif; ?><th></th></tr></thead>
      <tbody>
      <?php foreach ($customers as $c): $out = (float)$c['outstanding']; ?>
        <tr>
          <td class="cell-strong"><a href="<?= url('/customers/'.$c['id'].'#payments') ?>"><?= e($c['name']) ?></a></td>
          <td><span class="pill pill--blue">Customer</span></td>
          <td class="mono"><?= e($c['mobile']) ?></td>
          <td>
            <?php if ($contactKey === 'customer:'.$c['id']): ?><span class="pill pill--sky">Contact</span> <?php endif; ?>
            <?php if ($payerId === (int)$c['id']): ?><span class="pill pill--green">Payer</span><?php endif; ?>
          </td>
          <?php if (sees_money()): ?>
          <td class="mono"><?= money($c['billed']) ?></td>
          <td class="mono"><?= money($c['received']) ?></td>
          <td class="mono"><?= $c['discount'] > 0 ? money($c['discount']) : '—' ?></td>
          <td class="mono" style="color:<?= $out>0.004?'var(--danger)':'var(--success)' ?>"><?= $out < -0.004 ? money(abs($out)).' <span class="muted small">advance</span>' : money($out) ?></td>
          <?php endif; ?>
          <td class="text-right" style="white-space:nowrap">
            <?php if ($canPay): ?><a class="btn btn--primary btn--sm" href="<?= url('/customers/'.$c['id'].'?pay=1#payments') ?>">Receive Payment</a><?php endif; ?>
            <?php if ($canEdit): ?>
            <form method="post" action="<?= url('/families/'.$f['id'].'/remove-member') ?>" style="display:inline" onsubmit="return confirm('Remove from this group? Their payments stay with them.')">
              <?= csrf_field() ?><input type="hidden" name="member_type" value="customer"><input type="hidden" name="member_id" value="<?= (int)$c['id'] ?>">
              <button class="btn btn--sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5">Remove</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php foreach ($leads as $l): ?>
        <tr>
          <td class="cell-strong"><a href="<?= url('/leads/'.$l['id']) ?>"><?= e($l['name']) ?></a></td>
          <td><span class="pill pill--amber">Lead</span></td>
          <td class="mono"><?= e($l['mobile']) ?></td>
          <td><?php if ($contactKey === 'lead:'.$l['id']): ?><span class="pill pill--sky">Contact</span><?php endif; ?></td>
          <?php if (sees_money()): ?><td class="muted">—</td><td class="muted">—</td><td class="muted">—</td><td class="muted">—</td><?php endif; ?>
          <td class="text-right"><?php if ($canEdit): ?>
            <form method="post" action="<?= url('/families/'.$f['id'].'/remove-member') ?>" style="display:inline" onsubmit="return confirm('Remove from this group?')">
              <?= csrf_field() ?><input type="hidden" name="member_type" value="lead"><input type="hidden" name="member_id" value="<?= (int)$l['id'] ?>">
              <button class="btn btn--sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5">Remove</button></form>
          <?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$customers && !$leads): ?><tr><td colspan="9" class="muted" style="padding:18px;text-align:center">No members. Add people from their Customer or Lead page.</td></tr><?php endif; ?>
      </tbody>
      <?php if ($customers && sees_money()): ?>
      <tfoot><tr style="font-weight:700;background:var(--n-50)">
        <td colspan="4">Group total</td>
        <td class="mono"><?= money($summary['billed']) ?></td>
        <td class="mono"><?= money($summary['received']) ?></td>
        <td class="mono"><?= money($summary['discount']) ?></td>
        <td class="mono" style="color:var(--danger)"><?= money($summary['outstanding']) ?></td>
        <td></td>
      </tr></tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php if (can('invoices.manage')): ?>
<div class="card" style="margin-top:18px">
  <div class="card__head"><h3>Group invoices</h3><a class="btn btn--outline btn--sm" href="<?= url('/families/'.$f['id'].'/invoices/new') ?>">+ New group invoice</a></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Bill No</th><th>Date</th><th>Addressed to</th><th>Total</th><th></th></tr></thead>
      <tbody>
      <?php if (!$invoices): ?><tr><td colspan="5" class="muted" style="padding:20px;text-align:center">No group invoices yet. Each member can also be invoiced separately from their own page.</td></tr>
      <?php else: foreach ($invoices as $iv): ?>
        <tr>
          <td class="mono cell-strong"><a href="<?= url('/invoices/'.$iv['id']) ?>"><?= e($iv['invoice_no']) ?></a></td>
          <td><?= date_h($iv['invoice_date']) ?></td>
          <td><?= e($iv['customer_name']) ?></td>
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
<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Edit group & roles -->
<div class="modal-backdrop" id="roleModal" style="display:none">
  <form method="post" action="<?= url('/families/'.$f['id'].'/roles') ?>" class="modal">
    <?= csrf_field() ?>
    <div class="modal__head"><h3>Edit group &amp; roles</h3><button type="button" class="icon-btn" onclick="document.getElementById('roleModal').style.display='none'">✕</button></div>
    <div class="modal__body">
      <div class="field"><label>Group name</label><input class="input" name="name" value="<?= e($f['name']) ?>"></div>
      <div class="field"><label>Contact person <span class="muted small">(who we call / message)</span></label>
        <div class="select-wrap"><select class="select" name="contact">
          <?php foreach ($customers as $c): ?><option value="customer:<?= (int)$c['id'] ?>" <?= $contactKey==='customer:'.$c['id']?'selected':'' ?>><?= e($c['name']) ?> (customer)</option><?php endforeach; ?>
          <?php foreach ($leads as $l): ?><option value="lead:<?= (int)$l['id'] ?>" <?= $contactKey==='lead:'.$l['id']?'selected':'' ?>><?= e($l['name']) ?> (lead)</option><?php endforeach; ?>
        </select></div></div>
      <div class="field"><label>Usually pays <span class="muted small">(for reference — each member's payments are recorded on their own page)</span></label>
        <div class="select-wrap"><select class="select" name="payer">
          <?php foreach ($customers as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $payerId===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></div></div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn--light" onclick="document.getElementById('roleModal').style.display='none'">Cancel</button>
      <button class="btn btn--primary">Save</button>
    </div>
  </form>
</div>
<?php endif; ?>
