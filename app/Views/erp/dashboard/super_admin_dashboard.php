<?php
use App\Models\RolesModel;
use App\Models\UsersModel;
use App\Models\SystemModel;
use App\Models\MembershipModel;
use App\Models\InvoicepaymentsModel;
use App\Models\CompanymembershipModel;

$SystemModel = new SystemModel();
$RolesModel = new RolesModel();
$UsersModel = new UsersModel();
$InvoicepaymentsModel = new InvoicepaymentsModel();
$CompanymembershipModel = new CompanymembershipModel();
$MembershipModel = new MembershipModel();

$session = \Config\Services::session();
$usession = $session->get('sup_username');
$request = \Config\Services::request();

$xin_system = $SystemModel->where('setting_id', 1)->first();
$user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

// Stats
$total_companies = $UsersModel->where('user_type','company')->countAllResults();
$active_companies = $UsersModel->where('user_type','company')->where('is_active',1)->countAllResults();
$inactive_companies = $total_companies - $active_companies;
$total_staff = $UsersModel->where('user_type','staff')->countAllResults();
$total_membership = $MembershipModel->orderBy('membership_id', 'ASC')->countAllResults();

// Recent companies
$recent_companies = $UsersModel->where('user_type','company')->orderBy('user_id','DESC')->findAll(5);

// Recent invoices
$get_invoices = $InvoicepaymentsModel->orderBy('membership_invoice_id','DESC')->findAll(10);

// Expiring soon (next 7 days)
$db = \Config\Database::connect();
$expiring = $db->table('ci_company_membership cm')
    ->select('cm.*, u.company_name, u.email, u.first_name, u.last_name')
    ->join('ci_erp_users u', 'u.user_id = cm.company_id')
    ->where('cm.is_active', 1)
    ->where('cm.expiry_date <=', date('Y-m-d', strtotime('+7 days')))
    ->where('cm.expiry_date >=', date('Y-m-d'))
    ->orderBy('cm.expiry_date', 'ASC')
    ->get()->getResultArray();
?>

<!-- Row 1: Key Metrics -->
<div class="row">
  <div class="col-xl-3 col-md-6">
    <div class="card bg-primary text-white">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h2 class="f-w-300 m-b-0"><?= $total_companies; ?></h2>
            <p class="m-b-0 opacity-75">Total Companies</p>
          </div>
          <i class="feather icon-briefcase f-36 opacity-50"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card bg-success text-white">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h2 class="f-w-300 m-b-0"><?= $active_companies; ?></h2>
            <p class="m-b-0 opacity-75">Active Subscriptions</p>
          </div>
          <i class="feather icon-check-circle f-36 opacity-50"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card bg-danger text-white">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h2 class="f-w-300 m-b-0"><?= $inactive_companies; ?></h2>
            <p class="m-b-0 opacity-75">Inactive / Expired</p>
          </div>
          <i class="feather icon-alert-triangle f-36 opacity-50"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card bg-warning text-white">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h2 class="f-w-300 m-b-0"><?= $total_staff; ?></h2>
            <p class="m-b-0 opacity-75">Total Employees (All Companies)</p>
          </div>
          <i class="feather icon-users f-36 opacity-50"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Row 2: Quick Actions -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-2" style="gap:8px;">
          <a href="<?= site_url('erp/companies-list'); ?>" class="btn btn-outline-primary"><i class="feather icon-briefcase mr-1"></i> Manage Companies</a>
          <a href="<?= site_url('erp/system-settings'); ?>" class="btn btn-outline-secondary"><i class="feather icon-settings mr-1"></i> System Settings</a>
          <a href="<?= site_url('erp/system-payment-settings'); ?>" class="btn btn-outline-success"><i class="feather icon-credit-card mr-1"></i> Payment Settings</a>
          <a href="<?= site_url('erp/landing-page'); ?>" class="btn btn-outline-info"><i class="feather icon-layout mr-1"></i> Landing Page CMS</a>
          <a href="<?= site_url('erp/archive'); ?>" class="btn btn-outline-dark"><i class="feather icon-archive mr-1"></i> Archive Portal</a>
          <a href="<?= site_url('erp/all-subscription-invoices'); ?>" class="btn btn-outline-warning"><i class="feather icon-file-text mr-1"></i> All Invoices</a>
          <a href="<?= site_url('erp/broadcasts'); ?>" class="btn btn-outline-primary"><i class="feather icon-send mr-1"></i> Broadcasts</a>
          <a href="<?= site_url('api/docs'); ?>" class="btn btn-outline-secondary" target="_blank"><i class="feather icon-book mr-1"></i> API Docs</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Row 3: Expiring Soon + Recent Companies -->
