<?php
$prevMonth = isset($ym) ? date('Y-m', strtotime($ym . '-01 -1 month')) : null;
$nextMonth = isset($ym) ? date('Y-m', strtotime($ym . '-01 +1 month')) : null;
$canNext = isset($ym) && $nextMonth <= date('Y-m');
?>
<div class="page-head">
  <div><div class="crumb">Home · <?= $manage ? 'Attendance' : 'My Attendance' ?></div>
    <h1><?= $manage ? 'Employee Attendance' : 'My Attendance' ?></h1></div>
  <?php if ($manage): ?>
  <div class="page-head__actions">
    <?php if ($mode === 'month'): ?><a class="btn btn--light" href="<?= url('/attendance/export?month=' . e($ym)) ?>">Download CSV</a><?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php if ($manage): ?>
<div class="tabs" style="margin-bottom:16px">
  <a class="tab <?= $mode === 'daily' ? 'active' : '' ?>" href="<?= url('/attendance') ?>" style="text-decoration:none">Daily sheet</a>
  <a class="tab <?= $mode === 'month' ? 'active' : '' ?>" href="<?= url('/attendance?view=month') ?>" style="text-decoration:none">Monthly</a>
</div>
<?php endif; ?>

<?php /* ======================================================= DAILY SHEET */ ?>
<?php if ($mode === 'daily'): ?>
  <div class="card" style="margin-bottom:18px">
    <div class="card__body">
      <form method="get" class="filter-bar" style="margin-bottom:0">
        <a class="btn btn--light btn--sm" href="<?= url('/attendance?date=' . date('Y-m-d', strtotime($date . ' -1 day'))) ?>">‹ Prev</a>
        <input class="input" type="date" name="date" value="<?= e($date) ?>" max="<?= e($today) ?>" style="width:auto" onchange="this.form.submit()">
        <?php if ($date < $today): ?><a class="btn btn--light btn--sm" href="<?= url('/attendance?date=' . date('Y-m-d', strtotime($date . ' +1 day'))) ?>">Next ›</a><?php endif; ?>
        <a class="btn btn--ghost btn--sm" href="<?= url('/attendance') ?>">Today</a>
        <span class="muted small" style="margin-left:auto"><?= e(date('l, d M Y', strtotime($date))) ?></span>
      </form>
    </div>
  </div>

  <div class="grid grid--4" style="margin-bottom:18px">
    <?php foreach ($statuses as $k => $st): ?>
      <div class="stat" style="padding:14px 16px"><div class="stat__val" style="font-size:22px"><?= (int)$count[$k] ?></div><div class="stat__label"><?= e($st['label']) ?></div></div>
    <?php endforeach; ?>
  </div>

  <form method="post" action="<?= url('/attendance') ?>">
    <?= csrf_field() ?><input type="hidden" name="date" value="<?= e($date) ?>">
    <div class="card">
      <div class="card__head">
        <h3>Mark attendance · <?= e(date('d M Y', strtotime($date))) ?> <?php if ($count['unmarked']): ?><span class="pill pill--gray" style="margin-left:6px"><?= (int)$count['unmarked'] ?> not marked</span><?php endif; ?></h3>
        <div class="flex gap-8">
          <button type="button" class="btn btn--outline btn--sm" onclick="markAll('present')">Mark all Present</button>
          <button class="btn btn--primary btn--sm">Save attendance</button>
        </div>
      </div>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Employee</th><?php foreach ($statuses as $k => $st): ?><th style="text-align:center"><?= e($st['label']) ?></th><?php endforeach; ?><th style="text-align:center">Not marked</th><th>Remark</th></tr></thead>
          <tbody>
          <?php if (!$employees): ?><tr><td colspan="7" class="muted" style="padding:22px;text-align:center">No active employees.</td></tr>
          <?php else: foreach ($employees as $e): $m = $marked[(int)$e['id']] ?? null; $cur = $m['status'] ?? ''; ?>
            <tr>
              <td class="cell-strong"><?= e($e['name']) ?></td>
              <?php foreach ($statuses as $k => $st): ?>
                <td style="text-align:center"><input type="radio" name="status[<?= (int)$e['id'] ?>]" value="<?= e($k) ?>" <?= $cur === $k ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--primary)"></td>
              <?php endforeach; ?>
              <td style="text-align:center"><input type="radio" name="status[<?= (int)$e['id'] ?>]" value="" <?= $cur === '' ? 'checked' : '' ?> style="width:18px;height:18px"></td>
              <td><input class="input" name="remarks[<?= (int)$e['id'] ?>]" value="<?= e($m['remarks'] ?? '') ?>" maxlength="255" placeholder="optional" style="height:34px;min-width:160px"></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </form>
  <script>
  function markAll(v){ document.querySelectorAll('input[type=radio][value="'+v+'"]').forEach(function(r){ r.checked = true; }); }
  </script>

