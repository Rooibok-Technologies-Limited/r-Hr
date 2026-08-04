<?php
/** @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved. */
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
            $data['poll_url']      = site_url('erp/attendance-live/poll');
            return view('erp/timesheet/live_attendance_tv', $data);
        }

        // Standard dashboard mode
        $data['title']       = 'Live Attendance | ' . ($xin_system['application_name'] ?? '');
        $data['path_url']    = 'timesheet';
        $data['breadcrumbs'] = 'Live Attendance';
        $data['stream_url']  = site_url('erp/attendance-live/stream/');
        $data['poll_url']    = site_url('erp/attendance-live/poll');

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

        // Free the DB connection back to the pool for the whole request lifetime
        // is not possible on php-fpm (SSE holds one worker per open connection), so
        // we bound the connection tightly instead — see the loop below.
        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        $db    = \Config\Database::connect();
        $today = date('Y-m-d');

        // WORKER-EXHAUSTION GUARD: on php-fpm every open SSE connection pins one
        // worker, so we must (a) end each connection quickly and let the browser's
        // EventSource auto-reconnect, and (b) detect a dropped client fast so an
        // abandoned tab doesn't hold a worker.
        //   - total lifetime capped at LIFETIME_SECONDS (then the client reconnects)
        //   - 1s ticks with a keep-alive comment so connection_aborted() updates and
        //     a closed tab frees the worker within ~1s (was up to 30s)
        //   - data pushed every POLL_SECONDS; other ticks send an SSE comment ping
        ignore_user_abort(false);           // terminate when the client goes away
        @set_time_limit(0);                  // wall-clock is bounded by the loop below
        $LIFETIME_SECONDS = 55;              // < typical 60s proxy idle timeout
        $POLL_SECONDS     = 15;              // data refresh cadence
        $start            = time();
        $lastPush         = 0;

        while ((time() - $start) < $LIFETIME_SECONDS) {
            if (connection_aborted()) {
                break;
            }
            $now = time();
            if ($lastPush === 0 || ($now - $lastPush) >= $POLL_SECONDS) {
                $summary = $this->getAttendanceSummary($db, (int) $companyId, $today);
                echo 'data: ' . json_encode($summary) . "\n\n";
                $lastPush = $now;
            } else {
                echo ": keep-alive\n\n"; // comment ping — forces output so aborts show
            }
            if (ob_get_level() > 0) {
                @ob_flush();
            }
            @flush();
            if (connection_aborted()) {
                break;
            }
            sleep(1);
        }

        // Ask the browser to reconnect promptly (EventSource handles this).
        echo "retry: 3000\n\n";
        @ob_flush();
        @flush();
        exit;
    }

    /**
     * Poll endpoint — returns the attendance summary ONCE as JSON and exits.
     *
     * This is the worker-safe alternative to stream(): on php-fpm every open SSE
     * connection pins one of the (few) workers for its whole lifetime, so a handful
     * of viewers can exhaust the pool and take the app down. The live-attendance UI
     * polls this endpoint on an interval instead — each request holds a worker for
     * only milliseconds. stream() is kept for compatibility but is no longer used
     * by the UI.
     */
    public function poll()
    {
        $session  = \Config\Services::session();
        $usession = $session->get('sup_username');
        if (! $usession) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }
        $user_info = (new UsersModel())->where('user_id', $usession['sup_user_id'])->first();
        if (! $user_info) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }
        $companyId = ($user_info['user_type'] === 'company')
            ? $user_info['user_id']
            : $user_info['company_id'];

        $db = \Config\Database::connect();
        return $this->response->setJSON($this->getAttendanceSummary($db, (int) $companyId, date('Y-m-d')));
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

        // Currently clocked in (still in building). clock_in_out is a VARCHAR
        // column storing '0'/'1' — compare as strings, else Postgres errors with
        // "operator does not exist: character varying = integer".
        $clockedIn = $db->table('ci_timesheet')
            ->where('company_id', $companyId)
            ->where('attendance_date', $today)
            ->where('clock_in_out', '0')
            ->countAllResults();

        // Clocked out today
        $clockedOut = $db->table('ci_timesheet')
            ->where('company_id', $companyId)
            ->where('attendance_date', $today)
            ->where('clock_in_out', '1')
            ->countAllResults();

        // Recent clock events (last 10)
        $recentQuery = $db->table('ci_timesheet t')
            ->select('t.*, u.first_name, u.last_name, u.profile_photo')
            ->join('ci_erp_users u', 'u.user_id = t.employee_id')
            ->where('t.company_id', $companyId)
            ->where('t.attendance_date', $today)
            ->orderBy('t.time_attendance_id', 'DESC')
            ->limit(10)
            ->get();
        $recent = $recentQuery ? $recentQuery->getResultArray() : [];

        // Currently in building
        // department_id lives on ci_erp_users_details (per employee), not on
        // ci_erp_users — join through it to reach the department name.
        $inBuildingQuery = $db->table('ci_timesheet t')
            ->select('t.*, u.first_name, u.last_name, u.profile_photo, d.department_name')
            ->join('ci_erp_users u', 'u.user_id = t.employee_id')
            ->join('ci_erp_users_details ud', 'ud.user_id = u.user_id', 'left')
            ->join('ci_departments d', 'd.department_id = ud.department_id', 'left')
            ->where('t.company_id', $companyId)
            ->where('t.attendance_date', $today)
            ->where('t.clock_in_out', '0')
            ->orderBy('t.clock_in', 'DESC')
            ->get();
        $inBuilding = $inBuildingQuery ? $inBuildingQuery->getResultArray() : [];

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
