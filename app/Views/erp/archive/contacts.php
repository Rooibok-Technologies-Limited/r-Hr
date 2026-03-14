<?php
/**
 * Phase 10.7 — Marketing Intelligence Contacts
 */
?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h5><i class="feather icon-mail"></i> Marketing Contacts</h5>
        <div class="card-header-right">
          <a href="<?= site_url('erp/archive'); ?>" class="btn btn-sm btn-light-primary"><i class="feather icon-arrow-left"></i> Back</a>
          <button type="button" class="btn btn-sm btn-light-success" id="btn-export-csv"><i class="feather icon-download"></i> Export CSV</button>
          <a href="<?= site_url('erp/broadcasts/create'); ?>" class="btn btn-sm btn-light-warning"><i class="feather icon-send"></i> Send Broadcast</a>
        </div>
      </div>
      <div class="card-body">
        <!-- Segmentation Filters -->
        <div class="row mb-3" id="contact-filters">
          <div class="col-md-2">
            <label class="small">Status</label>
            <select id="f-status" class="form-control form-control-sm">
              <option value="">All</option>
              <option value="active">Active</option>
              <option value="churned">Churned</option>
              <option value="expired">Expired</option>
              <option value="trial">Trial</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="small">Region / City</label>
            <input type="text" id="f-region" class="form-control form-control-sm" placeholder="Region...">
          </div>
          <div class="col-md-2">
            <label class="small">Plan Tier</label>
            <select id="f-plan" class="form-control form-control-sm">
              <option value="">All</option>
              <option value="free">Free</option>
              <option value="basic">Basic</option>
              <option value="professional">Professional</option>
              <option value="enterprise">Enterprise</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="small">Industry</label>
            <input type="text" id="f-industry" class="form-control form-control-sm" placeholder="Industry...">
          </div>
          <div class="col-md-1">
            <label class="small">Emp Min</label>
            <input type="number" id="f-emp-min" class="form-control form-control-sm" placeholder="0">
          </div>
          <div class="col-md-1">
            <label class="small">Emp Max</label>
            <input type="number" id="f-emp-max" class="form-control form-control-sm" placeholder="999">
          </div>
          <div class="col-md-1">
            <label class="small">Consent</label>
            <select id="f-consent" class="form-control form-control-sm">
              <option value="">All</option>
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>
          <div class="col-md-1">
            <label class="small">&nbsp;</label>
            <button type="button" class="btn btn-primary btn-sm btn-block" id="btn-filter"><i class="feather icon-filter"></i></button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped table-bordered" id="contacts-table" width="100%">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Company</th>
                <th>Location</th>
                <th>Industry</th>
                <th>Plan</th>
                <th>Employees</th>
                <th>Status</th>
                <th>Consent</th>
                <th>Subscription</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  var table = $('#contacts-table').DataTable({
    "processing": true,
    "pageLength": 50,
    "ajax": {
      "url": "<?= site_url('erp/archive/contacts-list'); ?>",
      "type": "GET",
      "data": function(d){
        d.status   = $('#f-status').val();
        d.region   = $('#f-region').val();
        d.plan_tier = $('#f-plan').val();
        d.industry = $('#f-industry').val();
        d.emp_min  = $('#f-emp-min').val();
        d.emp_max  = $('#f-emp-max').val();
        d.consent  = $('#f-consent').val();
      }
    },
    "columns": [
      {title:"Name"}, {title:"Email"}, {title:"Phone"}, {title:"Company"},
      {title:"Location"}, {title:"Industry"}, {title:"Plan"}, {title:"Employees"},
      {title:"Status"}, {title:"Consent"}, {title:"Subscription"}
    ]
  });

  $('#btn-filter').on('click', function(){ table.ajax.reload(); });

  // CSV export
  $('#btn-export-csv').on('click', function(){
    var data = table.rows({search:'applied'}).data();
    if(!data.length){ toastr.warning('No data to export.'); return; }
    var csv = 'Name,Email,Phone,Company,Location,Industry,Plan,Employees,Status\n';
    data.each(function(row){
      // Strip HTML tags from cells
      var clean = row.map(function(cell){ return '"' + $('<div>').html(cell).text().replace(/"/g,'""') + '"'; });
      csv += clean.join(',') + '\n';
    });
    var blob = new Blob([csv], {type:'text/csv'});
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    a.href = url; a.download = 'archive_contacts_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click(); URL.revokeObjectURL(url);
  });
});
</script>
