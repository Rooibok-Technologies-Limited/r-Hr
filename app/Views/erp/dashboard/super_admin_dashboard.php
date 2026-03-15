<?php
use App\Models\UsersModel;
use App\Models\SystemModel;
use App\Models\MembershipModel;
use App\Models\CompanymembershipModel;

$SystemModel = new SystemModel();
$UsersModel = new UsersModel();
$MembershipModel = new MembershipModel();
$CompanymembershipModel = new CompanymembershipModel();

$session = \Config\Services::session();
$usession = get_safe_session();
$xin_system = $SystemModel->where('setting_id', 1)->first();
$user_info = $usession ? $UsersModel->where('user_id', $usession['sup_user_id'])->first() : null;
$db = \Config\Database::connect();

// ── Core stats ──
$total_companies = $UsersModel->where('user_type','company')->countAllResults();
$_as_q = $db->query("SELECT COUNT(*) AS c FROM ci_company_membership WHERE is_active = 1");
$active_subs     = $_as_q ? (int)($_as_q->getRow()->c ?? 0) : 0;
$inactive_subs   = $total_companies - $active_subs;
$activation_rate = $total_companies > 0 ? round(($active_subs / $total_companies) * 100, 1) : 0;

// Revenue this month vs last
$_rtm = $db->query("SELECT COALESCE(SUM(amount),0) AS s FROM ci_subscription_invoices WHERE status='paid' AND TO_CHAR(issued_at,'YYYY-MM')=TO_CHAR(NOW(),'YYYY-MM')");
$rev_this_month = $_rtm ? (float)($_rtm->getRow()->s ?? 0) : 0;
$_rlm = $db->query("SELECT COALESCE(SUM(amount),0) AS s FROM ci_subscription_invoices WHERE status='paid' AND TO_CHAR(issued_at,'YYYY-MM')=TO_CHAR(NOW()-INTERVAL '1 month','YYYY-MM')");
$rev_last_month = $_rlm ? (float)($_rlm->getRow()->s ?? 0) : 0;
$rev_change_pct = $rev_last_month > 0 ? round((($rev_this_month - $rev_last_month) / $rev_last_month) * 100) : 0;

// New companies this month (created_at is VARCHAR, cast to timestamp)
$_ntm = $db->query("SELECT COUNT(*) AS c FROM ci_erp_users WHERE user_type='company' AND TO_CHAR(safe_to_timestamp(created_at),'YYYY-MM')=TO_CHAR(NOW(),'YYYY-MM')");
$new_this_month = $_ntm ? (int)($_ntm->getRow()->c ?? 0) : 0;

// Inactive change vs last month
$_ilm = $db->query("SELECT COUNT(*) AS c FROM ci_company_membership WHERE is_active=0 AND TO_CHAR(COALESCE(expiry_date,safe_to_timestamp(created_at)),'YYYY-MM')=TO_CHAR(NOW()-INTERVAL '1 month','YYYY-MM')");
$inactive_last_month = $_ilm ? (int)($_ilm->getRow()->c ?? 0) : 0;
$inactive_change = $inactive_subs - $inactive_last_month;

// ── MRR + Churn ──
$_mrr_q = $db->query("SELECT COALESCE(SUM(m.price),0) AS mrr FROM ci_company_membership cm JOIN ci_membership m ON m.membership_id=cm.membership_id WHERE cm.is_active=1");
$mrr = $_mrr_q ? (float)($_mrr_q->getRow()->mrr ?? 0) : 0;
$_mrr_lm = $db->query("SELECT COALESCE(SUM(m.price),0) AS mrr FROM ci_company_membership cm JOIN ci_membership m ON m.membership_id=cm.membership_id WHERE cm.is_active=1 AND safe_to_timestamp(cm.created_at) < DATE_TRUNC('month',NOW())");
$mrr_last = $_mrr_lm ? (float)($_mrr_lm->getRow()->mrr ?? 0) : 0;
$mrr_change = $mrr_last > 0 ? round((($mrr - $mrr_last)/$mrr_last)*100,1) : 0;
$_ch_q = $db->query("SELECT COUNT(*) AS c FROM ci_company_membership WHERE is_active=0 AND expiry_date>=DATE_TRUNC('month',NOW()) AND expiry_date<=NOW()");
$churned = $_ch_q ? (int)($_ch_q->getRow()->c ?? 0) : 0;
$churn_rate = $total_companies > 0 ? round(($churned/$total_companies)*100,1) : 0;

// MRR monthly trend (last 8 months — revenue per month as MRR proxy)
$_mrr_trend_q = $db->query("SELECT TO_CHAR(si.issued_at,'YYYY-MM') AS month, COALESCE(SUM(si.amount),0) AS mrr FROM ci_subscription_invoices si WHERE si.status='paid' AND si.issued_at>=NOW()-INTERVAL '8 months' GROUP BY month ORDER BY month");
$mrr_trend_raw = $_mrr_trend_q ? $_mrr_trend_q->getResultArray() : [];
$mrr_trend = [];
for($i=7;$i>=0;$i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $label = date('M', strtotime($m.'-01'));
    $val = 0;
    foreach($mrr_trend_raw as $r) { if($r['month'] === $m) $val = (float)$r['mrr']; }
    $mrr_trend[] = ['label'=>$label, 'value'=>$val];
}
$mrr_trend_max = max(1, max(array_column($mrr_trend, 'value')));

