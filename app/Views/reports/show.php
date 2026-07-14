<div class="page-head">
  <div><div class="crumb"><a href="<?= url('/reports') ?>">Reports</a> · <?= e($title) ?></div><h1><?= e($title) ?></h1></div>
  <div class="page-head__actions">
    <?php $eq = http_build_query(array_merge($f??[], ['format'=>'csv'])); ?>
    <a href="<?= url('/reports/'.$type.'/export?'.$eq) ?>" class="btn btn--light btn--sm">CSV</a>
    <?php $eq2 = http_build_query(array_merge($f??[], ['format'=>'excel'])); ?>
    <a href="<?= url('/reports/'.$type.'/export?'.$eq2) ?>" class="btn btn--light btn--sm">Excel</a>
  </div>
</div>

<?php if ($type === 'fees'): ?>
<div class="card" style="margin-bottom:18px">
  <div class="card__body" style="padding-bottom:0">
    <form method="get" class="filter-bar">
      <div class="select-wrap"><select class="select" name="financial_year">
        <option value="">All Years</option>
        <?php for($fy=2040;$fy>=2018;$fy--):$fyLabel=$fy.'-'.str_pad(($fy+1)%100,2,'0',STR_PAD_LEFT); ?>
          <option value="<?= $fyLabel ?>" <?= ($f['financial_year']??'')===$fyLabel?'selected':'' ?>><?= $fyLabel ?></option>
        <?php endfor; ?>
      </select></div>
      <div class="select-wrap"><select class="select" name="service">
        <option value="">All Services</option>
        <?php foreach ($services as $s): ?><option value="<?= e($s['code']) ?>" <?= ($f['service']??'')===$s['code']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
      </select></div>
      <input class="input" type="date" name="date_from" value="<?= e($f['date_from']??'') ?>" placeholder="From date" style="width:auto">
      <input class="input" type="date" name="date_to" value="<?= e($f['date_to']??'') ?>" placeholder="To date" style="width:auto">
      <button class="btn btn--primary btn--sm">Filter</button>
      <a href="<?= url('/reports/fees') ?>" class="btn btn--ghost btn--sm">Reset</a>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($totals): ?>
<div class="grid grid--4" style="margin-bottom:18px">
  <div class="stat" style="padding:16px"><div class="stat__val mono" style="font-size:22px"><?= money($totals['billed']) ?></div><div class="stat__label">Total Billed</div></div>
  <div class="stat" style="padding:16px"><div class="stat__val mono" style="font-size:22px;color:var(--success)"><?= money($totals['collected']) ?></div><div class="stat__label">Collected</div></div>
  <div class="stat" style="padding:16px"><div class="stat__val mono" style="font-size:22px;color:var(--danger)"><?= money(max(0,$totals['outstanding'])) ?></div><div class="stat__label">Outstanding</div></div>
  <div class="stat" style="padding:16px"><div class="stat__val" style="font-size:22px"><?= $totals['billed']>0?round($totals['collected']/$totals['billed']*100):0 ?>%</div><div class="stat__label">Collection Rate</div></div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card__head"><h3><?= e($title) ?></h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><?php foreach ($cols as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="<?= count($cols) ?>" class="empty" style="padding:36px">No data yet.</td></tr>
      <?php else: foreach ($rows as $r): $vals = array_values($r); ?>
        <tr><?php foreach ($vals as $i=>$v): ?>
          <td class="<?= $i===0?'cell-strong':'mono' ?>"><?= is_numeric($v) && $i>0 ? ($v==(int)$v && $title!=='Fees / Collection' ? e($v) : money($v)) : e(ucfirst((string)$v)) ?></td>
        <?php endforeach; ?></tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
