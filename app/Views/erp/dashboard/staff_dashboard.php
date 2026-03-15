<?php
use App\Models\UsersModel;
use App\Models\SystemModel;
use App\Models\ConstantsModel;
use App\Models\StaffdetailsModel;
use App\Models\ShiftModel;

$UsersModel = new UsersModel();
$ConstantsModel = new ConstantsModel();
$StaffdetailsModel = new StaffdetailsModel();
$ShiftModel = new ShiftModel();

$session = \Config\Services::session();
$usession = get_safe_session();
$uid = $usession ? $usession['sup_user_id'] : 0;
$db = \Config\Database::connect();

$user_info = $uid ? $UsersModel->where('user_id', $uid)->first() : null;
if(!$user_info) return;
$company_id = $user_info['company_id'];
$staff_detail = $StaffdetailsModel->where('user_id', $uid)->first();

// Department + Designation
$dept_q = $db->query("SELECT d.department_name, des.designation_name FROM ci_erp_users_details ud LEFT JOIN ci_departments d ON d.department_id=ud.department_id LEFT JOIN ci_designations des ON des.designation_id=ud.designation_id WHERE ud.user_id=?", [$uid]);
$dept_info = $dept_q ? $dept_q->getRowArray() : [];

// Office shift
$shift = !empty($staff_detail['office_shift_id']) ? $ShiftModel->where('office_shift_id', $staff_detail['office_shift_id'])->first() : null;
$day_key = strtolower(date('l'));
$shift_in = $shift[$day_key.'_in_time'] ?? '';
$shift_out = $shift[$day_key.'_out_time'] ?? '';
$shift_label = $shift_in ? date('H:i', strtotime($shift_in)).' - '.date('H:i', strtotime($shift_out)) : 'No shift';
$shift_name = $shift['shift_name'] ?? 'N/A';

// Today's attendance (attendance_date is VARCHAR in DD-MM-YYYY format)
$today_date_str = date('d-m-Y');
$today_q = $db->query("SELECT clock_in, clock_out, attendance_status FROM ci_timesheet WHERE employee_id=? AND attendance_date=?", [$uid, $today_date_str]);
$today = $today_q ? $today_q->getRowArray() : null;
$clocked_in = !empty($today['clock_in']);
$clocked_out = !empty($today['clock_out']);
$hours_worked = 0;
if($clocked_in) {
    $cin = strtotime($today['clock_in']);
    $cout = $clocked_out ? strtotime($today['clock_out']) : time();
    $hours_worked = ($cin && $cout) ? round(($cout - $cin) / 3600, 1) : 0;
}
$hours_h = floor($hours_worked);
$hours_m = round(($hours_worked - $hours_h) * 60);

// Month attendance summary (attendance_date is VARCHAR DD-MM-YYYY)
$month_pattern = '%-' . date('m-Y');
$month_q = $db->query("SELECT attendance_date, attendance_status FROM ci_timesheet WHERE employee_id=? AND attendance_date LIKE ?", [$uid, $month_pattern]);
$month_data = $month_q ? $month_q->getResultArray() : [];
$present = $absent = $late = $on_leave = 0;
foreach($month_data as $md) {
    $s = $md['attendance_status'] ?? '';
    if($s=='Present') $present++;
    elseif($s=='Absent') $absent++;
    elseif($s=='Late') { $late++; $present++; }
    elseif($s=='On Leave' || $s=='Leave') $on_leave++;
}
$total_days = $present + $absent + $late + $on_leave;
$ontime_rate = $total_days > 0 ? round((($present - $late) / max(1,$present + $absent + $late)) * 100) : 0;

// Attendance heatmap for current month
$days_in_month = (int)date('t');
$first_dow = (int)date('N', strtotime(date('Y-m-01')));
$heatmap = [];
foreach($month_data as $md) {
    // attendance_date is DD-MM-YYYY, extract the day part
    $parts = explode('-', $md['attendance_date'] ?? '');
    $day = (int)($parts[0] ?? 0);
    if($day > 0) $heatmap[$day] = $md['attendance_status'];
}

