<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
/**
 * Abstract Organic — PORTRAIT BACK. viewBox 0 0 540 856.
 * Header: tenant logo + company name. Then SCAN FOR MORE INFORMATION, QR, terms.
 */
$terms = trim((string) ($c['settings']['terms'] ?? ''));
$wrap = static function (string $text, int $width): array {
    $out = []; $line = '';
    foreach (preg_split('/\s+/', $text) as $w) {
        if ($line !== '' && mb_strlen($line . ' ' . $w) > $width) { $out[] = $line; $line = $w; }
        else { $line = $line === '' ? $w : $line . ' ' . $w; }
        if (count($out) >= 5) { break; }
    }
    if ($line !== '' && count($out) < 6) { $out[] = $line; }
    return $out;
};
$termLines = $wrap($terms, 42);
$coName = trim((string) ($c['company']['name'] ?? ''));
?>
<!-- ============ PORTRAIT BACK PATTERN ============ -->
<g class="pattern" aria-hidden="true">
  <path d="M-30,-30 C120,-46 150,70 96,120 C40,168 -20,120 -30,70 Z" fill="var(--c-primary)"/>
  <circle cx="70" cy="40" r="16" fill="var(--c-secondary)"/>
  <path d="M575,-30 C450,-44 430,90 512,120 C560,138 585,50 575,-30 Z" fill="var(--c-primary)"/>
  <path d="M470,-30 C556,-40 566,78 496,96 C440,110 410,20 470,-30 Z" fill="var(--c-dark)"/>
  <circle cx="500" cy="150" r="10" fill="var(--c-accent)"/>
  <circle cx="30" cy="430" r="15" fill="var(--c-light)"/>
  <circle cx="512" cy="470" r="24" fill="var(--c-secondary)" opacity="0.9"/>
  <circle cx="500" cy="360" r="13" fill="var(--c-light)"/>
  <circle cx="500" cy="800" r="94" fill="var(--c-light)"/>
  <path d="M-30,824 C70,792 150,820 150,880 L-30,880 Z" fill="var(--c-secondary)"/>
  <rect x="-40" y="792" width="180" height="120" rx="58" fill="var(--c-dark)"/>
  <circle cx="150" cy="792" r="12" fill="var(--c-accent)"/>
</g>

<!-- ============ HEADER: logo + company name ============ -->
<?php $headY = 46; if ($showLogo): ?>
  <image href="<?= $E($c['company']['logo']) ?>" xlink:href="<?= $E($c['company']['logo']) ?>"
         x="185" y="36" width="170" height="64" preserveAspectRatio="xMidYMid meet"/>
  <?php $headY = 128; endif; ?>
<?php if ($coName !== ''): ?>
  <text x="270" y="<?= $headY ?>" text-anchor="middle" fill="var(--c-dark)"
        font-family="Poppins,Segoe UI,sans-serif" font-size="<?= $fit($coName, 24, 14, 0.58, 420) ?>"
        font-weight="700" letter-spacing="1"><?= $E(mb_strtoupper($coName)) ?></text>
  <?php $headY += 34; endif; ?>

<!-- ============ SCAN + QR ============ -->
<text x="270" y="<?= $headY + 40 ?>" text-anchor="middle" fill="var(--c-muted)"
      font-family="Poppins,Segoe UI,sans-serif" font-size="18" letter-spacing="4" font-weight="500">SCAN FOR MORE</text>
<text x="270" y="<?= $headY + 76 ?>" text-anchor="middle" fill="var(--c-text)"
      font-family="Poppins,Segoe UI,sans-serif" font-size="28" letter-spacing="6" font-weight="700">INFORMATION</text>

<?php $qy = $headY + 104; if ($enableQr): ?>
  <rect x="160" y="<?= $qy ?>" width="220" height="220" rx="10" fill="#ffffff" stroke="var(--c-light)" stroke-width="2"/>
  <image class="idcard-qr" x="176" y="<?= $qy + 16 ?>" width="188" height="188" preserveAspectRatio="xMidYMid meet"/>
<?php else: ?>
  <text x="270" y="<?= $qy + 110 ?>" text-anchor="middle" fill="var(--c-muted)" font-family="Poppins,Segoe UI,sans-serif" font-size="18">QR verification disabled</text>
<?php endif; ?>

<!-- ============ TERMS ============ -->
<?php $tTop = $qy + 268; ?>
<text x="270" y="<?= $tTop ?>" text-anchor="middle" fill="var(--c-text)"
      font-family="Poppins,Segoe UI,sans-serif" font-size="22" letter-spacing="3" font-weight="700">TERMS &amp; CONDITIONS</text>
<?php $ty = $tTop + 30; foreach ($termLines as $tl): ?>
  <text x="270" y="<?= $ty ?>" text-anchor="middle" fill="var(--c-muted)"
        font-family="Poppins,Segoe UI,sans-serif" font-size="15"><?= $E($tl) ?></text>
  <?php $ty += 22; endforeach; ?>
