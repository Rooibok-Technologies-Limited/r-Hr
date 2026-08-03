<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Controllers\Erp;

use App\Controllers\BaseController;
use App\Models\UsersModel;

/**
 * Disbursements — maker-checker operations over payout batches (ROADMAP F2
 * phase 2). Preparers build batches; a DIFFERENT admin approves; then it is
 * processed. All heavy lifting is in service('disbursementEngine'); this
 * controller only gates access and marshals request/response.
 *
 * Restricted to company and super admins (money movement).
 */
class Disbursements extends BaseController
{
    /** @return array{0:int,1:string}|null [userId, userType] or null if unauthorised */
    private function admin()
    {
        $session = \Config\Services::session();
        if (! $session->has('sup_username')) {
            return null;
        }
        $u = (new UsersModel())->select('user_id, user_type')
            ->where('user_id', $session->get('sup_username')['sup_user_id'])->first();
        if (! $u || ! in_array($u['user_type'], ['super_user', 'company'], true)) {
            return null;
        }
        return [(int) $u['user_id'], (string) $u['user_type']];
    }

    private function deny()
    {
        return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'reason' => 'unauthorized']);
    }

    /**
     * The company a non-super admin is confined to (their own user_id IS the
     * company_id in this codebase). Super admins return null = all companies.
     */
    private function companyScope(array $a): ?int
    {
        return $a[1] === 'super_user' ? null : $a[0];
    }

    /**
     * A non-super admin may only act on batches belonging to their own company;
     * this both prevents cross-tenant IDOR and keeps maker-checker within one
     * company (a stranger admin can't be the "different approver"). [SECURITY: H1]
     */
    private function ownsBatch(array $a, int $batchId): bool
    {
        $scope = $this->companyScope($a);
        if ($scope === null) {
            return true; // super admin
        }
        $b = \Config\Database::connect()->table('ci_disbursement_batches')
            ->select('company_id')->where('batch_id', $batchId)->get()->getRowArray();
        return $b && (int) $b['company_id'] === $scope;
    }

    /** GET erp/disbursements — batch dashboard (build / approve / process / reconcile). */
    public function index()
    {
        if (! $this->admin()) {
            return redirect()->to(site_url('erp/login'));
        }
        helper('main');
        $data = [
            'title'       => 'Disbursements',
            'path_url'    => 'disbursements',
            'breadcrumbs' => 'Disbursements',
            'caps'        => [
                'per_txn' => (float) (system_setting('disbursement_max_per_txn') ?: 0),
                'per_run' => (float) (system_setting('disbursement_max_per_run') ?: 0),
                'per_day' => (float) (system_setting('disbursement_max_per_day') ?: 0),
            ],
        ];
        $data['subview'] = view('erp/disbursements/dashboard', $data);
        return view('erp/layout/layout_main', $data);
    }

    /** GET erp/disbursements/lines?batch_id= — line items for one batch. */
    public function lines()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $bid = (int) $this->request->getGet('batch_id');
        if (! $this->ownsBatch($a, $bid)) {
            return $this->deny();
        }
        $rows = \Config\Database::connect()->table('ci_disbursements')
            ->where('batch_id', $bid)->orderBy('disbursement_id', 'ASC')->get()->getResultArray();
        return $this->response->setJSON(['ok' => true, 'lines' => $rows]);
    }

    /** GET erp/disbursements/list — recent batches. */
    public function list()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $q = \Config\Database::connect()->table('ci_disbursement_batches')->orderBy('batch_id', 'DESC')->limit(200);
        if (($scope = $this->companyScope($a)) !== null) {
            $q->where('company_id', $scope); // company admins see only their own batches [SECURITY: H1]
        }
        return $this->response->setJSON(['ok' => true, 'batches' => $q->get()->getResultArray()]);
    }

    /** Allowed batch sources (server-side allowlist; blocks free-text XSS/abuse). */
    private const SOURCES = ['manual', 'payroll', 'expense', 'loan'];

    /** POST erp/disbursements/build — {items:[{employee_id,amount}], source, period}. */
    public function build()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $items = $this->request->getPost('items');
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }
        $source = (string) ($this->request->getPost('source') ?: 'manual');
        if (! in_array($source, self::SOURCES, true)) {
            $source = 'manual';
        }
        $period = trim((string) $this->request->getPost('period'));
        if ($period !== '' && ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'invalid period (expected YYYY-MM)', 'csrf_hash' => csrf_hash()]);
        }
        $res = service('disbursementEngine')->buildBatch((array) $items, [
            'source'      => $source,
            'period'      => $period ?: null,
            'company_id'  => $this->targetCompany($a),
            'prepared_by' => $a[0],
        ]);
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    /** POST erp/disbursements/build-payroll — {period, company_id?}. */
    public function build_payroll()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $period = trim((string) $this->request->getPost('period'));
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'invalid period (expected YYYY-MM)', 'csrf_hash' => csrf_hash()]);
        }
        $companyId = $this->targetCompany($a);
        if ($companyId === null) {
            // super admin: may target a specific company or all
            $req = $this->request->getPost('company_id');
            $companyId = ($req !== null && $req !== '') ? (int) $req : null;
        }
        $res = service('disbursementEngine')->buildFromPayroll($period, $companyId, ['prepared_by' => $a[0]]);
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    /** A non-super admin is pinned to their own company; super admin ⇒ null. */
    private function targetCompany(array $a): ?int
    {
        return $this->companyScope($a);
    }

    /** POST erp/disbursements/approve — {batch_id}. Maker-checker (approver≠preparer, same company). */
    public function approve()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $bid = (int) $this->request->getPost('batch_id');
        if (! $this->ownsBatch($a, $bid)) {
            return $this->deny();
        }
        $res = service('disbursementEngine')->approve($bid, $a[0]);
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    /** POST erp/disbursements/process — {batch_id}. */
    public function process()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $bid = (int) $this->request->getPost('batch_id');
        if (! $this->ownsBatch($a, $bid)) {
            return $this->deny();
        }
        $res = service('disbursementEngine')->process($bid);
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    /** POST erp/disbursements/reconcile — {batch_id?}. */
    public function reconcile()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $bid = (int) $this->request->getPost('batch_id');
        // A company admin may only reconcile their own batch; a blanket reconcile
        // (no batch_id) is super-admin only. [SECURITY: H1]
        if ($bid > 0) {
            if (! $this->ownsBatch($a, $bid)) {
                return $this->deny();
            }
        } elseif ($this->companyScope($a) !== null) {
            return $this->deny();
        }
        $res = service('disbursementEngine')->reconcile($bid ?: null);
        return $this->response->setJSON(['ok' => true] + $res + ['csrf_hash' => csrf_hash()]);
    }
}
