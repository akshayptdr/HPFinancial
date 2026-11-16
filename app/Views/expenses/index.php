<div class="page-head">
  <div><div class="crumb">Home · Expenses</div><h1>Expenses &amp; Cash in hand</h1>
    <div class="muted" style="margin-top:4px">Record daily expenses. Cash received from customers (mode = Cash) is added to cash in hand automatically; every expense reduces it.</div></div>
  <?php if ($cfg): ?>
  <div class="page-head__actions">
    <button class="btn btn--primary" onclick="document.getElementById('expModal').style.display='grid'">+ Add Expense</button>
    <button class="btn btn--outline" onclick="document.getElementById('cashModal').style.display='grid'">Add / Take out cash</button>
    <button class="btn btn--light" onclick="document.getElementById('openModal').style.display='grid'" title="Change the opening cash or the start date">Opening cash</button>
  </div>
  <?php endif; ?>
</div>

<?php if (!$cfg): ?>
  <div class="card" style="max-width:640px">
    <div class="card__head"><h3>Start your cash book</h3></div>
    <div class="card__body">
      <p class="muted" style="margin-top:0">Tell me how much cash you have in hand <b>right now</b> and from which date to count. From that date, customer cash receipts are added and expenses are deducted automatically.</p>
      <form method="post" action="<?= url('/expenses/opening') ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="field"><label>Opening cash in hand (₹) <span class="req">*</span></label><input class="input mono" type="number" step="0.01" min="0" name="opening" value="0" required></div>
          <div class="field"><label>Counting from (date) <span class="req">*</span></label><input class="input" type="date" name="start" value="<?= e($today) ?>" max="<?= e($today) ?>" required></div>
        </div>
        <div class="text-right"><button class="btn btn--primary">Start cash book</button></div>
      </form>
    </div>
  </div>
<?php return; endif; ?>

<?php $neg = $inHand < -0.004; ?>
<div class="grid grid--4" style="margin-bottom:18px">
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:22px;color:<?= $neg ? 'var(--danger)' : 'var(--success)' ?>"><?= money($inHand) ?></div><div class="stat__label">Cash in hand (now)</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:22px;color:var(--danger)"><?= money($todaySums['expenses']) ?></div><div class="stat__label">Today's expenses</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:22px"><?= money($monthSums['expenses']) ?></div><div class="stat__label">This month's expenses</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val mono" style="font-size:22px;color:var(--success)"><?= money($todaySums['receipts']) ?></div><div class="stat__label">Cash received today (customers)</div></div>
</div>
<?php if ($neg): ?><div class="alert alert--error" style="margin-bottom:18px">Cash in hand is negative. Some cash received or added may not have been recorded — check the cash book below or add the missing cash.</div><?php endif; ?>

<div class="card" style="margin-bottom:18px">
  <div class="card__body" style="padding-bottom:0">
    <form method="get" class="filter-bar">
      <label class="muted small">From</label><input class="input" type="date" name="from" value="<?= e($from) ?>" min="<?= e($cfg['start']) ?>" style="width:auto">
      <label class="muted small">To</label><input class="input" type="date" name="to" value="<?= e($to) ?>" style="width:auto">
      <div class="select-wrap"><select class="select" name="category"><option value="">All categories</option>
        <?php foreach ($categories as $cn): ?><option value="<?= e($cn) ?>" <?= $cat === $cn ? 'selected' : '' ?>><?= e($cn) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn--primary btn--sm">Filter</button>
      <a href="<?= url('/expenses') ?>" class="btn btn--ghost btn--sm">This month</a>
    </form>
  </div>
  <div class="card__body muted small" style="padding-top:6px">Cash book counted from <b><?= date_h($cfg['start']) ?></b> with opening cash <b class="mono"><?= money($cfg['opening']) ?></b>.</div>
</div>

