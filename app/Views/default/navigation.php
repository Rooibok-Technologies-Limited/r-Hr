<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Single navigation renderer for every portal. Consumes NavBuilder output; the
 * three menu views (company/staff/super) delegate here so there is one tree,
 * one active-state rule, one accessibility contract. Markup keeps the theme's
 * `.pc-navbar / .pc-item / .pc-submenu` classes so existing CSS + the collapse
 * JS keep working.
 *
 * Expects: $nav_groups (array from NavBuilder::build()).
 */
use App\Libraries\NavBuilder;

if (! isset($nav_groups)) {
    $session   = \Config\Services::session();
    $usession  = $session->get('sup_username');
    $uid       = is_array($usession) ? ($usession['sup_user_id'] ?? 0) : 0;
    $uinfo     = $uid ? (new \App\Models\UsersModel())->where('user_id', $uid)->first() : null;
    $utype     = $uinfo['user_type'] ?? 'staff';
    $resources = function_exists('staff_role_resource') ? (array) staff_role_resource() : [];
    $curPath   = trim(service('request')->getPath(), '/');
    $nav_groups = (new NavBuilder($utype, $resources, $curPath))->build();
}

/** Resolve a "nav.foo" label token to its localised string. */
$navLabel = static function (string $token): string {
    $key = strpos($token, '.') !== false ? substr($token, strpos($token, '.') + 1) : $token;
    return lang('Nav.' . $key);
};
?>
<nav aria-label="<?= esc(lang('Nav.group_overview')) ?>">
<div class="nav-search px-3 pt-2 pb-1">
  <input type="text" id="nav-filter" class="form-control form-control-sm"
         placeholder="<?= esc(lang('Nav.search_placeholder')) ?>"
         aria-label="<?= esc(lang('Nav.search_placeholder')) ?>" autocomplete="off">
</div>
<ul class="pc-navbar">
<?php foreach ($nav_groups as $group): ?>
  <li class="pc-item pc-caption"><label><?= esc($navLabel($group['label'])) ?></label></li>
  <?php foreach ($group['items'] as $item): ?>
    <?php $label = esc($navLabel($item['label'])); $hasChildren = ! empty($item['children']); ?>
    <?php if ($hasChildren): ?>
      <li class="pc-item pc-hasmenu<?= $item['active'] ? ' active pc-trigger' : '' ?>"
          data-nav-id="<?= esc($item['id']) ?>">
        <a href="<?= site_url($item['href']) ?>" class="pc-link"
           aria-expanded="<?= $item['active'] ? 'true' : 'false' ?>"
           <?= $item['active'] ? 'aria-current="page"' : '' ?>>
          <span class="pc-micon"><i data-feather="<?= esc($item['icon']) ?>"></i></span>
          <span class="pc-mtext" title="<?= $label ?>"><?= $label ?></span>
          <span class="pc-arrow"><i data-feather="chevron-right"></i></span>
        </a>
        <ul class="pc-submenu"<?= $item['active'] ? ' style="display:block"' : '' ?>>
        <?php foreach ($item['children'] as $child): ?>
          <li class="pc-item<?= $child['active'] ? ' active' : '' ?>">
            <a class="pc-link" href="<?= site_url($child['href']) ?>"
               <?= $child['active'] ? 'aria-current="page"' : '' ?>><?= esc($navLabel($child['label'])) ?></a>
          </li>
        <?php endforeach; ?>
        </ul>
      </li>
    <?php else: ?>
      <li class="pc-item<?= $item['active'] ? ' active' : '' ?>" data-nav-id="<?= esc($item['id']) ?>">
        <a href="<?= $item['external'] ? esc($item['href'], 'attr') : site_url($item['href']) ?>"
           class="pc-link"<?= $item['external'] ? ' target="_blank" rel="noopener"' : '' ?>
           <?= $item['active'] ? 'aria-current="page"' : '' ?>>
          <span class="pc-micon"><i data-feather="<?= esc($item['icon']) ?>"></i></span>
          <span class="pc-mtext" title="<?= $label ?>"><?= $label ?></span>
        </a>
      </li>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endforeach; ?>
</ul>
</nav>
<script>
// Sidebar label filter — pure client-side, filters the whole tree by label text.
(function () {
  var box = document.getElementById('nav-filter');
  if (!box) return;
  box.addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    document.querySelectorAll('.pc-navbar > .pc-item').forEach(function (li) {
      if (li.classList.contains('pc-caption')) { li.style.display = ''; return; }
      var txt = (li.textContent || '').toLowerCase();
      li.style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
    });
  });
})();
</script>