// Leave balance
$leave_types = $ConstantsModel->where('type','leave_type')->where('company_id', $company_id)->findAll();
$leave_balances = [];
foreach($leave_types as $lt) {
    $total_days_allowed = (int)($lt['field_one'] ?? 0);
    $taken_q = $db->query("SELECT COUNT(*) AS c FROM ci_leave_applications WHERE employee_id=? AND leave_type_id=? AND status=2", [$uid, $lt['constants_id']]);
    $taken = $taken_q ? (int)($taken_q->getRow()->c ?? 0) : 0;
    $pending_q = $db->query("SELECT COUNT(*) AS c FROM ci_leave_applications WHERE employee_id=? AND leave_type_id=? AND status=1", [$uid, $lt['constants_id']]);
    $pending = $pending_q ? (int)($pending_q->getRow()->c ?? 0) : 0;
    $leave_balances[] = ['name'=>$lt['category_name'], 'total'=>$total_days_allowed, 'taken'=>$taken, 'pending'=>$pending, 'remaining'=>max(0,$total_days_allowed-$taken)];
}
$total_leave_remaining = array_sum(array_column($leave_balances, 'remaining'));
$total_leave_used = array_sum(array_column($leave_balances, 'taken'));
$total_leave_pending = array_sum(array_column($leave_balances, 'pending'));

// Recent leave requests
$recent_leaves_q = $db->query("SELECT la.*, c.category_name FROM ci_leave_applications la JOIN ci_erp_constants c ON c.constants_id=la.leave_type_id WHERE la.employee_id=? ORDER BY la.created_at DESC LIMIT 3", [$uid]);
$recent_leaves = $recent_leaves_q ? $recent_leaves_q->getResultArray() : [];

// Payroll last 6 months
$payroll_q = $db->query("SELECT salary_month, net_salary, basic_salary, paye_tax, nssf_employee FROM ci_payslips WHERE staff_id=? ORDER BY salary_month DESC LIMIT 6", [$uid]);
$payroll_history = $payroll_q ? $payroll_q->getResultArray() : [];
$payroll_history = array_reverse($payroll_history);
$payroll_max = !empty($payroll_history) ? max(array_column($payroll_history, 'net_salary')) : 1;

// This month pay + YTD
$this_month_pay_q = $db->query("SELECT net_salary, paye_tax, nssf_employee FROM ci_payslips WHERE staff_id=? AND salary_month=?", [$uid, date('Y-m')]);
$this_month_pay = $this_month_pay_q ? $this_month_pay_q->getRowArray() : null;
$ytd_q = $db->query("SELECT COALESCE(SUM(net_salary),0) AS net, COALESCE(SUM(paye_tax+nssf_employee),0) AS ded FROM ci_payslips WHERE staff_id=? AND salary_month LIKE ?", [$uid, date('Y').'%']);
$ytd = $ytd_q ? $ytd_q->getRowArray() : ['net'=>0,'ded'=>0];

// Tasks
$tasks_q = $db->query("SELECT task_id, task_name, task_status, end_date, start_date FROM ci_tasks WHERE assigned_to=? AND company_id=? ORDER BY task_status ASC, end_date ASC LIMIT 5", [$uid, $company_id]);
$tasks = $tasks_q ? $tasks_q->getResultArray() : [];
$tasks_due_week = 0;
$tasks_overdue = 0;
foreach($tasks as $t) {
    if(in_array($t['task_status'],['0','1']) && !empty($t['end_date'])) {
        $diff = (strtotime($t['end_date']) - time()) / 86400;
        if($diff <= 7 && $diff >= 0) $tasks_due_week++;
        if($diff < 0) $tasks_overdue++;
    }
}

// Expenses
$expenses_q = $db->query("SELECT expense_id, description, amount, status, expense_date FROM ci_expenses WHERE employee_id=? AND company_id=? ORDER BY created_at DESC LIMIT 4", [$uid, $company_id]);
$expenses = $expenses_q ? $expenses_q->getResultArray() : [];
$pending_expenses_q = $db->query("SELECT COALESCE(SUM(amount),0) AS s FROM ci_expenses WHERE employee_id=? AND status='0'", [$uid]);
$pending_expense_total = $pending_expenses_q ? (float)($pending_expenses_q->getRow()->s ?? 0) : 0;
$pending_expense_count_q = $db->query("SELECT COUNT(*) AS c FROM ci_expenses WHERE employee_id=? AND status='0'", [$uid]);
$pending_expense_count = $pending_expense_count_q ? (int)($pending_expense_count_q->getRow()->c ?? 0) : 0;

