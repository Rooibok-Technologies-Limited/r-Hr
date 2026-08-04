<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * PlanFeature (registration billing) — feature-gating by plan tier. Applied via the
 * URI-pattern $filters config to the premium module routes; it maps the request
 * path to a gateable feature and, if the tenant's plan does not grant it, blocks
 * (403 JSON for AJAX, else a redirect to the upgrade page). Real enforcement, not
 * just menu-hiding. Runs alongside the route's checklogin filter.
 */
class PlanFeature implements FilterInterface
{
    /** URI prefix → feature key (must exist in plan_gateable_features()). */
    private const MAP = [
        'erp/payroll'        => 'payroll',
        'erp/disbursements'  => 'payroll',
        'erp/payout-methods' => 'payroll',
        'erp/advance-salary' => 'payroll',
        'erp/loan-request'   => 'payroll',
        'erp/jobs'           => 'recruitment',
        'erp/recruitment'    => 'recruitment',
        'erp/performance'    => 'performance',
        'erp/goals'          => 'performance',
        'erp/goal-type'      => 'performance',
        'erp/competencies'   => 'performance',
        'erp/training'       => 'training',
        'erp/projects'       => 'projects',
        'erp/tasks'          => 'projects',
        'erp/product'        => 'inventory',
        'erp/warehouse'      => 'inventory',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        helper('main');
        $path = ltrim((string) $request->getPath(), '/');

        $feature = null;
        foreach (self::MAP as $prefix => $feat) {
            if ($path === $prefix || strpos($path, $prefix . '-') === 0 || strpos($path, $prefix . '/') === 0) {
                $feature = $feat;
                break;
            }
        }
        if ($feature === null || plan_allows($feature)) {
            return; // not gated here, or the plan grants it
        }

        $label = plan_gateable_features()[$feature] ?? $feature;
        if ($request->isAJAX()) {
            return service('response')->setStatusCode(403)
                ->setJSON(['error' => 'Your plan does not include ' . $label . '. Upgrade to enable it.']);
        }
        return redirect()->to(site_url('erp/feature-locked/' . $feature));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