<div class="row">
  <!-- Expiring Soon -->
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-clock text-danger mr-2"></i>Expiring Within 7 Days</h5>
      </div>
      <div class="card-body p-0">
        <?php if(empty($expiring)): ?>
        <div class="p-4 text-center text-muted">No subscriptions expiring soon.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Company</th>
                <th>Contact</th>
                <th>Expires</th>
                <th>Days Left</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($expiring as $exp): ?>
              <?php $daysLeft = (int)((strtotime($exp['expiry_date']) - time()) / 86400); ?>
              <tr>
                <td><strong><?= esc($exp['company_name']); ?></strong></td>
                <td><small><?= esc($exp['email']); ?></small></td>
                <td><?= $exp['expiry_date']; ?></td>
                <td>
                  <span class="badge badge-<?= $daysLeft <= 2 ? 'danger' : 'warning'; ?>">
                    <?= $daysLeft; ?> day<?= $daysLeft != 1 ? 's' : ''; ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Recent Companies -->
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-user-plus text-primary mr-2"></i>Recent Companies</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Company</th>
                <th>Admin</th>
                <th>Email</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($recent_companies as $rc): ?>
              <tr>
                <td><strong><?= esc($rc['company_name']); ?></strong></td>
                <td><?= esc($rc['first_name'] . ' ' . $rc['last_name']); ?></td>
                <td><small><?= esc($rc['email']); ?></small></td>
                <td>
                  <?php if($rc['is_active'] == 1): ?>
                  <span class="badge badge-success">Active</span>
                  <?php else: ?>
                  <span class="badge badge-danger">Inactive</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Row 4: Revenue + Membership Plans -->
<div class="row">
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-dollar-sign text-success mr-2"></i>Revenue Summary</h5>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-4">
            <h4 class="text-success"><?= number_format(total_membership_payments()); ?></h4>
            <p class="text-muted mb-0">Total Revenue</p>
          </div>
          <div class="col-4">
            <h4 class="text-primary"><?= $total_membership; ?></h4>
            <p class="text-muted mb-0">Plans Available</p>
          </div>
          <div class="col-4">
            <h4 class="text-warning"><?= count($get_invoices); ?></h4>
            <p class="text-muted mb-0">Recent Invoices</p>
          </div>
        </div>
        <hr>
        <div id="company-invoice-chart" style="min-height:200px;"></div>
      </div>
    </div>
  </div>

  <!-- Recent Invoices -->
  <div class="col-xl-6 col-md-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-file-text text-info mr-2"></i>Latest Subscription Invoices</h5>
      </div>
      <div class="card-body p-0">
        <?php if(empty($get_invoices)): ?>
        <div class="p-4 text-center text-muted">No invoices yet.</div>
        <?php else: ?>
        <div class="table-responsive" style="max-height:350px;overflow-y:auto;">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Method</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($get_invoices as $r): ?>
              <?php $membership = $MembershipModel->where('membership_id', $r['membership_id'])->first(); ?>
              <tr>
                <td>
                  <a href="<?= site_url('erp/billing-detail/'.uencode($r['membership_invoice_id'])); ?>">
                    <?= esc($r['invoice_id']); ?>
                  </a>
                </td>
                <td><?= esc($membership['membership_type'] ?? 'N/A'); ?></td>
                <td><strong><?= number_format($r['membership_price'] ?? 0); ?></strong></td>
                <td><span class="badge badge-light"><?= esc($r['payment_method'] ?? 'N/A'); ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Row 5: System Health -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-activity text-success mr-2"></i>System Status</h5>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-md-2 col-4 mb-3">
            <div class="p-3 bg-light rounded">
              <i class="feather icon-database f-24 text-success"></i>
              <p class="mb-0 mt-2 small">PostgreSQL</p>
              <span class="badge badge-success">Online</span>
            </div>
          </div>
          <div class="col-md-2 col-4 mb-3">
            <div class="p-3 bg-light rounded">
              <i class="feather icon-server f-24 text-success"></i>
              <p class="mb-0 mt-2 small">Redis Cache</p>
              <span class="badge badge-success">Online</span>
            </div>
          </div>
          <div class="col-md-2 col-4 mb-3">
            <div class="p-3 bg-light rounded">
              <i class="feather icon-mail f-24 text-success"></i>
              <p class="mb-0 mt-2 small">Email Service</p>
              <span class="badge badge-secondary">Configure</span>
            </div>
          </div>
          <div class="col-md-2 col-4 mb-3">
            <div class="p-3 bg-light rounded">
              <i class="feather icon-credit-card f-24 text-<?= system_setting('stripe_active') == '1' ? 'success' : 'secondary'; ?>"></i>
              <p class="mb-0 mt-2 small">Stripe</p>
              <span class="badge badge-<?= system_setting('stripe_active') == '1' ? 'success' : 'secondary'; ?>"><?= system_setting('stripe_active') == '1' ? 'Active' : 'Configure'; ?></span>
            </div>
          </div>
          <div class="col-md-2 col-4 mb-3">
            <div class="p-3 bg-light rounded">
              <i class="feather icon-smartphone f-24 text-<?= system_setting('mtn_active') == '1' ? 'success' : 'secondary'; ?>"></i>
              <p class="mb-0 mt-2 small">MTN MoMo</p>
              <span class="badge badge-<?= system_setting('mtn_active') == '1' ? 'success' : 'secondary'; ?>"><?= system_setting('mtn_active') == '1' ? 'Active' : 'Configure'; ?></span>
            </div>
          </div>
          <div class="col-md-2 col-4 mb-3">
            <div class="p-3 bg-light rounded">
              <i class="feather icon-message-circle f-24 text-<?= system_setting('sms_active') == '1' ? 'success' : 'secondary'; ?>"></i>
              <p class="mb-0 mt-2 small">SMS</p>
              <span class="badge badge-<?= system_setting('sms_active') == '1' ? 'success' : 'secondary'; ?>"><?= system_setting('sms_active') == '1' ? 'Active' : 'Configure'; ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
