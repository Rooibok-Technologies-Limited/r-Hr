<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
/**
 * Abstract Organic — PORTRAIT FRONT. viewBox 0 0 540 856.
 * Order: tenant logo (top) → photo → full name → position → ID strip → dates.
 * Name is one line unless the person has >2 names (then it wraps).
 */
$fnFull = trim((string) ($c['first_name'] ?? '') . ' ' . (string) ($c['last_name'] ?? ''));
$fnFull = mb_strtoupper($fnFull) ?: 'STAFF MEMBER';
$words  = preg_split('/\s+/', $fnFull);
if (count($words) <= 2) {
    $nameLines = [implode(' ', $words)];
} else {
    $half = (int) ceil(count($words) / 2);
    $nameLines = [implode(' ', array_slice($words, 0, $half)), implode(' ', array_slice($words, $half))];
}
$longest  = '';
foreach ($nameLines as $nl) { if (mb_strlen($nl) > mb_strlen($longest)) { $longest = $nl; } }
$nameSize = $fit($longest, 46, 22, 0.60, 410);

$pos    = trim((string) ($c['position'] ?? ''));
$posT   = $pos !== '' ? mb_strtoupper($pos) : '';
$posSize= $posT !== '' ? $fit($posT, 21, 12, 0.60, 340) : 0;
$idText = 'ID. ' . ($c['staff_id'] ?? '');
$idSize = $fit($idText, 23, 12, 0.56, 200);

// Rule-of-thirds rhythm: logo (top), photo straddling the upper third (~285),
// name+position at centre, ID strip on the lower-third line (~570), dates + bottom
// pattern filling the last third. Photo now ends at y=406 (lifted to kill the top gap).
$nameTop = 470;
$lineGap = $nameSize + 6;
$nameBottom = $nameTop + (count($nameLines) - 1) * $lineGap;
$posY    = $nameBottom + ($posT !== '' ? 36 : 0);
$stripTop= $posY + ($posT !== '' ? 26 : 30);
$stripTy = $stripTop + 27;
$datesY  = $stripTop + 70;
?>
<!-- ============ PORTRAIT FRONT PATTERN ============ -->
<g class="pattern" aria-hidden="true">
  <path d="M-30,-30 C130,-52 210,70 150,150 C110,205 20,180 -30,150 Z" fill="var(--c-primary)"/>
  <path d="M70,-40 C180,-48 196,86 116,104 C44,120 6,36 70,-40 Z" fill="var(--c-dark)"/>
  <path d="M575,-25 C470,-35 432,66 505,96 C556,116 585,40 575,-25 Z" fill="var(--c-primary)"/>
  <circle cx="508" cy="70" r="16" fill="var(--c-secondary)"/>
  <circle cx="470" cy="150" r="8" fill="var(--c-accent)"/>
  <path d="M150,28 C58,118 92,224 26,300" fill="none" stroke="#20201E" stroke-width="2.2" stroke-linecap="round" opacity="0.55"/>
  <path d="M392,840 C300,762 366,720 300,690" fill="none" stroke="#20201E" stroke-width="2.2" stroke-linecap="round" opacity="0.5"/>
  <circle cx="-8" cy="384" r="70" fill="var(--c-light)"/>
  <circle cx="527" cy="432" r="26" fill="var(--c-secondary)" opacity="0.92"/>
  <circle cx="520" cy="330" r="15" fill="var(--c-light)"/>
  <circle cx="430" cy="470" r="9" fill="var(--c-light)"/>
  <circle cx="58" cy="556" r="8" fill="var(--c-secondary)" opacity="0.6"/>
  <circle cx="38" cy="772" r="96" fill="var(--c-light)"/>
  <path d="M-30,822 C78,788 172,820 150,880 L-30,880 Z" fill="var(--c-primary)"/>
  <circle cx="112" cy="812" r="20" fill="var(--c-secondary)"/>
  <rect x="392" y="778" width="200" height="120" rx="60" fill="var(--c-dark)"/>
  <circle cx="470" cy="734" r="22" fill="var(--c-secondary)"/>
  <circle cx="424" cy="704" r="10" fill="var(--c-accent)"/>
</g>

<!-- ============ CONTENT ============ -->
<?php if ($showLogo): ?>
  <image href="<?= $E($c['company']['logo']) ?>" xlink:href="<?= $E($c['company']['logo']) ?>"
         x="150" y="44" width="240" height="70" preserveAspectRatio="xMidYMid meet"/>
<?php endif; ?>

<?php if (! empty($f['photo'])): ?>
  <clipPath id="pf-photo"><rect x="164" y="150" width="212" height="256" rx="3"/></clipPath>
  <rect x="158" y="144" width="224" height="268" rx="6" fill="#ffffff"/>
  <?php if (! empty($c['photo_url'])): ?>
    <image href="<?= $E($c['photo_url']) ?>" xlink:href="<?= $E($c['photo_url']) ?>"
           x="164" y="150" width="212" height="256" preserveAspectRatio="xMidYMid slice" clip-path="url(#pf-photo)"/>
  <?php else: ?>
    <rect x="164" y="150" width="212" height="256" fill="var(--c-light)"/>
    <circle cx="270" cy="248" r="46" fill="var(--c-muted)" opacity="0.5"/>
    <path d="M188,406 C188,332 352,332 352,406 Z" fill="var(--c-muted)" opacity="0.5"/>
  <?php endif; ?>
  <rect x="164" y="150" width="212" height="256" rx="3" fill="none" stroke="var(--c-secondary)" stroke-width="7"/>
<?php endif; ?>

<?php if (! empty($f['name'])): $ny = $nameTop; foreach ($nameLines as $nl): ?>
  <text x="270" y="<?= $ny ?>" text-anchor="middle" fill="var(--c-text)"
        font-family="Poppins,Segoe UI,sans-serif" font-weight="700"
        font-size="<?= $nameSize ?>" letter-spacing="1"><?= $E($nl) ?></text>
  <?php $ny += $lineGap; endforeach; endif; ?>

<?php if (! empty($f['position']) && $posT !== ''): ?>
  <text x="270" y="<?= $posY ?>" text-anchor="middle" fill="var(--c-muted)"
        font-family="Poppins,Segoe UI,sans-serif" font-size="<?= $posSize ?>"
        letter-spacing="3" font-weight="500"><?= $E($posT) ?></text>
<?php endif; ?>

<?php if (! empty($f['staff_id'])): ?>
  <rect x="160" y="<?= $stripTop ?>" width="220" height="40" rx="9" fill="var(--c-secondary)"/>
  <text x="270" y="<?= $stripTy ?>" text-anchor="middle" fill="var(--c-dark)"
        font-family="Poppins,Segoe UI,sans-serif" font-size="<?= $idSize ?>"
        font-weight="600" letter-spacing="1"><?= $E($idText) ?></text>
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
  <text x="270" y="<?= $ly ?>" text-anchor="middle" fill="var(--c-text)"
        font-family="Poppins,Segoe UI,sans-serif" font-size="19" letter-spacing="0.5"><?= $E($ln2) ?></text>
  <?php $ly += 27; endforeach; ?>