// Announcements
$announce_q = $db->query("SELECT announcement_id, title, summary, start_date, published_by FROM ci_announcements WHERE company_id=? AND is_active=1 ORDER BY start_date DESC LIMIT 5", [$company_id]);
$announcements = $announce_q ? $announce_q->getResultArray() : [];

// Documents
$docs_q = $db->query("SELECT document_id, document_name, document_type, document_file, created_at FROM ci_users_documents WHERE user_id=? ORDER BY document_id DESC LIMIT 5", [$uid]);
$documents = $docs_q ? $docs_q->getResultArray() : [];

// Training sessions (upcoming or recent)
$training_q = $db->query("SELECT t.training_id, t.start_date, t.finish_date, t.training_status, c.category_name AS training_type FROM ci_training t LEFT JOIN ci_erp_constants c ON c.constants_id=t.training_type_id WHERE t.employee_id=? AND t.company_id=? ORDER BY t.start_date DESC LIMIT 4", [$uid, $company_id]);
$training = $training_q ? $training_q->getResultArray() : [];

// Team on leave today
$team_leave_q = $db->query("SELECT u.first_name, u.last_name, c.category_name AS leave_type FROM ci_leave_applications la JOIN ci_erp_users u ON u.user_id=la.employee_id LEFT JOIN ci_erp_constants c ON c.constants_id=la.leave_type_id WHERE la.company_id=? AND la.status=2 AND la.employee_id!=? AND CURRENT_DATE BETWEEN safe_to_timestamp(la.from_date)::date AND safe_to_timestamp(la.to_date)::date LIMIT 5", [$company_id, $uid]);
$team_leave = $team_leave_q ? $team_leave_q->getResultArray() : [];

// Upcoming holidays
$holidays_q = $db->query("SELECT event_name, start_date, end_date FROM ci_holidays WHERE company_id=? AND safe_to_timestamp(start_date) >= CURRENT_DATE AND is_publish=1 ORDER BY safe_to_timestamp(start_date) ASC LIMIT 4", [$company_id]);
$holidays = $holidays_q ? $holidays_q->getResultArray() : [];

// Performance goals
$goals_q = $db->query("SELECT subject, goal_progress, start_date, end_date FROM ci_track_goals WHERE company_id=? ORDER BY tracking_id DESC LIMIT 4", [$company_id]);
$goals = $goals_q ? $goals_q->getResultArray() : [];

// Assets assigned
$assets_q = $db->query("SELECT a.name, a.company_asset_code, c.category_name FROM ci_assets a LEFT JOIN ci_erp_constants c ON c.constants_id=a.assets_category_id WHERE a.employee_id=? AND a.company_id=? LIMIT 5", [$uid, $company_id]);
$assets = $assets_q ? $assets_q->getResultArray() : [];

// Awards
$awards_q = $db->query("SELECT a.gift_item, a.cash_price, a.award_photo, c.category_name AS award_type, a.award_id FROM ci_awards a LEFT JOIN ci_erp_constants c ON c.constants_id=a.award_type_id WHERE a.employee_id=? AND a.company_id=? ORDER BY a.award_id DESC LIMIT 3", [$uid, $company_id]);
$awards = $awards_q ? $awards_q->getResultArray() : [];

// Money formatter
if(!function_exists('sa_fmt_money')) { function sa_fmt_money($n) { if($n>=1000000) return number_format($n/1000000,1).'M'; if($n>=1000) return number_format($n/1000,0).'K'; return number_format($n,0); } }
?>

<link rel="stylesheet" href="<?= base_url(); ?>/public/assets/css/sa-dashboard.css">

