<div class="page-head">
  <div><div class="crumb">Home · Invoices</div><h1>Invoices</h1>
    <div class="muted" style="margin-top:4px">To create one, open a customer and click <b>Create Invoice</b>.</div></div>
</div>
<div class="card">
  <div class="card__body" style="padding-bottom:0">
    <form method="get" class="filter-bar">
      <input class="input" name="q" value="<?= e($q) ?>" placeholder="Search bill no / customer…" style="width:240px">
      <button class="btn btn--primary btn--sm">Search</button>
      <a href="<?= url('/invoices') ?>" class="btn btn--ghost btn--sm">Reset</a>
    </form>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Bill No</th><th>Date</th><th>Customer</th><th>Total</th><th></th></tr></thead>
      <tbody>
      <?php if (!$invoices): ?><tr><td colspan="5" class="muted" style="padding:28px;text-align:center">No invoices yet.</td></tr>
      <?php else: foreach ($invoices as $i): ?>
        <tr>
          <td class="mono cell-strong"><a href="<?= url('/invoices/'.$i['id']) ?>"><?= e($i['invoice_no']) ?></a><?= !empty($i['group_id']) ? ' <span class="pill pill--sky">Group</span>' : '' ?></td>
          <td><?= date_h($i['invoice_date']) ?></td>
          <td><a href="<?= url('/customers/'.$i['customer_id'].'#invoices') ?>"><?= e($i['firm_name'] ?: $i['customer_name']) ?></a><?= !empty($i['group_name']) ? '<div class="cell-sub">Group: '.e($i['group_name']).'</div>' : '' ?></td>
          <td class="mono cell-strong"><?= money($i['total']) ?></td>
          <td class="text-right"><a class="btn btn--light btn--sm" href="<?= url('/invoices/'.$i['id']) ?>">View / Print</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
