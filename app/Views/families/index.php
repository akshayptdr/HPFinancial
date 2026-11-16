<div class="page-head">
  <div><div class="crumb">Groups</div><h1>Groups</h1>
    <div class="muted" style="margin-top:4px">Keep relatives together under one contact person. Add people to a group from their Customer or Lead page.</div></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Group</th><th>Members</th><th>Contact</th><th>Payer</th><th>Billed</th><th>Received</th><th>Discount</th><th>To collect</th><th></th></tr></thead>
      <tbody>
      <?php if (!$families): ?>
        <tr><td colspan="9" class="muted" style="padding:28px;text-align:center">No groups yet. Open a customer or lead and use “Add to group”.</td></tr>
      <?php else: foreach ($families as $f): $s = $f['summary']; ?>
        <tr>
          <td class="cell-strong"><a href="<?= url('/families/'.$f['id']) ?>"><?= e($f['name']) ?></a></td>
          <td><?= (int)$f['member_count'] ?></td>
          <td><?= $f['contact'] ? e($f['contact']['name']).'<div class="cell-sub mono">'.e($f['contact']['mobile']).'</div>' : '—' ?></td>
          <td><?= $f['payer'] ? e($f['payer']['name']) : '—' ?></td>
          <td class="mono"><?= money($s['billed']) ?></td>
          <td class="mono" style="color:var(--success)"><?= money($s['received']) ?></td>
          <td class="mono"><?= $s['discount']>0 ? money($s['discount']) : '—' ?></td>
          <td class="mono" style="color:<?= $s['outstanding']>0?'var(--danger)':'var(--success)' ?>"><?= money($s['outstanding']) ?></td>
          <td class="text-right"><a class="btn btn--light btn--sm" href="<?= url('/families/'.$f['id']) ?>">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