<!-- Header Strip -->
<div class="sa-header">
  <div>
    <h4 class="sa-header-title">Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening'); ?>, <?= esc($user_info['first_name']); ?></h4>
    <div class="sa-header-date"><?= esc($dept_info['designation_name'] ?? 'Staff'); ?> &middot; <?= esc($dept_info['department_name'] ?? ''); ?> &middot; <?= $shift_name; ?> <?= $shift_in ? date('H:i',strtotime($shift_in)).'–'.date('H:i',strtotime($shift_out)) : ''; ?></div>
  </div>
  <div class="sa-header-actions">
    <?php if($this_month_pay): ?>
    <a href="<?= site_url('erp/payroll-list'); ?>" class="btn btn-outline-primary btn-sm">Download Payslip</a>
    <?php endif; ?>
    <a href="<?= site_url('erp/leave-list'); ?>" class="btn btn-outline-primary btn-sm">Apply for Leave</a>
  </div>
</div>

<!-- Quick Actions — Row 1 -->
<div class="row mb-2">
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/leave-list'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-calendar"></i><div class="small mt-1">Apply Leave</div></div></div></a></div>
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/expenses'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-file-text"></i><div class="small mt-1">Submit Expense</div></div></div></a></div>
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/support-tickets'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-help-circle"></i><div class="small mt-1">Raise Ticket</div></div></div></a></div>
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/payroll-list'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-download"></i><div class="small mt-1">My Payslips</div></div></div></a></div>
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/advance-salary'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-dollar-sign"></i><div class="small mt-1">Advance Salary</div></div></div></a></div>
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/overtime-request'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-clock"></i><div class="small mt-1">Overtime</div></div></div></a></div>
</div>
<!-- Quick Actions — Row 2 -->
<div class="row mb-3">
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/my-profile'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-user"></i><div class="small mt-1">My Profile</div></div></div></a></div>
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/attendance-list'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-check-circle"></i><div class="small mt-1">Attendance</div></div></div></a></div>
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/tasks-grid'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-edit"></i><div class="small mt-1">Tasks</div></div></div></a></div>
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/projects-grid'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-layers"></i><div class="small mt-1">Projects</div></div></div></a></div>
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/news-list'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-bell"></i><div class="small mt-1">Announcements</div></div></div></a></div>
  <div class="col-lg-2 col-md-4 col-6 mb-2"><a href="<?= site_url('erp/holidays-list'); ?>" class="sa-kpi-link"><div class="card sa-kpi-card"><div class="card-body text-center py-3"><i class="feather icon-sun"></i><div class="small mt-1">Holidays</div></div></div></a></div>
</div>

<!-- Clock In/Out + Month Summary -->
<div class="row">
  <div class="col-xl-5 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Today's Attendance</h5>
        <span class="text-muted small"><?= date('l, d F Y'); ?></span>
      </div>
      <div class="card-body">
        <?php if(!$shift_in): ?>
        <span class="badge badge-light-warning mb-3">Day off — no shift scheduled</span>
        <?php endif; ?>
        <div class="row text-center mb-3">
          <div class="col-4">
            <div class="small text-muted">Clock in</div>
            <h4 class="mb-0"><?= $clocked_in ? date('h:i A', strtotime($today['clock_in'])) : '--:--'; ?></h4>
          </div>
          <div class="col-4">
            <div class="small text-muted">Clock out</div>
            <h4 class="mb-0"><?= $clocked_out ? date('h:i A', strtotime($today['clock_out'])) : '--:--'; ?></h4>
          </div>
          <div class="col-4">
            <div class="small text-muted">Hours worked</div>
            <h4 class="mb-0"><?= $hours_h; ?>h <?= $hours_m; ?>m</h4>
            <?php if($clocked_in && !$clocked_out): ?>
            <span class="badge badge-success"><span class="sa-pulse"></span> In progress</span>
            <?php endif; ?>
          </div>
        </div>
        <?php if($clocked_in): ?>
        <div class="text-muted small mb-2">Clocked in at <?= date('h:i A', strtotime($today['clock_in'])); ?></div>
        <?php endif; ?>
        <?php if(!$clocked_in): ?>
        <a href="<?= site_url('erp/attendance-list'); ?>" class="btn btn-success btn-block">Clock In Now</a>
        <?php elseif(!$clocked_out): ?>
        <a href="<?= site_url('erp/attendance-list'); ?>" class="btn btn-warning btn-block">Clock Out Now</a>
        <?php else: ?>
        <div class="text-center text-success small"><i class="feather icon-check-circle mr-1"></i>Shift complete</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-7 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Attendance Summary — <?= date('F Y'); ?></h5>
        <span class="text-muted small"><?= $ontime_rate; ?>% on-time</span>
      </div>
      <div class="card-body">
        <div class="row text-center mb-3">
          <div class="col-3"><h4 class="mb-0 text-success"><?= $present; ?></h4><small>Present</small></div>
          <div class="col-3"><h4 class="mb-0 text-danger"><?= $absent; ?></h4><small>Absent</small></div>
          <div class="col-3"><h4 class="mb-0 text-warning"><?= $late; ?></h4><small>Late</small></div>
          <div class="col-3"><h4 class="mb-0 text-info"><?= $on_leave; ?></h4><small>On Leave</small></div>
        </div>
        <!-- Heatmap -->
        <div class="d-flex flex-wrap" id="sa-heatmap">
          <?php for($d=1; $d<=$days_in_month; $d++):
            $status = $heatmap[$d] ?? '';
            $cls = 'sa-hm-empty';
            if($status=='Present') $cls='sa-hm-present';
            elseif($status=='Late') $cls='sa-hm-late';
            elseif($status=='Absent') $cls='sa-hm-absent';
            elseif($status=='On Leave' || $status=='Leave') $cls='sa-hm-leave';
            $is_today = ($d == (int)date('j'));
          ?>
          <div class="sa-hm-cell <?= $cls; ?> <?= $is_today ? 'sa-hm-today' : ''; ?>" title="<?= $d; ?> <?= date('M'); ?> — <?= $status ?: 'No record'; ?>"></div>
          <?php endfor; ?>
        </div>
        <div class="d-flex mt-2" style="gap:12px;">
          <small><span class="sa-hm-dot sa-hm-present"></span> Present</small>
          <small><span class="sa-hm-dot sa-hm-absent"></span> Absent</small>
          <small><span class="sa-hm-dot sa-hm-late"></span> Late</small>
          <small><span class="sa-hm-dot sa-hm-leave"></span> Leave</small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- KPI Strip -->