// ── Revenue Forecast ──
$_fc_q = $db->query("SELECT COALESCE(SUM(m.price),0) AS f FROM ci_company_membership cm JOIN ci_membership m ON m.membership_id=cm.membership_id WHERE cm.is_active=1 AND cm.expiry_date>(DATE_TRUNC('month',NOW())+INTERVAL '2 months'-INTERVAL '1 day')");
$forecast = $_fc_q ? (float)($_fc_q->getRow()->f ?? 0) : 0;
$_atrisk_q = $db->query("SELECT COUNT(*) AS c FROM ci_company_membership WHERE is_active=1 AND expiry_date>=DATE_TRUNC('month',NOW()+INTERVAL '1 month') AND expiry_date<DATE_TRUNC('month',NOW()+INTERVAL '2 months')");
$at_risk = $_atrisk_q ? (int)($_atrisk_q->getRow()->c ?? 0) : 0;

// ── Sparklines (8 weeks) — cast VARCHAR created_at to timestamp ──
$spark_co_q  = $db->query("SELECT DATE_TRUNC('week',safe_to_timestamp(created_at))::date AS w, COUNT(*) AS c FROM ci_erp_users WHERE user_type='company' AND safe_to_timestamp(created_at)>=NOW()-INTERVAL '8 weeks' GROUP BY w ORDER BY w");
$spark_co    = $spark_co_q ? array_pad(array_column($spark_co_q->getResultArray(),'c'),8,0) : array_fill(0,8,0);
$spark_act_q = $db->query("SELECT DATE_TRUNC('week',safe_to_timestamp(created_at))::date AS w, COUNT(*) AS c FROM ci_company_membership WHERE is_active=1 AND safe_to_timestamp(created_at)>=NOW()-INTERVAL '8 weeks' GROUP BY w ORDER BY w");
$spark_act   = $spark_act_q ? array_pad(array_column($spark_act_q->getResultArray(),'c'),8,0) : array_fill(0,8,0);
$spark_exp_q = $db->query("SELECT DATE_TRUNC('week',expiry_date)::date AS w, COUNT(*) AS c FROM ci_company_membership WHERE is_active=0 AND expiry_date>=NOW()-INTERVAL '8 weeks' GROUP BY w ORDER BY w");
$spark_exp   = $spark_exp_q ? array_pad(array_column($spark_exp_q->getResultArray(),'c'),8,0) : array_fill(0,8,0);
$spark_rev_q = $db->query("SELECT DATE_TRUNC('week',issued_at)::date AS w, COALESCE(SUM(amount),0) AS c FROM ci_subscription_invoices WHERE status='paid' AND issued_at>=NOW()-INTERVAL '8 weeks' GROUP BY w ORDER BY w");
$spark_rev   = $spark_rev_q ? array_pad(array_column($spark_rev_q->getResultArray(),'c'),8,0) : array_fill(0,8,0);

// ── Revenue by month by plan (all 3 periods, built server-side for instant switching) ──
$_rr_all = $db->query("SELECT TO_CHAR(si.issued_at,'YYYY-MM') AS month, m.membership_type, COALESCE(SUM(si.amount),0) AS total FROM ci_subscription_invoices si JOIN ci_membership m ON m.membership_id=si.membership_id WHERE si.status='paid' GROUP BY month,m.membership_type ORDER BY month");
$revenue_all_raw = $_rr_all ? $_rr_all->getResultArray() : [];

$plan_types = array_values(array_unique(array_column($revenue_all_raw,'membership_type')));
if(empty($plan_types)) $plan_types = ['Pro Plan'];
$plan_colors = ['#4ade80','#60a5fa','#2dd4bf','#fbbf24','#f87171','#a78bfa'];

// Build datasets for each period
$_chart_periods = [];
foreach(['6','12','all'] as $_p) {
    $_cnt = $_p === 'all' ? 24 : (int)$_p;
    $_ml = []; for($i=$_cnt-1;$i>=0;$i--) $_ml[]=date('Y-m',strtotime("-{$i} months"));
    $_cd = []; $_tot = 0;
    foreach($_ml as $m) {
        $row = ['month'=>date('M',strtotime($m.'-01'))];
        foreach($plan_types as $p) $row[$p]=0;
        foreach($revenue_all_raw as $r) { if($r['month']===$m) { $row[$r['membership_type']]=(float)$r['total']; $_tot+=(float)$r['total']; } }
        $has_data = false; foreach($plan_types as $p) if($row[$p]>0) $has_data=true;
        // For 'all', skip months with no data at the start
        $_cd[] = $row;
    }
    // Trim leading empty months for 'all'
    if($_p === 'all') {
        while(!empty($_cd)) {
            $first = $_cd[0]; $has = false;
            foreach($plan_types as $p) if($first[$p]>0) $has = true;
            if(!$has && count($_cd)>6) array_shift($_cd); else break;
        }
    }
    $_chart_periods[$_p] = ['chart'=>$_cd,'total'=>$_tot];
}
$chart_data = $_chart_periods['6']['chart'];
$revenue_total_6m = $_chart_periods['6']['total'];
$chart_max = 1;
foreach($chart_data as $cd) { $s=0; foreach($plan_types as $p) $s+=$cd[$p]; $chart_max=max($chart_max,$s); }

// ── Plan distribution ──
$_pd = $db->query("SELECT m.membership_type, COUNT(*) AS cnt FROM ci_company_membership cm JOIN ci_membership m ON m.membership_id=cm.membership_id WHERE cm.is_active=1 GROUP BY m.membership_type ORDER BY cnt DESC");
$plan_dist = $_pd ? $_pd->getResultArray() : [];

