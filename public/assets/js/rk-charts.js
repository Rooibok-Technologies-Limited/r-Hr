/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * rkChart(selector, options) — a safe ApexCharts wrapper (Phase 2).
 *   - No-ops when the target container is absent (generalizes the ad-hoc
 *     leave_status.js guard) so a chart script on the wrong page never throws
 *     "Element not found".
 *   - Applies the Rooibok categorical palette unless the caller sets colors.
 *   - Follows the active light/dark theme.
 * Returns the ApexCharts instance, or null when it couldn't render.
 */
(function (w) {
  var PALETTE = ['#6d5ffb', '#17c666', '#3ec9d6', '#f5a623', '#ef4d56', '#8b5cf6', '#0ea5e9'];

  w.RK_PALETTE = PALETTE;

  w.rkChart = function (selector, options) {
    var el = (typeof selector === 'string') ? document.querySelector(selector) : selector;
    if (!el || typeof ApexCharts === 'undefined') { return null; }

    options = options || {};
    if (!options.colors) { options.colors = PALETTE; }

    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    options.theme = Object.assign({ mode: dark ? 'dark' : 'light' }, options.theme || {});
    if (dark) { options.chart = Object.assign({ background: 'transparent' }, options.chart || {}); }

    try {
      var chart = new ApexCharts(el, options);
      chart.render();
      return chart;
    } catch (e) {
      if (w.console) { console.warn('rkChart:', e && e.message); }
      return null;
    }
  };
})(window);
