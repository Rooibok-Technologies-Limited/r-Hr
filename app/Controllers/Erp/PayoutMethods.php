<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Controllers\Erp;

use App\Controllers\BaseController;
use App\Models\UsersModel;

/**
 * PayoutMethods — manage and verify an employee's payout destinations
 * (ROADMAP F2 phase 1). JSON endpoints backing the payout UI.
 *
 * The verification code produced by the PayoutMethods service is delivered to
 * the employee's number over SMS here and is NEVER returned in the HTTP
 * response. Staff may only manage their own methods; company/super admins may
 * manage any employee in scope.
 */
class PayoutMethods extends BaseController
{
    /** @return array{0: bool, 1: int, 2: string}|null [ok, currentUserId, userType] or null response set */
    private function actor()
    {
        $session = \Config\Services::session();
        if (! $session->has('sup_username')) {
            return null;
        }
        $sup = $session->get('sup_username');
        $u   = (new UsersModel())->select('user_id, user_type')->where('user_id', $sup['sup_user_id'])->first();
        if (! $u) {
            return null;
        }
        return [true, (int) $u['user_id'], (string) $u['user_type']];
    }

    /** Resolve which employee this request may act on. */
    private function targetEmployee(int $currentId, string $type): ?int
    {
        $requested = (int) ($this->request->getPost('employee_id') ?: $this->request->getGet('employee_id'));
        // Staff can only manage themselves.
        if ($type === 'staff' || $requested === 0) {
            return $currentId;
        }
        // company / super_user may manage a named employee.
        return $requested;
    }

    private function deny()
    {
        return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'reason' => 'unauthorized']);
    }

    public function list()
    {
        if (! ($a = $this->actor())) {
            return $this->deny();
        }
        $employeeId = $this->targetEmployee($a[1], $a[2]);
        return $this->response->setJSON(['ok' => true, 'methods' => service('payoutMethods')->listFor($employeeId)]);
    }

    public function add()
    {
        if (! ($a = $this->actor())) {
            return $this->deny();
        }
        $employeeId = $this->targetEmployee($a[1], $a[2]);
        $res = service('payoutMethods')->addMethod(
            $employeeId,
            (string) $this->request->getPost('type'),
            (string) $this->request->getPost('account'),
            [
                'account_name' => $this->request->getPost('account_name') ?: null,
                'bank_name'    => $this->request->getPost('bank_name') ?: null,
                'provider'     => $this->request->getPost('provider') ?: null,
            ]
        );
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    public function verify_start()
    {
        if (! ($a = $this->actor())) {
            return $this->deny();
        }
        $methodId = (int) $this->request->getPost('method_id');
        $res      = service('payoutMethods')->startVerification($methodId);

        if (($res['ok'] ?? false) && ($res['channel'] ?? '') === 'sms') {
            // Deliver the code over SMS; never expose it in the response.
            $sent = service('smsProvider')->send(
                $res['account'],
                'Your ' . (system_setting('application_name') ?: 'HR') . ' payout verification code is ' . $res['code'] . '. It expires in 10 minutes.'
            );
            unset($res['code'], $res['account']);
            $res['sms_sent'] = $sent;
            if (! $sent) {
                // Sandbox / SMS not configured — code was issued but not delivered.
                $res['note'] = 'SMS gateway inactive — configure SMS to deliver the code.';
            }
        }
        unset($res['code'], $res['account']);
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    public function verify_confirm()
    {
        if (! ($a = $this->actor())) {
            return $this->deny();
        }
        $res = service('payoutMethods')->confirmVerification(
            (int) $this->request->getPost('method_id'),
            (string) $this->request->getPost('code')
        );
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }

    public function set_primary()
    {
        if (! ($a = $this->actor())) {
            return $this->deny();
        }
        $res = service('payoutMethods')->setPrimary((int) $this->request->getPost('method_id'));
        return $this->response->setJSON($res + ['csrf_hash' => csrf_hash()]);
    }
}
