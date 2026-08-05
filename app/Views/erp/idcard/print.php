<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
/**
 * Standalone printable Staff ID Card (front + back) for one employee.
 * Print-ready: exact CR80 @page sizing per orientation, front on page 1, back on
 * page 2. Vector SVG stays crisp at any DPI; "Save as PDF" from the print dialog
 * yields a print-ready file. PNG export via the toolbar (300 DPI).
 */
$o    = (($c['orientation'] ?? 'portrait') === 'landscape') ? 'landscape' : 'portrait';
$pageW = $o === 'landscape' ? '85.6mm' : '53.98mm';
$pageH = $o === 'landscape' ? '53.98mm' : '85.6mm';
$boxW  = $o === 'landscape' ? 420 : 300; // on-screen preview width (px)
$name  = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: 'card';
$slug  = preg_replace('/[^A-Za-z0-9]+/', '-', $name . '-' . ($c['staff_id'] ?? ''));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($name) ?> — Staff ID Card</title>
<style>
  :root { --pw: <?= $boxW ?>px; }
  * { box-sizing: border-box; }
  body { margin:0; background:#e9e7e2; font-family:'Poppins',Segoe UI,sans-serif; color:#23201c; }
  .toolbar { position:sticky; top:0; display:flex; gap:8px; flex-wrap:wrap; align-items:center;
             padding:12px 16px; background:#fff; border-bottom:1px solid #e2ded6; box-shadow:0 1px 4px rgba(0,0,0,.05); z-index:10; }
  .toolbar .sp { flex:1; } .toolbar h1 { font-size:15px; margin:0 12px 0 0; font-weight:600; }
  .btn { border:0; border-radius:8px; padding:8px 14px; font-size:13px; font-weight:600; cursor:pointer; background:#3B5A45; color:#fff; }
  .btn.alt { background:#fff; color:#3B5A45; border:1px solid #cdd8cf; }
  .stage { display:flex; gap:28px; flex-wrap:wrap; justify-content:center; padding:32px 16px; }
  .pcard { width:var(--pw); }
  .pcard .cap { text-align:center; font-size:11px; letter-spacing:2px; color:#8a857c; text-transform:uppercase; margin-bottom:8px; }
  .pcard .face { width:var(--pw); aspect-ratio: <?= $o === 'landscape' ? '856/540' : '540/856' ?>;
                 border-radius:10px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,.14); background:#ECE8E1; }
  .pcard .face svg { display:block; width:100%; height:100%; }
  @media print {
    @page { size: <?= $pageW ?> <?= $pageH ?>; margin:0; }
    body { background:#fff; }
    .toolbar { display:none; }
    .stage { display:block; padding:0; gap:0; }
    .pcard { width:<?= $pageW ?>; page-break-after: always; }
    .pcard .cap { display:none; }
    .pcard .face { width:<?= $pageW ?>; height:<?= $pageH ?>; border-radius:0; box-shadow:none; }
  }
</style>
</head>
<body>
  <div class="toolbar">
    <h1>Staff ID Card — <?= esc($name) ?></h1>
    <span class="sp"></span>
    <button class="btn alt" onclick="IdCard.exportPNG(document.getElementById('face-front'),'<?= esc($slug) ?>-front.png',300)">PNG Front</button>
    <button class="btn alt" onclick="IdCard.exportPNG(document.getElementById('face-back'),'<?= esc($slug) ?>-back.png',300)">PNG Back</button>
    <button class="btn" onclick="IdCard.print()">Print / Save PDF</button>
  </div>
  <div class="stage">
    <div class="pcard">
      <div class="cap">Front</div>
      <div class="face" id="face-front"><?= view('erp/idcard/face', ['c' => $c, 'side' => 'front']) ?></div>
    </div>
    <div class="pcard">
      <div class="cap">Back</div>
      <div class="face" id="face-back"><?= view('erp/idcard/face', ['c' => $c, 'side' => 'back']) ?></div>
    </div>
  </div>
  <script src="<?= base_url('public/assets/plugins/qrcode/qrcode.min.js') ?>"></script>
  <script src="<?= base_url('public/module_scripts/idcard.js') ?>"></script>
</body>
</html>
