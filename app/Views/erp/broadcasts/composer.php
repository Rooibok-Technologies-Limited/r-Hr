<?php
use App\Models\UsersModel;
$session = \Config\Services::session();
$usession = $session->get('sup_username');
$UsersModel = new UsersModel();
$current_user = $user_info ?? $UsersModel->where('user_id', $usession['sup_user_id'])->first();
$departments = $departments ?? [];
$employees   = $employees ?? [];
$templates   = $templates ?? [];
$isSuperAdmin = ($current_user['user_type'] === 'super_user');
?>

<style>
.wizard-step { display: none; }
.wizard-step.active { display: block; }
.step-indicator { cursor: pointer; }
.step-indicator.active { font-weight: bold; border-bottom: 3px solid #4680ff; }
.step-indicator.completed { color: #2ed8a3; }
.token-btn { margin: 2px; font-size: 11px; }
.sms-counter { font-size: 12px; color: #888; }
.sms-counter.over-limit { color: #e74c3c; font-weight: bold; }
</style>

<div class="card">
  <div class="card-header">
    <h5><i class="feather icon-radio"></i> New Broadcast</h5>
    <div class="card-header-right">
      <a href="<?= site_url('erp/broadcasts'); ?>" class="btn btn-sm btn-light-secondary">
        <i class="feather icon-arrow-left"></i> Back to List
      </a>
    </div>
  </div>
  <div class="card-body">

    <!-- Step Indicators -->
    <div class="row mb-4">
      <div class="col-12">
        <ul class="nav nav-tabs" id="broadcastSteps">
          <li class="nav-item">
            <a class="nav-link step-indicator active" data-step="1" href="javascript:void(0)">
              <span class="badge badge-primary mr-1">1</span> Audience
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link step-indicator" data-step="2" href="javascript:void(0)">
              <span class="badge badge-secondary mr-1">2</span> Message
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link step-indicator" data-step="3" href="javascript:void(0)">
              <span class="badge badge-secondary mr-1">3</span> Preview
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link step-indicator" data-step="4" href="javascript:void(0)">
              <span class="badge badge-secondary mr-1">4</span> Schedule &amp; Send
            </a>
          </li>
        </ul>
      </div>
    </div>

    <input type="hidden" id="broadcast_id" name="broadcast_id" value="">
    <input type="hidden" id="csrf_token" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

    <!-- ================================================================ -->
    <!-- STEP 1: Audience -->
    <!-- ================================================================ -->
    <div class="wizard-step active" id="step-1">
      <h5 class="mb-3">Select Audience</h5>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="audience_type">Audience Type <span class="text-danger">*</span></label>
            <select class="form-control" id="audience_type" name="audience_type">
              <option value="all_employees">All Employees</option>
              <option value="department">By Department</option>
              <option value="individual">Individual(s)</option>
              <?php if ($isSuperAdmin): ?>
              <option value="all_company_admins">All Company Admins</option>
              <?php endif; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Recipient Count</label>
            <div class="input-group">
              <input type="text" class="form-control" id="recipient_count_display" value="0" readonly>
              <div class="input-group-append">
                <span class="input-group-text"><i class="feather icon-users"></i></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Department multi-select (shown when audience_type = department) -->
      <div class="row" id="department_selector" style="display:none;">
        <div class="col-md-8">
          <div class="form-group">
            <label for="audience_departments">Select Department(s)</label>
            <select class="form-control" id="audience_departments" name="audience_ids[]" multiple="multiple" data-plugin="select_hrm" data-placeholder="Select departments...">
              <?php foreach ($departments as $dept): ?>
              <option value="<?= $dept['department_id'] ?>"><?= esc($dept['department_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Individual multi-select (shown when audience_type = individual) -->
      <div class="row" id="individual_selector" style="display:none;">
        <div class="col-md-8">
          <div class="form-group">
            <label for="audience_individuals">Select Employee(s)</label>
            <select class="form-control" id="audience_individuals" name="audience_ids[]" multiple="multiple" data-plugin="select_hrm" data-placeholder="Select employees...">
              <?php foreach ($employees as $emp): ?>
              <option value="<?= $emp['user_id'] ?>"><?= esc(trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''))) ?> (<?= esc($emp['email'] ?? '') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="text-right mt-3">
        <button type="button" class="btn btn-primary" onclick="goToStep(2)">
          Next <i class="feather icon-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- ================================================================ -->
    <!-- STEP 2: Message -->
    <!-- ================================================================ -->
    <div class="wizard-step" id="step-2">
      <h5 class="mb-3">Compose Message</h5>

      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label for="broadcast_type">Type Tag</label>
            <select class="form-control" id="broadcast_type" name="broadcast_type">
              <option value="memo">Memo</option>
              <option value="announcement">Announcement</option>
              <option value="alert">Alert</option>
              <option value="newsletter">Newsletter</option>
              <option value="policy_update">Policy Update</option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label for="template_select">Load Template</label>
            <select class="form-control" id="template_select">
              <option value="">-- Select Template --</option>
              <?php foreach ($templates as $t): ?>
              <option value="<?= $t['template_id'] ?>"
                      data-subject="<?= esc($t['subject'] ?? '') ?>"
                      data-body="<?= esc($t['body_html'] ?? '') ?>"
                      data-sms="<?= esc($t['body_sms'] ?? '') ?>">
                <?= esc($t['template_name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <div class="form-group w-100">
            <button type="button" class="btn btn-outline-info btn-sm" onclick="saveAsTemplate()">
              <i class="feather icon-save"></i> Save as Template
            </button>
          </div>
        </div>
      </div>

      <!-- Token insertion buttons -->
      <div class="mb-2">
        <label>Insert Token:</label><br>
        <button type="button" class="btn btn-outline-secondary btn-sm token-btn" onclick="insertToken('{{first_name}}')">First Name</button>
        <button type="button" class="btn btn-outline-secondary btn-sm token-btn" onclick="insertToken('{{last_name}}')">Last Name</button>
        <button type="button" class="btn btn-outline-secondary btn-sm token-btn" onclick="insertToken('{{full_name}}')">Full Name</button>
        <button type="button" class="btn btn-outline-secondary btn-sm token-btn" onclick="insertToken('{{company_name}}')">Company</button>
        <button type="button" class="btn btn-outline-secondary btn-sm token-btn" onclick="insertToken('{{department}}')">Department</button>
        <button type="button" class="btn btn-outline-secondary btn-sm token-btn" onclick="insertToken('{{designation}}')">Designation</button>
        <button type="button" class="btn btn-outline-secondary btn-sm token-btn" onclick="insertToken('{{date}}')">Date</button>
        <button type="button" class="btn btn-outline-secondary btn-sm token-btn" onclick="insertToken('{{month}}')">Month</button>
        <button type="button" class="btn btn-outline-secondary btn-sm token-btn" onclick="insertToken('{{sender_name}}')">Sender Name</button>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label for="subject">Subject <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter broadcast subject...">
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            <label for="body_html">Body (Email / In-App)</label>
            <textarea class="form-control editor" id="body_html" name="body_html" rows="10" placeholder="Compose your message..."></textarea>
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            <label for="body_sms">SMS Body</label>
            <textarea class="form-control" id="body_sms" name="body_sms" rows="3" maxlength="320" placeholder="SMS message (max 320 chars)..."></textarea>
            <small class="sms-counter" id="sms_counter">0 / 160 characters (0 SMS segments)</small>
          </div>
        </div>
      </div>

      <!-- Channel toggles -->
      <div class="row">
        <div class="col-md-12">
          <label>Delivery Channels:</label>
          <div class="d-flex">
            <div class="custom-control custom-switch mr-4">
              <input type="checkbox" class="custom-control-input" id="channel_inapp" name="channel_inapp" value="1" checked>
              <label class="custom-control-label" for="channel_inapp">In-App Notification</label>
            </div>
            <div class="custom-control custom-switch mr-4">
              <input type="checkbox" class="custom-control-input" id="channel_email" name="channel_email" value="1" checked>
              <label class="custom-control-label" for="channel_email">Email</label>
            </div>
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="channel_sms" name="channel_sms" value="1">
              <label class="custom-control-label" for="channel_sms">SMS</label>
            </div>
          </div>
        </div>
      </div>

      <div class="text-right mt-3">
        <button type="button" class="btn btn-secondary mr-2" onclick="goToStep(1)">
          <i class="feather icon-arrow-left"></i> Back
        </button>
        <button type="button" class="btn btn-outline-secondary mr-2" onclick="saveDraft()">
          <i class="feather icon-save"></i> Save Draft
        </button>
        <button type="button" class="btn btn-primary" onclick="goToStep(3)">
          Next <i class="feather icon-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- ================================================================ -->
    <!-- STEP 3: Preview -->
    <!-- ================================================================ -->
    <div class="wizard-step" id="step-3">
      <h5 class="mb-3">Preview</h5>
      <p class="text-muted">Showing personalised output for a sample recipient.</p>

      <div id="preview_loading" style="display:none;" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="sr-only">Loading...</span>
        </div>
      </div>

      <div id="preview_content" style="display:none;">
        <div class="row">
          <div class="col-md-12">
            <div class="alert alert-info">
              <strong>Sample Recipient:</strong> <span id="preview_recipient_name"></span>
              &nbsp;&mdash;&nbsp;
              <strong>Total Recipients:</strong> <span id="preview_total_recipients"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="card border">
              <div class="card-header bg-light">
                <strong>Subject:</strong> <span id="preview_subject"></span>
              </div>
              <div class="card-body" id="preview_body">
              </div>
            </div>
          </div>
        </div>
        <div class="row" id="preview_sms_section">
          <div class="col-md-6">
            <div class="card border">
              <div class="card-header bg-light">
                <strong>SMS Preview</strong>
              </div>
              <div class="card-body">
                <pre id="preview_sms" class="mb-0" style="white-space:pre-wrap;"></pre>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div id="preview_error" class="alert alert-danger" style="display:none;"></div>

      <div class="text-right mt-3">
        <button type="button" class="btn btn-secondary mr-2" onclick="goToStep(2)">
          <i class="feather icon-arrow-left"></i> Back
        </button>
        <button type="button" class="btn btn-primary" onclick="goToStep(4)">
          Next <i class="feather icon-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- ================================================================ -->
    <!-- STEP 4: Schedule & Send -->
    <!-- ================================================================ -->
    <div class="wizard-step" id="step-4">
      <h5 class="mb-3">Schedule &amp; Confirm</h5>

      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>When to Send</label>
            <div class="custom-control custom-radio mb-2">
              <input type="radio" class="custom-control-input" id="send_now" name="send_schedule" value="now" checked>
              <label class="custom-control-label" for="send_now">Send Now</label>
            </div>
            <div class="custom-control custom-radio">
              <input type="radio" class="custom-control-input" id="send_later" name="send_schedule" value="later">
              <label class="custom-control-label" for="send_later">Schedule for Later</label>
            </div>
          </div>
        </div>
        <div class="col-md-6" id="schedule_datetime_wrapper" style="display:none;">
          <div class="form-group">
            <label for="scheduled_at">Scheduled Date &amp; Time</label>
            <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at">
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div class="card border mt-3">
        <div class="card-header bg-light">
          <strong>Broadcast Summary</strong>
        </div>
        <div class="card-body">
          <table class="table table-borderless mb-0">
            <tr>
              <td class="font-weight-bold" width="180">Type:</td>
              <td id="summary_type"></td>
            </tr>
            <tr>
              <td class="font-weight-bold">Audience:</td>
              <td id="summary_audience"></td>
            </tr>
            <tr>
              <td class="font-weight-bold">Recipients:</td>
              <td id="summary_recipients"></td>
            </tr>
            <tr>
              <td class="font-weight-bold">Subject:</td>
              <td id="summary_subject"></td>
            </tr>
            <tr>
              <td class="font-weight-bold">Channels:</td>
              <td id="summary_channels"></td>
            </tr>
            <tr>
              <td class="font-weight-bold">Schedule:</td>
              <td id="summary_schedule"></td>
            </tr>
          </table>
        </div>
      </div>

      <div class="text-right mt-3">
        <button type="button" class="btn btn-secondary mr-2" onclick="goToStep(3)">
          <i class="feather icon-arrow-left"></i> Back
        </button>
        <button type="button" class="btn btn-success btn-lg" id="btn_send" onclick="sendBroadcast()">
          <i class="feather icon-send"></i> Confirm &amp; Send
        </button>
      </div>

      <div id="send_result" class="mt-3" style="display:none;"></div>
    </div>

  </div><!-- card-body -->
</div><!-- card -->

<!-- Save Template Modal -->
<div class="modal fade" id="saveTemplateModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Save as Template</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="tpl_name">Template Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="tpl_name" placeholder="Enter template name...">
        </div>
        <div class="form-group">
          <label for="tpl_category">Category</label>
          <select class="form-control" id="tpl_category">
            <option value="general">General</option>
            <option value="hr">HR</option>
            <option value="finance">Finance</option>
            <option value="compliance">Compliance</option>
            <option value="marketing">Marketing</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="confirmSaveTemplate()">Save Template</button>
      </div>
    </div>
  </div>
</div>

<script>
// =====================================================================
//  Wizard navigation
// =====================================================================
function goToStep(step) {
    document.querySelectorAll('.wizard-step').forEach(function(el) {
        el.classList.remove('active');
    });
    document.getElementById('step-' + step).classList.add('active');

    document.querySelectorAll('.step-indicator').forEach(function(el) {
        el.classList.remove('active');
        if (parseInt(el.getAttribute('data-step')) === step) {
            el.classList.add('active');
        }
        if (parseInt(el.getAttribute('data-step')) < step) {
            el.classList.add('completed');
        }
    });

    // Step 3: load preview
    if (step === 3) {
        loadPreview();
    }
    // Step 4: update summary
    if (step === 4) {
        updateSummary();
    }
}

// Step indicator clicks
document.querySelectorAll('.step-indicator').forEach(function(el) {
    el.addEventListener('click', function() {
        goToStep(parseInt(this.getAttribute('data-step')));
    });
});

// =====================================================================
//  Audience type toggle
// =====================================================================
document.getElementById('audience_type').addEventListener('change', function() {
    var val = this.value;
    document.getElementById('department_selector').style.display = (val === 'department') ? '' : 'none';
    document.getElementById('individual_selector').style.display = (val === 'individual') ? '' : 'none';
    updateRecipientCount();
});

function getAudienceIds() {
    var audienceType = document.getElementById('audience_type').value;
    var ids = [];
    if (audienceType === 'department') {
        var sel = document.getElementById('audience_departments');
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].selected) ids.push(sel.options[i].value);
        }
    } else if (audienceType === 'individual') {
        var sel = document.getElementById('audience_individuals');
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].selected) ids.push(sel.options[i].value);
        }
    }
    return ids;
}

function updateRecipientCount() {
    var audienceType = document.getElementById('audience_type').value;
    var ids = getAudienceIds();

    var url = '<?= site_url("erp/broadcasts/recipient-count"); ?>?audience_type=' + audienceType;
    if (ids.length > 0) {
        url += '&audience_ids=' + JSON.stringify(ids);
    }

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('recipient_count_display').value = data.count || 0;
    })
    .catch(function() {});
}

