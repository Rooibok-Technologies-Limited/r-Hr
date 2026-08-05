<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
/**
 * Abstract Organic — LANDSCAPE BACK. viewBox 0 0 856 540.
 * Header: tenant logo + company name. Left: SCAN + QR. Right: TERMS.
 */
$terms = trim((string) ($c['settings']['terms'] ?? ''));
$wrap = static function (string $text, int $width): array {
    $out = []; $line = '';
    foreach (preg_split('/\s+/', $text) as $w) {
        if ($line !== '' && mb_strlen($line . ' ' . $w) > $width) { $out[] = $line; $line = $w; }
        else { $line = $line === '' ? $w : $line . ' ' . $w; }
        if (count($out) >= 7) { break; }
    }
    if ($line !== '' && count($out) < 8) { $out[] = $line; }
    return $out;
};
$termLines = $wrap($terms, 30);
$coName = trim((string) ($c['company']['name'] ?? ''));
?>
<!-- ============ LANDSCAPE BACK PATTERN ============ -->
<g class="pattern" aria-hidden="true">
  <path d="M-30,-30 C120,-46 160,70 100,120 C44,168 -20,110 -30,60 Z" fill="var(--c-primary)"/>
  <circle cx="80" cy="44" r="16" fill="var(--c-secondary)"/>
  <path d="M890,-30 C760,-44 740,80 826,116 C880,138 900,40 890,-30 Z" fill="var(--c-primary)"/>
  <path d="M770,-30 C876,-40 886,80 812,98 C756,112 720,20 770,-30 Z" fill="var(--c-dark)"/>
  <circle cx="806" cy="150" r="10" fill="var(--c-accent)"/>
  <circle cx="26" cy="300" r="14" fill="var(--c-light)"/>
  <circle cx="60" cy="500" r="80" fill="var(--c-light)"/>
  <path d="M-30,514 C80,486 160,510 150,560 L-30,560 Z" fill="var(--c-secondary)"/>
  <rect x="726" y="470" width="180" height="110" rx="55" fill="var(--c-dark)"/>
  <circle cx="770" cy="452" r="18" fill="var(--c-secondary)"/>
  <circle cx="820" cy="470" r="9" fill="var(--c-accent)"/>
</g>

<!-- ============ HEADER: logo + company name ============ -->
<?php $base = 64; if ($showLogo): ?>
  <image href="<?= $E($c['company']['logo']) ?>" xlink:href="<?= $E($c['company']['logo']) ?>"
         x="343" y="24" width="170" height="52" preserveAspectRatio="xMidYMid meet"/>
  <?php $base = 96; endif; ?>
<?php if ($coName !== ''): ?>
  <text x="428" y="<?= $base ?>" text-anchor="middle" fill="var(--c-dark)"
        font-family="Poppins,Segoe UI,sans-serif" font-size="<?= $fit($coName, 22, 13, 0.56, 640) ?>"
        font-weight="700" letter-spacing="1"><?= $E(mb_strtoupper($coName)) ?></text>
  <?php $base += 26; endif; ?>

<!-- ============ LEFT: scan + QR ============ -->
<text x="252" y="<?= $base + 34 ?>" text-anchor="middle" fill="var(--c-muted)"
      font-family="Poppins,Segoe UI,sans-serif" font-size="16" letter-spacing="4" font-weight="500">SCAN FOR MORE</text>
<text x="252" y="<?= $base + 62 ?>" text-anchor="middle" fill="var(--c-text)"
      font-family="Poppins,Segoe UI,sans-serif" font-size="24" letter-spacing="5" font-weight="700">INFORMATION</text>
<?php $qy = $base + 84; if ($enableQr): ?>
  <rect x="158" y="<?= $qy ?>" width="188" height="188" rx="10" fill="#ffffff" stroke="var(--c-light)" stroke-width="2"/>
  <image class="idcard-qr" x="174" y="<?= $qy + 16 ?>" width="156" height="156" preserveAspectRatio="xMidYMid meet"/>
<?php else: ?>
  <text x="252" y="<?= $qy + 90 ?>" text-anchor="middle" fill="var(--c-muted)" font-family="Poppins,Segoe UI,sans-serif" font-size="15">QR disabled</text>
<?php endif; ?>

<!-- ============ RIGHT: terms ============ -->
<text x="605" y="<?= $base + 40 ?>" text-anchor="middle" fill="var(--c-text)"
      font-family="Poppins,Segoe UI,sans-serif" font-size="21" letter-spacing="2" font-weight="700">TERMS &amp; CONDITIONS</text>
<?php $ty = $base + 78; foreach ($termLines as $tl): ?>
  <text x="605" y="<?= $ty ?>" text-anchor="middle" fill="var(--c-muted)"
        font-family="Poppins,Segoe UI,sans-serif" font-size="15"><?= $E($tl) ?></text>
  <?php $ty += 22; endforeach; ?>
