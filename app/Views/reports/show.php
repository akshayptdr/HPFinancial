<div class="page-head">
  <div><div class="crumb"><a href="<?= url('/reports') ?>">Reports</a> · <?= e($title) ?></div><h1><?= e($title) ?></h1></div>
  <div class="page-head__actions">
    <?php $xs = !empty($show) ? ['show'=>$show] : []; ?>
    <?php $eq = http_build_query(array_merge($f??[], $xs, ['format'=>'csv'])); ?>
    <a href="<?= url('/reports/'.$type.'/export?'.$eq) ?>" class="btn btn--light btn--sm">CSV</a>
    <?php $eq2 = http_build_query(array_merge($f??[], $xs, ['format'=>'excel'])); ?>
    <a href="<?= url('/reports/'.$type.'/export?'.$eq2) ?>" class="btn btn--light btn--sm">Excel</a>
  </div>
</div>


<?php if ($totals): ?>
<?php
  $show = $show ?? '';
  $cardDefs = [
    ['billed',      'Total Billed',             $totals['billed'],              ''],
    ['collected',   'Collected',                $totals['collected'],           'var(--success)'],
    ['discount',    'Discount / Written off',   $totals['discount'],            'var(--warning-fg)'],
    ['outstanding', 'Outstanding (to collect)', max(0, $totals['outstanding']), 'var(--danger)'],
  ];
  $showNames = ['billed' => 'Billed', 'collected' => 'Collected', 'discount' => 'Discount / Written off', 'outstanding' => 'Outstanding'];
?>
<style>
  .stat--pick{display:block;text-decoration:none;color:inherit;cursor:pointer;transition:transform .15s,box-shadow .15s,border-color .15s}
  .stat--pick:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(20,48,107,.12);border-color:#bfdbfe}
  .stat--pick:focus-visible{outline:3px solid #93c5fd;outline-offset:2px}
  .stat--pick.is-on{border-color:#2563eb;background:#eff6ff;box-shadow:0 0 0 2px #2563eb inset}
  .stat--pick .hint{font-size:11.5px;color:var(--text-muted);margin-top:8px}
  .stat--pick.is-on .hint{color:#1d4ed8;font-weight:600}
</style>
<div class="grid grid--4" style="margin-bottom:18px">
  <?php foreach ($cardDefs as [$key, $label, $amount, $color]): $on = ($show === $key); ?>
    <a class="stat stat--pick<?= $on ? ' is-on' : '' ?>" style="padding:16px" href="<?= url('/reports/' . $type . ($on ? '' : '?show=' . $key)) ?>" title="<?= $on ? 'Click to show all customers' : 'Click to list these customers' ?>">
      <div class="stat__val mono" style="font-size:22px<?= $color ? ';color:' . $color : '' ?>"><?= money($amount) ?></div>
      <div class="stat__label"><?= e($label) ?></div>
      <div class="hint"><?= $on ? 'Showing below · click to clear' : 'Click to filter the list' ?></div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="card__head"><h3><?= e($title) ?><?php if (!empty($show) && isset($showNames[$show])): ?> <span class="pill pill--blue" style="margin-left:8px"><?= e($showNames[$show]) ?> only</span><?php endif; ?></h3>
    <?php if (!empty($show)): ?><a class="btn btn--light btn--sm" href="<?= url('/reports/' . $type) ?>">Show all customers</a><?php endif; ?>
  </div>
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