// Listen for changes on selectors
if (document.getElementById('audience_departments')) {
    document.getElementById('audience_departments').addEventListener('change', updateRecipientCount);
}
if (document.getElementById('audience_individuals')) {
    document.getElementById('audience_individuals').addEventListener('change', updateRecipientCount);
}
// Initial count
updateRecipientCount();

// =====================================================================
//  Token insertion
// =====================================================================
var lastFocusedField = null;
['subject', 'body_html', 'body_sms'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) {
        el.addEventListener('focus', function() { lastFocusedField = this; });
    }
});

function insertToken(token) {
    var field = lastFocusedField || document.getElementById('subject');
    if (!field) return;
    var start = field.selectionStart || 0;
    var end   = field.selectionEnd || 0;
    var val   = field.value;
    field.value = val.substring(0, start) + token + val.substring(end);
    field.focus();
    field.selectionStart = field.selectionEnd = start + token.length;
}

// =====================================================================
//  SMS counter
// =====================================================================
document.getElementById('body_sms').addEventListener('input', function() {
    var len = this.value.length;
    var segments = len <= 160 ? 1 : Math.ceil(len / 153);
    var counter = document.getElementById('sms_counter');
    counter.textContent = len + ' / 160 characters (' + segments + ' SMS segment' + (segments !== 1 ? 's' : '') + ')';
    counter.className = len > 160 ? 'sms-counter over-limit' : 'sms-counter';
});

