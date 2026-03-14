<?php
/**
 * Rooibok HR System — Unsubscribe Controller
 * Phase 10.11: One-click marketing unsubscribe via tokenised link.
 */
namespace App\Controllers;

use App\Controllers\BaseController;

class Unsubscribe extends BaseController
{
    /**
     * Process an unsubscribe request.
     * GET /unsubscribe?token=<encoded_contact_id>
     */
    public function index()
    {
        $token = $this->request->getGet('token');

        if (!$token) {
            return redirect()->to('/');
        }

        $contactId = udecode($token);

        if (empty($contactId)) {
            return redirect()->to('/');
        }

        $archDb = \Config\Database::connect('archive');
        $archDb->table('arc_contacts')
            ->where('contact_id', $contactId)
            ->set([
                'unsubscribed'    => 1,
                'unsubscribed_at' => date('Y-m-d H:i:s'),
            ])
            ->update();

        return view('frontend/unsubscribed');
    }
}
