<?php
/** Family box shown on customer and lead pages. Expects $familyBox from Family::boxFor(). */
$fb = $familyBox; $fam = $fb['family']; $ftype = $fb['type']; $fm = $fb['member'];
?>
<div class="card" style="margin-bottom:18px">
  <div class="card__body" style="padding:14px 18px">
  <?php if ($fam): $fs = $fb['summary']; ?>
    <div class="flex between items-center" style="flex-wrap:wrap;gap:12px">
      <div>
        <div class="cell-sub">Group</div>
        <div class="cell-strong"><a href="<?= url('/families/'.$fam['id']) ?>"><?= e($fam['name']) ?></a></div>
      </div>
      <div>
        <div class="cell-sub">Contact person</div>
        <div class="cell-strong"><?= !empty($fb['contact']) ? e($fb['contact']['name']).' <span class="muted mono small">'.e($fb['contact']['mobile']).'</span>' : '—' ?></div>
      </div>
      <div>
        <div class="cell-sub">Pays</div>
        <div class="cell-strong"><?= !empty($fb['payer']) ? e($fb['payer']['name']) : '—' ?></div>
      </div>
      <div>
        <div class="cell-sub">Group outstanding</div>
        <div class="cell-strong mono" style="color:<?= $fs['outstanding']>0?'var(--danger)':'var(--success)' ?>"><?= money($fs['outstanding']) ?></div>
      </div>
      <a class="btn btn--light btn--sm" href="<?= url('/families/'.$fam['id']) ?>">View group</a>
    </div>
  <?php else: ?>
    <div class="flex between items-center" style="flex-wrap:wrap;gap:12px">
      <div>
        <div class="cell-strong">Not in a group</div>
        <div class="muted small">Link relatives to share one contact person and collect dues from one payer.</div>
        <?php foreach ($fb['matches'] as $mf): ?>
          <form method="post" action="<?= url('/families/assign') ?>" style="margin-top:8px">
            <?= csrf_field() ?>
            <input type="hidden" name="member_type" value="<?= e($ftype) ?>"><input type="hidden" name="member_id" value="<?= (int)$fm['id'] ?>">
            <input type="hidden" name="family_id" value="<?= (int)$mf['id'] ?>">
            <span class="pill pill--amber">Possible match</span> shares a phone/address with <b><?= e($mf['name']) ?></b>
            <?php if (can('customers.edit')): ?><button class="btn btn--outline btn--sm" style="margin-left:6px">Add to this group</button><?php endif; ?>
          </form>
        <?php endforeach; ?>
      </div>
      <?php if (can('customers.edit')): ?>
      <form method="post" action="<?= url('/families/assign') ?>" class="flex gap-8 items-center" style="flex-wrap:wrap">
        <?= csrf_field() ?>
        <input type="hidden" name="member_type" value="<?= e($ftype) ?>"><input type="hidden" name="member_id" value="<?= (int)$fm['id'] ?>">
        <div class="select-wrap"><select class="select" name="family_id" id="famSel" onchange="document.getElementById('famNew').style.display=this.value==='new'?'':'none'">
          <option value="new">+ Create new group</option>
          <?php foreach ($fb['families'] as $o): ?><option value="<?= (int)$o['id'] ?>"><?= e($o['name']) ?></option><?php endforeach; ?>
        </select></div>
        <input class="input" id="famNew" name="name" placeholder="Group name (optional)" style="width:200px">
        <button class="btn btn--primary btn--sm">Add to group</button>
      </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  </div>
</div>
