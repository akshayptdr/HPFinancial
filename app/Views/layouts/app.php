<?php
use App\Core\Auth;
$u = Auth::user();
$active = $activeNav ?? '';
$nav = [
  ['Main'],
  ['dashboard','Dashboard','/', true],
  ['leads','Leads','/leads', can('leads.view')],
  ['customers','Customers','/customers', can('customers.view')],
  ['board','Work Board','/work-board', can('services.view')],
  ['reports','Reports','/reports', can('reports.view')],
  ['Administration'],
  ['employees','Employees','/employees', can('employees.view')],
  ['roles','Roles & Access','/roles', can('roles.manage')],
  ['settings','Settings','/settings', can('settings.manage')],
];
$icons = [
  'dashboard'=>'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z',
  'leads'=>'M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13z',
  'customers'=>'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
  'board'=>'M4 5h16v2H4V5zm0 6h10v2H4v-2zm0 6h16v2H4v-2zM16 11h4v6h-4v-6z',
  'reports'=>'M5 9.2h3V19H5V9.2zM10.6 5h3v14h-3V5zm5.6 8H19v6h-3v-6z',
  'employees'=>'M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13z',
  'roles'=>'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z',
  'settings'=>'M19.14 12.94a7.49 7.49 0 0 0 .05-1.88l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.3 7.3 0 0 0-1.62-.94l-.36-2.54a.5.5 0 0 0-.5-.42h-3.84a.5.5 0 0 0-.5.42l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96a.5.5 0 0 0-.6.22L2.62 8.84a.5.5 0 0 0 .12.64l2.03 1.58a7.49 7.49 0 0 0 0 1.88L2.74 14.5a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.49.38 1.03.7 1.62.94l.36 2.54a.5.5 0 0 0 .5.42h3.84a.5.5 0 0 0 .5-.42l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58zM12 15.5A3.5 3.5 0 1 1 12 8.5a3.5 3.5 0 0 1 0 7z',
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? 'HP Financial') ?> · HP Financial</title>
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
</head>
<body>

<!-- Sidebar overlay (mobile/tablet) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
      <div class="brand__logo">HP</div>
      <div class="brand__name">HP<span>Financial</span></div>
      <!-- Close button inside sidebar on mobile -->
      <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>
    <nav class="nav">
      <?php foreach ($nav as $item): ?>
        <?php if (count($item) === 1): ?>
          <div class="nav__label"><?= e($item[0]) ?></div>
        <?php elseif ($item[3]): ?>
          <a class="nav__item <?= active($item[0], $active) ?>" href="<?= url($item[2]) ?>">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="<?= $icons[$item[0]] ?>"/></svg>
            <span><?= e($item[1]) ?></span>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div style="padding:14px 18px;border-top:1px solid rgba(255,255,255,.08);font-size:11px;color:#7a90c8">v1.0 · Phase 1</div>
  </aside>

  <div class="main">
    <header class="topbar">
      <!-- Hamburger: visible only on tablet/mobile -->
      <button class="hamburger" id="hamburger" aria-label="Open menu">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
      </button>

      <div class="topbar__search">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/></svg>
        <input type="text" placeholder="Search…">
      </div>

      <div class="topbar__spacer"></div>

      <a class="icon-btn" href="<?= url('/notifications') ?>" title="Notifications">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
        <?php if (!empty($unreadCount)): ?><span class="dot"></span><?php endif; ?>
      </a>

      <form method="post" action="<?= url('/logout') ?>" style="display:flex;align-items:center;gap:10px;margin:0">
        <?= csrf_field() ?>
        <div class="user-chip">
          <div class="avatar"><?= e(initials($u['name'] ?? 'U')) ?></div>
          <div class="user-chip__info">
            <div class="user-chip__name"><?= e($u['name'] ?? '') ?></div>
            <div class="user-chip__role"><?= e($u['role_name'] ?? '') ?></div>
          </div>
        </div>
        <button class="btn btn--light btn--sm" type="submit" title="Logout">
          <span class="logout-label">Logout</span>
          <svg class="logout-icon" viewBox="0 0 24 24" fill="currentColor" style="display:none"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        </button>
      </form>
    </header>

    <div id="page">
      <div class="content">
        <?php if ($m = flash('success')): ?><div class="alert alert--success"><?= e($m) ?></div><?php endif; ?>
        <?php if ($m = flash('error')): ?><div class="alert alert--error"><?= e($m) ?></div><?php endif; ?>
        <?php if ($m = flash('info')): ?><div class="alert alert--info"><?= e($m) ?></div><?php endif; ?>
        <?= $content ?>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var sidebar  = document.getElementById('sidebar');
  var overlay  = document.getElementById('sidebarOverlay');
  var hamburger = document.getElementById('hamburger');
  var closeBtn = document.getElementById('sidebarClose');

  function open()  { sidebar.classList.add('open'); overlay.classList.add('active'); document.body.style.overflow = 'hidden'; }
  function close() { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }

  hamburger.addEventListener('click', open);
  closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', close);

  // Auto-close sidebar on nav click (mobile)
  document.querySelectorAll('.nav__item').forEach(function (a) { a.addEventListener('click', close); });
})();
</script>
</body>
</html>
