<div class="page-head" style="max-width:760px">
  <div><div class="crumb">Home · Notifications</div><h1>Notifications</h1></div>
  <div class="page-head__actions">
    <form method="post" action="<?= url('/notifications/read-all') ?>"><?= csrf_field() ?><button class="btn btn--light">Mark all read</button></form>
  </div>
</div>
<div class="card" style="max-width:760px">
  <?php if (!$items): ?><div class="empty" style="padding:48px">No notifications.</div><?php endif; ?>
  <?php foreach ($items as $n): ?>
    <div class="notif <?= $n['is_read']?'':'unread' ?>">
      <div class="notif__ico bg-blue"><svg viewBox="0 0 24 24" width="18" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg></div>
      <div class="flex between w-full">
        <div><div class="cell-strong"><?= e($n['title']) ?></div><div class="cell-sub"><?= e($n['message'] ?: '') ?></div></div>
        <span class="muted small"><?= e(date('d M, H:i', strtotime($n['created_at']))) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>