// ── Expiring within 7 days ──
$_eq = $db->query("SELECT cm.*, u.company_name, u.email, m.membership_type, (cm.expiry_date::date - CURRENT_DATE) AS days_left FROM ci_company_membership cm JOIN ci_erp_users u ON u.user_id=cm.company_id JOIN ci_membership m ON m.membership_id=cm.membership_id WHERE cm.is_active=1 AND cm.expiry_date<=(CURRENT_DATE+7) AND cm.expiry_date>=CURRENT_DATE ORDER BY cm.expiry_date ASC");
$expiring = $_eq ? $_eq->getResultArray() : [];
$critical_expiring = array_filter($expiring, fn($e) => (int)$e['days_left'] <= 2);

// ── Geographic breakdown ──
$_gq = $db->query("SELECT COALESCE(NULLIF(TRIM(city),''),'Other') AS location, COUNT(*) AS cnt FROM ci_erp_users WHERE user_type='company' GROUP BY location ORDER BY cnt DESC LIMIT 8");
$geo = $_gq ? $_gq->getResultArray() : [];
$geo_max = !empty($geo) ? (int)max(array_column($geo,'cnt')) : 1;

// ── Industry breakdown ──
$_iq = $db->query("SELECT COALESCE(c.category_name,'Other') AS industry, COUNT(*) AS cnt FROM ci_erp_users u LEFT JOIN ci_erp_constants c ON c.constants_id=u.company_type_id WHERE u.user_type='company' GROUP BY industry ORDER BY cnt DESC LIMIT 8");
$industries = $_iq ? $_iq->getResultArray() : [];

