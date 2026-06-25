<div class="page-head">
  <div><div class="crumb">Home</div><h1><?= $isAdmin ? 'Dashboard' : 'My Dashboard' ?></h1></div>
  <div class="page-head__actions">
    <?php if (can('leads.create')): ?><a href="<?= url('/leads/create') ?>" class="btn btn--primary">+ New Lead</a><?php endif; ?>
  </div>
</div>

<div class="grid grid--4" style="margin-bottom:18px">
  <div class="stat"><div class="stat__icon bg-blue"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg></div>
    <div class="stat__val"><?= (int)$stats['leads'] ?></div><div class="stat__label"><?= $isAdmin?'Total Leads':'My Leads' ?></div></div>
  <div class="stat"><div class="stat__icon bg-violet"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg></div>
    <div class="stat__val"><?= (int)$stats['customers'] ?></div><div class="stat__label"><?= $isAdmin?'Active Customers':'My Customers' ?></div></div>
  <div class="stat"><div class="stat__icon bg-green"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13z"/></svg></div>
    <div class="stat__val mono"><?= inr_short($stats['collected']) ?></div><div class="stat__label">Fees Collected (<?= date('M') ?>)</div></div>
  <div class="stat"><div class="stat__icon bg-amber"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21z"/></svg></div>
    <div class="stat__val mono"><?= inr_short(max(0,$stats['pending'])) ?></div><div class="stat__label">Pending Fees</div></div>
</div>

<div class="grid" style="grid-template-columns:1.7fr 1fr">
  <div class="card">
    <div class="card__head"><h3>Due Soon &amp; Overdue Jobs</h3><a href="<?= url('/work-board') ?>" class="btn btn--light btn--sm">Work board</a></div>
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th>Customer</th><th>Service</th><th>Period</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (!$dueSoon): ?>
          <tr><td colspan="5" class="muted" style="padding:24px;text-align:center">Nothing due in the next 7 days 🎉</td></tr>
        <?php else: foreach ($dueSoon as $j): ?>
          <tr>
            <td class="cell-strong"><a href="<?= url('/jobs/'.$j['id'].'/edit') ?>"><?= e($j['firm_name'] ?: $j['customer_name']) ?></a></td>
            <td><?= e(ucwords(str_replace('_',' ',$j['service_code']))) ?><?= $j['sub_type']?' · '.e(strtoupper($j['sub_type'])):'' ?></td>
            <td class="mono"><?= e($j['period_label'] ?: '—') ?></td>
            <td><?= due_pill($j['due_date']) ?></td>
            <td><?= $j['status_name'] ? status_pill($j['status_name']) : '<span class="pill pill--gray">—</span>' ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h3>Follow-ups Due</h3></div>
    <div class="card__body" style="padding:0">
      <?php if (!$followups): ?>
        <div class="muted" style="padding:24px;text-align:center">No upcoming follow-ups.</div>
      <?php else: foreach ($followups as $f): ?>
        <div style="padding:12px 18px;border-bottom:1px solid var(--border)" class="flex between items-center">
          <div><div class="cell-strong"><a href="<?= url('/leads/'.$f['id']) ?>"><?= e($f['name']) ?></a></div>
            <div class="cell-sub"><?= e($f['type_name'] ?: '—') ?> · <?= e($f['category_name'] ?: '—') ?></div></div>
          <?= due_pill($f['follow_up_date']) ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php if ($isAdmin): ?>
<div class="grid" style="grid-template-columns:1fr 1fr;margin-top:18px">
  <div class="card">
    <div class="card__head"><h3>Leads by Stage</h3></div>
    <div class="card__body">
      <?php $tot = max(1, array_sum($leadStages)); foreach ($leadStages as $st=>$n): ?>
        <div class="flex between" style="margin-bottom:6px"><span><?= status_pill($st) ?></span><b><?= $n ?></b></div>
        <div class="progress" style="margin-bottom:12px"><span style="width:<?= round($n/$tot*100) ?>%"></span></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card__head"><h3>Recent Activity</h3></div>
    <div class="card__body" style="padding:0">
      <?php if (!$recent): ?><div class="muted" style="padding:24px;text-align:center">No activity yet.</div>
      <?php else: foreach ($recent as $a): ?>
        <div style="padding:11px 18px;border-bottom:1px solid var(--border)" class="flex between">
          <span><b><?= e($a['user_name'] ?: 'System') ?></b> <?= e(str_replace('_',' ',$a['action'])) ?> <span class="muted"><?= e($a['entity']) ?></span></span>
          <span class="muted small"><?= e(date('d M H:i', strtotime($a['created_at']))) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>
