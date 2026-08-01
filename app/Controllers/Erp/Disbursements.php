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

    /** GET erp/disbursements/list — recent batches. */
    public function list()
    {
        if (! $this->admin()) {
            return $this->deny();
        }
        $rows = \Config\Database::connect()->table('ci_disbursement_batches')
            ->orderBy('batch_id', 'DESC')->limit(200)->get()->getResultArray();
        return $this->response->setJSON(['ok' => true, 'batches' => $rows]);
    }

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
        $res = service('disbursementEngine')->buildBatch((array) $items, [
            'source'      => $this->request->getPost('source') ?: 'manual',
            'period'      => $this->request->getPost('period') ?: null,
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
        $period    = trim((string) $this->request->getPost('period'));
        $companyId = $this->request->getPost('company_id');
        if ($period === '') {
            return $this->response->setJSON(['ok' => false, 'reason' => 'period required']);
        }
        $res = service('disbursementEngine')->buildFromPayroll(
            $period,
            $companyId !== null && $companyId !== '' ? (int) $companyId : null,
            ['prepared_by' => $a[0]]
        );
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    /** POST erp/disbursements/approve — {batch_id}. Maker-checker enforced in the engine. */
    public function approve()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $res = service('disbursementEngine')->approve((int) $this->request->getPost('batch_id'), $a[0]);
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    /** POST erp/disbursements/process — {batch_id}. */
    public function process()
    {
        if (! $this->admin()) {
            return $this->deny();
        }
        $res = service('disbursementEngine')->process((int) $this->request->getPost('batch_id'));
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    /** POST erp/disbursements/reconcile — {batch_id?}. */
    public function reconcile()
    {
        if (! $this->admin()) {
            return $this->deny();
        }
        $bid = (int) $this->request->getPost('batch_id');
        $res = service('disbursementEngine')->reconcile($bid ?: null);
        return $this->response->setJSON(['ok' => true] + $res + ['csrf_hash' => csrf_hash()]);
    }
}
