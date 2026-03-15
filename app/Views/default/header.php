<?php
use App\Models\SystemModel;
use App\Models\RolesModel;
use App\Models\UsersModel;

$SystemModel = new SystemModel();
$UserRolesModel = new RolesModel();
$UsersModel = new UsersModel();

$session = \Config\Services::session();
$usession = $session->get('sup_username');
$request = \Config\Services::request();
$router = service('router');
$user_id_h = (!empty($usession) && is_array($usession)) ? ($usession['sup_user_id'] ?? 0) : 0;
$user = $user_id_h ? $UsersModel->where('user_id', $user_id_h)->first() : null;
$user_type_h = !empty($user) ? $user['user_type'] : '';

if($user_type_h == 'super_user'){
	$xin_system = $SystemModel->where('setting_id', 1)->first();
} else {
	$xin_system = erp_company_settings();
}
$ci_erp_settings = $SystemModel->where('setting_id', 1)->first();
$xin_com_system = erp_company_settings();

if(!empty($xin_com_system['header_background']) && $xin_com_system['header_background'] != ''){
	$bg_option = $xin_com_system['header_background'];
} else {
	$bg_option = '';
}
$setup_modules = !empty($xin_com_system['setup_modules']) ? unserialize($xin_com_system['setup_modules']) : [];
?>
<header class="pc-header <?= $bg_option;?>">
    <div class="header-wrapper">
       <?php if(!empty($user)){ ?>
        <div class="m-header d-flex align-items-center">
            <a href="<?= site_url('erp/desk');?>" class="b-brand">
                <img src="<?= base_url();?>/public/uploads/logo/<?= $ci_erp_settings['logo'] ?? '';?>" alt="" class="logo logo-lg" height="40" width="138">
            </a>
        </div>
        <?php } ?>
        <div class="mr-auto pc-mob-drp">
            <ul class="list-unstyled">
                <?php if($user_type_h != 'customer' && $user_type_h != 'super_user'){ ?>
                <li class="pc-h-item">
                    <a class="pc-head-link active arrow-none mr-0" data-toggle="tooltip" data-placement="top" title="<?= lang('Main.xin_account_settings');?>" href="<?= site_url('erp/my-profile');?>">
                        <i data-feather="user-check"></i>
                    </a>
                </li>
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link active dropdown-toggle arrow-none mr-0" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <span data-toggle="tooltip" data-placement="top" title="<?= lang('Dashboard.xin_apps');?>"><?= lang('Dashboard.xin_apps');?></span>
                    </a>
                    <div class="dropdown-menu pc-h-dropdown">
                     	<?php if(isset($setup_modules['travel']) && $setup_modules['travel']==1):?>
                        <?php if($user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/business-travel');?>" class="dropdown-item">
                            <i data-feather="globe"></i>
                            <span><?= lang('Dashboard.left_travels');?></span>
                        </a>
                        <?php } ?>
                        <?php endif;?>
                        <?php if(isset($setup_modules['events']) && $setup_modules['events']==1):?>
                        <?php if(in_array('hr_event1',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/events-list');?>" class="dropdown-item">
                            <i data-feather="disc"></i>
                            <span><?= lang('Dashboard.xin_hr_events');?></span>
                        </a>
                        <?php } ?>
                        <?php endif;?>
						<?php if(in_array('holiday1',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/holidays-list');?>" class="dropdown-item">
                            <i data-feather="sun"></i>
                            <span><?= lang('Dashboard.left_holidays');?></span>
                        </a>
                        <?php } ?>
						<?php if(in_array('visitor1',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/visitors-list');?>" class="dropdown-item">
                            <i data-feather="user-plus"></i>
                            <span><?= lang('Main.xin_visitor_book');?></span>
                        </a>
                        <?php } ?>
                        <?php if(isset($setup_modules['events']) && $setup_modules['events']==1):?>
                        <?php if(in_array('conference1',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/meeting-list');?>" class="dropdown-item">
                            <i data-feather="calendar"></i>
                            <span><?= lang('Dashboard.xin_hr_meetings');?></span>
                        </a>
                        <?php } ?>
                        <?php endif;?>
                        <?php if(isset($setup_modules['fmanager']) && $setup_modules['fmanager']==1):?>
						<?php if(in_array('file1',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/upload-files');?>" class="dropdown-item">
                            <i data-feather="file-plus"></i>
                            <span><?= lang('Dashboard.xin_upload_files');?></span>
                        </a>
                        <?php } ?>
                        <?php endif;?>
                        <div class="dropdown-divider"></div>
						<?php if(in_array('asset1',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/assets-list');?>" class="dropdown-item">
                            <i data-feather="command"></i>
                            <span><?= lang('Dashboard.xin_assets');?></span>
                        </a>
                        <?php } ?>
                        <?php if(isset($setup_modules['award']) && $setup_modules['award']==1):?>
						<?php if(in_array('award1',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/awards-list');?>" class="dropdown-item">
                            <i data-feather="award"></i>
                            <span><?= lang('Dashboard.left_awards');?></span>
                        </a>
                        <?php } ?>
                        <?php endif;?>
                        <?php if(in_array('transfers1',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/transfers-list');?>" class="dropdown-item">
                            <i data-feather="minimize-2"></i>
                            <span><?= lang('Dashboard.left_transfers');?></span>
                        </a>
                        <?php } ?>
						<?php if(in_array('complaint1',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/complaints-list');?>" class="dropdown-item">
                            <i data-feather="edit"></i>
                            <span><?= lang('Dashboard.left_complaints');?></span>
                        </a>
                        <?php } ?>
						<?php if(in_array('resignation1',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/resignation-list');?>" class="dropdown-item">
                            <i data-feather="user-minus"></i>
                            <span><?= lang('Dashboard.left_resignations');?></span>
                        </a>
                       <?php } ?>
                       <?php if(in_array('custom_fields',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/custom-fields');?>" class="dropdown-item">
                            <i data-feather="file-text"></i>
                            <span><?= lang('Main.xin_custom_fields');?></span>
                        </a>
                        <?php } ?>
                       <div class="dropdown-divider"></div>
                       <?php if(in_array('settings5',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/system-backup');?>" class="dropdown-item">
                            <i data-feather="download-cloud"></i>
                            <span><?= lang('Main.header_db_log');?></span>
                        </a>
                       <?php } ?>
                       <?php if(in_array('settings6',staff_role_resource()) || $user_type_h == 'company') {?>
                        <a href="<?= site_url('erp/currency-converter');?>" class="dropdown-item">
                            <i data-feather="pocket"></i>
                            <span><?= lang('Main.xin_currency_converter');?></span>
                        </a>
                       <?php } ?>
                    </div>
                </li>
                <?php if(in_array('system_calendar',staff_role_resource()) || $user_type_h == 'company') {?>
                <li class="pc-h-item">
                    <a class="pc-head-link active arrow-none mr-0" data-toggle="tooltip" data-placement="top" title="<?= lang('Dashboard.xin_system_calendar');?>" href="<?= site_url('erp/system-calendar');?>">
                        <i data-feather="calendar"></i>
                    </a>
                </li>
                <?php } ?>
				<?php if(in_array('system_reports',staff_role_resource()) || $user_type_h == 'company') {?>
                <li class="pc-h-item">
                    <a class="pc-head-link active arrow-none mr-0" data-toggle="tooltip" data-placement="top" title="<?= lang('Dashboard.xin_system_reports');?>" href="<?= site_url('erp/system-reports');?>">
                        <i data-feather="pie-chart"></i>
                    </a>
                </li>
                <?php } ?>
                <?php if(in_array('settings1',staff_role_resource()) || $user_type_h == 'company') {?>
                <li class="pc-h-item">
                    <a class="pc-head-link active arrow-none mr-0" data-toggle="tooltip" data-placement="top" title="<?= lang('Main.xin_configuration_wizard');?>" href="<?= site_url('erp/system-settings');?>">
                        <i data-feather="settings"></i>
                    </a>
                </li>
                <?php } ?>
				<?php } if($user_type_h == 'customer') {?>
                <li class="pc-h-item">
                    <a class="pc-head-link active arrow-none mr-0" data-toggle="tooltip" data-placement="top" title="<?= lang('Dashboard.xin_acc_calendar');?>" href="<?= site_url('erp/my-invoices-calendar');?>">
                        <i data-feather="calendar"></i>
                    </a>
                </li>
                <?php } if($user_type_h == 'super_user') {?>
                <li class="pc-h-item">
                    <a class="pc-head-link active arrow-none mr-0" data-toggle="tooltip" data-placement="top" title="View Landing Page" href="<?= site_url('/'); ?>" target="_blank">
                        <i data-feather="globe"></i>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </div>
        <div class="ml-auto">
            <ul class="list-unstyled">
                <li class="pc-h-item gs-search-wrap">
                    <input type="text" id="gs-search-input" class="form-control gs-search-input" placeholder="Search... (Ctrl+K)" autocomplete="off">
                    <div id="gs-search-results" class="gs-search-results"></div>
                </li>
                <li class="dropdown pc-h-item gs-notification-wrap">
                    <a href="#" class="pc-head-link mr-0" data-toggle="dropdown" id="notif-bell">
                        <i data-feather="bell"></i>
                        <span class="badge badge-danger" id="notification-count"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" id="notif-dropdown">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong>Notifications</strong>
                            <a href="#" id="notif-mark-all" class="small text-primary">Mark all read</a>
                        </div>
                        <div id="notification-list" class="notif-scroll">
                            <div class="p-3 text-center text-muted small">Loading...</div>
                        </div>
                        <div class="border-top text-center py-2">
                            <a href="<?= site_url('erp/notifications-page'); ?>" class="small text-primary">View All Notifications</a>
                        </div>
                    </div>
                </li>
                <?php if(in_array('todo_ist',staff_role_resource()) || $user_type_h == 'company' || $user_type_h == 'customer' || $user_type_h == 'super_user') {?>
                <li class="pc-h-item">
                    <a class="pc-head-link mr-0" data-toggle="tooltip" data-placement="top" title="<?= lang('Main.xin_todo_ist');?>" href="<?= site_url('erp/todo-list');?>">
                        <i data-feather="check-circle"></i>
                        <span class="sr-only"></span>
                    </a>
                </li>
                <?php }?>
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none mr-0" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <img src="<?= !empty($user) ? staff_profile_photo($user['user_id']) : '';?>" alt="" class="user-avtar">
                        <span>
                            <span class="user-name"><?= !empty($user) ? esc($user['first_name'].' '.$user['last_name']) : '';?></span>
                            <span class="user-desc"><?= !empty($user) ? esc($user['username']) : '';?></span>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right pc-h-dropdown">
                        <div class="dropdown-header d-flex align-items-center">
                            <img src="<?= !empty($user) ? staff_profile_photo($user['user_id']) : '';?>" alt="" class="img-radius mr-3" width="40" height="40">
                            <div>
                                <h6 class="m-0"><?= !empty($user) ? esc($user['first_name'].' '.$user['last_name']) : '';?></h6>
                                <small class="text-muted"><?= !empty($user) ? esc($user['email'] ?? $user['username']) : '';?></small>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?= site_url('erp/my-profile');?>" class="dropdown-item">
                            <i data-feather="user"></i>
                            <span><?= lang('Dashboard.xin_my_account');?></span>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>
<script>
(function(){
  var gsInput = document.getElementById('gs-search-input');
  var gsResults = document.getElementById('gs-search-results');
  var gsTimer = null;
  var gsUrl = '<?= site_url("erp/global-search"); ?>';

  if(!gsInput || !gsResults) return;

  // Ctrl+K shortcut
  document.addEventListener('keydown', function(e){
    if((e.ctrlKey || e.metaKey) && e.key === 'k'){ e.preventDefault(); gsInput.focus(); gsInput.select(); }
    if(e.key === 'Escape'){ gsResults.classList.remove('gs-visible'); gsInput.blur(); }
  });

  gsInput.addEventListener('input', function(){
    clearTimeout(gsTimer);
    var q = this.value.trim();
    if(q.length < 2){ gsResults.classList.remove('gs-visible'); gsResults.innerHTML=''; return; }
    gsTimer = setTimeout(function(){
      fetch(gsUrl + '?q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(d){ gsRender(d); })
        .catch(function(){});
    }, 250);
  });

  gsInput.addEventListener('focus', function(){ if(gsResults.innerHTML) gsResults.classList.add('gs-visible'); });

  document.addEventListener('click', function(e){
    if(!gsInput.contains(e.target) && !gsResults.contains(e.target)) gsResults.classList.remove('gs-visible');
  });

  function gsRender(d){
    var html = '';
    if(d.companies && d.companies.length){
      html += '<div class="gs-group-header"><i data-feather="briefcase" class="gs-group-icon"></i> Companies</div>';
      d.companies.forEach(function(c){
        html += '<a href="<?= site_url("erp/company-detail/"); ?>' + c.user_id + '" class="gs-result-item">';
        html += '<span class="gs-result-name">' + gsEsc(c.company_name) + '</span>';
        html += '<span class="gs-result-sub">' + gsEsc(c.email || '') + (c.city ? ' &middot; ' + gsEsc(c.city) : '') + '</span></a>';
      });
    }
    if(d.employees && d.employees.length){
      html += '<div class="gs-group-header"><i data-feather="users" class="gs-group-icon"></i> Employees</div>';
      d.employees.forEach(function(e){
        html += '<a href="<?= site_url("erp/staff-profile/"); ?>' + e.user_id + '" class="gs-result-item">';
        html += '<span class="gs-result-name">' + gsEsc(e.first_name + ' ' + e.last_name) + '</span>';
        html += '<span class="gs-result-sub">' + gsEsc(e.email || '') + '</span></a>';
      });
    }
    if(d.invoices && d.invoices.length){
      html += '<div class="gs-group-header"><i data-feather="file-text" class="gs-group-icon"></i> Invoices</div>';
      d.invoices.forEach(function(i){
        html += '<a href="<?= site_url("erp/all-subscription-invoices"); ?>" class="gs-result-item">';
        html += '<span class="gs-result-name">#' + i.invoice_id + ' &mdash; ' + gsEsc(i.company_name || '') + '</span>';
        html += '<span class="gs-result-sub">' + i.amount + ' ' + (i.currency||'') + ' &middot; ' + i.status + '</span></a>';
      });
    }
    if(!html) html = '<div class="gs-no-results">No results found</div>';
    gsResults.innerHTML = html;
    gsResults.classList.add('gs-visible');
    if(typeof feather !== 'undefined') feather.replace();
  }

  function gsEsc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
})();

// Notification system
(function(){
  var notifUrl = '<?= site_url("erp/notifications"); ?>';
  var markReadUrl = '<?= site_url("erp/notifications/mark-read"); ?>';
  var markAllUrl = '<?= site_url("erp/notifications/mark-all-read"); ?>';
  var badge = document.getElementById('notification-count');
  var list = document.getElementById('notification-list');
  var markAll = document.getElementById('notif-mark-all');

  function timeAgo(dt){
    if(!dt) return '';
    var d = (Date.now() - new Date(dt).getTime()) / 1000;
    if(d<60) return 'just now';
    if(d<3600) return Math.floor(d/60)+'m ago';
    if(d<86400) return Math.floor(d/3600)+'h ago';
    if(d<172800) return 'Yesterday';
    return new Date(dt).toLocaleDateString('en-GB',{day:'numeric',month:'short'});
  }

  function loadNotifications(){
    fetch(notifUrl).then(function(r){return r.json()}).then(function(data){
      if(data.count > 0){
        badge.textContent = data.count > 9 ? '9+' : data.count;
        badge.classList.add('has-notif');
      } else {
        badge.classList.remove('has-notif');
      }
      if(!data.notifications || data.notifications.length === 0){
        list.innerHTML = '<div class="notif-empty">No notifications yet</div>';
        return;
      }
      var html = '';
      var baseUrl = '<?= site_url(); ?>';
      data.notifications.forEach(function(n){
        var cls = n.is_read == 0 ? 'notif-item unread' : 'notif-item';
        var href = n.link ? (n.link.indexOf('http') === 0 ? n.link : baseUrl + n.link.replace(/^\//, '')) : '<?= site_url("erp/notifications-page"); ?>';
        html += '<a href="'+href+'" class="'+cls+'" data-nid="'+n.notification_id+'">';
        html += '<div class="notif-item-title">'+gsEsc(n.title)+'</div>';
        if(n.body) html += '<div class="notif-item-body">'+gsEsc(n.body)+'</div>';
        html += '<div class="notif-item-time">'+timeAgo(n.created_at)+'</div>';
        html += '</a>';
      });
      list.innerHTML = html;
    }).catch(function(){});
  }

  function gsEsc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

  // Click notification: mark as read then navigate immediately
  if(list) list.addEventListener('click', function(e){
    var item = e.target.closest('.notif-item');
    if(item && item.dataset.nid){
      // Fire-and-forget mark as read, don't wait for response
      navigator.sendBeacon(markReadUrl, new URLSearchParams({id: item.dataset.nid, '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'}));
      // Let the <a> href navigate naturally
    }
  });

  // Mark all read
  if(markAll) markAll.addEventListener('click', function(e){
    e.preventDefault();
    fetch(markAllUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'<?= csrf_token(); ?>=<?= csrf_hash(); ?>'})
      .then(function(){ loadNotifications(); });
  });

  // Load on page load + poll every 30s
  loadNotifications();
  setInterval(loadNotifications, 30000);
})();
</script>
