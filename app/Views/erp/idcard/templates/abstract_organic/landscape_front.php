<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
/**
 * Abstract Organic — LANDSCAPE FRONT. viewBox 0 0 856 540.
 * Photo left; content column right: logo → full name → position → ID strip → dates.
 * Name one line unless >2 names (then wraps).
 */
$colX = ! empty($f['photo']) ? 340 : 90;
$colW = 856 - $colX - 70;

$fnFull = trim((string) ($c['first_name'] ?? '') . ' ' . (string) ($c['last_name'] ?? ''));
$fnFull = mb_strtoupper($fnFull) ?: 'STAFF MEMBER';
$words  = preg_split('/\s+/', $fnFull);
if (count($words) <= 2) { $nameLines = [implode(' ', $words)]; }
else { $half = (int) ceil(count($words) / 2); $nameLines = [implode(' ', array_slice($words, 0, $half)), implode(' ', array_slice($words, $half))]; }
$longest = '';
foreach ($nameLines as $nl) { if (mb_strlen($nl) > mb_strlen($longest)) { $longest = $nl; } }
$nameSize = $fit($longest, 44, 22, 0.58, $colW);

$pos    = trim((string) ($c['position'] ?? ''));
$posT   = $pos !== '' ? mb_strtoupper($pos) : '';
$posSize= $posT !== '' ? $fit($posT, 20, 12, 0.58, $colW) : 0;
$idText = 'ID. ' . ($c['staff_id'] ?? '');
$idSize = $fit($idText, 22, 12, 0.55, 230);

$nameTop = 176;
$lineGap = $nameSize + 6;
$nameBottom = $nameTop + (count($nameLines) - 1) * $lineGap;
$posY    = $nameBottom + ($posT !== '' ? 30 : 0);
$stripTop= $posY + ($posT !== '' ? 18 : 24);
$stripTy = $stripTop + 26;
$datesY  = $stripTop + 62;
?>
<!-- ============ LANDSCAPE FRONT PATTERN ============ -->
<g class="pattern" aria-hidden="true">
  <path d="M-30,-30 C120,-50 200,50 150,120 C110,175 20,150 -30,120 Z" fill="var(--c-primary)"/>
  <path d="M60,-40 C165,-46 180,70 104,90 C40,104 6,30 60,-40 Z" fill="var(--c-dark)"/>
  <path d="M890,-30 C770,-44 740,70 820,104 C876,128 900,40 890,-30 Z" fill="var(--c-primary)"/>
  <circle cx="812" cy="70" r="18" fill="var(--c-secondary)"/>
  <circle cx="760" cy="150" r="9" fill="var(--c-accent)"/>
  <path d="M210,20 C120,90 170,180 96,240" fill="none" stroke="#20201E" stroke-width="2.2" stroke-linecap="round" opacity="0.5"/>
  <path d="M700,520 C620,460 680,410 620,372" fill="none" stroke="#20201E" stroke-width="2.2" stroke-linecap="round" opacity="0.45"/>
  <circle cx="-6" cy="300" r="60" fill="var(--c-light)"/>
  <circle cx="836" cy="330" r="26" fill="var(--c-secondary)" opacity="0.9"/>
  <circle cx="806" cy="250" r="13" fill="var(--c-light)"/>
  <circle cx="46" cy="500" r="80" fill="var(--c-light)"/>
  <path d="M-30,512 C90,486 170,510 150,560 L-30,560 Z" fill="var(--c-primary)"/>
  <circle cx="120" cy="512" r="18" fill="var(--c-secondary)"/>
  <rect x="720" y="470" width="200" height="110" rx="55" fill="var(--c-dark)"/>
  <circle cx="770" cy="452" r="20" fill="var(--c-secondary)"/>
  <circle cx="720" cy="430" r="9" fill="var(--c-accent)"/>
</g>

