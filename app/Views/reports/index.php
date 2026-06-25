<div class="page-head"><div><div class="crumb">Home · Reports</div><h1>Reports</h1></div></div>
<div class="grid grid--3">
  <?php
  $cards = [
    ['fees','Fees / Collection','bg-green','Collected vs outstanding, by service.'],
    ['leads','Leads Report','bg-blue','Pipeline counts by status.'],
    ['customers','Customers by Service','bg-violet','How many clients per service.'],
    ['services','Service / Job Report','bg-amber','Job volume per service.'],
    ['employees','Employee Performance','bg-sky','Jobs handled & fees collected.'],
  ];
  foreach ($cards as [$t,$label,$bg,$desc]): ?>
    <a href="<?= url('/reports/'.$t) ?>" class="card" style="text-decoration:none;color:inherit"><div class="card__body">
      <div class="stat__icon <?= $bg ?>"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 9.2h3V19H5zM10.6 5h3v14h-3zm5.6 8H19v6h-3z"/></svg></div>
      <h3 style="font-size:16px"><?= e($label) ?></h3><p class="muted small mt-8"><?= e($desc) ?></p>
    </div></a>
  <?php endforeach; ?>
</div>