// =====================================================================
//  Template load
// =====================================================================
document.getElementById('template_select').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('subject').value   = opt.getAttribute('data-subject') || '';
        document.getElementById('body_html').value  = opt.getAttribute('data-body') || '';
        document.getElementById('body_sms').value   = opt.getAttribute('data-sms') || '';
        document.getElementById('body_sms').dispatchEvent(new Event('input'));
    }
});

// =====================================================================
//  Save as template
// =====================================================================
function saveAsTemplate() {
    document.getElementById('tpl_name').value = '';
    $('#saveTemplateModal').modal('show');
}

function confirmSaveTemplate() {
    var name = document.getElementById('tpl_name').value.trim();
    if (!name) { alert('Template name is required.'); return; }

    var formData = new FormData();
    formData.append('template_name', name);
    formData.append('subject',  document.getElementById('subject').value);
    formData.append('body_html', document.getElementById('body_html').value);
    formData.append('body_sms', document.getElementById('body_sms').value);
    formData.append('category', document.getElementById('tpl_category').value);
    formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

    fetch('<?= site_url("erp/broadcasts/save-template"); ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.csrf_hash) document.getElementById('csrf_token').value = data.csrf_hash;
        if (data.result) {
            $('#saveTemplateModal').modal('hide');
            alert(data.result);
        } else if (data.error) {
            alert(data.error);
        }
    });
}

