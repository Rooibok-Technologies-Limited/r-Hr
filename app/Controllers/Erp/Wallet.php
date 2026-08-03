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

    /** GET erp/wallet — company self-service wallet page (balance, top-up, statement). */
    public function index()
    {
        if (! ($a = $this->admin())) {
            return redirect()->to(site_url('erp/login'));
        }
        $data = [
            'title'         => 'Wallet',
            'path_url'      => 'wallet',
            'breadcrumbs'   => 'Wallet',
            'is_super'      => $a[1] === 'super_user',
            'online_fund'   => service('flutterwaveCollections')->isConfigured(),
        ];
        $data['subview'] = view('erp/wallet/wallet', $data);
        return view('erp/layout/layout_main', $data);
    }

    /** GET erp/wallets — super-admin oversight page (all companies + float reconcile). */
    public function oversight()
    {
        if (! ($a = $this->admin()) || $a[1] !== 'super_user') {
            return redirect()->to(site_url('erp/login'));
        }
        $data = [
            'title'       => 'Company Wallets',
            'path_url'    => 'wallets',
            'breadcrumbs' => 'Company Wallets',
        ];
        $data['subview'] = view('erp/wallet/oversight', $data);
        return view('erp/layout/layout_main', $data);
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
        // Manual top-up is an UNBACKED credit (no inbound charge), so it is a
        // super-admin / finance action only. A company funds itself exclusively
        // through fund() → hosted checkout → verified charge webhook. [SECURITY: C1]
        if (! ($a = $this->admin()) || $a[1] !== 'super_user') {
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

    /**
     * POST erp/wallet/fund — {amount}. Start a self-service top-up via the
     * aggregator's hosted checkout; returns a payment link to redirect to. The
     * wallet is credited later by the charge.completed webhook, not here.
     */
    public function fund()
    {
        if (! ($a = $this->admin())) {
            return $this->deny();
        }
        $companyId = $this->scope($a, $this->request->getPost('company_id'));
        $amount    = round((float) $this->request->getPost('amount'), 2);
        if ($amount <= 0) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'amount must be positive', 'csrf_hash' => csrf_hash()]);
        }
        $col = service('flutterwaveCollections');
        if (! $col->isConfigured()) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'online funding not configured — use manual top-up', 'csrf_hash' => csrf_hash()]);
        }
        $res = $col->initiate($companyId, $amount, ['redirect_url' => site_url('erp/wallet')]);
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    /**
     * GET erp/wallet/companies — super-admin oversight: every company's balance.
     * Read-only, super_user only.
     */
    public function companies()
    {
        if (! ($a = $this->admin()) || $a[1] !== 'super_user') {
            return $this->deny();
        }
        $rows = \Config\Database::connect()->table('ci_company_wallets')
            ->orderBy('wallet_id', 'DESC')->get()->getResultArray();
        $total = 0.0;
        $held  = 0.0;
        foreach ($rows as &$r) {
            $r['balance']  = (float) $r['balance'];
            $r['reserved'] = (float) $r['reserved'];
            $total += $r['balance'];
            $held  += $r['reserved'];
        }
        return $this->response->setJSON([
            'ok'           => true,
            'wallets'      => $rows,
            'total_avail'  => $total,
            'total_held'   => $held,
        ]);
    }

    /**
     * GET erp/wallet/float?currency= — super-admin reconciliation: the ledger
     * total (sum of all company balances + reserved) vs the aggregator's reported
     * master-float balance. A gap flags a reconciliation break.
     */
    public function float()
    {
        if (! ($a = $this->admin()) || $a[1] !== 'super_user') {
            return $this->deny();
        }
        $currency = $this->request->getGet('currency') ?: 'UGX';
        $row = \Config\Database::connect()->table('ci_company_wallets')
            ->selectSum('balance')->selectSum('reserved')->get()->getRowArray();
        $ledgerTotal = (float) ($row['balance'] ?? 0) + (float) ($row['reserved'] ?? 0);

        $agg = new \App\Libraries\Disbursement\FlutterwaveDisbursement();
        $master = $agg->isConfigured() ? $agg->balance($currency) : ['ok' => false, 'available' => null, 'reason' => 'not configured'];

        $diff = ($master['available'] ?? null) === null ? null : round((float) $master['available'] - $ledgerTotal, 2);
        return $this->response->setJSON([
            'ok'            => true,
            'currency'      => $currency,
            'ledger_total'  => round($ledgerTotal, 2),
            'master_float'  => $master['available'] ?? null,
            'difference'    => $diff,
            'reconciled'    => $diff !== null && abs($diff) < 0.01,
            'note'          => $master['reason'] ?? null,
        ]);
    }
}
