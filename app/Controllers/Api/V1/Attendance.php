<?php

namespace App\Controllers\Api\V1;

use App\Models\TimesheetModel;
use App\Models\UsersModel;

class Attendance extends ApiBaseController
{
    /**
     * POST /api/v1/attendance/clock-in
     *
     * Accepts JSON: employee_id, latitude, longitude
     */
    public function clockIn()
    {
        $json = $this->request->getJSON(true);

        $employeeId = $json['employee_id'] ?? null;
        $latitude   = $json['latitude']    ?? '';
        $longitude  = $json['longitude']   ?? '';

        if (empty($employeeId)) {
            return $this->errorResponse('employee_id is required', 400);
        }

        // Verify the employee belongs to the same company
        $companyId = $this->jwtCompanyId();
        $usersModel = new UsersModel();
        $employee = $usersModel->where('user_id', $employeeId)
                               ->where('company_id', $companyId)
                               ->first();

        if (!$employee) {
            return $this->errorResponse('Employee not found in your company', 404);
        }

        $timesheetModel = new TimesheetModel();
        $todayDate = date('Y-m-d');
        $nowTime   = date('Y-m-d H:i:s');
        $ipAddress = $this->request->getIPAddress();

        // Check if already clocked in today with no clock-out
        $openEntry = $timesheetModel
            ->where('employee_id', $employeeId)
            ->where('attendance_date', $todayDate)
            ->where('clock_out', '')
            ->first();

        if ($openEntry) {
            return $this->errorResponse('Employee is already clocked in', 400);
        }

        // Calculate rest time from previous clock-out
        $totalRest = '';
        $previousEntry = $timesheetModel
            ->where('employee_id', $employeeId)
            ->where('attendance_date', $todayDate)
            ->orderBy('time_attendance_id', 'DESC')
            ->first();

        if ($previousEntry && !empty($previousEntry['clock_out'])) {
            $cout = date_create($previousEntry['clock_out']);
            $cin  = date_create($nowTime);
            $interval = date_diff($cin, $cout);
            $totalRest = $interval->format('%h') . ':' . $interval->format('%i');
        }

        $data = [
            'company_id'          => $companyId,
            'employee_id'         => $employeeId,
            'attendance_date'     => $todayDate,
            'clock_in'            => $nowTime,
            'clock_in_ip_address' => $ipAddress,
            'clock_out'           => '',
            'clock_out_ip_address'=> 1,
            'clock_in_out'        => 0,
            'clock_in_latitude'   => $latitude,
            'clock_in_longitude'  => $longitude,
            'clock_out_latitude'  => 1,
            'clock_out_longitude' => 1,
            'time_late'           => $nowTime,
            'early_leaving'       => $nowTime,
            'overtime'            => $nowTime,
            'total_work'          => '00:00',
            'total_rest'          => $totalRest,
            'attendance_status'   => 'Present',
        ];

        $result = $timesheetModel->insert($data);

        if ($result) {
            return $this->jsonResponse([
                'message'       => 'Clock-in recorded successfully',
                'attendance_id' => $result,
                'clock_in'      => $nowTime,
            ], 201);
        }

        return $this->errorResponse('Failed to record clock-in', 500);
    }

    /**
     * POST /api/v1/attendance/clock-out
     *
     * Accepts JSON: employee_id, latitude, longitude
     */
    public function clockOut()
    {
        $json = $this->request->getJSON(true);

        $employeeId = $json['employee_id'] ?? null;
        $latitude   = $json['latitude']    ?? '';
        $longitude  = $json['longitude']   ?? '';

        if (empty($employeeId)) {
            return $this->errorResponse('employee_id is required', 400);
        }

        // Verify the employee belongs to the same company
        $companyId = $this->jwtCompanyId();
        $usersModel = new UsersModel();
        $employee = $usersModel->where('user_id', $employeeId)
                               ->where('company_id', $companyId)
                               ->first();

        if (!$employee) {
            return $this->errorResponse('Employee not found in your company', 404);
        }

        $timesheetModel = new TimesheetModel();
        $todayDate = date('Y-m-d');
        $nowTime   = date('Y-m-d H:i:s');
        $ipAddress = $this->request->getIPAddress();

        // Find the open clock-in entry
        $openEntry = $timesheetModel
            ->where('employee_id', $employeeId)
            ->where('attendance_date', $todayDate)
            ->where('clock_out', '')
            ->orderBy('time_attendance_id', 'DESC')
            ->first();

        if (!$openEntry) {
            return $this->errorResponse('No active clock-in found for today', 400);
        }

        // Calculate total work time
        $clockInTime = date_create($openEntry['clock_in']);
        $clockOutTime = date_create($nowTime);
        $interval = date_diff($clockInTime, $clockOutTime);
        $totalWork = $interval->format('%h') . ':' . $interval->format('%i');

        $data = [
            'clock_out'            => $nowTime,
            'clock_out_ip_address' => $ipAddress,
            'clock_out_latitude'   => $latitude,
            'clock_out_longitude'  => $longitude,
            'clock_in_out'         => 0,
            'early_leaving'        => $nowTime,
            'overtime'             => $nowTime,
            'total_work'           => $totalWork,
        ];

        $result = $timesheetModel->update($openEntry['time_attendance_id'], $data);

        if ($result) {
            return $this->jsonResponse([
                'message'    => 'Clock-out recorded successfully',
                'clock_out'  => $nowTime,
                'total_work' => $totalWork,
            ]);
        }

        return $this->errorResponse('Failed to record clock-out', 500);
    }

    /**
     * GET /api/v1/attendance/status?employee_id=123
     *
     * Returns the current attendance status for the given employee.
     */
    public function status()
    {
        $employeeId = $this->request->getGet('employee_id');

        if (empty($employeeId)) {
            return $this->errorResponse('employee_id query parameter is required', 400);
        }

        // Verify the employee belongs to the same company
        $companyId = $this->jwtCompanyId();
        $usersModel = new UsersModel();
        $employee = $usersModel->where('user_id', $employeeId)
                               ->where('company_id', $companyId)
                               ->first();

        if (!$employee) {
            return $this->errorResponse('Employee not found in your company', 404);
        }

        $timesheetModel = new TimesheetModel();
        $todayDate = date('Y-m-d');

        // Get latest entry for today
        $latestEntry = $timesheetModel
            ->where('employee_id', $employeeId)
            ->where('attendance_date', $todayDate)
            ->orderBy('time_attendance_id', 'DESC')
            ->first();

        if (!$latestEntry) {
            return $this->jsonResponse([
                'employee_id' => (int) $employeeId,
                'date'        => $todayDate,
                'status'      => 'not_clocked_in',
                'clock_in'    => null,
                'clock_out'   => null,
            ]);
        }

        $isClockedIn = empty($latestEntry['clock_out']);

        return $this->jsonResponse([
            'employee_id'   => (int) $employeeId,
            'date'          => $todayDate,
            'status'        => $isClockedIn ? 'clocked_in' : 'clocked_out',
            'clock_in'      => $latestEntry['clock_in'],
            'clock_out'     => $isClockedIn ? null : $latestEntry['clock_out'],
            'total_work'    => $latestEntry['total_work'],
            'attendance_id' => (int) $latestEntry['time_attendance_id'],
        ]);
    }
}