// =====================================================================
//  Save draft
// =====================================================================
function saveDraft() {
    var formData = buildFormData();
    formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

    fetch('<?= site_url("erp/broadcasts/save-draft"); ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.csrf_hash) document.getElementById('csrf_token').value = data.csrf_hash;
        if (data.broadcast_id) document.getElementById('broadcast_id').value = data.broadcast_id;
        if (data.result) {
            alert(data.result);
        } else if (data.error) {
            alert(data.error);
        }
    });
}

// =====================================================================
//  Preview
// =====================================================================
function loadPreview() {
    document.getElementById('preview_loading').style.display = '';
    document.getElementById('preview_content').style.display = 'none';
    document.getElementById('preview_error').style.display   = 'none';

    var formData = buildFormData();
    formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

    fetch('<?= site_url("erp/broadcasts/preview"); ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('preview_loading').style.display = 'none';
        if (data.csrf_hash) document.getElementById('csrf_token').value = data.csrf_hash;

        if (data.error) {
            document.getElementById('preview_error').style.display = '';
            document.getElementById('preview_error').textContent = data.error;
            return;
        }

        var p = data.preview || {};
        document.getElementById('preview_recipient_name').textContent  = p.recipient_name || '-';
        document.getElementById('preview_total_recipients').textContent = data.total_recipients || 0;
        document.getElementById('preview_subject').textContent          = p.subject || '';
        document.getElementById('preview_body').innerHTML               = p.body_html || '';
        document.getElementById('preview_sms').textContent              = p.body_sms || '';

        var hasSms = document.getElementById('channel_sms').checked;
        document.getElementById('preview_sms_section').style.display = hasSms ? '' : 'none';

        document.getElementById('preview_content').style.display = '';
    })
    .catch(function() {
        document.getElementById('preview_loading').style.display = 'none';
        document.getElementById('preview_error').style.display   = '';
        document.getElementById('preview_error').textContent      = 'Failed to load preview.';
    });
}

