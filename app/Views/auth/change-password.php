<div style="min-height:100vh;display:grid;place-items:center;padding:24px;background:linear-gradient(160deg,var(--navy),#0c1d49)">
  <div style="background:#fff;border-radius:16px;box-shadow:var(--shadow-lg);width:100%;max-width:440px;overflow:hidden">
    <div style="padding:28px 28px 0;text-align:center">
      <div class="brand__logo" style="width:48px;height:48px;margin:0 auto 14px;font-size:18px">HP</div>
      <h1 style="font-size:22px">Set a new password</h1>
      <p class="muted" style="margin-top:6px;font-size:13px">Please replace your temporary password before continuing.</p>
    </div>
    <div style="padding:24px 28px 28px">
      <?php if ($m = flash('error')): ?><div class="alert alert--error"><?= e($m) ?></div><?php endif; ?>
      <form method="post" action="<?= url('/password/change') ?>">
        <?= csrf_field() ?>
        <div class="field"><label>New password <span class="req">*</span></label>
          <input class="input" type="password" name="password" placeholder="At least 8 characters" autofocus></div>
        <div class="field"><label>Confirm password <span class="req">*</span></label>
          <input class="input" type="password" name="password_confirmation" placeholder="Re-enter password"></div>
        <button class="btn btn--primary w-full" style="height:44px;margin-top:6px" type="submit">Save &amp; continue</button>
      </form>
    </div>
  </div>
</div>
