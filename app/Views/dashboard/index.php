<div class="page-head">
  <div><div class="crumb">Home</div><h1><?= $isAdmin ? 'Dashboard' : 'My Dashboard' ?></h1></div>
  <div class="page-head__actions">
    <?php if (can('leads.create')): ?><a href="<?= url('/leads/create') ?>" class="btn btn--primary">+ New Lead</a><?php endif; ?>
  </div>
</div>

<?php
  // Summary cards: each opens the page behind its number (only when the user may open that page).
  $cards = [
    ['href' => '/leads',        'perm' => 'leads.view',     'tone' => 'bg-blue',   'val' => (int)$stats['leads'],
     'label' => $isAdmin ? 'Open Leads' : 'My Open Leads',
     'svg' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.500 20c0-3.600 2.900-6 6.500-6s6.500 2.400 6.500 6"/><path d="M19 7.500v6M16 10.500h6"/>'],
    ['href' => '/customers',    'perm' => 'customers.view', 'tone' => 'bg-violet', 'val' => (int)$stats['customers'],
     'label' => $isAdmin ? 'Active Customers' : 'My Customers',
     'svg' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.500 20c0-3.600 2.900-6 6.500-6s6.500 2.400 6.500 6"/><circle cx="17.500" cy="9" r="2.500"/><path d="M17 14.200c2.600.3 4.500 2.200 4.500 5.300"/>'],
    ['href' => '/reports/fees', 'perm' => 'reports.view',   'tone' => 'bg-green',  'val' => inr_short($stats['collected']), 'mono' => true,
     'label' => 'Fees Collected (' . date('M') . ')',
     'svg' => '<circle cx="12" cy="12" r="9"/><path d="M8.500 8h7M8.500 11.500h7M10 8c3 0 4.500 1 4.500 3.200S13 14.500 10 14.500H9l5.500 4"/>'],
    ['href' => '/reports/fees', 'perm' => 'reports.view',   'tone' => 'bg-amber',  'val' => inr_short(max(0, $stats['pending'])), 'mono' => true,
     'label' => 'Pending Fees',
     'svg' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.500 2"/>'],
  ];
?>
<style>
  .stat--link{display:block;text-decoration:none;color:inherit;transition:transform .15s,box-shadow .15s,border-color .15s}
  .stat--link:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(20,48,107,.12);border-color:#bfdbfe}
  .stat--link:focus-visible{outline:3px solid #93c5fd;outline-offset:2px}
  .stat--link::after{content:"";position:absolute;top:18px;right:16px;width:18px;height:18px;opacity:.35;transition:opacity .15s,transform .15s;
    background:no-repeat center/18px url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2314306b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M7 17 17 7M8 7h9v9'/></svg>")}
  .stat--link:hover::after{opacity:.8;transform:translate(2px,-2px)}
  .stat__icon svg{width:22px;height:22px}
</style>
<div class="grid grid--4" style="margin-bottom:18px">
  <?php foreach ($cards as $c): $link = can($c['perm']); ?>
    <<?= $link ? 'a href="' . e(url($c['href'])) . '"' : 'div' ?> class="stat<?= $link ? ' stat--link' : '' ?>">
      <div class="stat__icon <?= $c['tone'] ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $c['svg'] ?></svg></div>
      <div class="stat__val<?= !empty($c['mono']) ? ' mono' : '' ?>"><?= e((string)$c['val']) ?></div>
      <div class="stat__label"><?= e($c['label']) ?></div>
    </<?= $link ? 'a' : 'div' ?>>
  <?php endforeach; ?>
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
            <td><?= e(ucwords(str_replace('_',' ',$j['service_code']))) ?><?= $j['sub_type']?' · '.e(strtoupper($j['sub_type'])):'' ?><?= e(job_kind_label($j)) ?></td>
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