// =====================================================================
//  Summary
// =====================================================================
function updateSummary() {
    var type = document.getElementById('broadcast_type').value;
    var audience = document.getElementById('audience_type');
    var audienceLabel = audience.options[audience.selectedIndex].text;
    var recipientCount = document.getElementById('recipient_count_display').value;
    var subject = document.getElementById('subject').value;

    var channels = [];
    if (document.getElementById('channel_inapp').checked) channels.push('In-App');
    if (document.getElementById('channel_email').checked) channels.push('Email');
    if (document.getElementById('channel_sms').checked)   channels.push('SMS');

    var schedule = document.querySelector('input[name="send_schedule"]:checked').value;
    var scheduleText = 'Send Now';
    if (schedule === 'later') {
        var dt = document.getElementById('scheduled_at').value;
        scheduleText = dt ? 'Scheduled: ' + dt : 'Schedule time not set';
    }

    document.getElementById('summary_type').textContent       = type.charAt(0).toUpperCase() + type.slice(1);
    document.getElementById('summary_audience').textContent    = audienceLabel;
    document.getElementById('summary_recipients').textContent  = recipientCount;
    document.getElementById('summary_subject').textContent     = subject;
    document.getElementById('summary_channels').textContent    = channels.join(', ');
    document.getElementById('summary_schedule').textContent    = scheduleText;
}

