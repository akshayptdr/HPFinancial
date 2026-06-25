<style>
  .auth{min-height:100vh;display:grid;grid-template-columns:1.05fr 1fr;}
  .auth__brand{background:linear-gradient(150deg,var(--navy),#0c1d49);color:#fff;padding:56px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;}
  .auth__brand::after{content:"";position:absolute;width:460px;height:460px;border-radius:50%;background:radial-gradient(circle,rgba(37,99,235,.45),transparent 70%);right:-140px;top:-120px;}
  .auth__brand h2{color:#fff;font-size:32px;line-height:1.18;max-width:460px;position:relative;}
  .auth__brand p{color:#b9c8ee;margin-top:14px;max-width:420px;position:relative;}
  .feat{display:flex;gap:12px;align-items:flex-start;margin-top:18px;position:relative;}
  .feat .ic{width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,.12);display:grid;place-items:center;flex-shrink:0;}
  .feat .ic svg{width:18px;height:18px;color:#fff;} .feat b{color:#fff;font-size:14px;} .feat span{color:#a9bce8;font-size:13px;}
  .auth__form{display:grid;place-items:center;padding:40px;}
  .auth__card{width:100%;max-width:380px;}
  @media(max-width:880px){.auth{grid-template-columns:1fr;}.auth__brand{display:none;}}
</style>
<div class="auth">
  <div class="auth__brand">
    <div style="display:flex;align-items:center;gap:12px;position:relative;">
      <div class="brand__logo">HP</div>
      <div class="brand__name" style="font-size:20px">HP<span>Financial</span></div>
    </div>
    <div>
      <h2>The complete practice management system for your CA firm.</h2>
      <p>Leads, clients, ITR &amp; GST filings, fees and reminders — all in one secure place.</p>
      <div class="feat"><div class="ic"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2l-3.5-3.5L4 14.2 9 19l11-11-1.5-1.5z"/></svg></div><div><b>10 service workflows</b><br><span>Income Tax, GST, Accounting, Insurance &amp; more</span></div></div>
      <div class="feat"><div class="ic"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg></div><div><b>Bank-grade security</b><br><span>Encrypted credentials &amp; role-based access</span></div></div>
    </div>
    <div style="position:relative;color:#90a6da;font-size:12px;">© 2026 HP Financial · v1.0</div>
  </div>
  <div class="auth__form">
    <div class="auth__card">
      <h1 style="font-size:26px">Welcome back</h1>
      <p class="muted" style="margin:6px 0 24px">Sign in with your mobile number to continue.</p>
      <?php if ($m = flash('error')): ?><div class="alert alert--error"><?= e($m) ?></div><?php endif; ?>
      <?php if ($m = flash('success')): ?><div class="alert alert--success"><?= e($m) ?></div><?php endif; ?>
      <form method="post" action="<?= url('/login') ?>">
        <?= csrf_field() ?>
        <div class="field"><label>Mobile number</label>
          <input class="input" type="text" name="mobile" value="<?= e(old('mobile')) ?>" placeholder="10-digit mobile" autofocus></div>
        <div class="field"><label>Password</label>
          <input class="input" type="password" name="password" placeholder="••••••••"></div>
        <button class="btn btn--primary w-full" style="height:44px;margin-top:8px" type="submit">Sign in</button>
      </form>
      <p class="muted small" style="text-align:center;margin-top:22px">Trouble logging in? Contact your administrator.</p>
    </div>
  </div>
</div>
