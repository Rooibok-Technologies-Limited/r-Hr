<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Payout methods (ADR-001) — capture + verify an employee's payout destination.
 * A method is payable only once verified (mobile money: name lookup + SMS code;
 * bank: manual). Talks to the Erp\PayoutMethods JSON endpoints via fetch.
 */
$self_only = $self_only ?? false;
$self_id   = $self_id ?? 0;
$staff     = $staff_list ?? [];
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><i data-feather="shield"></i> Payout methods</h5>
        <span class="text-muted small">A method must be verified before payroll can pay it.</span>
      </div>
      <div class="card-body">
        <?= csrf_field() ?>
        <?php if (! $self_only): ?>
        <div class="row">
          <div class="col-md-6"><div class="form-group mb-0">
            <label class="form-label">Employee <span class="text-danger">*</span></label>
            <select class="form-control" id="pm-emp">
              <option value="">— select employee —</option>
              <?php foreach ($staff as $s): ?>
                <option value="<?= (int) $s['user_id'] ?>"><?= esc(trim($s['first_name'] . ' ' . $s['last_name'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div id="pm-body" class="<?= $self_only ? '' : 'd-none' ?>">
  <div class="row">
    <!-- Methods list -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Destinations</h5></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered mb-0">
              <thead><tr><th>Type</th><th>Account</th><th>Name</th><th>Status</th><th></th></tr></thead>
              <tbody id="pm-rows"><tr><td colspan="5" class="text-center text-muted">No destinations yet.</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- Add method -->
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Add destination</h5></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select class="form-control" id="pm-type">
              <option value="momo">MTN MoMo</option>
              <option value="airtel">Airtel Money</option>
              <option value="bank">Bank account</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" id="pm-acct-label">Mobile number</label>
            <input type="text" class="form-control" id="pm-account" placeholder="e.g. 0772000000">
          </div>
          <div class="form-group">
            <label class="form-label">Account holder name</label>
            <input type="text" class="form-control" id="pm-name" placeholder="as registered">
          </div>
          <div class="form-group d-none" id="pm-bank-wrap">
            <label class="form-label">Bank name</label>
            <input type="text" class="form-control" id="pm-bank" placeholder="e.g. Stanbic">
          </div>
          <button class="btn btn-primary" id="pm-add"><i data-feather="plus"></i> Add destination</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Verify modal (inline card) -->
<div class="card d-none" id="pm-verify-card">
  <div class="card-header"><h5 class="mb-0">Verify destination <small class="text-muted" id="pm-verify-masked"></small></h5></div>
  <div class="card-body">
    <div id="pm-verify-msg" class="mb-2"></div>
    <div id="pm-verify-sms" class="d-none">
      <div class="form-group">
        <label class="form-label">Enter the 6-digit code sent to the number</label>
        <input type="text" class="form-control" id="pm-code" maxlength="6" style="max-width:180px" placeholder="______">
      </div>
      <button class="btn btn-success" id="pm-confirm"><i data-feather="check"></i> Confirm</button>
      <button class="btn btn-link" id="pm-verify-cancel">Cancel</button>
    </div>
    <div id="pm-verify-manual" class="d-none">
      <div class="form-group">
        <label class="form-label">Verification evidence <span class="text-muted">(e.g. penny-drop ref, bank letter)</span></label>
        <input type="text" class="form-control" id="pm-evidence" placeholder="describe how you confirmed this account">
      </div>
      <button class="btn btn-success" id="pm-manual-confirm"><i data-feather="check"></i> Mark verified</button>
      <button class="btn btn-link" id="pm-manual-cancel">Cancel</button>
    </div>
  </div>
</div>

<script>
(function () {
  var base = '<?= site_url('erp/payout-methods') ?>';
  var selfOnly = <?= $self_only ? 'true' : 'false' ?>;
  var emp = selfOnly ? <?= (int) $self_id ?> : 0;
  var verifyingId = null;
  var csrf = document.querySelector('input[name="csrf_token"]').value;

  function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
  function toast(k,m){ if(window.toastr) toastr[k](m); }
  function draw(){ if(window.feather) feather.replace(); }
  function post(url,payload){
    var fd=new FormData(); fd.append('csrf_token',csrf);
    Object.keys(payload||{}).forEach(function(k){ fd.append(k,payload[k]); });
    return fetch(url,{method:'POST',body:fd,headers:{'X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json();})
      .then(function(j){ if(j.csrf_hash){csrf=j.csrf_hash; document.querySelector('input[name="csrf_token"]').value=j.csrf_hash;} return j; });
  }
  function typeLabel(t){ return {momo:'MTN MoMo', airtel:'Airtel Money', bank:'Bank'}[t] || t; }

  function statusCell(m){
    if(m.verified){
      return '<span class="badge badge-light-success">verified</span>' + (m.is_primary?' <span class="badge badge-light-primary">primary</span>':'');
    }
    return '<span class="badge badge-light-warning">unverified</span>';
  }
  function actions(m){
    if(!m.verified) return '<button class="btn btn-sm btn-info" data-act="verify" data-id="'+m.method_id+'" data-masked="'+esc(m.masked)+'">Verify</button>';
    if(!m.is_primary) return '<button class="btn btn-sm btn-outline-primary" data-act="primary" data-id="'+m.method_id+'">Make primary</button>';
    return '';
  }

  function loadMethods(){
    if(!emp){ return; }
    fetch(base+'/list?employee_id='+emp,{headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json();})
      .then(function(j){
        var tb=document.getElementById('pm-rows');
        if(!j.ok){ tb.innerHTML='<tr><td colspan="5" class="text-center text-danger">Not authorized</td></tr>'; return; }
        var ms=j.methods||[];
        tb.innerHTML = ms.length ? ms.map(function(m){
          return '<tr><td>'+esc(typeLabel(m.type))+'</td><td>'+esc(m.masked)+(m.bank_name?'<br><small class="text-muted">'+esc(m.bank_name)+'</small>':'')+'</td>'
               + '<td>'+esc(m.name||'—')+'</td><td>'+statusCell(m)+'</td><td>'+actions(m)+'</td></tr>';
        }).join('') : '<tr><td colspan="5" class="text-center text-muted">No destinations yet.</td></tr>';
      });
  }

  // Type → label + bank field toggle
  document.getElementById('pm-type').addEventListener('change', function(){
    var t=this.value;
    document.getElementById('pm-acct-label').textContent = (t==='bank') ? 'Account number' : 'Mobile number';
    document.getElementById('pm-bank-wrap').classList.toggle('d-none', t!=='bank');
  });

  // Employee select
  var sel=document.getElementById('pm-emp');
  if(sel){ sel.addEventListener('change', function(){
    emp = parseInt(this.value||'0',10);
    document.getElementById('pm-body').classList.toggle('d-none', !emp);
    document.getElementById('pm-verify-card').classList.add('d-none');
    if(emp) loadMethods();
  }); }

  // Add
  document.getElementById('pm-add').addEventListener('click', function(){
    if(!emp){ toast('warning','Pick an employee first'); return; }
    var type=document.getElementById('pm-type').value;
    var account=document.getElementById('pm-account').value.trim();
    if(!account){ toast('warning','Enter the account'); return; }
    post(base+'/add', {employee_id:emp, type:type, account:account,
      account_name:document.getElementById('pm-name').value.trim(),
      bank_name:document.getElementById('pm-bank').value.trim(), provider:type
    }).then(function(j){
      if(!j.ok){ toast('error', j.reason||'Could not add'); return; }
      toast('success','Destination added — now verify it');
      document.getElementById('pm-account').value=''; document.getElementById('pm-name').value=''; document.getElementById('pm-bank').value='';
      loadMethods();
    });
  });

  // Row actions
  document.getElementById('pm-rows').addEventListener('click', function(e){
    var b=e.target.closest('button[data-act]'); if(!b) return;
    var id=b.getAttribute('data-id'), act=b.getAttribute('data-act');
    if(act==='primary'){
      post(base+'/set-primary',{method_id:id}).then(function(j){
        if(!j.ok){ toast('error', j.reason||'Failed'); return; } toast('success','Primary set'); loadMethods();
      }); return;
    }
    if(act==='verify'){ startVerify(id, b.getAttribute('data-masked')); }
  });

  function startVerify(id, masked){
    verifyingId=id;
    var card=document.getElementById('pm-verify-card');
    card.classList.remove('d-none');
    document.getElementById('pm-verify-masked').textContent = masked||'';
    document.getElementById('pm-verify-sms').classList.add('d-none');
    document.getElementById('pm-verify-manual').classList.add('d-none');
    document.getElementById('pm-verify-msg').innerHTML='<span class="text-muted">Requesting verification…</span>';
    card.scrollIntoView({behavior:'smooth'});
    post(base+'/verify-start',{method_id:id}).then(function(j){
      if(!j.ok){ document.getElementById('pm-verify-msg').innerHTML='<span class="text-danger">'+esc(j.reason||'Failed')+'</span>'; return; }
      if(j.channel==='manual'){
        if(selfOnly){
          document.getElementById('pm-verify-msg').innerHTML='<span class="text-info">Bank destination flagged for manual verification by an admin.</span>';
        } else {
          document.getElementById('pm-verify-msg').innerHTML='<span class="text-info">Bank destination — confirm against evidence, then mark it verified.</span>';
          document.getElementById('pm-evidence').value='';
          document.getElementById('pm-verify-manual').classList.remove('d-none');
        }
        return;
      }
      var msg='<span class="text-success">Code sent';
      if(j.name) msg+=' — account holder: <strong>'+esc(j.name)+'</strong>';
      msg+='.</span>';
      if(j.note) msg+=' <span class="text-warning">'+esc(j.note)+'</span>';
      if(j.dev_code) msg+=' <span class="badge badge-light-danger">DEV code: '+esc(j.dev_code)+'</span>';
      document.getElementById('pm-verify-msg').innerHTML=msg;
      document.getElementById('pm-verify-sms').classList.remove('d-none');
      document.getElementById('pm-code').value=''; document.getElementById('pm-code').focus();
    });
  }

  document.getElementById('pm-confirm').addEventListener('click', function(){
    var code=document.getElementById('pm-code').value.trim();
    if(code.length<4){ toast('warning','Enter the code'); return; }
    post(base+'/verify-confirm',{method_id:verifyingId, code:code}).then(function(j){
      if(!j.ok){ toast('error', j.reason||'Incorrect'); return; }
      toast('success','Destination verified'); document.getElementById('pm-verify-card').classList.add('d-none'); loadMethods();
    });
  });
  document.getElementById('pm-verify-cancel').addEventListener('click', function(){
    document.getElementById('pm-verify-card').classList.add('d-none');
  });
  document.getElementById('pm-manual-confirm').addEventListener('click', function(){
    post(base+'/verify-manual',{method_id:verifyingId, evidence:document.getElementById('pm-evidence').value.trim()}).then(function(j){
      if(!j.ok){ toast('error', j.reason||'Failed'); return; }
      toast('success','Bank destination verified'); document.getElementById('pm-verify-card').classList.add('d-none'); loadMethods();
    });
  });
  document.getElementById('pm-manual-cancel').addEventListener('click', function(){
    document.getElementById('pm-verify-card').classList.add('d-none');
  });

  if(selfOnly) loadMethods();
  draw();
})();
</script>
