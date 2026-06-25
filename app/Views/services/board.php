<div class="page-head">
  <div><div class="crumb">Home · Work Board</div><h1>Work Board</h1></div>
</div>
<div class="card">
  <div class="card__body" style="padding-bottom:0">
    <form method="get" class="filter-bar">
      <div class="select-wrap"><select class="select" name="service"><option value="">All Services</option>
        <?php foreach ($services as $s): ?><option value="<?= e($s['code']) ?>" <?= ($f['service']??'')==$s['code']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
      <div class="select-wrap"><select class="select" name="status"><option value="">All Statuses</option>
        <?php foreach ($fileStatuses as $fs): ?><option value="<?= $fs['id'] ?>" <?= ($f['status']??'')==$fs['id']?'selected':'' ?>><?= e($fs['name']) ?></option><?php endforeach; ?></select></div>
      <label class="chip-check <?= !empty($f['overdue'])?'on':'' ?>"><input type="checkbox" name="overdue" value="1" <?= !empty($f['overdue'])?'checked':'' ?> onchange="this.form.submit()"> Overdue only</label>
      <button class="btn btn--primary btn--sm">Filter</button>
      <a href="<?= url('/work-board') ?>" class="btn btn--ghost btn--sm">Reset</a>
    </form>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Customer</th><th>Service · Job</th><th>Period</th><th>Due</th><th>Assignee</th><th>Status</th><th>Balance</th><th></th></tr></thead>
      <tbody>
      <?php if (!$jobs): ?><tr><td colspan="8" class="empty" style="padding:36px">No jobs found.</td></tr>
      <?php else: foreach ($jobs as $j): $bal=(float)$j['fees_amount']-(float)$j['received']; $od = $j['due_date'] && strtotime($j['due_date'])<strtotime('today') && $j['status_name']!=='Completed'; ?>
        <tr style="<?= $od?'background:#fff7f7':'' ?>">
          <td class="cell-strong"><a href="<?= url('/customers/'.$j['customer_id']) ?>"><?= e($j['firm_name'] ?: $j['customer_name']) ?></a></td>
          <td><?= e($j['service_name']) ?><?= $j['sub_type']?' · '.e(strtoupper($j['sub_type'])):'' ?></td>
          <td class="mono"><?= e($j['period_label'] ?: $j['financial_year'] ?: '—') ?></td>
          <td><?= $j['due_date']?due_pill($j['due_date']):'—' ?></td>
          <td><?= $j['assignee_name']?'<span class="avatar-sm">'.e(initials($j['assignee_name'])).'</span>':'—' ?></td>
          <td><?= $j['status_name']?status_pill($j['status_name']):'<span class="pill pill--gray">—</span>' ?></td>
          <td class="mono" style="color:<?= $bal>0?'var(--danger)':'var(--success)' ?>"><?= money(max(0,$bal)) ?></td>
          <td class="text-right"><a href="<?= url('/jobs/'.$j['id'].'/edit') ?>" class="btn btn--light btn--sm">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
