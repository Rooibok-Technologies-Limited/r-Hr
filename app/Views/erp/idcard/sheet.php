<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
/**
 * Bulk print sheet — many employees' cards laid out for batch printing.
 * Cards keep exact CR80 physical size; the sheet flows onto A4 pages.
 */
$o = (($cards[0]['orientation'] ?? 'portrait') === 'landscape') ? 'landscape' : 'portrait';
$cw = $o === 'landscape' ? '85.6mm' : '53.98mm';
$ch = $o === 'landscape' ? '53.98mm' : '85.6mm';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Staff ID Cards — Batch (<?= count($cards) ?>)</title>
<style>
  * { box-sizing:border-box; }
  body { margin:0; background:#e9e7e2; font-family:'Poppins',Segoe UI,sans-serif; }
  .toolbar { position:sticky; top:0; display:flex; gap:8px; align-items:center; padding:12px 16px; background:#fff; border-bottom:1px solid #e2ded6; z-index:10; }
  .toolbar h1 { font-size:15px; margin:0; flex:1; }
  .btn { border:0; border-radius:8px; padding:8px 14px; font-weight:600; cursor:pointer; background:#3B5A45; color:#fff; }
  .grid { display:flex; flex-wrap:wrap; gap:10mm; padding:12mm; justify-content:flex-start; }
  .cell { width:<?= $cw ?>; }
  .cell .face { width:<?= $cw ?>; height:<?= $ch ?>; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,.12); background:#ECE8E1; }
  .cell .face svg { display:block; width:100%; height:100%; }
  .cell .cap { font-size:9px; color:#8a857c; text-align:center; margin:2px 0; text-transform:uppercase; letter-spacing:1px; }
  @media print {
    @page { size:A4; margin:8mm; }
    body { background:#fff; }
    .toolbar { display:none; }
    .grid { gap:6mm; padding:0; }
    .cell .face { box-shadow:none; }
    .cell .cap { display:none; }
  }
</style>
</head>
<body>
  <div class="toolbar"><h1>Staff ID Cards — <?= count($cards) ?> card(s), <?= esc($o) ?></h1>
    <button class="btn" onclick="window.print()">Print / Save PDF</button></div>
  <div class="grid">
    <?php foreach ($cards as $c): ?>
      <div class="cell"><div class="cap">Front</div><div class="face"><?= view('erp/idcard/face', ['c' => $c, 'side' => 'front']) ?></div></div>
      <div class="cell"><div class="cap">Back</div><div class="face"><?= view('erp/idcard/face', ['c' => $c, 'side' => 'back']) ?></div></div>
    <?php endforeach; ?>
  </div>
  <script src="<?= base_url('public/assets/plugins/qrcode/qrcode.min.js') ?>"></script>
  <script src="<?= base_url('public/module_scripts/idcard.js') ?>"></script>
</body>
</html>
