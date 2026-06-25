<div class="page-head">
  <div><div class="crumb">Administration · Roles &amp; Access</div><h1>Roles &amp; Permissions</h1></div>
</div>
<form method="post" action="<?= url('/roles') ?>">
  <?= csrf_field() ?>
  <div class="card" style="max-width:760px">
    <div class="card__head"><h3>Permission Matrix</h3><button class="btn btn--primary btn--sm" type="submit">Save Changes</button></div>
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th>Permission</th><th style="text-align:center">Admin</th><th style="text-align:center">Employee</th></tr></thead>
        <tbody>
        <?php foreach ($permissions as $p): ?>
          <tr>
            <td class="cell-strong"><?= e($p['name']) ?> <span class="muted small mono">(<?= e($p['slug']) ?>)</span></td>
            <td style="text-align:center"><input type="checkbox" checked disabled style="width:18px;height:18px"></td>
            <td style="text-align:center"><input type="checkbox" name="perms[]" value="<?= $p['id'] ?>" <?= in_array((int)$p['id'],$empPerms,true)?'checked':'' ?> style="width:18px;height:18px;accent-color:var(--primary)"></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card__body"><div class="muted small">Admin always has full access. Employees additionally see only records assigned to them.</div></div>
  </div>
</form>
