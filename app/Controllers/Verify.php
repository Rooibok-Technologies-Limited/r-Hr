<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * PUBLIC staff-card verification. Unauthenticated by design (scanned from a
 * printed QR). Validates the card in real time against the database, so a
 * revoked/expired card reflects immediately. Exposes ONLY a whitelisted subset
 * of employee data (see IdCardService::publicVerifyData) — never salary, IDs,
 * addresses, bank, contacts or HR notes.
 */
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Libraries\IdCardService;

class Verify extends Controller
{
    public function staff($token = null)
    {
        helper(['main', 'timehr']);
        $token = preg_replace('/[^a-f0-9]/i', '', (string) $token);
        $data  = $token !== '' ? (new IdCardService())->publicVerifyData($token) : null;
        return view('verify/staff', ['v' => $data, 'token' => $token]);
    }
}
