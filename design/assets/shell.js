/* HP Financial — shared app shell (sidebar + topbar).
   Each page sets: <body data-page="leads" data-role="Admin" data-user="Rahul Shah">
   and contains <div id="app"></div><div id="page">...content...</div>
   (For auth pages, omit #app; the page renders standalone.) */

const ICONS = {
  dashboard:'<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
  leads:'<path d="M16 11c1.66 0 3-1.34 3-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
  customers:'<path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>',
  services:'<path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/>',
  board:'<path d="M4 5h16v2H4V5zm0 6h10v2H4v-2zm0 6h16v2H4v-2zM16 11h4v6h-4v-6z"/>',
  reports:'<path d="M5 9.2h3V19H5V9.2zM10.6 5h3v14h-3V5zm5.6 8H19v6h-3v-6z"/>',
  employees:'<path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
  roles:'<path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>',
  settings:'<path d="M19.14 12.94a7.49 7.49 0 0 0 .05-1.88l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.3 7.3 0 0 0-1.62-.94l-.36-2.54a.5.5 0 0 0-.5-.42h-3.84a.5.5 0 0 0-.5.42l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96a.5.5 0 0 0-.6.22L2.62 8.84a.5.5 0 0 0 .12.64l2.03 1.58a7.49 7.49 0 0 0 0 1.88L2.74 14.5a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.49.38 1.03.7 1.62.94l.36 2.54a.5.5 0 0 0 .5.42h3.84a.5.5 0 0 0 .5-.42l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58zM12 15.5A3.5 3.5 0 1 1 12 8.5a3.5 3.5 0 0 1 0 7z"/>',
  bell:'<path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>',
  search:'<path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/>',
};

const NAV = [
  { sec: 'Main' },
  { key:'dashboard', label:'Dashboard', href:'dashboard-admin.html', icon:'dashboard' },
  { key:'leads', label:'Leads', href:'leads.html', icon:'leads', badge:'24' },
  { key:'customers', label:'Customers', href:'customers.html', icon:'customers' },
  { key:'services', label:'Services', href:'service-income-tax.html', icon:'services' },
  { key:'board', label:'Work Board', href:'work-board.html', icon:'board', badge:'7' },
  { key:'reports', label:'Reports', href:'reports.html', icon:'reports' },
  { sec: 'Administration' },
  { key:'employees', label:'Employees', href:'employees.html', icon:'employees' },
  { key:'roles', label:'Roles & Access', href:'roles.html', icon:'roles' },
  { key:'settings', label:'Settings', href:'settings.html', icon:'settings' },
];

function svg(p, cls){ return `<svg viewBox="0 0 24 24" fill="currentColor" class="${cls||''}">${p}</svg>`; }

function buildShell() {
  const app = document.getElementById('app');
  if (!app) return;
  const page = document.body.dataset.page || '';
  const role = document.body.dataset.role || 'Admin';
  const user = document.body.dataset.user || 'Rahul Shah';
  const initials = user.split(' ').map(s=>s[0]).slice(0,2).join('');

  let nav = '';
  NAV.forEach(n => {
    if (n.sec) { nav += `<div class="nav__label">${n.sec}</div>`; return; }
    const active = n.key === page ? 'active' : '';
    const badge = n.badge ? `<span class="nav__badge">${n.badge}</span>` : '';
    nav += `<a class="nav__item ${active}" href="${n.href}">${svg(ICONS[n.icon])}<span>${n.label}</span>${badge}</a>`;
  });

  app.innerHTML = `
  <aside class="sidebar">
    <div class="sidebar__brand">
      <div class="brand__logo">HP</div>
      <div class="brand__name">HP<span>Financial</span></div>
    </div>
    <nav class="nav">${nav}</nav>
    <div style="padding:14px 18px;border-top:1px solid rgba(255,255,255,.08);font-size:11px;color:#7a90c8">
      v1.0 · Phase 1
    </div>
  </aside>`;

  // topbar injected at top of #page
  const topbar = document.createElement('div');
  topbar.innerHTML = `
  <header class="topbar">
    <div class="topbar__search">
      ${svg(ICONS.search)}
      <input type="text" placeholder="Search customers, leads, jobs…">
    </div>
    <div class="topbar__spacer"></div>
    <a class="icon-btn" href="notifications.html" title="Notifications">${svg(ICONS.bell)}<span class="dot"></span></a>
    <div class="user-chip">
      <div class="avatar">${initials}</div>
      <div>
        <div class="user-chip__name">${user}</div>
        <div class="user-chip__role">${role}</div>
      </div>
    </div>
  </header>`;
  const pageEl = document.getElementById('page');
  pageEl.parentNode.insertBefore(topbar.firstElementChild, pageEl);
}
document.addEventListener('DOMContentLoaded', buildShell);
