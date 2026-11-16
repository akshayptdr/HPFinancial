<div class="page-head">
  <div><div class="crumb"><a href="<?= url('/families') ?>">Groups</a> · <a href="<?= url('/families/'.$f['id']) ?>"><?= e($f['name']) ?></a> · New group invoice</div>
    <h1>New Group Invoice</h1>
    <div class="muted" style="margin-top:4px">One invoice for the whole group. Tick the jobs to bill from <b>any member</b> — each line prints the member's name and their service. Payments are still recorded on each member's own page.</div></div>
  <div class="page-head__actions"><a class="btn btn--ghost" href="<?= url('/families/'.$f['id']) ?>">Cancel</a></div>
</div>

<form method="post" action="<?= url('/families/'.$f['id'].'/invoices') ?>">
  <?= csrf_field() ?>
  <div class="card" style="margin-bottom:18px">
    <div class="card__body">
      <div class="form-grid">
        <div class="field"><label>Invoice addressed to <span class="req">*</span></label>
          <div class="select-wrap"><select class="select" name="bill_to" required>
            <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>" <?= (int)$m['id'] === (int)$defaultBillTo ? 'selected' : '' ?>><?= e($m['name']) ?><?= (int)$m['id'] === (int)($f['payer_customer_id'] ?? 0) ? ' (payer)' : '' ?></option><?php endforeach; ?>
          </select></div>
          <div class="muted small" style="margin-top:4px">Their name and address appear under “Billed to”, with the group name below it.</div></div>
        <div class="field"><label>Invoice date</label>
          <input class="input" type="date" name="invoice_date" id="invDate" value="<?= e($date) ?>" max="<?= e(date('Y-m-d')) ?>">
          <div class="muted small" style="margin-top:4px">Bill number: <b class="mono"><?= e($next['no']) ?></b> <span class="muted">(assigned automatically when saved)</span></div></div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:18px">
    <div class="card__head"><h3>Jobs of all members</h3></div>
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th style="width:36px"></th><th style="width:150px">Member</th><th>Service / job</th><th style="width:28%">Particulars on invoice</th><th style="width:105px">Financial year</th><th style="width:120px">Amount (₹)</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6" class="muted" style="padding:22px;text-align:center">None of the members has a job yet. Use the extra line below.</td></tr>
        <?php else: $last = null; foreach ($rows as $r): $j = $r['job']; $jid = (int)$j['id']; $was = $billed[$jid] ?? null; ?>
          <tr>
            <td><input type="checkbox" name="pick[]" value="<?= $jid ?>" style="width:18px;height:18px;accent-color:var(--primary)" <?= ($was || $r['amount'] <= 0) ? '' : 'checked' ?>></td>
            <td class="cell-strong"><?= e($r['member']['name']) ?></td>
            <td><?= e(\App\Models\Invoice::defaultParticulars($j)) ?>
              <div class="cell-sub">Fee <?= money($j['fees_amount']) ?><?= $j['due_date'] ? ' · due ' . date_h($j['due_date']) : '' ?></div>
              <?php if ($was): ?><div><span class="pill pill--amber">Already invoiced: <?= e($was) ?></span></div><?php endif; ?></td>
            <td><input class="input" name="particulars[<?= $jid ?>]" value="<?= e($r['particulars']) ?>" maxlength="255"></td>
            <td><input class="input mono" name="fy[<?= $jid ?>]" value="<?= e($r['fy']) ?>" maxlength="9"></td>
            <td><input class="input mono amt" type="number" step="0.01" min="0" name="amount[<?= $jid ?>]" value="<?= e($r['amount'] ?: '') ?>"></td>
          </tr>
        <?php endforeach; endif; ?>
          <tr style="background:var(--n-50)">
            <td></td><td><input class="input" name="extra_name" placeholder="Member name (optional)" maxlength="120"></td>
            <td class="cell-strong">Extra line <span class="muted small">(optional, not tied to a job)</span></td>
            <td><input class="input" name="extra_particulars" placeholder="e.g. Out of pocket expenses" maxlength="255"></td>
            <td><input class="input mono" name="extra_fy" placeholder="2026-27" maxlength="9"></td>
            <td><input class="input mono amt" type="number" step="0.01" min="0" name="extra_amount"></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="card__body">
      <div class="flex between items-center" style="flex-wrap:wrap;gap:12px">
        <div style="flex:1;min-width:240px"><label class="muted small">Note on invoice (optional)</label><input class="input" name="notes" maxlength="255"></div>
        <div style="font-size:16px">Total: <b class="mono" id="grand">₹0</b></div>
      </div>
    </div>
  </div>
  <div class="text-right"><button class="btn btn--primary">Create group invoice</button></div>
</form>

<script>
(function(){
  function fmt(n){ return '₹' + (Math.round(n*100)/100).toLocaleString('en-IN'); }
  function total(){
    var s = 0;
    document.querySelectorAll('tbody tr').forEach(function(tr){
      var cb = tr.querySelector('input[type=checkbox]'), a = tr.querySelector('input.amt');
      if (!a) return;
      var isExtra = !cb;
      if ((isExtra || cb.checked) && parseFloat(a.value) > 0) s += parseFloat(a.value);
    });
    document.getElementById('grand').textContent = fmt(s);
  }
  document.querySelectorAll('input.amt, input[type=checkbox]').forEach(function(i){ i.addEventListener('input', total); i.addEventListener('change', total); });
  var d = document.getElementById('invDate');
  d.addEventListener('change', function(){ location.href = '?date=' + encodeURIComponent(d.value); });   // reload so the bill number matches the financial year
  total();
})();
</script>