<div class="row">
  <div class="col-xl-3 col-md-6">
    <a href="<?= site_url('erp/leave-list'); ?>" class="sa-kpi-link">
      <div class="card sa-kpi-card"><div class="sa-kpi sa-kpi-success">
        <i class="feather icon-calendar sa-kpi-icon"></i>
        <p class="sa-kpi-number"><?= $total_leave_remaining; ?></p>
        <div class="sa-kpi-label">Leave days remaining</div>
        <div class="sa-kpi-trend sa-kpi-trend-up"><?= $total_leave_used; ?> used &middot; <?= $total_leave_pending; ?> pending</div>
      </div></div>
    </a>
  </div>
  <div class="col-xl-3 col-md-6">
    <a href="<?= site_url('erp/payroll-list'); ?>" class="sa-kpi-link">
      <div class="card sa-kpi-card"><div class="sa-kpi sa-kpi-warning">
        <i class="feather icon-dollar-sign sa-kpi-icon"></i>
        <p class="sa-kpi-number">UGX <?= sa_fmt_money($this_month_pay ? (float)$this_month_pay['net_salary'] : 0); ?></p>
        <div class="sa-kpi-label">Net pay this month</div>
        <div class="sa-kpi-trend sa-kpi-trend-up"><?= $this_month_pay ? 'Payslip ready' : 'Not yet processed'; ?></div>
      </div></div>
    </a>
  </div>
  <div class="col-xl-3 col-md-6">
    <a href="<?= site_url('erp/expenses'); ?>" class="sa-kpi-link">
      <div class="card sa-kpi-card"><div class="sa-kpi sa-kpi-info">
        <i class="feather icon-file-text sa-kpi-icon"></i>
        <p class="sa-kpi-number">UGX <?= sa_fmt_money($pending_expense_total); ?></p>
        <div class="sa-kpi-label">Pending expenses</div>
        <div class="sa-kpi-trend"><?= $pending_expense_count; ?> claims awaiting approval</div>
      </div></div>
    </a>
  </div>
  <div class="col-xl-3 col-md-6">
    <a href="<?= site_url('erp/tasks-grid'); ?>" class="sa-kpi-link">
      <div class="card sa-kpi-card"><div class="sa-kpi sa-kpi-primary">
        <i class="feather icon-check-square sa-kpi-icon"></i>
        <p class="sa-kpi-number"><?= $tasks_due_week; ?></p>
        <div class="sa-kpi-label">Tasks due this week</div>
        <?php if($tasks_overdue > 0): ?>
        <div class="sa-kpi-trend sa-kpi-trend-down"><?= $tasks_overdue; ?> overdue</div>
        <?php else: ?>
        <div class="sa-kpi-trend sa-kpi-trend-up">On track</div>
        <?php endif; ?>
      </div></div>
    </a>
  </div>
