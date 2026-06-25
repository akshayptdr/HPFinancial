<div class="page-head">
  <div><div class="crumb">Home · Customers</div><h1>Customers</h1></div>
  <div class="page-head__actions"></div>
</div>
<div class="card">
  <div class="card__body" style="padding-bottom:0">
    <form method="get" class="filter-bar">
      <input class="input" name="q" value="<?= e($f['q']??'') ?>" placeholder="Search name, firm, GST, mobile…">
      <div class="select-wrap"><select class="select" name="service"><option value="">All Services</option>
        <?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>" <?= ($f['service']??'')==$s['id']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn--primary btn--sm">Filter</button>
      <a href="<?= url('/customers') ?>" class="btn btn--ghost btn--sm">Reset</a>
    </form>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Customer / Firm</th><th>Mobile</th><th>GST No.</th><th>Pending Fees</th><th></th></tr></thead>
      <tbody>
      <?php if (!$customers): ?>
        <tr><td colspan="5" class="empty" style="padding:36px">No customers found.</td></tr>
      <?php else: foreach ($customers as $c): ?>
        <tr>
          <td><div class="cell-strong"><a href="<?= url('/customers/'.$c['id']) ?>"><?= e($c['firm_name'] ?: $c['name']) ?></a></div>
            <div class="cell-sub"><?= e($c['name']) ?><?= $c['district']?' · '.e($c['district']):'' ?></div></td>
          <td class="mono"><?= e($c['mobile']) ?></td>
          <td class="mono"><?= e($c['gst_number'] ?: '—') ?></td>
          <td class="mono" style="font-weight:600;color:<?= $c['pending']>0?'var(--danger)':'var(--success)' ?>"><?= money(max(0,$c['pending'])) ?></td>
          <td class="text-right"><a href="<?= url('/customers/'.$c['id']) ?>" class="btn btn--light btn--sm">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