<?php /* ======================================================= MONTHLY (manager) */ ?>
<?php elseif ($mode === 'month'): ?>
  <?php $days = (int) date('t', strtotime($ym . '-01')); ?>
  <div class="card" style="margin-bottom:18px">
    <div class="card__body">
      <form method="get" class="filter-bar" style="margin-bottom:0">
        <input type="hidden" name="view" value="month">
        <a class="btn btn--light btn--sm" href="<?= url('/attendance?view=month&month=' . $prevMonth) ?>">‹ Prev</a>
        <input class="input" type="month" name="month" value="<?= e($ym) ?>" max="<?= e(date('Y-m')) ?>" style="width:auto" onchange="this.form.submit()">
        <?php if ($canNext): ?><a class="btn btn--light btn--sm" href="<?= url('/attendance?view=month&month=' . $nextMonth) ?>">Next ›</a><?php endif; ?>
        <span class="muted small" style="margin-left:auto">
          <?php foreach ($statuses as $k => $st): ?><span class="pill pill--<?= $st['pill'] ?>" style="margin-right:4px"><?= e($st['short']) ?></span><?= e($st['label']) ?>&nbsp;&nbsp;<?php endforeach; ?>
        </span>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card__head"><h3><?= e(date('F Y', strtotime($ym . '-01'))) ?></h3></div>
    <div class="table-wrap">
      <table class="tbl" style="font-size:12px">
        <thead><tr>
          <th style="position:sticky;left:0;background:var(--n-50)">Employee</th>
          <?php for ($d = 1; $d <= $days; $d++): $ds = sprintf('%s-%02d', $ym, $d); ?>
            <th style="text-align:center;padding:6px 3px"><a href="<?= url('/attendance?date=' . $ds) ?>" title="Open this day"><?= $d ?></a></th>
          <?php endfor; ?>
          <th style="text-align:center">Present</th><th style="text-align:center">Half</th><th style="text-align:center">Absent</th><th style="text-align:center">Leave</th><th style="text-align:center">Not marked</th><th style="text-align:center">Days worked</th>
        </tr></thead>
        <tbody>
        <?php foreach ($employees as $e): $by = $all[(int)$e['id']] ?? []; $s = $sums[(int)$e['id']]; ?>
          <tr>
            <td class="cell-strong" style="position:sticky;left:0;background:#fff"><?= e($e['name']) ?></td>
            <?php for ($d = 1; $d <= $days; $d++): $ds = sprintf('%s-%02d', $ym, $d); $r = $by[$ds] ?? null; ?>
              <td style="text-align:center;padding:4px 2px" title="<?= $r ? e($statuses[$r['status']]['label'] . ($r['remarks'] ? ' — ' . $r['remarks'] : '')) : '' ?>">
                <?= $r ? '<span class="pill pill--' . $statuses[$r['status']]['pill'] . '" style="padding:1px 6px">' . e($statuses[$r['status']]['short']) . '</span>' : ($ds <= $today ? '<span class="muted">·</span>' : '') ?>
              </td>
            <?php endfor; ?>
            <td class="mono" style="text-align:center"><?= $s['present'] ?></td><td class="mono" style="text-align:center"><?= $s['half'] ?></td>
            <td class="mono" style="text-align:center;color:<?= $s['absent'] ? 'var(--danger)' : 'inherit' ?>"><?= $s['absent'] ?></td>
            <td class="mono" style="text-align:center"><?= $s['leave'] ?></td><td class="mono" style="text-align:center"><?= $s['unmarked'] ?></td>
            <td class="mono cell-strong" style="text-align:center"><?= rtrim(rtrim(number_format($s['worked'], 1), '0'), '.') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php /* ======================================================= MY ATTENDANCE (employee) */ ?>
<?php else: ?>
  <?php $days = (int) date('t', strtotime($ym . '-01')); ?>
  <div class="card" style="margin-bottom:18px">
    <div class="card__body">
      <form method="get" class="filter-bar" style="margin-bottom:0">
        <a class="btn btn--light btn--sm" href="<?= url('/attendance?month=' . $prevMonth) ?>">‹ Prev</a>
        <input class="input" type="month" name="month" value="<?= e($ym) ?>" max="<?= e(date('Y-m')) ?>" style="width:auto" onchange="this.form.submit()">
        <?php if ($canNext): ?><a class="btn btn--light btn--sm" href="<?= url('/attendance?month=' . $nextMonth) ?>">Next ›</a><?php endif; ?>
      </form>
    </div>
  </div>
  <div class="grid grid--4" style="margin-bottom:18px">
    <div class="stat" style="padding:14px 16px"><div class="stat__val" style="font-size:22px;color:var(--success)"><?= $sum['present'] ?></div><div class="stat__label">Present</div></div>
    <div class="stat" style="padding:14px 16px"><div class="stat__val" style="font-size:22px"><?= $sum['half'] ?></div><div class="stat__label">Half days</div></div>
    <div class="stat" style="padding:14px 16px"><div class="stat__val" style="font-size:22px;color:var(--danger)"><?= $sum['absent'] ?></div><div class="stat__label">Absent</div></div>
    <div class="stat" style="padding:14px 16px"><div class="stat__val" style="font-size:22px"><?= rtrim(rtrim(number_format($sum['worked'], 1), '0'), '.') ?></div><div class="stat__label">Days worked<?= $sum['leave'] ? ' · ' . $sum['leave'] . ' leave' : '' ?></div></div>
  </div>
  <div class="card">
    <div class="card__head"><h3><?= e(date('F Y', strtotime($ym . '-01'))) ?></h3></div>
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th>Date</th><th>Day</th><th>Status</th><th>Remark</th></tr></thead>
        <tbody>
        <?php for ($d = $days; $d >= 1; $d--): $ds = sprintf('%s-%02d', $ym, $d); if ($ds > $today) continue; $r = $mine[$ds] ?? null; ?>
          <tr>
            <td class="cell-strong"><?= date_h($ds) ?></td><td><?= e(date('l', strtotime($ds))) ?></td>
            <td><?= $r ? '<span class="pill pill--' . $statuses[$r['status']]['pill'] . '">' . e($statuses[$r['status']]['label']) . '</span>' : '<span class="muted">Not marked</span>' ?></td>
            <td class="small"><?= $r && $r['remarks'] ? e($r['remarks']) : '<span class="muted">—</span>' ?></td>
          </tr>
        <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