</div>

<!-- Payroll Chart + Leave Balance -->
<div class="row">
  <div class="col-xl-7 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Payroll — last 6 months</h5>
        <a href="<?= site_url('erp/payroll-list'); ?>" class="small text-primary">View all payslips</a>
      </div>
      <div class="card-body">
        <?php if(empty($payroll_history)): ?>
        <p class="text-muted text-center">No payroll data yet.</p>
        <?php else: ?>
        <div class="sa-chart-area">
          <?php foreach($payroll_history as $ph): $h = $payroll_max > 0 ? ((float)$ph['net_salary'] / $payroll_max) * 140 : 0; ?>
          <div class="sa-chart-bar-group">
            <div class="sa-chart-stacked"><div class="sa-chart-segment" data-height="<?= max(3,$h); ?>" data-color="var(--success)"></div></div>
            <div class="sa-chart-bar-value"><?= sa_fmt_money((float)$ph['net_salary']); ?></div>
            <div class="sa-chart-bar-label"><?= date('M', strtotime($ph['salary_month'].'-01')); ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="row text-center mt-3">
          <div class="col-4"><h5 class="mb-0">UGX <?= sa_fmt_money((float)($ytd['net'] ?? 0)); ?></h5><small class="text-muted">YTD earnings</small></div>
          <div class="col-4"><h5 class="mb-0">UGX <?= sa_fmt_money($this_month_pay ? (float)$this_month_pay['net_salary'] : 0); ?></h5><small class="text-muted">This month net</small></div>
          <div class="col-4"><h5 class="mb-0 text-danger">UGX <?= sa_fmt_money((float)($ytd['ded'] ?? 0)); ?></h5><small class="text-muted">PAYE + NSSF deducted</small></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-5 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Leave Balance — <?= date('Y'); ?></h5>
        <span class="text-muted small"><?= array_sum(array_column($leave_balances,'total')); ?> days total</span>
      </div>
      <div class="card-body">
        <?php foreach(array_slice($leave_balances, 0, 4) as $lb): $pct = $lb['total'] > 0 ? round(($lb['remaining']/$lb['total'])*100) : 0; ?>
        <div class="d-flex justify-content-between mb-1">
          <span class="small"><?= esc($lb['name']); ?></span>
          <span class="small font-weight-bold"><?= $lb['remaining']; ?> / <?= $lb['total']; ?> remaining</span>
        </div>
        <div class="progress mb-3" style="height:6px;">
          <div class="progress-bar bg-success" style="width:<?= $pct; ?>%"></div>
        </div>
        <?php endforeach; ?>

        <?php if(!empty($recent_leaves)): ?>
        <h6 class="text-muted mt-3 mb-2">Recent leave requests</h6>
        <?php foreach($recent_leaves as $rl):
          $ls = (int)$rl['status'];
          $badge = $ls==2 ? 'badge-light-success' : ($ls==1 ? 'badge-light-warning' : 'badge-light-danger');
          $label = $ls==2 ? 'Approved' : ($ls==1 ? 'Pending' : 'Rejected');
        ?>
        <div class="d-flex justify-content-between mb-2">
          <?php $fd = str_replace('/','-',$rl['from_date']??''); $td = str_replace('/','-',$rl['to_date']??''); ?>
          <span class="small"><?= esc($rl['category_name']); ?> &middot; <?= $fd ? date('d M',strtotime($fd)) : ''; ?><?= ($fd && $td && $fd!=$td) ? '–'.date('d M',strtotime($td)) : ''; ?></span>
          <span class="badge <?= $badge; ?>"><?= $label; ?></span>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Tasks + Announcements + Expenses -->
