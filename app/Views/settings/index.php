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

<!-- Invoice details printed on every invoice -->
<div class="card" style="max-width:1000px;margin-top:18px">
  <div class="card__head"><h3>Invoice details</h3></div>
  <div class="card__body">
    <form method="post" action="<?= url('/settings/invoice') ?>" enctype="multipart/form-data"><?= csrf_field() ?>
      <div class="form-grid">
        <div class="field" style="grid-column:span 2">
          <label>Logo <span class="muted small">(PNG / JPG / WebP, up to 1 MB — a square or wide image works best)</span></label>
          <div class="flex gap-12 items-center" style="flex-wrap:wrap">
            <?php if ($inv['logo']): ?>
              <img src="<?= url('/invoice-logo') ?>?v=<?= e($inv['logo']) ?>" alt="logo" style="height:56px;max-width:160px;object-fit:contain;border:1px solid var(--border);border-radius:8px;padding:4px;background:#fff">
              <label class="chip-check"><input type="checkbox" name="remove_logo" value="1"> Remove logo</label>
            <?php else: ?><span class="muted small">No logo yet — invoices show a monogram of your firm's initials.</span><?php endif; ?>
            <input class="input" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp" style="max-width:280px">
          </div>
        </div>
        <div class="field" style="grid-column:span 2">
          <label class="chip-check <?= ($inv['show_ca_logo'] ?? '') !== '0' ? 'on' : '' ?>" style="display:inline-flex">
            <input type="hidden" name="show_ca_logo" value="0">
            <input type="checkbox" name="show_ca_logo" value="1" <?= ($inv['show_ca_logo'] ?? '') !== '0' ? 'checked' : '' ?>> Show the ICAI “CA” logo on invoices
          </label>
          <div class="muted small" style="margin-top:4px">Printed at the top left of every invoice (it moves to the top right if you also upload your own logo). It is always shown exactly as issued — ICAI's rules say not to change its colours, proportions or white background, and not to rotate or distort it.</div>
        </div>
        <div class="field"><label>Firm name</label><input class="input" name="firm_name" value="<?= e($inv['firm_name']) ?>"></div>
        <div class="field"><label>Tagline <span class="muted small">(under the name)</span></label><input class="input" name="tagline" value="<?= e($inv['tagline']) ?>" placeholder="Chartered Accountants"></div>
        <div class="field"><label>PAN</label><input class="input mono" name="firm_pan" value="<?= e($inv['firm_pan']) ?>"></div>
        <div class="field"><label>ICAI Firm Reg. No. (FRN) <span class="muted small">(optional)</span></label><input class="input mono" name="frn" value="<?= e($inv['frn']) ?>"></div>
        <div class="field" style="grid-column:span 2"><label>Address</label><input class="input" name="firm_address" value="<?= e($inv['firm_address']) ?>"></div>
        <div class="field"><label>Phone</label><input class="input" name="firm_phone" value="<?= e($inv['firm_phone']) ?>"></div>
        <div class="field"><label>Email</label><input class="input" name="firm_email" value="<?= e($inv['firm_email']) ?>"></div>
        <div class="field"><label>UPI ID</label><input class="input" name="upi" value="<?= e($inv['upi']) ?>"></div>
        <div class="field"><label>A/c name</label><input class="input" name="acc_name" value="<?= e($inv['acc_name']) ?>"></div>
        <div class="field"><label>A/c type</label><input class="input" name="acc_type" value="<?= e($inv['acc_type']) ?>"></div>
        <div class="field"><label>A/c number</label><input class="input mono" name="acc_no" value="<?= e($inv['acc_no']) ?>"></div>
        <div class="field"><label>Bank (branch)</label><input class="input" name="bank" value="<?= e($inv['bank']) ?>"></div>
        <div class="field"><label>IFSC code</label><input class="input mono" name="ifsc" value="<?= e($inv['ifsc']) ?>"></div>
        <div class="field" style="grid-column:span 2"><label>Signatory (printed bottom right)</label><input class="input" name="signatory" value="<?= e($inv['signatory']) ?>"></div>
        <div class="field" style="grid-column:span 2"><label>Terms line <span class="muted small">(optional, printed at the bottom — e.g. “Payment due within 15 days. Subject to Barwani jurisdiction.”)</span></label><input class="input" name="terms" maxlength="255" value="<?= e($inv['terms']) ?>"></div>
      </div>
      <div class="divider"></div>
      <b style="font-size:14px">Sharing on WhatsApp</b>
      <div class="form-grid" style="margin-top:8px">
        <div class="field"><label>WhatsApp number <span class="muted small">(shown in the message: “for queries WhatsApp us on …”)</span></label><input class="input mono" name="whatsapp" value="<?= e($inv['whatsapp'] ?? '') ?>" placeholder="8643043584"></div>
        <div class="field"><label>Website address for invoice links <span class="muted small">(optional)</span></label><input class="input" name="public_url" value="<?= e($inv['public_url'] ?? '') ?>" placeholder="https://cahpgroup.in"></div>
      </div>
      <div class="muted small" style="margin:-4px 0 12px">The shared link only works for your customer when the app is online. Leave the address blank to use the address you are using now, or set it to your live site once it is deployed.</div>
      <div class="divider"></div>
      <b style="font-size:14px">Bill numbering</b>
      <div class="muted small" style="margin:4px 0 10px">The last bill number you issued <b>before</b> using this system. New invoices continue from it (e.g. 26-27/120 → 26-27/121) and restart at 1 in a new financial year.</div>
      <div class="flex gap-8 items-center" style="flex-wrap:wrap">
        <div class="field" style="margin:0"><label>Financial year</label><input class="input mono" name="last_fy" value="<?= e($inv['last_fy']) ?>" placeholder="26-27" style="width:110px"></div>
        <div class="field" style="margin:0"><label>Last number used</label><input class="input mono" type="number" min="0" name="last_seq" value="<?= e($inv['last_seq']) ?>" style="width:130px"></div>
      </div>
      <div class="text-right"><button class="btn btn--primary">Save invoice details</button></div>
    </form>
  </div>
</div>
