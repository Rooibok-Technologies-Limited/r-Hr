<?php
/**
 * Live Attendance Dashboard Controller (Phase 6.4)
 *
 * Provides a real-time attendance view via Server-Sent Events (SSE).
 * Includes a standard dashboard mode and a TV/kiosk display mode.
 */
namespace App\Controllers\Erp;

use App\Controllers\BaseController;
use App\Models\SystemModel;
use App\Models\UsersModel;

class AttendanceLive extends BaseController
{
    /**
     * Display the live attendance dashboard page.
     * Supports ?display=tv query param for fullscreen TV/kiosk mode.
     */
    public function index()
    {
        $UsersModel  = new UsersModel();
        $SystemModel = new SystemModel();
        $session     = \Config\Services::session();
        $usession    = $session->get('sup_username');

        if (!$session->has('sup_username')) {
            $session->setFlashdata('err_not_logged_in', lang('Dashboard.err_not_logged_in'));
            return redirect()->to(site_url('erp/login'));
        }

        $user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

        if ($user_info['user_type'] !== 'company' && $user_info['user_type'] !== 'staff') {
            $session->setFlashdata('unauthorized_module', lang('Dashboard.xin_error_unauthorized_module'));
            return redirect()->to(site_url('erp/desk'));
        }

        $xin_system = $SystemModel->where('setting_id', 1)->first();

        // TV / kiosk display mode
        $displayMode = $this->request->getGet('display');
        if ($displayMode === 'tv') {
            $data['title']         = 'Live Attendance';
            $data['app_name']      = $xin_system['application_name'] ?? 'Rooibok HR';
            $data['stream_url']    = site_url('erp/attendance-live/stream/');
            return view('erp/timesheet/live_attendance_tv', $data);
        }

        // Standard dashboard mode
        $data['title']       = 'Live Attendance | ' . ($xin_system['application_name'] ?? '');
        $data['path_url']    = 'timesheet';
        $data['breadcrumbs'] = 'Live Attendance';
        $data['stream_url']  = site_url('erp/attendance-live/stream/');

        $data['subview'] = view('erp/timesheet/live_attendance', $data);
        return view('erp/layout/layout_main', $data);
    }

    /**
     * SSE endpoint — streams attendance data every 30 seconds.
     */
    public function stream()
    {
        $session  = \Config\Services::session();
        $usession = $session->get('sup_username');

        if (!$usession) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $UsersModel = new UsersModel();
        $user_info  = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

        if (!$user_info) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $companyId = ($user_info['user_type'] === 'company')
            ? $user_info['user_id']
            : $user_info['company_id'];

        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        $db    = \Config\Database::connect();
        $today = date('Y-m-d');

        // Send data in a loop
        $maxIterations = 120; // ~60 minutes at 30s intervals, then client reconnects
        for ($i = 0; $i < $maxIterations; $i++) {
            $summary = $this->getAttendanceSummary($db, (int) $companyId, $today);

            echo "data: " . json_encode($summary) . "\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            if (connection_aborted()) {
                break;
            }
            sleep(30);
        }

        // Send a final retry hint so the browser reconnects
        echo "retry: 5000\n\n";
        exit;
    }

    /**
     * Build attendance summary for a given company and date.
     */
    private function getAttendanceSummary($db, int $companyId, string $today): array
    {
        // Total active staff
        $totalStaff = $db->table('ci_erp_users')
            ->where('company_id', $companyId)
            ->where('user_type', 'staff')
            ->where('is_active', 1)
            ->countAllResults();

        // Currently clocked in (still in building)
        $clockedIn = $db->table('ci_timesheet')
            ->where('company_id', $companyId)
            ->where('attendance_date', $today)
            ->where('clock_in_out', 0)
            ->countAllResults();

        // Clocked out today
        $clockedOut = $db->table('ci_timesheet')
            ->where('company_id', $companyId)
            ->where('attendance_date', $today)
            ->where('clock_in_out', 1)
            ->countAllResults();

        // Recent clock events (last 10)
        $recent = $db->table('ci_timesheet t')
            ->select('t.*, u.first_name, u.last_name, u.profile_photo')
            ->join('ci_erp_users u', 'u.user_id = t.employee_id')
            ->where('t.company_id', $companyId)
            ->where('t.attendance_date', $today)
            ->orderBy('t.time_attendance_id', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        // Currently in building
        $inBuilding = $db->table('ci_timesheet t')
            ->select('t.*, u.first_name, u.last_name, u.profile_photo, d.department_name')
            ->join('ci_erp_users u', 'u.user_id = t.employee_id')
            ->join('ci_departments d', 'd.department_id = u.department_id', 'left')
            ->where('t.company_id', $companyId)
            ->where('t.attendance_date', $today)
            ->where('t.clock_in_out', 0)
            ->orderBy('t.clock_in', 'DESC')
            ->get()->getResultArray();

        return [
            'total_staff'   => $totalStaff,
            'clocked_in'    => $clockedIn,
            'clocked_out'   => $clockedOut,
            'absent'        => max(0, $totalStaff - $clockedIn - $clockedOut),
            'recent_events' => $recent,
            'in_building'   => $inBuilding,
            'updated_at'    => date('H:i:s'),
        ];
    }
}
