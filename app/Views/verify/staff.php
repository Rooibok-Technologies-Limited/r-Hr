<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
/**
 * PUBLIC staff-card verification page (rendered from a scanned QR). Shows only
 * whitelisted, non-sensitive fields and a real-time status badge.
 */
$found = is_array($v);
$status = $found ? ($v['status'] ?? 'active') : 'invalid';
$badge = [
    'active'   => ['#2f7d4f', '#e6f4ec', 'ACTIVE'],
    'expired'  => ['#a5741b', '#fbf1dc', 'EXPIRED'],
    'revoked'  => ['#b3352f', '#fce8e6', 'CARD REVOKED'],
    'inactive' => ['#5b5b5b', '#ececec', 'INACTIVE'],
    'invalid'  => ['#b3352f', '#fce8e6', 'INVALID CARD'],
    'not_issued' => ['#5b5b5b', '#ececec', 'NOT ISSUED'],
][$status] ?? ['#5b5b5b', '#ececec', strtoupper((string) $status)];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff Verification</title>
<style>
  :root { color-scheme: light; }
  * { box-sizing:border-box; }
  body { margin:0; min-height:100vh; background:#ECE8E1; font-family:'Poppins',Segoe UI,system-ui,sans-serif;
         color:#23201c; display:flex; align-items:flex-start; justify-content:center; padding:24px 16px; }
  .card { width:100%; max-width:420px; background:#fff; border-radius:18px; overflow:hidden;
          box-shadow:0 12px 40px rgba(0,0,0,.12); }
  .head { background:#3B5A45; color:#fff; padding:22px 24px; display:flex; align-items:center; gap:14px; }
  .head img { height:40px; max-width:120px; object-fit:contain; background:#fff; border-radius:6px; padding:3px; }
  .head .co { font-size:16px; font-weight:600; letter-spacing:.3px; }
  .head .sub { font-size:11px; opacity:.85; letter-spacing:2px; text-transform:uppercase; }
  .body { padding:24px; }
  .who { display:flex; gap:16px; align-items:center; margin-bottom:18px; }
  .who img { width:84px; height:100px; object-fit:cover; border-radius:8px; border:3px solid #A7C49A; background:#f0ede7; }
  .who h1 { font-size:20px; margin:0 0 4px; }
  .who .pos { color:#6E6A63; font-size:13px; }
  .badge { display:inline-block; padding:6px 14px; border-radius:999px; font-size:12px; font-weight:700; letter-spacing:1px; }
  .rows { margin-top:18px; border-top:1px solid #eee; }
  .row { display:flex; justify-content:space-between; padding:11px 2px; border-bottom:1px solid #f2f0eb; font-size:14px; }
  .row .k { color:#8a857c; } .row .v { font-weight:600; text-align:right; }
  .foot { text-align:center; font-size:11px; color:#a49f96; padding:16px; }
  .empty { text-align:center; padding:40px 24px; }
  .empty .x { font-size:40px; }
</style>
</head>
<body>
  <div class="card">
    <?php if (! $found): ?>
      <div class="head"><span class="co">Staff Verification</span></div>
      <div class="empty">
        <div class="x">⚠️</div>
        <p style="font-weight:600;margin:12px 0 4px;">This card could not be verified.</p>
        <p style="color:#8a857c;font-size:13px;margin:0;">The verification code is invalid or the card no longer exists.</p>
        <div style="margin-top:16px;"><span class="badge" style="color:<?= $badge[0] ?>;background:<?= $badge[1] ?>;"><?= esc($badge[2]) ?></span></div>
      </div>
    <?php else: ?>
      <div class="head">
        <?php if (! empty($v['company_logo'])): ?><img src="<?= esc($v['company_logo']) ?>" alt=""><?php endif; ?>
        <div><div class="co"><?= esc($v['company_name'] ?: 'Company') ?></div><div class="sub">Staff Verification</div></div>
      </div>
      <div class="body">
        <div class="who">
          <img src="<?= esc($v['photo_url']) ?>" alt="">
          <div>
            <h1><?= esc($v['full_name']) ?></h1>
            <div class="pos"><?= esc($v['position'] ?: '—') ?></div>
            <div style="margin-top:8px;"><span class="badge" style="color:<?= $badge[0] ?>;background:<?= $badge[1] ?>;"><?= esc($badge[2]) ?></span></div>
          </div>
        </div>
        <div class="rows">
          <div class="row"><span class="k">Staff ID</span><span class="v"><?= esc($v['staff_id']) ?></span></div>
          <?php if (! empty($v['department'])): ?><div class="row"><span class="k">Department</span><span class="v"><?= esc($v['department']) ?></span></div><?php endif; ?>
          <?php if (! empty($v['join_date'])): ?><div class="row"><span class="k">Joined</span><span class="v"><?= esc($v['join_date']) ?></span></div><?php endif; ?>
          <?php if (! empty($v['expiry_date'])): ?><div class="row"><span class="k">Valid until</span><span class="v"><?= esc($v['expiry_date']) ?></span></div><?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    <div class="foot">Verified in real time · <?= esc(date('d M Y, H:i')) ?></div>
  </div>
</body>
</html>