<!-- Daily cash book -->
<div class="card" style="margin-bottom:18px">
  <div class="card__head"><h3>Daily cash book</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Date</th><th>Opening</th><th>Cash received</th><th>Cash added</th><th>Cash taken out</th><th>Expenses</th><th>Closing</th></tr></thead>
      <tbody>
      <?php if (!$days): ?><tr><td colspan="7" class="muted" style="padding:18px;text-align:center">No days in this range.</td></tr>
      <?php else: foreach ($days as $d): $busy = $d['receipts'] || $d['in'] || $d['out'] || $d['expenses']; ?>
        <tr style="<?= $busy ? '' : 'color:var(--text-muted)' ?>">
          <td class="cell-strong"><?= date_h($d['date']) ?><?= $d['date'] === $today ? ' <span class="pill pill--blue">Today</span>' : '' ?></td>
          <td class="mono"><?= money($d['opening']) ?></td>
          <td class="mono" style="color:var(--success)"><?= $d['receipts'] ? '+ '.money($d['receipts']) : '—' ?></td>
          <td class="mono" style="color:var(--success)"><?= $d['in'] ? '+ '.money($d['in']) : '—' ?></td>
          <td class="mono"><?= $d['out'] ? '− '.money($d['out']) : '—' ?></td>
          <td class="mono" style="color:var(--danger)"><?= $d['expenses'] ? '− '.money($d['expenses']) : '—' ?></td>
          <td class="mono cell-strong" style="color:<?= $d['closing'] < -0.004 ? 'var(--danger)' : 'inherit' ?>"><?= money($d['closing']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
      <?php if ($days): ?>
      <tfoot><tr style="font-weight:700;background:var(--n-50)">
        <td>Total for range</td><td></td>
        <td class="mono"><?= money($rangeSums['receipts']) ?></td><td class="mono"><?= money($rangeSums['in']) ?></td>
        <td class="mono"><?= money($rangeSums['out']) ?></td><td class="mono"><?= money($rangeSums['expenses']) ?></td><td></td>
      </tr></tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<div class="grid" style="grid-template-columns:2fr 1fr;gap:18px;align-items:start">
  <!-- Expenses -->
  <div class="card">
    <div class="card__head"><h3>Expenses <?= $cat ? '· '.e($cat) : '' ?></h3></div>
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>By</th><th></th></tr></thead>
        <tbody>
        <?php if (!$expenses): ?><tr><td colspan="6" class="muted" style="padding:22px;text-align:center">No expenses in this range. Use “+ Add Expense”.</td></tr>
        <?php else: foreach ($expenses as $e): ?>
          <tr>
            <td><?= date_h($e['expense_date']) ?></td>
            <td><span class="tag"><?= e(\App\Models\Expense::label($e)) ?></span></td>
            <td class="small"><?= $e['description'] ? e($e['description']) : '<span class="muted">—</span>' ?></td>
            <td class="mono cell-strong" style="color:var(--danger)"><?= money($e['amount']) ?></td>
            <td class="cell-sub"><?= e($e['by_name'] ?: '—') ?></td>
            <td class="text-right">
              <form method="post" action="<?= url('/expenses/'.$e['id'].'/delete') ?>" style="display:inline" onsubmit="return confirm('Remove this expense? Cash in hand will go back up.')">
                <?= csrf_field() ?><button class="btn btn--sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;padding:2px 8px" title="Remove">✕</button></form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:18px">
    <!-- By category -->
    <div class="card">
      <div class="card__head"><h3>By category</h3></div>
      <div class="card__body">
        <?php if (!$byCat): ?><div class="muted small">Nothing yet.</div>
        <?php else: foreach ($byCat as $cn => $amt): ?>
          <div class="flex between" style="padding:5px 0;border-bottom:1px solid var(--border)"><span><?= e($cn) ?></span><b class="mono"><?= money($amt) ?></b></div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <!-- Cash added / taken out -->
    <div class="card">
      <div class="card__head"><h3>Cash added / taken out</h3></div>
      <div class="card__body">
        <?php if (!$adjustments): ?><div class="muted small">No manual cash entries in this range.</div>
        <?php else: foreach ($adjustments as $a): ?>
          <div style="padding:6px 0;border-bottom:1px solid var(--border)">
            <div class="flex between items-center">
              <span><span class="pill pill--<?= $a['direction'] === 'in' ? 'green' : 'amber' ?>"><?= $a['direction'] === 'in' ? 'Added' : 'Taken out' ?></span> <b class="mono"><?= money($a['amount']) ?></b></span>
              <form method="post" action="<?= url('/cash-adjustments/'.$a['id'].'/delete') ?>" onsubmit="return confirm('Remove this cash entry?')"><?= csrf_field() ?>
                <button class="btn btn--sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;padding:0 7px" title="Remove">✕</button></form>
            </div>
            <div class="cell-sub"><?= date_h($a['entry_date']) ?> · <?= e($a['remarks']) ?> · <?= e($a['by_name'] ?: '—') ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Add expense -->
<div class="modal-backdrop" id="expModal" style="display:none">
  <form method="post" action="<?= url('/expenses') ?>" class="modal">
    <?= csrf_field() ?>
    <div class="modal__head"><h3>Add Expense</h3><button type="button" class="icon-btn" onclick="document.getElementById('expModal').style.display='none'">✕</button></div>
    <div class="modal__body">
      <div class="form-grid">
        <div class="field"><label>Amount (₹) <span class="req">*</span></label><input class="input mono" type="number" step="0.01" min="0.01" name="amount" required></div>
        <div class="field"><label>Date</label><input class="input" type="date" name="expense_date" value="<?= e($today) ?>" min="<?= e($cfg['start']) ?>" max="<?= e($today) ?>"></div>
        <div class="field" style="grid-column:span 2"><label>Category <span class="req">*</span></label>
          <div class="select-wrap"><select class="select" name="category" id="expCat" required onchange="document.getElementById('otherBox').style.display=this.value==='Other'?'':'none';document.getElementById('otherName').required=this.value==='Other'"><option value="">— select —</option>
            <?php foreach ($categories as $cn): ?><option value="<?= e($cn) ?>"><?= e($cn) ?></option><?php endforeach; ?></select></div></div>
        <div class="field" id="otherBox" style="grid-column:span 2;display:none"><label>Option name <span class="req">*</span></label><input class="input" name="other_name" id="otherName" maxlength="80" placeholder="e.g. Courier, Donation, Gift…"></div>
        <div class="field" style="grid-column:span 2"><label>Description <span class="muted small">(optional)</span></label><input class="input" name="description" maxlength="255" placeholder="e.g. courier to client, printer ink…"></div>
      </div>
      <p class="muted small" style="margin:6px 0 0">Paid in cash — this reduces cash in hand.</p>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn--light" onclick="document.getElementById('expModal').style.display='none'">Cancel</button>
      <button class="btn btn--primary">Save expense</button>
    </div>
  </form>
</div>

<!-- Add / take out cash -->
<div class="modal-backdrop" id="cashModal" style="display:none">
  <form method="post" action="<?= url('/expenses/cash') ?>" class="modal">
    <?= csrf_field() ?>
    <div class="modal__head"><h3>Add / Take out cash</h3><button type="button" class="icon-btn" onclick="document.getElementById('cashModal').style.display='none'">✕</button></div>
    <div class="modal__body">
      <p class="muted small" style="margin-bottom:12px">For cash that is <b>not</b> an expense or a customer payment — for example the owner puts cash in, or cash is deposited to the bank.</p>
      <div class="form-grid">
        <div class="field" style="grid-column:span 2"><label>What happened?</label>
          <div class="select-wrap"><select class="select" name="direction"><option value="in">Cash added to the drawer (money in)</option><option value="out">Cash taken out (deposited to bank, withdrawn…)</option></select></div></div>
        <div class="field"><label>Amount (₹) <span class="req">*</span></label><input class="input mono" type="number" step="0.01" min="0.01" name="amount" required></div>
        <div class="field"><label>Date</label><input class="input" type="date" name="entry_date" value="<?= e($today) ?>" min="<?= e($cfg['start']) ?>" max="<?= e($today) ?>"></div>
        <div class="field" style="grid-column:span 2"><label>Remark <span class="req">*</span></label><input class="input" name="remarks" maxlength="255" required placeholder="e.g. Deposited to bank, owner added cash"></div>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn--light" onclick="document.getElementById('cashModal').style.display='none'">Cancel</button>
      <button class="btn btn--primary">Save</button>
    </div>
  </form>
</div>

<!-- Opening cash -->
<div class="modal-backdrop" id="openModal" style="display:none">
  <form method="post" action="<?= url('/expenses/opening') ?>" class="modal" onsubmit="return confirm('Change the opening cash / start date? Cash in hand is recalculated from the new start date.')">
    <?= csrf_field() ?>
    <div class="modal__head"><h3>Opening cash</h3><button type="button" class="icon-btn" onclick="document.getElementById('openModal').style.display='none'">✕</button></div>
    <div class="modal__body">
      <div class="form-grid">
        <div class="field"><label>Opening cash (₹)</label><input class="input mono" type="number" step="0.01" min="0" name="opening" value="<?= e($cfg['opening']) ?>" required></div>
        <div class="field"><label>Counting from</label><input class="input" type="date" name="start" value="<?= e($cfg['start']) ?>" max="<?= e($today) ?>" required></div>
      </div>
      <p class="muted small" style="margin:6px 0 0">Entries dated before the start date are not counted.</p>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn--light" onclick="document.getElementById('openModal').style.display='none'">Cancel</button>
      <button class="btn btn--primary">Save</button>
    </div>
  </form>
</div>
