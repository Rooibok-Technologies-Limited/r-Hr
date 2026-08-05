<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Single ID-card FACE renderer (pure SVG). Inputs (passed as view data):
 *   $c    array  card data bundle (App\Libraries\IdCardService::buildCardData)
 *   $side string 'front' | 'back'
 * Orientation comes from $c['orientation']. Geometry lives entirely in the
 * per-orientation template partials; colours come in as CSS custom properties on
 * the <svg> so a branding change never alters the pattern geometry.
 *
 * The <svg> carries data-qr / data-status so the page script can inject the QR
 * bitmap and any status banner without re-fetching.
 */
helper('main');

$side = ($side ?? 'front') === 'back' ? 'back' : 'front';
$o    = (($c['orientation'] ?? 'portrait') === 'landscape') ? 'landscape' : 'portrait';

// CR80 coordinate systems (10 units ≈ 1mm).
if ($o === 'landscape') { $VBW = 856; $VBH = 540; } else { $VBW = 540; $VBH = 856; }

$s = $c['settings'] ?? [];
$t = [
    'primary'   => hex_color($s['color_primary']   ?? '#E07B54', '#E07B54'),
    'secondary' => hex_color($s['color_secondary'] ?? '#A7C49A', '#A7C49A'),
    'accent'    => hex_color($s['color_accent']    ?? '#E8A07E', '#E8A07E'),
    'dark'      => hex_color($s['color_dark']      ?? '#3B5A45', '#3B5A45'),
    'light'     => hex_color($s['color_light']     ?? '#D8D2C7', '#D8D2C7'),
    'bg'        => hex_color($s['color_bg']        ?? '#ECE8E1', '#ECE8E1'),
    'text'      => hex_color($s['color_text']      ?? '#23201C', '#23201C'),
    'muted'     => hex_color($s['color_muted']     ?? '#6E6A63', '#6E6A63'),
];
$f = $s['fields'] ?? [];
$showLogo = (int) ($s['show_logo'] ?? 1) === 1 && ! empty($c['company']['logo']);
$enableQr = (int) ($s['enable_qr'] ?? 1) === 1;

// --- tiny view helpers available to the templates -------------------------
$E = static fn($v) => esc((string) $v);                       // text/attr escape
/** Auto-fit font size for a label to a target box width (proportional). */
$fit = static function (string $text, float $max, float $min, float $perChar, float $boxW) {
    $len = max(1, mb_strlen(trim($text)));
    $size = min($max, $boxW / ($len * $perChar));
    return round(max($min, $size), 1);
};
$tpl = __DIR__ . '/templates/abstract_organic/' . $o . '_' . $side . '.php';
?>
<svg class="idcard-face idcard-<?= $o ?> idcard-<?= $side ?>"
     xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
     viewBox="0 0 <?= $VBW ?> <?= $VBH ?>" preserveAspectRatio="xMidYMid meet"
     data-orientation="<?= $o ?>" data-side="<?= $side ?>"
     data-qr="<?= $E($c['verify_url'] ?? '') ?>" data-status="<?= $E($c['status'] ?? 'active') ?>"
     style="--c-primary:<?= $t['primary'] ?>;--c-secondary:<?= $t['secondary'] ?>;--c-accent:<?= $t['accent'] ?>;--c-dark:<?= $t['dark'] ?>;--c-light:<?= $t['light'] ?>;--c-bg:<?= $t['bg'] ?>;--c-text:<?= $t['text'] ?>;--c-muted:<?= $t['muted'] ?>;">
  <defs>
    <clipPath id="photoclip-<?= $o ?>-<?= $side ?>"><rect id="photoclip-rect" x="0" y="0" width="1" height="1"/></clipPath>
  </defs>
  <rect x="0" y="0" width="<?= $VBW ?>" height="<?= $VBH ?>" fill="var(--c-bg)"/>
  <?php include $tpl; ?>
</svg>
