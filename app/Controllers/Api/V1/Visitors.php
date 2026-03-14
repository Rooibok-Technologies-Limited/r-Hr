<?php

namespace App\Controllers\Api\V1;

use App\Models\VisitorsModel;

class Visitors extends ApiBaseController
{
    /**
     * POST /api/v1/visitors/check-in
     *
     * Accepts JSON: visitor_name, phone, whom_to_visit, purpose
     */
    public function checkIn()
    {
        $json = $this->request->getJSON(true);

        $visitorName = $json['visitor_name']   ?? '';
        $phone       = $json['phone']          ?? '';
        $whomToVisit = $json['whom_to_visit']  ?? '';
        $purpose     = $json['purpose']        ?? '';

        if (empty($visitorName)) {
            return $this->errorResponse('visitor_name is required', 400);
        }

        $companyId = $this->jwtCompanyId();
        $userId    = $this->jwtUserId();

        $data = [
            'company_id'    => $companyId,
            'visitor_name'  => $visitorName,
            'phone'         => $phone,
            'visit_purpose' => $purpose,
            'description'   => 'Visit to: ' . $whomToVisit,
            'visit_date'    => date('Y-m-d'),
            'check_in'      => date('Y-m-d H:i:s'),
            'check_out'     => '',
            'created_by'    => $userId,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $visitorsModel = new VisitorsModel();
        $result = $visitorsModel->insert($data);

        if ($result) {
            return $this->jsonResponse([
                'message'    => 'Visitor checked in successfully',
                'visitor_id' => $result,
                'check_in'   => $data['check_in'],
            ], 201);
        }

        return $this->errorResponse('Failed to record visitor check-in', 500);
    }
}
