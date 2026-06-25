<div class="page-head">
  <div><div class="crumb"><a href="<?= url('/reports') ?>">Reports</a> · <?= e($title) ?></div><h1><?= e($title) ?></h1></div>
</div>

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
