<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Controllers\Erp;

use App\Controllers\BaseController;
use App\Models\UsersModel;

/**
 * Wallet — per-company virtual-wallet surface over the aggregator float
 * (ROADMAP F2, ADR-002).
 *
 * A company admin sees and tops up ONLY their own wallet; a Rooibok super admin
 * has read-only oversight of any company (pass ?company_id) and can record a
 * top-up on a company's behalf. All money movement is handled by service('wallet')
 * and audited (F1). This controller only gates access and resolves scope.
 */
class Wallet extends BaseController
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

    /**
     * Resolve which company the request targets. A company admin is pinned to
     * their own id (their user_id IS the company_id in this codebase); a super
     * admin may target any company via $requested, else their own.
     */
    private function scope(array $a, $requested): int
    {
        [$userId, $userType] = $a;
        if ($userType === 'super_user' && $requested !== null && $requested !== '') {
            return (int) $requested;
        }
        return $userId;
    }

    private function deny()
    {
        return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'reason' => 'unauthorized']);
    }

    /** GET erp/wallet/balance?company_id= — available + reserved balance. */
    public function balance()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $companyId = $this->scope($a, $this->request->getGet('company_id'));
        return $this->response->setJSON([
            'ok'         => true,
            'company_id' => $companyId,
            'wallet'     => service('wallet')->balance($companyId),
        ]);
    }

    /** GET erp/wallet/statement?company_id=&limit= — ledger, newest first. */
    public function statement()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $companyId = $this->scope($a, $this->request->getGet('company_id'));
        $limit     = (int) ($this->request->getGet('limit') ?: 200);
        $limit     = max(1, min($limit, 500));
        return $this->response->setJSON([
            'ok'           => true,
            'company_id'   => $companyId,
            'transactions' => service('wallet')->transactions($companyId, $limit),
        ]);
    }

    /**
     * POST erp/wallet/topup — {company_id?, amount, reference?, description?}.
     * Records a top-up credit (aggregator-confirmed or manual). Idempotent on
     * reference. Super admin may top up any company; a company admin only their own.
     */
    public function topup()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $companyId = $this->scope($a, $this->request->getPost('company_id'));
        $amount    = round((float) $this->request->getPost('amount'), 2);
        if ($amount <= 0) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'amount must be positive', 'csrf_hash' => csrf_hash()]);
        }
        $res = service('wallet')->credit($companyId, $amount, 'topup', [
            'reference'   => $this->request->getPost('reference') ?: null,
            'description' => $this->request->getPost('description') ?: 'Manual top-up',
        ]);
        return $this->response->setJSON($res + ['company_id' => $companyId, 'csrf_hash' => csrf_hash()]);
    }
}