<!-- ============ CONTENT ============ -->
<?php if ($showLogo): ?>
  <image href="<?= $E($c['company']['logo']) ?>" xlink:href="<?= $E($c['company']['logo']) ?>"
         x="<?= $colX ?>" y="46" width="190" height="72" preserveAspectRatio="xMinYMid meet"/>
<?php endif; ?>

<?php if (! empty($f['photo'])): ?>
  <clipPath id="lf-photo"><rect x="76" y="121" width="218" height="298" rx="3"/></clipPath>
  <rect x="70" y="115" width="230" height="310" rx="6" fill="#ffffff"/>
  <?php if (! empty($c['photo_url'])): ?>
    <image href="<?= $E($c['photo_url']) ?>" xlink:href="<?= $E($c['photo_url']) ?>"
           x="76" y="121" width="218" height="298" preserveAspectRatio="xMidYMid slice" clip-path="url(#lf-photo)"/>
  <?php else: ?>
    <rect x="76" y="121" width="218" height="298" fill="var(--c-light)"/>
    <circle cx="185" cy="220" r="46" fill="var(--c-muted)" opacity="0.5"/>
    <path d="M100,419 C100,344 270,344 270,419 Z" fill="var(--c-muted)" opacity="0.5"/>
  <?php endif; ?>
  <rect x="76" y="121" width="218" height="298" rx="3" fill="none" stroke="var(--c-secondary)" stroke-width="7"/>
<?php endif; ?>

<?php if (! empty($f['name'])): $ny = $nameTop; foreach ($nameLines as $nl): ?>
  <text x="<?= $colX ?>" y="<?= $ny ?>" fill="var(--c-text)" font-family="Poppins,Segoe UI,sans-serif"
        font-weight="700" font-size="<?= $nameSize ?>" letter-spacing="1"><?= $E($nl) ?></text>
  <?php $ny += $lineGap; endforeach; endif; ?>

<?php if (! empty($f['position']) && $posT !== ''): ?>
  <text x="<?= $colX ?>" y="<?= $posY ?>" fill="var(--c-muted)" font-family="Poppins,Segoe UI,sans-serif"
        font-size="<?= $posSize ?>" letter-spacing="3" font-weight="500"><?= $E($posT) ?></text>
<?php endif; ?>

<?php if (! empty($f['staff_id'])): ?>
  <rect x="<?= $colX ?>" y="<?= $stripTop ?>" width="250" height="38" rx="9" fill="var(--c-secondary)"/>
  <text x="<?= $colX + 18 ?>" y="<?= $stripTy ?>" fill="var(--c-dark)" font-family="Poppins,Segoe UI,sans-serif"
        font-size="<?= $idSize ?>" font-weight="600" letter-spacing="1"><?= $E($idText) ?></text>
<?php endif; ?>

<?php
  $lines = [];
  if (! empty($f['join_date']))   { $lines[] = 'JOIN: '   . (($c['join_date'] ?? '')   ?: '--'); }
  if (! empty($f['expiry_date'])) { $lines[] = 'EXPIRE: ' . (($c['expiry_date'] ?? '') ?: '--'); }
  if (! empty($f['date_of_birth']) && ! empty($c['dob'])) { $lines[] = 'DOB: ' . $c['dob']; }
  if (! empty($f['department']) && ! empty($c['department'])) { $lines[] = mb_strtoupper($c['department']); }
  if (! empty($f['phone']) && ! empty($c['phone']))          { $lines[] = 'TEL: ' . $c['phone']; }
  if (! empty($f['blood_group']) && ! empty($c['blood_group'])) { $lines[] = 'BLOOD: ' . $c['blood_group']; }
  $ly = $datesY;
  foreach ($lines as $ln2):
?>
  <text x="<?= $colX ?>" y="<?= $ly ?>" fill="var(--c-text)" font-family="Poppins,Segoe UI,sans-serif"
        font-size="19" letter-spacing="0.5"><?= $E($ln2) ?></text>
  <?php $ly += 27; endforeach; ?>
