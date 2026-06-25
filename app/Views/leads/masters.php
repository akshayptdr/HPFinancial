<div class="page-head" style="max-width:900px">
  <div><div class="crumb"><a href="<?= url('/leads') ?>">Leads</a> · Masters</div><h1>Lead Masters</h1></div>
</div>
<div class="grid grid--2" style="max-width:900px">
  <div class="card">
    <div class="card__head"><h3>Lead Types</h3></div>
    <div class="card__body">
      <form method="post" action="<?= url('/lead-masters') ?>" class="flex gap-8" style="margin-bottom:14px">
        <?= csrf_field() ?><input type="hidden" name="kind" value="type">
        <input class="input" name="name" placeholder="New lead type"><button class="btn btn--primary">Add</button>
      </form>
      <table class="tbl"><tbody>
        <?php foreach ($types as $t): ?><tr><td class="cell-strong"><?= e($t['name']) ?></td><td class="text-right"><?= status_pill($t['status']) ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
  <div class="card">
    <div class="card__head"><h3>Interested Categories</h3></div>
    <div class="card__body">
      <form method="post" action="<?= url('/lead-masters') ?>" class="flex gap-8" style="margin-bottom:14px">
        <?= csrf_field() ?><input type="hidden" name="kind" value="category">
        <input class="input" name="name" placeholder="New category"><button class="btn btn--primary">Add</button>
      </form>
      <table class="tbl"><tbody>
        <?php foreach ($categories as $c): ?><tr><td class="cell-strong"><?= e($c['name']) ?></td><td class="text-right"><?= status_pill($c['status']) ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
</div>
