<div class="page-head"><div><div class="crumb">Administration · Settings</div><h1>Settings &amp; Masters</h1></div></div>
<div class="grid" style="grid-template-columns:1.4fr 1fr;max-width:1000px">
  <div class="card">
    <div class="card__head"><h3>Services</h3>
      <form method="post" action="<?= url('/masters/service') ?>" class="flex gap-8">
        <?= csrf_field() ?>
        <input class="input" name="name" placeholder="Name" style="height:32px;width:120px">
        <input class="input mono" name="code" placeholder="code" style="height:32px;width:110px">
        <button class="btn btn--primary btn--sm">Add</button>
      </form>
    </div>
    <div class="table-wrap">
      <table class="tbl"><thead><tr><th>Service</th><th>Code</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($services as $s): ?><tr><td class="cell-strong"><?= e($s['name']) ?></td><td class="mono"><?= e($s['code']) ?></td><td><?= status_pill($s['status']) ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
  <div style="display:flex;flex-direction:column;gap:18px">
    <div class="card">
      <div class="card__head"><h3>File Statuses</h3>
        <form method="post" action="<?= url('/masters/status') ?>" class="flex gap-8"><?= csrf_field() ?>
          <input class="input" name="name" placeholder="Status" style="height:32px;width:120px"><button class="btn btn--primary btn--sm">Add</button></form>
      </div>
      <div class="card__body" style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($fileStatuses as $fs): ?><div class="flex between items-center"><?= status_pill($fs['name']) ?><span class="muted small">order <?= (int)$fs['sort_order'] ?></span></div><?php endforeach; ?>
      </div>
    </div>
    <div class="card">
      <div class="card__head"><h3>Reminders</h3></div>
      <div class="card__body">
        <form method="post" action="<?= url('/settings') ?>"><?= csrf_field() ?>
          <div class="field"><label>Reminder lead time (days before due)</label><input class="input mono" name="reminder_lead_days" value="<?= e($reminderDays) ?>" style="width:90px"></div>
          <div class="muted small" style="margin-bottom:12px">In-app reminders generated daily by the cron. Email / WhatsApp / SMS arrive in Phase 2.</div>
          <button class="btn btn--primary btn--sm">Save</button>
        </form>
      </div>
    </div>
  </div>
</div>
