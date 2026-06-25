<?php $isWon = $lead['status'] === 'won'; ?>
<div class="page-head">
  <div><div class="crumb"><a href="<?= url('/leads') ?>">Leads</a> · <?= e($lead['name']) ?></div>
    <h1 class="flex items-center gap-12"><?= e($lead['name']) ?> <?= status_pill($lead['status']) ?></h1></div>
  <div class="page-head__actions">
    <?php if (!$isWon && can('leads.edit')): ?>
      <a href="<?= url('/leads/'.$lead['id'].'/edit') ?>" class="btn btn--light">Edit</a>
    <?php endif; ?>
    <?php if ($isWon && $customer): ?>
      <a href="<?= url('/customers/'.$customer['id']) ?>" class="btn btn--primary">View Customer Profile</a>
    <?php elseif (!$isWon && can('customers.create')): ?>
      <form method="post" action="<?= url('/leads/'.$lead['id'].'/convert') ?>" onsubmit="return confirm('Convert this lead to a customer?')"><?= csrf_field() ?>
        <button class="btn btn--primary">Convert to Customer</button></form>
    <?php endif; ?>
  </div>
</div>

<?php if ($isWon): ?>
<div class="alert alert--info" style="max-width:900px;margin-bottom:18px">
  This lead has been converted to a customer. The lead record is now read-only — edit details from the <a href="<?= $customer ? url('/customers/'.$customer['id']) : url('/customers') ?>" style="font-weight:600">Customer Profile</a>.
</div>
<?php endif; ?>

<div class="grid" style="grid-template-columns:1fr 1.4fr">
  <div style="display:flex;flex-direction:column;gap:18px">
    <div class="card">
      <div class="card__head"><h3>Lead Details</h3></div>
      <div class="card__body">
        <div class="form-grid" style="gap:14px 20px">
          <div><div class="cell-sub">Mobile</div><div class="cell-strong mono"><?= e($lead['mobile']) ?></div></div>
          <div><div class="cell-sub">Lead Type</div><div class="cell-strong"><?= e($lead['type_name'] ?: '—') ?></div></div>
          <div><div class="cell-sub">Interested In</div><div class="cell-strong"><?= e($lead['category_names'] ?: '—') ?></div></div>
          <div><div class="cell-sub">Contact Person</div><div class="cell-strong"><?= e($lead['contact_person'] ?: '—') ?></div></div>
          <div><div class="cell-sub">Follow-up</div><div><?= $lead['follow_up_date']?due_pill($lead['follow_up_date']):'—' ?></div></div>
          <div class="col-span-2" style="grid-column:span 2"><div class="cell-sub">Location</div>
            <div class="cell-strong"><?= e(trim(implode(', ', array_filter([$lead['village'],$lead['tehsil'],$lead['district'],$lead['state']]))) ?: '—') ?></div></div>
          <?php if ($lead['notes']): ?><div class="col-span-2" style="grid-column:span 2"><div class="cell-sub">Notes</div><div><?= nl2br(e($lead['notes'])) ?></div></div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h3>Activity &amp; Follow-ups</h3></div>
    <div class="card__body">
      <?php if (!$isWon && can('leads.edit')): ?>
      <form method="post" action="<?= url('/leads/'.$lead['id'].'/activity') ?>" style="background:var(--n-50);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:20px">
        <?= csrf_field() ?>
        <div class="flex gap-8" style="margin-bottom:10px">
          <div class="select-wrap"><select class="select" name="type" style="height:34px">
            <option value="note">Note</option><option value="call">Call</option><option value="meeting">Meeting</option></select></div>
          <div class="select-wrap"><select class="select" name="status" style="height:34px">
            <option value="">— keep status —</option>
            <?php foreach (['new','contacted','qualified','lost'] as $s): ?><option value="<?= $s ?>" <?= $lead['status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
        </div>
        <textarea class="input" name="description" rows="2" placeholder="Add a note or log a call…"></textarea>
        <div class="flex between items-center" style="margin-top:10px">
          <input class="input" type="date" name="follow_up_at" style="width:auto" title="Next follow-up">
          <button class="btn btn--primary btn--sm">Add activity</button>
        </div>
      </form>
      <?php endif; ?>
      <div class="timeline">
        <?php if (!$activities): ?><div class="muted">No activity yet.</div><?php endif; ?>
        <?php foreach ($activities as $a): ?>
          <div class="tl-item <?= e($a['type']==='status_change'?'status':$a['type']) ?>">
            <div class="when"><?= e(date('d M Y, H:i', strtotime($a['created_at']))) ?> · <?= e($a['user_name'] ?: 'System') ?></div>
            <div class="what"><?= e(ucfirst($a['type'])) ?><?= $a['follow_up_at']?' · follow-up '.date_h($a['follow_up_at']):'' ?></div>
            <?php if ($a['description']): ?><div class="muted small"><?= nl2br(e($a['description'])) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
