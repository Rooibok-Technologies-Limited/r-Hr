<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Placeholder view — this section's controller was wired into the nav but its
 * view was never built (previously a 500 "Invalid file"). Renders a clean
 * empty-state until the full section ships.
 */
?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body text-center py-5">
        <i data-feather="tool" style="width:44px;height:44px" class="text-muted mb-3"></i>
        <h4 class="mb-2"><?= esc($breadcrumbs ?? ($title ?? 'Section')) ?></h4>
        <p class="text-muted mb-0">This section is being set up and will be available shortly.</p>
      </div>
    </div>
  </div>
</div>