// Schedule toggle
document.querySelectorAll('input[name="send_schedule"]').forEach(function(el) {
    el.addEventListener('change', function() {
        document.getElementById('schedule_datetime_wrapper').style.display =
            (this.value === 'later') ? '' : 'none';
    });
});

// =====================================================================
//  Send broadcast
// =====================================================================
function sendBroadcast() {
    if (!confirm('Are you sure you want to send this broadcast?')) return;

    var btn = document.getElementById('btn_send');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Sending...';

    var formData = buildFormData();

    var schedule = document.querySelector('input[name="send_schedule"]:checked').value;
    if (schedule === 'later') {
        formData.append('scheduled_at', document.getElementById('scheduled_at').value);
    }

    formData.append('<?= csrf_token() ?>', document.getElementById('csrf_token').value);

    var broadcastId = document.getElementById('broadcast_id').value;
    if (broadcastId) formData.append('broadcast_id', broadcastId);

    fetch('<?= site_url("erp/broadcasts/send"); ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="feather icon-send"></i> Confirm & Send';
        if (data.csrf_hash) document.getElementById('csrf_token').value = data.csrf_hash;

        var resultDiv = document.getElementById('send_result');
        resultDiv.style.display = '';

        if (data.result) {
            resultDiv.className = 'mt-3 alert alert-success';
            resultDiv.innerHTML = '<strong>Success!</strong> ' + data.result +
                ' <br>Queued <strong>' + (data.queued || 0) + '</strong> recipient(s).' +
                ' <a href="<?= site_url("erp/broadcasts/details"); ?>/' + data.broadcast_id + '" class="alert-link">View Details</a>';
        } else if (data.error) {
            resultDiv.className = 'mt-3 alert alert-danger';
            resultDiv.textContent = data.error;
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="feather icon-send"></i> Confirm & Send';
        var resultDiv = document.getElementById('send_result');
        resultDiv.style.display = '';
        resultDiv.className = 'mt-3 alert alert-danger';
        resultDiv.textContent = 'Network error. Please try again.';
    });
}

// =====================================================================
//  Helpers
// =====================================================================
function buildFormData() {
    var fd = new FormData();
    fd.append('broadcast_type', document.getElementById('broadcast_type').value);
    fd.append('audience_type',  document.getElementById('audience_type').value);
    fd.append('subject',        document.getElementById('subject').value);
    fd.append('body_html',      document.getElementById('body_html').value);
    fd.append('body_sms',       document.getElementById('body_sms').value);
    fd.append('channel_inapp',  document.getElementById('channel_inapp').checked ? '1' : '');
    fd.append('channel_email',  document.getElementById('channel_email').checked ? '1' : '');
    fd.append('channel_sms',    document.getElementById('channel_sms').checked ? '1' : '');

    var ids = getAudienceIds();
    ids.forEach(function(id) { fd.append('audience_ids[]', id); });

    return fd;
}
</script>