<div class="row">
  <div class="col-xl-4 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">My Tasks</h5>
        <a href="<?= site_url('erp/tasks-grid'); ?>" class="small text-primary">View all</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($tasks)): ?>
        <div class="p-4 text-center text-muted small">No tasks assigned.</div>
        <?php else: foreach($tasks as $t):
          $is_done = in_array($t['task_status'],['2','4']);
          $due = !empty($t['end_date']) ? $t['end_date'] : '';
          $overdue = $due && strtotime($due) < time() && !$is_done;
          $due_label = $due ? (date('Y-m-d',strtotime($due))==date('Y-m-d') ? 'Today' : date('d M',strtotime($due))) : '';
        ?>
        <div class="d-flex justify-content-between align-items-start px-3 py-2 border-bottom">
          <div class="<?= $is_done ? 'text-decoration-line-through text-muted' : ''; ?>">
            <span class="sa-hm-dot <?= $overdue ? 'sa-hm-absent' : ($is_done ? 'sa-hm-present' : 'sa-hm-late'); ?> mr-1"></span>
            <span class="small"><?= esc($t['task_name']); ?></span>
          </div>
          <span class="small <?= $overdue ? 'text-danger' : 'text-muted'; ?>"><?= $is_done ? 'Done' : $due_label; ?></span>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Announcements</h5>
        <span class="text-muted small"><?= count($announcements); ?> recent</span>
      </div>
      <div class="card-body p-0">
        <?php if(empty($announcements)): ?>
        <div class="p-4 text-center text-muted small">No announcements.</div>
        <?php else: foreach($announcements as $an): ?>
        <div class="px-3 py-2 border-bottom">
          <div class="small font-weight-bold"><?= esc($an['title']); ?></div>
          <div class="small text-muted"><?= esc($an['summary'] ?? ''); ?></div>
          <div class="small text-muted"><?= date('d M', strtotime($an['start_date'])); ?></div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Expense Claims</h5>
        <a href="<?= site_url('erp/expenses'); ?>" class="small text-primary">New claim</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($expenses)): ?>
        <div class="p-4 text-center text-muted small">No expense claims.</div>
        <?php else: foreach($expenses as $ex):
          $es = $ex['status'] ?? '0';
          $eb = $es=='1' ? 'badge-light-success' : ($es=='0' ? 'badge-light-warning' : 'badge-light-danger');
          $el = $es=='1' ? 'Approved' : ($es=='0' ? 'Pending' : 'Rejected');
        ?>
        <div class="d-flex justify-content-between align-items-start px-3 py-2 border-bottom">
          <div>
            <div class="small font-weight-bold"><?= esc($ex['description'] ?? 'Expense'); ?></div>
            <div class="small text-muted">Submitted <?= date('d M', strtotime($ex['expense_date'])); ?></div>
          </div>
          <div class="text-right">
            <div class="small font-weight-bold">UGX <?= sa_fmt_money((float)$ex['amount']); ?></div>
            <span class="badge <?= $eb; ?>"><?= $el; ?></span>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="px-3 py-2 text-right">
          <small>Pending reimbursement <strong class="text-success">UGX <?= sa_fmt_money($pending_expense_total); ?></strong></small>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Row: Team Calendar + Upcoming Holidays -->
<div class="row">
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="feather icon-users mr-2"></i>Team — Who's Out Today</h5>
      </div>
      <div class="card-body p-0">
        <?php if(empty($team_leave)): ?>
        <div class="p-4 text-center text-muted small">Everyone is in today</div>
        <?php else: foreach($team_leave as $tl): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
          <div><span class="sa-hm-dot sa-hm-leave mr-1"></span><strong class="small"><?= esc($tl['first_name'].' '.$tl['last_name']); ?></strong></div>
          <span class="badge badge-light-info"><?= esc($tl['leave_type'] ?? 'Leave'); ?></span>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="feather icon-sun mr-2"></i>Upcoming Holidays</h5>
        <a href="<?= site_url('erp/holidays-list'); ?>" class="small text-primary">View all</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($holidays)): ?>
        <div class="p-4 text-center text-muted small">No upcoming holidays</div>
        <?php else: foreach($holidays as $h): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
          <strong class="small"><?= esc($h['event_name']); ?></strong>
          <span class="small text-muted"><?= date('d M Y', strtotime(str_replace('/','-',$h['start_date']))); ?></span>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Row: Training + Performance Goals -->
