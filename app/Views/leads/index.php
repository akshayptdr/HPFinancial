<div class="page-head">
  <div><div class="crumb">Home · Leads</div><h1>Leads</h1></div>
  <div class="page-head__actions">
    <?php if (can('leads.create')): ?><a href="<?= url('/leads/create') ?>" class="btn btn--primary">+ New Lead</a><?php endif; ?>
  </div>
</div>

<div class="grid grid--4" style="margin-bottom:18px">
  <div class="stat" style="padding:14px 16px"><div class="stat__val" style="font-size:22px"><?= $counts['new'] ?></div><div class="stat__label">New</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val" style="font-size:22px"><?= $counts['contacted'] ?></div><div class="stat__label">Contacted</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val" style="font-size:22px"><?= $counts['qualified'] ?></div><div class="stat__label">Qualified</div></div>
  <div class="stat" style="padding:14px 16px"><div class="stat__val" style="font-size:22px"><?= $counts['won'] ?></div><div class="stat__label">Won</div></div>
</div>

<div class="card">
  <div class="card__body" style="padding-bottom:0">
    <form method="get" class="filter-bar">
      <input class="input" name="q" value="<?= e($f['q']??'') ?>" placeholder="Search name / mobile…">
      <div class="select-wrap"><select class="select" name="type"><option value="">All Types</option>
        <?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= ($f['type']??'')==$t['id']?'selected':'' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
      <div class="select-wrap"><select class="select" name="category"><option value="">All Categories</option>
        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($f['category']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="select-wrap"><select class="select" name="status"><option value="">All Status</option>
        <?php foreach (['new','contacted','qualified','won','lost'] as $s): ?><option value="<?= $s ?>" <?= ($f['status']??'')==$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn--primary btn--sm">Filter</button>
      <a href="<?= url('/leads') ?>" class="btn btn--ghost btn--sm">Reset</a>
      <?php if (can('masters.manage')): ?><a href="<?= url('/lead-masters') ?>" class="btn btn--light btn--sm toolbar-right">Manage Masters</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Name</th><th>Mobile</th><th>Type</th><th>Interested In</th><th>Location</th><th>Follow-up</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (!$leads): ?>
        <tr><td colspan="8" class="empty" style="padding:36px">No leads found.</td></tr>
      <?php else: foreach ($leads as $l): ?>
        <tr>
          <td class="cell-strong"><a href="<?= url('/leads/'.$l['id']) ?>"><?= e($l['name']) ?></a></td>
          <td class="mono"><?= e($l['mobile']) ?></td>
          <td><?= $l['type_name'] ? '<span class="tag">'.e($l['type_name']).'</span>' : '—' ?></td>
          <td><?= e($l['category_names'] ?: '—') ?></td>
          <td class="cell-sub"><?= e(trim(($l['district']??'').' '.($l['state']??'')) ?: '—') ?></td>
          <td><?= $l['follow_up_date'] ? due_pill($l['follow_up_date']) : '—' ?></td>
          <td><?= status_pill($l['status']) ?></td>
          <td class="text-right"><a href="<?= url('/leads/'.$l['id']) ?>" class="btn btn--light btn--sm">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