// ── Activity feed (created_at is VARCHAR in ci_erp_users, cast to timestamp) ──
$af_q = $db->query("
    (SELECT 'renewal' AS type, u.company_name AS label, si.amount::text AS detail, si.issued_at::text AS event_at FROM ci_subscription_invoices si JOIN ci_erp_users u ON u.user_id=si.company_id WHERE si.status='paid' ORDER BY si.issued_at DESC LIMIT 5)
    UNION ALL (SELECT 'registration', company_name, city, created_at FROM ci_erp_users WHERE user_type='company' ORDER BY created_at DESC LIMIT 3)
    UNION ALL (SELECT 'reminder', u.company_name, br.reminder_day::text, br.sent_at::text FROM ci_billing_reminders_log br JOIN ci_erp_users u ON u.user_id=br.company_id ORDER BY br.sent_at DESC LIMIT 3)
    UNION ALL (SELECT 'broadcast', b.subject, b.total_recipients::text, b.sent_at::text FROM ci_broadcasts b WHERE b.status='sent' ORDER BY b.sent_at DESC LIMIT 2)
    ORDER BY event_at DESC LIMIT 10
");
$activity_feed = $af_q ? $af_q->getResultArray() : [];

// ── System health ──
$_pgq = $db->query("SELECT pg_size_pretty(pg_database_size(current_database())) AS size");
$pg_size = $_pgq ? ($_pgq->getRow()->size ?? 'N/A') : 'N/A';
$redis_info = 'N/A';
try { $r = new \Redis(); if($r->connect('redis',6379,1)) { $i=$r->info('stats'); $h=(int)($i['keyspace_hits']??0); $m=(int)($i['keyspace_misses']??0); $redis_info=($h+$m)>0?round($h/($h+$m)*100).'% hit':'0% hit'; } } catch(\Exception $e) { $redis_info='offline'; }
$_smq = $db->query("SELECT COUNT(*) AS c FROM ci_broadcast_log WHERE email_sent=1 AND DATE(sent_at)=CURRENT_DATE");
$smtp_today = $_smq ? (int)($_smq->getRow()->c ?? 0) : 0;
$queue_ready = 0;
try { $bs=@fsockopen('beanstalkd',11300,$en,$es,1); if($bs){fwrite($bs,"stats\r\n");$st=fread($bs,4096);preg_match('/current-jobs-ready: (\d+)/',$st,$qm);$queue_ready=(int)($qm[1]??0);fclose($bs);} } catch(\Exception $e){}
$archive_size = 'N/A';
try { $adb=\Config\Database::connect('archive'); $archive_size=$adb->query("SELECT pg_size_pretty(pg_database_size(current_database())) AS size")->getRow()->size ?? 'N/A'; } catch(\Exception $e){}
$last_backup = $db->query("SELECT created_at FROM ci_database_backup ORDER BY backup_id DESC LIMIT 1")->getRow();
$last_backup_str = $last_backup ? date('d M H:i', strtotime($last_backup->created_at)) : 'Never';

// ── Helpers (namespaced to avoid conflicts) ──
if(!function_exists('sa_fmt_money')) {
    function sa_fmt_money($n) { if($n>=1000000) return number_format($n/1000000,1).'M'; if($n>=1000) return number_format($n/1000,0).'K'; return number_format($n,0); }
}
if(!function_exists('sa_time_ago')) {
    function sa_time_ago($dt) { if(!$dt) return ''; $d=time()-strtotime($dt); if($d<60) return 'just now'; if($d<3600) return floor($d/60).' min ago'; if($d<86400) return floor($d/3600).' hrs ago'; if($d<172800) return 'Yesterday'; return date('d M',strtotime($dt)); }
}
?>

<link rel="stylesheet" href="<?= base_url(); ?>/public/assets/css/sa-dashboard.css">

<div class="sa-dash">

<!-- Header -->
<div class="sa-header">
  <div>
    <h4 class="sa-header-title">Super Admin Dashboard</h4>
    <div class="sa-header-date"><?= date('l, d F Y'); ?> &middot; Kampala, Uganda</div>
  </div>
  <div class="sa-header-actions">
    <a href="<?= site_url('erp/companies-list'); ?>" class="btn btn-outline-primary btn-sm">+ Add Company</a>
    <a href="<?= site_url('erp/broadcasts'); ?>" class="btn btn-outline-primary btn-sm">Send Broadcast</a>
  </div>
</div>

<?php if(!empty($critical_expiring)): ?>
<!-- Urgent Alert Strip -->
<div class="sa-alert-strip">
  <i class="feather icon-alert-triangle sa-alert-icon"></i>
  <div class="sa-alert-text"><?= count($critical_expiring); ?> compan<?= count($critical_expiring)==1?'y':'ies'; ?> expire<?= count($critical_expiring)==1?'s':''; ?> within 48 hours — immediate action needed</div>
  <div class="sa-alert-chips">
    <?php foreach($critical_expiring as $ce): ?>
    <span class="sa-alert-chip"><?= esc($ce['company_name']); ?> — <?= (int)$ce['days_left']==0?'TODAY':$ce['days_left'].' day'.((int)$ce['days_left']!=1?'s':''); ?></span>
    <?php endforeach; ?>
  </div>
  <a href="<?= site_url('erp/broadcasts'); ?>" class="btn btn-danger btn-sm">Send Reminders</a>
</div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="row">
  <?php
  $kpi_ids = ['sa-kpi-total-companies','sa-kpi-active-subs','sa-kpi-inactive','sa-kpi-revenue'];
  $kpis = [
    ['num'=>$total_companies,'label'=>'Total companies','trend'=>'+'.$new_this_month.' this month','dir'=>'up','icon'=>'icon-briefcase','variant'=>'primary','spark'=>$spark_co,'url'=>site_url('erp/companies-list')],
    ['num'=>$active_subs,'label'=>'Active subscriptions','trend'=>'&uarr; '.$activation_rate.'% activation rate','dir'=>'up','icon'=>'icon-check-circle','variant'=>'success','spark'=>$spark_act,'url'=>site_url('erp/membership-list')],
    ['num'=>$inactive_subs,'label'=>'Expired / inactive','trend'=>($inactive_change>=0?'+':'').$inactive_change.' vs last month'.($inactive_change>0?' — investigate':''),'dir'=>$inactive_change>0?'down':'up','icon'=>'icon-alert-circle','variant'=>'danger','spark'=>$spark_exp,'url'=>site_url('erp/companies-list')],
    ['num'=>'UGX '.sa_fmt_money($rev_this_month),'label'=>'Revenue this month','trend'=>($rev_change_pct>=0?'&uarr; +':'&darr; ').$rev_change_pct.'% vs last month','dir'=>$rev_change_pct>=0?'up':'down','icon'=>'icon-dollar-sign','variant'=>'warning','spark'=>$spark_rev,'url'=>site_url('erp/all-subscription-invoices')],
  ];
  $ki=0; foreach($kpis as $k): $smax=max(1,max($k['spark'])); ?>
  <div class="col-xl-3 col-md-6">
    <a href="<?= $k['url']; ?>" class="sa-kpi-link">
      <div class="card sa-kpi-card">
        <div class="sa-kpi sa-kpi-<?= $k['variant']; ?>">
          <i class="feather <?= $k['icon']; ?> sa-kpi-icon"></i>
          <p class="sa-kpi-number" id="<?= $kpi_ids[$ki]; ?>"><?= $k['num']; ?></p>
          <div class="sa-kpi-label"><?= $k['label']; ?></div>
          <div class="sa-kpi-trend sa-kpi-trend-<?= $k['dir']; ?>"><?= $k['trend']; ?></div>
          <div class="sa-sparkline">
            <?php foreach($k['spark'] as $sv): $h=max(3,($sv/$smax)*28); ?>
            <div class="sa-spark-bar" data-height="<?= $h; ?>"></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </a>
  </div>
  <?php $ki++; endforeach; ?>
</div>

<!-- MRR Line Chart + Forecast -->
<div class="row">
  <div class="col-xl-8 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Monthly Recurring Revenue</h5>
          <span class="text-muted small">Current MRR: <strong id="sa-kpi-mrr">UGX <?= sa_fmt_money($mrr); ?></strong>
          &nbsp; <span class="sa-kpi-trend-<?= $mrr_change >= 0 ? 'up' : 'down'; ?>"><?= ($mrr_change >= 0 ? '&uarr; +' : '&darr; ').$mrr_change; ?>%</span>
          &nbsp; Churn: <span class="badge badge-<?= $churn_rate > 5 ? 'danger' : ($churn_rate > 2 ? 'warning' : 'success'); ?>"><?= $churn_rate; ?>%</span>
          </span>
        </div>
        <a href="<?= site_url('erp/all-subscription-invoices'); ?>" class="btn btn-outline-primary btn-sm">View Invoices</a>
      </div>
      <div class="card-body">
        <?php
        // Build SVG line chart
        $svgW = 600; $svgH = 160; $padL = 10; $padR = 10; $padT = 10; $padB = 30;
        $chartW = $svgW - $padL - $padR;
        $chartH = $svgH - $padT - $padB;
        $points = [];
        $count = count($mrr_trend);
        foreach($mrr_trend as $i => $pt) {
            $x = $padL + ($count > 1 ? ($i / ($count - 1)) * $chartW : $chartW / 2);
            $y = $padT + $chartH - ($mrr_trend_max > 0 ? ($pt['value'] / $mrr_trend_max) * $chartH : 0);
            $points[] = ['x'=>round($x,1), 'y'=>round($y,1), 'label'=>$pt['label'], 'value'=>$pt['value']];
        }
        $polyline = implode(' ', array_map(fn($p) => $p['x'].','.$p['y'], $points));
        // Area fill path
        $area_path = 'M'.$points[0]['x'].','.$points[0]['y'];
        for($i=1;$i<count($points);$i++) $area_path .= ' L'.$points[$i]['x'].','.$points[$i]['y'];
        $area_path .= ' L'.$points[count($points)-1]['x'].','.($padT+$chartH).' L'.$points[0]['x'].','.($padT+$chartH).' Z';
        ?>
        <svg class="sa-mrr-chart" viewBox="0 0 <?= $svgW; ?> <?= $svgH; ?>" preserveAspectRatio="none">
          <defs>
            <linearGradient id="mrr-gradient" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#17a2b8" stop-opacity="0.3"/>
              <stop offset="100%" stop-color="#17a2b8" stop-opacity="0.02"/>
            </linearGradient>
          </defs>
          <path d="<?= $area_path; ?>" fill="url(#mrr-gradient)"/>
          <polyline points="<?= $polyline; ?>" fill="none" stroke="#17a2b8" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
          <?php foreach($points as $p): ?>
          <circle cx="<?= $p['x']; ?>" cy="<?= $p['y']; ?>" r="4" fill="#fff" stroke="#17a2b8" stroke-width="2"/>
          <?php endforeach; ?>
          <?php foreach($points as $p): ?>
          <text x="<?= $p['x']; ?>" y="<?= $padT + $chartH + 18; ?>" text-anchor="middle" fill="#999" font-size="11"><?= $p['label']; ?></text>
          <?php endforeach; ?>
          <?php foreach($points as $p): if($p['value'] > 0): ?>
          <text x="<?= $p['x']; ?>" y="<?= max($padT + 5, $p['y'] - 8); ?>" text-anchor="middle" fill="#17a2b8" font-size="10" font-weight="600"><?= sa_fmt_money($p['value']); ?></text>
          <?php endif; endforeach; ?>
        </svg>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-12">
    <a href="<?= site_url('erp/membership-list'); ?>" class="sa-kpi-link">
      <div class="card sa-kpi-card">
        <div class="sa-kpi sa-kpi-success">
          <i class="feather icon-bar-chart-2 sa-kpi-icon"></i>
          <p class="sa-kpi-number" id="sa-kpi-forecast">UGX <?= sa_fmt_money($forecast); ?></p>
          <div class="sa-kpi-label">Predicted revenue next month</div>
          <div class="sa-kpi-trend sa-kpi-trend-<?= $at_risk > 0 ? 'down' : 'up'; ?>">
            <?= $at_risk; ?> subscription<?= $at_risk != 1 ? 's' : ''; ?> at risk of non-renewal
          </div>
        </div>
      </div>
    </a>
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <span class="small text-muted">Current MRR</span>
          <span class="small font-weight-bold">UGX <?= sa_fmt_money($mrr); ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="small text-muted">Projected ARR</span>
          <span class="small font-weight-bold">UGX <?= sa_fmt_money($mrr * 12); ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="small text-muted">Churned this month</span>
          <span class="small font-weight-bold"><?= $churned; ?> companies</span>
        </div>
        <div class="d-flex justify-content-between">
          <span class="small text-muted">Churn rate</span>
          <span class="badge badge-<?= $churn_rate > 5 ? 'danger' : ($churn_rate > 2 ? 'warning' : 'success'); ?>"><?= $churn_rate; ?>%</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Revenue Chart + Plan Distribution -->
<div class="row">
  <div class="col-xl-8 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Monthly subscription revenue (UGX)</h5>
        <div class="sa-chart-tabs">
          <span class="sa-chart-tab sa-chart-tab-active" data-period="6">6 months</span>
          <span class="sa-chart-tab" data-period="12">12 months</span>
          <span class="sa-chart-tab" data-period="all">All time</span>
        </div>
      </div>
      <div class="card-body">
        <div class="sa-chart-legend">
          <span id="sa-revenue-legend"><?php $ci=0; foreach($plan_types as $pt): ?>
          <span class="sa-chart-legend-item"><span class="sa-chart-legend-dot" data-color="<?= $plan_colors[$ci%count($plan_colors)]; ?>"></span><?= esc($pt); ?></span>
          <?php $ci++; endforeach; ?></span>
          <span class="sa-chart-legend-total" id="sa-revenue-total">Total: UGX <?= sa_fmt_money($revenue_total_6m); ?> (6 months)</span>
        </div>
        <div class="sa-chart-area" id="sa-revenue-chart-area">
          <?php foreach($chart_data as $cd): ?>
          <div class="sa-chart-bar-group">
            <div class="sa-chart-stacked" data-max="<?= $chart_max; ?>">
              <?php $ci=0; foreach($plan_types as $pt): $val=$cd[$pt]; $h=$chart_max>0?($val/$chart_max)*150:0; ?>
              <div class="sa-chart-segment" data-height="<?= max(0,$h); ?>" data-color="<?= $plan_colors[$ci%count($plan_colors)]; ?>"></div>
              <?php $ci++; endforeach; ?>
            </div>
            <div class="sa-chart-bar-value"><?php $s=0; foreach($plan_types as $p) $s+=$cd[$p]; echo sa_fmt_money($s); ?></div>
            <div class="sa-chart-bar-label"><?= $cd['month']; ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <a href="<?= site_url('erp/membership-list'); ?>" class="sa-section-link"><h5>Plan distribution</h5></a>
        <span class="text-muted small"><?= $active_subs; ?> active</span>
      </div>
      <div class="sa-card-body sa-donut-wrap">
        <?php $total_plans=max(1,array_sum(array_column($plan_dist,'cnt'))); $donut_r=70; $donut_c=$donut_r*2*M_PI; $offset=0; ?>
        <svg width="160" height="160" viewBox="0 0 200 200">
          <circle cx="100" cy="100" r="<?= $donut_r; ?>" fill="none" stroke="#e9ecef" stroke-width="24"/>
          <?php $di=0; foreach($plan_dist as $pd): $pct=$pd['cnt']/$total_plans; $dash=$pct*$donut_c; $gap=$donut_c-$dash; ?>
          <circle cx="100" cy="100" r="<?= $donut_r; ?>" fill="none" stroke="<?= $plan_colors[$di%count($plan_colors)]; ?>" stroke-width="24" stroke-dasharray="<?= $dash; ?> <?= $gap; ?>" stroke-dashoffset="<?= -$offset; ?>" transform="rotate(-90 100 100)"/>
          <?php $offset+=$dash; $di++; endforeach; ?>
          <text x="100" y="92" text-anchor="middle" fill="currentColor" font-size="28" font-weight="700"><?= $active_subs; ?></text>
          <text x="100" y="114" text-anchor="middle" fill="#999" font-size="12">active</text>
        </svg>
        <div class="sa-donut-legend">
          <?php $di=0; foreach($plan_dist as $pd): $pct=round($pd['cnt']/$total_plans*100); ?>
          <div class="sa-donut-legend-row">
            <span class="sa-donut-dot" data-color="<?= $plan_colors[$di%count($plan_colors)]; ?>"></span>
            <span class="sa-donut-name"><?= esc($pd['membership_type']); ?></span>
            <div class="sa-donut-bar"><div class="sa-donut-bar-fill" data-width="<?= $pct; ?>" data-color="<?= $plan_colors[$di%count($plan_colors)]; ?>"></div></div>
            <span class="sa-donut-count"><?= $pd['cnt']; ?></span>
            <span class="sa-donut-pct"><?= $pct; ?>%</span>
          </div>
          <?php $di++; endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body sa-quick-actions">
        <a href="<?= site_url('erp/companies-list'); ?>" class="sa-qa-item">
          <span class="sa-qa-icon sa-qa-icon-primary"><i class="feather icon-plus-circle"></i></span>
          <span class="sa-qa-label">Add Company</span>
        </a>
        <a href="<?= site_url('erp/broadcasts'); ?>" class="sa-qa-item">
          <span class="sa-qa-icon sa-qa-icon-info"><i class="feather icon-send"></i></span>
          <span class="sa-qa-label">New Broadcast</span>
        </a>
        <a href="<?= site_url('erp/system-settings'); ?>" class="sa-qa-item">
          <span class="sa-qa-icon sa-qa-icon-warning"><i class="feather icon-settings"></i></span>
          <span class="sa-qa-label">System Settings</span>
        </a>
        <a href="<?= site_url('erp/system-backup'); ?>" class="sa-qa-item">
          <span class="sa-qa-icon sa-qa-icon-success"><i class="feather icon-download-cloud"></i></span>
          <span class="sa-qa-label">DB Backup</span>
        </a>
        <a href="<?= site_url('erp/archive'); ?>" class="sa-qa-item">
          <span class="sa-qa-icon sa-qa-icon-dark"><i class="feather icon-archive"></i></span>
          <span class="sa-qa-label">Archive Portal</span>
        </a>
        <a href="<?= site_url('erp/all-subscription-invoices'); ?>" class="sa-qa-item">
          <span class="sa-qa-icon sa-qa-icon-danger"><i class="feather icon-file-text"></i></span>
          <span class="sa-qa-label">All Invoices</span>
        </a>
        <a href="<?= site_url('erp/system-reports'); ?>" class="sa-qa-item">
          <span class="sa-qa-icon sa-qa-icon-primary"><i class="feather icon-download"></i></span>
          <span class="sa-qa-label">Export Report</span>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Expiry List + Geographic Breakdown -->
<div class="row">
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <a href="<?= site_url('erp/companies-list'); ?>" class="sa-section-link"><h5>Subscription expiry — next 7 days</h5></a>
        <span class="text-muted small"><?= count($expiring); ?> companies</span>
      </div>
      <div class="card-body">
        <?php if(empty($expiring)): ?>
        <p class="sa-activity-empty">No subscriptions expiring within 7 days.</p>
        <?php else: foreach($expiring as $exp):
          $dl=(int)$exp['days_left'];
          if($dl==0){$bc='sa-badge-today';$bt='Expires TODAY';}
          elseif($dl<=2){$bc='sa-badge-critical';$bt=$dl.' day'.($dl!=1?'s':'');}
          elseif($dl<=5){$bc='sa-badge-warning';$bt=$dl.' days';}
          else{$bc='sa-badge-normal';$bt=$dl.' days';}
        ?>
        <div class="sa-expiry-row">
          <div>
            <div class="sa-expiry-name"><?= esc($exp['company_name']); ?></div>
            <div class="sa-expiry-detail"><?= esc($exp['email']); ?> &middot; <?= esc($exp['membership_type']); ?></div>
          </div>
          <div class="sa-expiry-right">
            <span class="sa-badge <?= $bc; ?>"><?= $bt; ?></span>
            <div class="sa-expiry-date"><?= date('d M Y',strtotime($exp['expiry_date'])); ?></div>
          </div>
        </div>
        <?php endforeach; endif; ?>
        <div class="sa-expiry-actions">
          <a href="<?= site_url('erp/broadcasts'); ?>" class="btn btn-outline-secondary btn-sm">Send all reminders</a>
          <a href="<?= site_url('erp/companies-list'); ?>" class="btn btn-outline-secondary btn-sm">View full expiry list</a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <a href="<?= site_url('erp/companies-list'); ?>" class="sa-section-link"><h5>Companies by region</h5></a>
        <span class="text-muted small">Uganda</span>
      </div>
      <div class="card-body">
        <?php foreach($geo as $g): $w=round(($g['cnt']/$geo_max)*100); ?>
        <div class="sa-geo-row">
          <div class="sa-geo-label"><?= esc($g['location']); ?></div>
          <div class="sa-geo-track"><div class="sa-geo-bar" data-width="<?= $w; ?>"></div></div>
          <div class="sa-geo-count"><?= $g['cnt']; ?></div>
        </div>
        <?php endforeach; ?>
        <div class="sa-industry-section">
          <div class="sa-industry-heading">Industry breakdown</div>
          <?php foreach($industries as $ind): ?>
          <span class="sa-ind-chip"><?= esc($ind['industry']); ?> &middot; <?= $ind['cnt']; ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Activity Feed + System Health -->
<div class="row">
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Recent activity</h5>
        <span class="text-muted small">Live feed</span>
      </div>
      <div class="card-body">
        <?php if(empty($activity_feed)): ?>
        <p class="sa-activity-empty">No recent activity.</p>
        <?php else: foreach($activity_feed as $af):
          $dot='sa-dot-green'; $text='';
          switch($af['type']){
            case 'renewal': $dot='sa-dot-green'; $text='<strong>'.esc($af['label']).'</strong> renewed &middot; UGX '.number_format((float)$af['detail'],0); break;
            case 'registration': $dot='sa-dot-blue'; $text='<strong>New registration:</strong> '.esc($af['label']).($af['detail']?' &middot; '.esc($af['detail']):''); break;
            case 'reminder': $dot='sa-dot-amber'; $text='Billing reminder sent to <strong>'.esc($af['label']).'</strong> ('.$af['detail'].' day'.((int)$af['detail']!=1?'s':'').' remaining)'; break;
            case 'broadcast': $dot='sa-dot-blue'; $text='Broadcast sent: "'.esc($af['label']).'" &gt; '.$af['detail'].' recipients'; break;
            default: $text=esc($af['label'] ?? '');
          }
        ?>
        <div class="sa-activity-row">
          <span class="sa-activity-dot <?= $dot; ?>"></span>
          <div class="sa-activity-text"><?= $text; ?></div>
          <div class="sa-activity-time"><?= sa_time_ago($af['event_at']); ?></div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <a href="<?= site_url('erp/system-settings'); ?>" class="sa-section-link"><h5>System health</h5></a>
        <span class="text-muted small">Checked just now</span>
      </div>
      <div class="card-body">
        <div class="sa-health-grid">
          <div class="sa-health-item">
            <span class="sa-health-dot sa-health-dot-green"></span><span class="sa-health-name">PostgreSQL</span>
            <div class="sa-health-detail"><?= $pg_size; ?></div>
          </div>
          <div class="sa-health-item">
            <span class="sa-health-dot <?= $redis_info=='offline'?'sa-health-dot-red':'sa-health-dot-green'; ?>"></span><span class="sa-health-name">Redis</span>
            <div class="sa-health-detail"><?= $redis_info; ?></div>
          </div>
          <div class="sa-health-item">
            <span class="sa-health-dot sa-health-dot-green"></span><span class="sa-health-name">Beanstalkd</span>
            <div class="sa-health-detail"><?= $queue_ready; ?> jobs ready</div>
          </div>
          <div class="sa-health-item">
            <span class="sa-health-dot <?= system_setting('stripe_active')=='1'?'sa-health-dot-green':'sa-health-dot-amber'; ?>"></span><span class="sa-health-name">Stripe</span>
            <div class="sa-health-detail"><?= system_setting('stripe_active')=='1'?'Live mode':'Configure'; ?></div>
          </div>
          <div class="sa-health-item">
            <span class="sa-health-dot <?= system_setting('mtn_active')=='1'?'sa-health-dot-green':'sa-health-dot-amber'; ?>"></span><span class="sa-health-name">MTN MoMo</span>
            <div class="sa-health-detail"><?= system_setting('mtn_active')=='1'?'Production':'Configure'; ?></div>
          </div>
          <div class="sa-health-item">
            <span class="sa-health-dot <?= $smtp_today>425?'sa-health-dot-amber':'sa-health-dot-green'; ?>"></span><span class="sa-health-name">SMTP</span>
            <div class="sa-health-detail"><?= $smtp_today; ?>/500 today</div>
          </div>
          <div class="sa-health-item">
            <span class="sa-health-dot <?= system_setting('sms_active')=='1'?'sa-health-dot-green':'sa-health-dot-amber'; ?>"></span><span class="sa-health-name">SMS</span>
            <div class="sa-health-detail"><?= system_setting('sms_active')=='1'?'AT &middot; active':'Configure'; ?></div>
          </div>
          <div class="sa-health-item">
            <span class="sa-health-dot sa-health-dot-green"></span><span class="sa-health-name">Archive DB</span>
            <div class="sa-health-detail"><?= $archive_size; ?></div>
          </div>
          <div class="sa-health-item">
            <span class="sa-health-dot sa-health-dot-green"></span><span class="sa-health-name">Backup</span>
            <div class="sa-health-detail"><?= $last_backup_str; ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

</div>

<script>
var SA_PLAN_COLORS = <?= json_encode($plan_colors); ?>;
var SA_PLAN_TYPES = <?= json_encode($plan_types); ?>;
var SA_CHART_DATA = <?= json_encode($_chart_periods); ?>;

function saApplyDataStyles(){
  document.querySelectorAll('.sa-spark-bar').forEach(function(el){ el.style.height = el.dataset.height + 'px'; });
  document.querySelectorAll('.sa-chart-segment').forEach(function(el){ el.style.height = el.dataset.height + 'px'; el.style.background = el.dataset.color; });
  document.querySelectorAll('.sa-chart-legend-dot, .sa-donut-dot').forEach(function(el){ el.style.background = el.dataset.color; });
  document.querySelectorAll('.sa-donut-bar-fill').forEach(function(el){ el.style.width = el.dataset.width + '%'; el.style.background = el.dataset.color; });
  document.querySelectorAll('.sa-geo-bar').forEach(function(el){ el.style.width = el.dataset.width + '%'; });
}

function saFmtMoney(n){ if(n>=1000000) return (n/1000000).toFixed(1)+'M'; if(n>=1000) return Math.round(n/1000)+'K'; return n.toString(); }

function saRenderChart(period){
  document.querySelectorAll('.sa-chart-tab').forEach(function(t){ t.classList.remove('sa-chart-tab-active'); });
  document.querySelector('.sa-chart-tab[data-period="'+period+'"]').classList.add('sa-chart-tab-active');

  var d = SA_CHART_DATA[period];
  var area = document.getElementById('sa-revenue-chart-area');
  var total = document.getElementById('sa-revenue-total');
  var labels = {'6':'6 months','12':'12 months','all':'all time'};
  total.textContent = 'Total: UGX ' + saFmtMoney(d.total) + ' (' + labels[period] + ')';

  var cmax = 1;
  d.chart.forEach(function(cd){
    var s = 0; SA_PLAN_TYPES.forEach(function(p){ s += (cd[p]||0); }); cmax = Math.max(cmax, s);
  });

  var html = '';
  d.chart.forEach(function(cd){
    html += '<div class="sa-chart-bar-group"><div class="sa-chart-stacked">';
    SA_PLAN_TYPES.forEach(function(pt, i){
      var val = cd[pt] || 0;
      var h = cmax > 0 ? (val / cmax) * 150 : 0;
      html += '<div class="sa-chart-segment" data-height="'+Math.max(0,h)+'" data-color="'+SA_PLAN_COLORS[i % SA_PLAN_COLORS.length]+'"></div>';
    });
    var s = 0; SA_PLAN_TYPES.forEach(function(p){ s += (cd[p]||0); });
    html += '</div><div class="sa-chart-bar-value">'+saFmtMoney(s)+'</div>';
    html += '<div class="sa-chart-bar-label">'+cd.month+'</div></div>';
  });
  area.innerHTML = html;

  // Re-apply dynamic styles
  document.querySelectorAll('.sa-chart-segment').forEach(function(el){ el.style.height = el.dataset.height + 'px'; el.style.background = el.dataset.color; });
}

document.addEventListener('DOMContentLoaded', function(){
  saApplyDataStyles();
  document.querySelectorAll('.sa-chart-tab').forEach(function(tab){
    tab.addEventListener('click', function(){ saRenderChart(this.dataset.period); });
  });

  // Auto-refresh KPIs every 60s
  setInterval(function(){
    fetch('<?= site_url("erp/dashboard/kpi-refresh"); ?>')
      .then(function(r){ return r.json(); })
      .then(function(d){
        var el;
        el = document.getElementById('sa-kpi-total-companies'); if(el) el.textContent = d.total_companies;
        el = document.getElementById('sa-kpi-active-subs'); if(el) el.textContent = d.active_subs;
        el = document.getElementById('sa-kpi-inactive'); if(el) el.textContent = d.inactive_subs;
        el = document.getElementById('sa-kpi-revenue'); if(el) el.textContent = 'UGX ' + saFmtMoney(d.rev_this_month);
        el = document.getElementById('sa-kpi-mrr'); if(el) el.textContent = 'UGX ' + saFmtMoney(d.mrr);
        el = document.getElementById('sa-kpi-forecast'); if(el) el.textContent = 'UGX ' + saFmtMoney(d.forecast);
      })
      .catch(function(){});
  }, 60000);
});
</script>