<div class="row">
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="feather icon-target mr-2"></i>Training Schedule</h5>
        <a href="<?= site_url('erp/training-sessions'); ?>" class="small text-primary">View all</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($training)): ?>
        <div class="p-4 text-center text-muted small">No training sessions assigned</div>
        <?php else: foreach($training as $tr):
          $ts = $tr['training_status'] ?? '';
          $tb = $ts=='Completed' ? 'badge-light-success' : ($ts=='Started' ? 'badge-light-primary' : 'badge-light-warning');
        ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
          <div>
            <div class="small font-weight-bold"><?= esc($tr['training_type'] ?? 'Training'); ?></div>
            <div class="small text-muted"><?= date('d M', strtotime(str_replace('/','-',$tr['start_date'] ?? ''))); ?> — <?= date('d M', strtotime(str_replace('/','-',$tr['finish_date'] ?? ''))); ?></div>
          </div>
          <span class="badge <?= $tb; ?>"><?= esc($ts ?: 'Scheduled'); ?></span>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="feather icon-trending-up mr-2"></i>Performance Goals</h5>
        <a href="<?= site_url('erp/track-goals'); ?>" class="small text-primary">View all</a>
      </div>
      <div class="card-body">
        <?php if(empty($goals)): ?>
        <div class="text-center text-muted small">No goals set</div>
        <?php else: foreach($goals as $g): $prog = (int)($g['goal_progress'] ?? 0); ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span class="small font-weight-bold"><?= esc($g['subject']); ?></span>
            <span class="small text-muted"><?= $prog; ?>%</span>
          </div>
          <div class="progress" style="height:6px;">
            <div class="progress-bar bg-<?= $prog >= 80 ? 'success' : ($prog >= 40 ? 'primary' : 'warning'); ?>" style="width:<?= $prog; ?>%"></div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Row: Assets + Awards + Documents -->
<div class="row">
  <div class="col-xl-4 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="feather icon-monitor mr-2"></i>My Assets</h5>
        <a href="<?= site_url('erp/assets-list'); ?>" class="small text-primary">View all</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($assets)): ?>
        <div class="p-4 text-center text-muted small">No assets assigned</div>
        <?php else: foreach($assets as $as): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
          <div>
            <div class="small font-weight-bold"><?= esc($as['name']); ?></div>
            <div class="small text-muted"><?= esc($as['category_name'] ?? ''); ?></div>
          </div>
          <span class="badge badge-light-secondary"><?= esc($as['company_asset_code'] ?? ''); ?></span>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="feather icon-award mr-2"></i>Awards</h5>
        <a href="<?= site_url('erp/awards-list'); ?>" class="small text-primary">View all</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($awards)): ?>
        <div class="p-4 text-center text-muted small">No awards yet</div>
        <?php else: foreach($awards as $aw): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
          <div>
            <div class="small font-weight-bold"><?= esc($aw['award_type'] ?? 'Award'); ?></div>
            <div class="small text-muted"><?= esc($aw['gift_item'] ?? ''); ?></div>
          </div>
          <?php if(!empty($aw['cash_price']) && $aw['cash_price'] > 0): ?>
          <span class="small font-weight-bold text-success">UGX <?= sa_fmt_money((float)$aw['cash_price']); ?></span>
          <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="feather icon-folder mr-2"></i>My Documents</h5>
        <a href="<?= site_url('erp/upload-files'); ?>" class="small text-primary">Upload</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($documents)): ?>
        <div class="p-4 text-center text-muted small">No documents uploaded</div>
        <?php else: foreach($documents as $doc): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
          <div>
            <div class="small font-weight-bold"><?= esc($doc['document_name']); ?></div>
            <div class="small text-muted"><?= esc($doc['document_type'] ?? ''); ?></div>
          </div>
          <?php if(!empty($doc['document_file'])): ?>
          <a href="<?= base_url('public/uploads/documents/'.$doc['document_file']); ?>" class="btn btn-sm btn-light-primary" target="_blank"><i class="feather icon-download"></i></a>
          <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
