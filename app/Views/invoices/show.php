<?php
$hasPaise = false;
foreach ($inv['items'] as $it) { if (abs($it['amount'] - round($it['amount'])) > 0.004) $hasPaise = true; }
$fmt = function ($n) use ($hasPaise) {            // same number of decimals on every line when any amount has paise
    $t = indian_number($n);
    return ($hasPaise && strpos($t, '.') === false) ? $t . '.00' : $t;
};
$filler = max(0, 5 - count($inv['items']));        // keep the table a balanced height on short invoices
$public = !empty($public);                        // opened through the private share link (no login)
$flash = $public ? null : \App\Core\Session::flash('success');
$words = trim(substr(amount_in_words($inv['total']), 7));   // drop the leading "Rupees "
$bank = array_filter([
    'UPI ID' => $s['upi'], 'A/c Name' => $s['acc_name'], 'A/c Type' => $s['acc_type'],
    'A/c No' => $s['acc_no'], 'Bank (Branch)' => $s['bank'], 'IFSC Code' => $s['ifsc'],
], fn($v) => $v !== '');

// Emblem (top left): the firm's own uploaded logo if there is one; otherwise the official ICAI "CA India" logo
// (if enabled in Settings); otherwise a monogram. When the firm has its own logo AND the CA logo is enabled, the CA
// logo goes top right. The CA logo is always shown exactly as issued: same colours, proportions, white background.
$logoFile = $s['logo'] !== '' ? STORAGE_PATH . '/uploads/invoice/' . basename($s['logo']) : '';
$logoUrl = ($logoFile && is_file($logoFile)) ? ($logoSrc ?? url('/invoice-logo') . '?v=' . filemtime($logoFile)) : null;
$caOn = $s['show_ca_logo'] !== '0';
$caSrc = asset('img/ca-logo.png');
$initials = '';
foreach (preg_split('/[\s&.,]+/', trim($s['firm_name'])) as $w) {
    if ($w !== '' && !in_array(strtoupper($w), ['CO', 'AND'], true)) $initials .= strtoupper(mb_substr($w, 0, 1));
    if (mb_strlen($initials) >= 2) break;
}
$isGroup = !empty($inv['group_id']);
$backUrl = $isGroup ? url('/families/' . $inv['group_id']) : url('/customers/' . $inv['customer_id'] . '#invoices');
$fyLong = preg_match('/^(\d{2})-(\d{2})$/', $inv['fy_label'] ?? '', $m) ? '20' . $m[1] . '-' . $m[2] : '';
?>
<style>
  /* Palette taken from the CA India logo itself: blue #1a70a0, saffron #f07830, green #68b850 */
  :root{--blue:#1a70a0;--ink:#0e4a6f;--text:#1c2733;--muted:#5f6f7d;--saffron:#f07830;--green:#68b850;--line:#d9e6f0;--tint:#f3f8fc;--zebra:#f8fbfd}
  *{box-sizing:border-box}
  body{background:#e8edf2!important}
  .bar{max-width:794px;margin:18px auto 12px;padding:0 4px;display:flex;justify-content:space-between;align-items:center;gap:8px}
  .share{max-width:794px;margin:0 auto 14px;background:#fff;border:1px solid var(--line);border-left:4px solid #25d366;border-radius:8px;padding:12px 16px;font-family:"Segoe UI",system-ui,Arial,sans-serif}
  .share .t{font-weight:700;color:var(--ink);margin-bottom:8px}
  .share .row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  .share .cc{background:var(--tint);border:1px solid var(--line);border-radius:6px;padding:7px 10px;font-weight:600;color:var(--ink)}
  .share input{max-width:200px}
  .share .wa{background:#25d366;border-color:#25d366;color:#fff;font-weight:700}
  .share .wa:hover{background:#1ebe5b}
  .share .hint{margin-top:8px;font-size:12px;color:var(--muted)}
  .share .warn{margin-top:8px;font-size:12px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:6px 10px}
  .sheet{width:100%;max-width:794px;margin:0 auto 32px;background:#fff;color:var(--text);position:relative;overflow:hidden;
         font-family:"Segoe UI","Helvetica Neue",Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;
         box-shadow:0 6px 30px rgba(14,74,111,.14);
         -webkit-print-color-adjust:exact;print-color-adjust:exact}
  .sheet:before{content:"";display:block;height:8px;background:linear-gradient(90deg,var(--blue) 0,var(--blue) 80%,var(--saffron) 80%,var(--saffron) 90%,var(--green) 90%,var(--green) 100%)}
  .inner{padding:28px 44px 0}
  .head{display:flex;justify-content:space-between;align-items:flex-start;gap:24px}
  .brand{display:flex;gap:18px;align-items:center}
  .emblem{flex:0 0 auto;display:flex;align-items:center;justify-content:center;background:#fff}
  .emblem img.ca{display:block;height:72px;width:auto}                 /* CA logo: proportions untouched */
  .emblem img.own{display:block;max-height:72px;max-width:96px;object-fit:contain}
  .mono-logo{width:72px;height:72px;border-radius:12px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;letter-spacing:1px}
  .firm{font-size:22px;font-weight:700;color:var(--ink);line-height:1.2;letter-spacing:.2px}
  .tagline{font-size:10.5px;letter-spacing:3px;text-transform:uppercase;color:var(--blue);font-weight:700;margin-top:4px}
  .sub{color:var(--muted);font-size:11.5px;margin-top:3px}
  .doc{text-align:right}
  .doc img.ca{display:block;margin:0 0 8px auto;height:54px;width:auto}
  .doc-title{font-size:30px;font-weight:300;color:var(--blue);letter-spacing:7px;line-height:1}
  .doc-no{margin-top:8px;font-size:12px;color:var(--muted)}
  .doc-no b{color:var(--ink);font-size:15px;letter-spacing:.3px}
  .rule{margin:20px 0 22px;height:4px;border-top:2px solid var(--blue);border-bottom:1px solid var(--saffron)}
  .two{display:flex;gap:16px;align-items:stretch}
  .panel{background:var(--tint);border:1px solid var(--line);border-radius:6px;padding:13px 16px}
  .billto{flex:1.5;border-left:4px solid var(--blue)}
  .details{flex:1}
  .label{font-size:10px;font-weight:700;letter-spacing:1.8px;color:var(--blue);text-transform:uppercase;margin-bottom:5px}
  .billto .name{font-size:15.5px;font-weight:700;color:var(--ink)}
  .billto div{margin-top:1px}
  .dl{display:grid;grid-template-columns:auto 1fr;gap:4px 16px;font-size:12.5px}
  .dl dt{color:var(--muted)}
  .dl dd{margin:0;text-align:right;font-weight:700;color:var(--ink)}
  table.items{width:100%;border-collapse:collapse;margin-top:24px}
  table.items thead th{background:var(--blue);color:#fff;font-size:11px;font-weight:600;letter-spacing:.9px;text-transform:uppercase;padding:10px 12px;text-align:left;border-bottom:3px solid var(--saffron)}
  table.items th.r,table.items td.r{text-align:right}
  table.items th.c,table.items td.c{text-align:center}
  table.items tbody td{padding:11px 12px;border-bottom:1px solid var(--line);vertical-align:top}
  table.items tbody tr:nth-child(even) td{background:var(--zebra)}
  table.items td.num{font-variant-numeric:tabular-nums;white-space:nowrap;font-weight:600}
  table.items td.idx{color:var(--muted);width:56px}
  .summary{display:flex;justify-content:space-between;align-items:stretch;gap:16px;margin-top:20px}
  .words{flex:1;border:1px dashed var(--blue);border-radius:6px;padding:11px 15px;display:flex;flex-direction:column;justify-content:center;background:var(--tint)}
  .words .t{color:var(--ink);font-weight:600;font-size:13.5px;margin-top:2px}
  .total{min-width:250px;background:var(--blue);color:#fff;border-radius:6px;padding:12px 18px;display:flex;justify-content:space-between;align-items:center;box-shadow:inset 0 -4px 0 var(--saffron)}
  .total .l{font-size:10.5px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#e4f0f8}
  .total .v{font-size:23px;font-weight:700;font-variant-numeric:tabular-nums}
  .note{margin-top:12px;color:var(--muted);font-size:12px}
  .foot{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;margin-top:30px}
  .bank{border:1px solid var(--line);border-radius:6px;padding:12px 16px;min-width:330px;background:#fff}
  .bank table{border-collapse:collapse}
  .bank td{padding:2px 0;font-size:12.5px;vertical-align:top}
  .bank td:first-child{color:var(--muted);padding-right:18px;white-space:nowrap}
  .bank td:last-child{font-weight:600;color:var(--ink)}
  .sign{text-align:center;min-width:230px}
  .sign .for{font-weight:700;color:var(--ink)}
  .sign .space{height:112px}            /* room for the firm's seal / stamp between "For …" and the signature line */
  .sign .who{border-top:1px solid var(--ink);padding-top:5px;font-weight:700;color:var(--ink)}
  .sign .role{font-size:10.5px;letter-spacing:1.6px;text-transform:uppercase;color:var(--blue);font-weight:700}
  .terms{margin-top:20px;font-size:11px;color:var(--muted);text-align:center}
  .band{margin-top:22px;background:var(--blue);color:#eef6fb;text-align:center;font-size:11px;letter-spacing:.6px;padding:9px 14px;border-top:3px solid var(--saffron)}
  @media (max-width:640px){.inner{padding:22px 16px 0}.head,.two,.summary,.foot{flex-direction:column;align-items:stretch}.doc{text-align:left}.doc img.ca{margin-left:0}}
  @media print{
    body{background:#fff!important}
    .no-print{display:none!important}
    .sheet{box-shadow:none;max-width:none;margin:0;font-size:12.5px}
    .inner{padding:14px 34px 0}
    .emblem img.ca{height:64px}
    .emblem img.own,.mono-logo{max-height:64px}
    .mono-logo{width:64px;height:64px;font-size:25px}
    .doc img.ca{height:48px;margin-bottom:5px}
    .firm{font-size:21px}
    .doc-title{font-size:28px}
    .rule{margin:12px 0 14px}
    .panel{padding:9px 14px}
    table.items{margin-top:16px}
    table.items thead th{padding:8px 12px}
    table.items tbody td{padding:7px 12px}
    .summary{margin-top:14px;break-inside:avoid}
    .total .v{font-size:21px}
    .foot{margin-top:20px;break-inside:avoid}
    .sign .space{height:92px}
    .terms{margin-top:12px}
    .band{margin-top:14px;padding:7px 12px}
    @page{size:A4;margin:7mm}
  }
</style>

<div class="bar no-print">
  <?php if (!$public): ?><a class="btn btn--light" href="<?= e($backUrl) ?>">← Back to <?= $isGroup ? 'group' : 'customer' ?></a><?php else: ?><span></span><?php endif; ?>
  <div class="flex gap-8 items-center">
    <?php if ($flash): ?><span class="pill pill--green"><?= e($flash) ?></span><?php endif; ?>
    <button class="btn btn--primary" onclick="window.print()">Print / Save as PDF</button>
  </div>
</div>

<?php if (!$public && !empty($share)): ?>
<div class="share no-print">
  <div class="t">Share this invoice on WhatsApp</div>
  <div class="row">
    <span class="cc">+91</span>
    <input class="input" id="waPhone" value="<?= e($share['phone']) ?>" placeholder="Customer's WhatsApp number" inputmode="numeric" autocomplete="off">
    <button type="button" class="btn wa" onclick="shareWa()">Share on WhatsApp</button>
    <button type="button" class="btn btn--light" id="copyBtn" onclick="copyLink()">Copy link</button>
    <form method="post" action="<?= url('/invoices/'.$inv['id'].'/reset-link') ?>" style="display:inline" onsubmit="return confirm('Create a new link? The link you shared earlier will stop working.')">
      <?= csrf_field() ?><button class="btn btn--ghost">Reset link</button></form>
  </div>
  <div class="hint">Opens WhatsApp with the customer's number and a ready message containing a private link to this invoice. You press Send from the WhatsApp that is open on your device. The number is taken from the customer's profile — change it above if needed.</div>
  <?php if (!empty($share['local'])): ?><div class="warn">This link uses a local address, so your customer cannot open it yet. Once the app is online, set the <b>Website address</b> under Settings → Invoice details (for example https://cahpgroup.in).</div><?php endif; ?>
</div>
<script>
  var WA = { msg: <?= json_encode($share['message'], JSON_UNESCAPED_UNICODE) ?>, link: <?= json_encode($share['link']) ?> };
  function shareWa(){
    var d = (document.getElementById('waPhone').value || '').replace(/\D/g, '');
    if (d.length === 11 && d.charAt(0) === '0') d = d.slice(1);
    if (d.length === 10) d = '91' + d;                                   // India
    var url = 'https://wa.me/' + (d.length >= 11 ? d : '') + '?text=' + encodeURIComponent(WA.msg);
    window.open(url, '_blank', 'noopener');
  }
  function copyLink(){
    var b = document.getElementById('copyBtn');
    function done(){ b.textContent = 'Copied ✓'; setTimeout(function(){ b.textContent = 'Copy link'; }, 1800); }
    if (navigator.clipboard) { navigator.clipboard.writeText(WA.link).then(done); }
    else { var t = document.createElement('textarea'); t.value = WA.link; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t); done(); }
  }
</script>
<?php endif; ?>

<div class="sheet">
  <div class="inner">
    <div class="head">
      <div class="brand">
        <div class="emblem">
          <?php if ($logoUrl): ?><img class="own" src="<?= e($logoUrl) ?>" alt="<?= e($s['firm_name']) ?>">
          <?php elseif ($caOn): ?><img class="ca" src="<?= e($caSrc) ?>" alt="CA India">
          <?php else: ?><div class="mono-logo"><?= e($initials ?: 'CA') ?></div><?php endif; ?>
        </div>
        <div>
          <div class="firm"><?= e($s['firm_name']) ?></div>
          <?php if ($s['tagline'] !== ''): ?><div class="tagline"><?= e($s['tagline']) ?></div><?php endif; ?>
          <div class="sub"><?= e($s['firm_address']) ?></div>
          <?php if ($s['firm_phone'] || $s['firm_email']): ?>
            <div class="sub"><?= $s['firm_phone'] ? 'Phone: ' . e($s['firm_phone']) : '' ?><?= ($s['firm_phone'] && $s['firm_email']) ? ' &nbsp;·&nbsp; ' : '' ?><?= $s['firm_email'] ? e($s['firm_email']) : '' ?></div>
          <?php endif; ?>
          <?php if ($s['firm_pan'] || $s['frn']): ?>
            <div class="sub"><?= $s['firm_pan'] ? 'PAN: ' . e($s['firm_pan']) : '' ?><?= ($s['firm_pan'] && $s['frn']) ? ' &nbsp;·&nbsp; ' : '' ?><?= $s['frn'] ? 'FRN: ' . e($s['frn']) : '' ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="doc">
        <?php if ($logoUrl && $caOn): ?><img class="ca" src="<?= e($caSrc) ?>" alt="CA India"><?php endif; ?>
        <div class="doc-title">INVOICE</div>
        <div class="doc-no">No. <b><?= e($inv['invoice_no']) ?></b></div>
      </div>
    </div>

    <div class="rule"></div>

    <div class="two">
      <div class="panel billto">
        <div class="label">Billed to</div>
        <div class="name"><?= e($inv['customer_name']) ?></div>
        <?php if ($inv['firm_name']): ?><div>Entity: <?= e($inv['firm_name']) ?></div><?php endif; ?>
        <?php if ($isGroup && $inv['group_name']): ?><div>Group: <b><?= e($inv['group_name']) ?></b></div><?php endif; ?>
        <?php if ($inv['address']): ?><div style="color:var(--muted)"><?= e($inv['address']) ?></div><?php endif; ?>
      </div>
      <div class="panel details">
        <div class="label">Invoice details</div>
        <dl class="dl" style="margin:0">
          <dt>Bill No</dt><dd><?= e($inv['invoice_no']) ?></dd>
          <dt>Date</dt><dd><?= e(date('d-m-Y', strtotime($inv['invoice_date']))) ?></dd>
          <?php if ($fyLong): ?><dt>Financial Year</dt><dd><?= e($fyLong) ?></dd><?php endif; ?>
        </dl>
      </div>
    </div>

    <table class="items">
      <thead><tr><th class="idx">S.No.</th><?php if ($isGroup): ?><th style="width:150px">Name</th><?php endif; ?><th><?= $isGroup ? 'Service / particulars' : 'Particulars' ?></th><th class="c" style="width:150px;white-space:nowrap">Financial Year</th><th class="r" style="width:130px;white-space:nowrap">Amount (₹)</th></tr></thead>
      <tbody>
        <?php foreach ($inv['items'] as $i => $it): ?>
          <tr><td class="idx"><?= $i + 1 ?></td><?php if ($isGroup): ?><td style="font-weight:600;color:var(--ink)"><?= e($it['member_name'] ?? '') ?></td><?php endif; ?><td><?= e($it['particulars']) ?></td><td class="c"><?= e($it['financial_year'] ?: '') ?></td><td class="r num"><?= $fmt($it['amount']) ?></td></tr>
        <?php endforeach; ?>
        <?php for ($k = 0; $k < $filler; $k++): ?><tr><td class="idx">&nbsp;</td><?= $isGroup ? '<td></td>' : '' ?><td></td><td></td><td></td></tr><?php endfor; ?>
      </tbody>
    </table>

    <div class="summary">
      <div class="words"><div class="label" style="margin:0">Amount in words</div><div class="t">Rupees <?= e($words) ?></div></div>
      <div class="total"><span class="l">Total</span><span class="v">₹ <?= $fmt($inv['total']) ?></span></div>
    </div>
    <?php if ($inv['notes']): ?><div class="note"><b>Note:</b> <?= e($inv['notes']) ?></div><?php endif; ?>

    <div class="foot">
      <div class="bank">
        <div class="label">Bank particulars</div>
        <table>
          <?php foreach ($bank as $l => $v): ?><tr><td><?= e($l) ?></td><td><?= e($v) ?></td></tr><?php endforeach; ?>
        </table>
      </div>
      <div class="sign">
        <div class="for">For <?= e(ucwords(strtolower($s['firm_name']))) ?></div>
        <div class="space"></div>
        <div class="who"><?= e($s['signatory']) ?></div>
        <div class="role">Authorised Signatory</div>
      </div>
    </div>

    <?php if ($s['terms'] !== ''): ?><div class="terms"><?= e($s['terms']) ?></div><?php endif; ?>
  </div>
  <div class="band">Thank you for your business &nbsp;·&nbsp; <?= e($s['firm_name']) ?><?= $s['firm_address'] ? ' &nbsp;·&nbsp; ' . e($s['firm_address']) : '' ?></div>
</div>
