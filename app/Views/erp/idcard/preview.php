<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
/**
 * Staff ID Card console (embedded in layout_main). Live preview of all four
 * faces for a chosen employee: orientation + side + zoom + compare-all, plus
 * issue / regenerate / revoke / print / PNG for managers.
 * Vars: $canManage, $settings, $previewId, $employees.
 */
$csrfField = csrf_token(); $csrfHash = csrf_hash();
$allowChoice = (int) ($settings['allow_orientation_choice'] ?? 1) === 1;
$defOrient   = ($settings['default_orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait';
?>
<style>
  .idc-wrap { --sage:#A7C49A; --forest:#3B5A45; }
  .idc-bar { display:flex; gap:18px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #ebe8e2;
             border-radius:12px; padding:16px 18px; margin-bottom:18px; }
  .idc-bar .grp { display:flex; flex-direction:column; gap:6px; }
  .idc-bar label { font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#8a857c; font-weight:600; }
  .idc-seg { display:inline-flex; border:1px solid #d9d5cd; border-radius:8px; overflow:hidden; }
  .idc-seg button { border:0; background:#fff; padding:8px 14px; font-size:13px; font-weight:600; cursor:pointer; color:#4a463f; }
  .idc-seg button.on { background:var(--forest); color:#fff; }
  .idc-sel { border:1px solid #d9d5cd; border-radius:8px; padding:8px 10px; font-size:13px; min-width:200px; background:#fff; }
  .idc-actions { margin-left:auto; display:flex; gap:8px; flex-wrap:wrap; }
  .idc-btn { border:0; border-radius:8px; padding:9px 15px; font-size:13px; font-weight:600; cursor:pointer; background:var(--forest); color:#fff; }
  .idc-btn.alt { background:#fff; color:var(--forest); border:1px solid #cdd8cf; }
  .idc-btn.warn { background:#fff; color:#b3352f; border:1px solid #ecc6c3; }
  .idc-btn:disabled { opacity:.5; cursor:not-allowed; }
  .idc-status { font-size:12px; font-weight:700; padding:4px 12px; border-radius:999px; letter-spacing:1px; }
  .idc-stage { background:#efece6; border-radius:14px; padding:32px; min-height:420px; display:flex; flex-wrap:wrap;
               gap:30px; justify-content:center; align-items:flex-start; }
  .idc-group { display:flex; flex-direction:column; align-items:center; gap:12px; }
  .idc-group .glabel { font-size:11px; letter-spacing:2px; text-transform:uppercase; color:#8a857c; font-weight:700; }
  .idc-faces { display:flex; gap:20px; flex-wrap:wrap; justify-content:center; }
  .idc-face-box { display:flex; flex-direction:column; align-items:center; gap:8px; }
  .idc-face-box .cap { font-size:10px; letter-spacing:2px; text-transform:uppercase; color:#a49f96; }
  .idc-card { overflow:hidden; border-radius:10px; box-shadow:0 8px 30px rgba(0,0,0,.14); background:#ECE8E1; }
  .idc-card.portrait  { width:calc(260px * var(--zoom,1)); aspect-ratio:540/856; }
  .idc-card.landscape { width:calc(400px * var(--zoom,1)); aspect-ratio:856/540; }
  .idc-card svg { display:block; width:100%; height:100%; }
  .idc-empty { color:#a49f96; font-size:14px; padding:60px; text-align:center; width:100%; }
  @media (max-width:640px){ .idc-actions{ margin-left:0; } .idc-faces{ flex-direction:column; } }
</style>

<div class="idc-wrap" data-preview-id="<?= (int) $previewId ?>" data-can-manage="<?= $canManage ? 1 : 0 ?>"
     data-def-orient="<?= $defOrient ?>" data-allow-choice="<?= $allowChoice ? 1 : 0 ?>">
  <input type="hidden" id="idc_csrf_field" value="<?= esc($csrfField) ?>">
  <input type="hidden" id="idc_csrf_hash" value="<?= esc($csrfHash) ?>">

  <div class="idc-bar">
    <?php if ($canManage && ! empty($employees)): ?>
    <div class="grp">
      <label>Preview employee</label>
      <select class="idc-sel" id="idc-emp">
        <?php foreach ($employees as $e): ?>
          <option value="<?= (int) $e['user_id'] ?>" <?= (int) $e['user_id'] === (int) $previewId ? 'selected' : '' ?>>
            <?= esc(trim($e['first_name'] . ' ' . $e['last_name'])) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <div class="grp">
      <label>Orientation</label>
      <div class="idc-seg" id="idc-orient">
        <button data-o="portrait"  class="<?= $defOrient === 'portrait' ? 'on' : '' ?>">Portrait</button>
        <button data-o="landscape" class="<?= $defOrient === 'landscape' ? 'on' : '' ?>">Landscape</button>
      </div>
    </div>

    <div class="grp">
      <label>View</label>
      <div class="idc-seg" id="idc-view">
        <button data-v="both" class="on">Front + Back</button>
        <button data-v="front">Front</button>
        <button data-v="back">Back</button>
      </div>
    </div>

    <div class="grp">
      <label>Zoom</label>
      <div class="idc-seg">
        <button type="button" id="idc-zoom-out">−</button>
        <button type="button" id="idc-zoom-val" style="min-width:64px;cursor:default;">100%</button>
        <button type="button" id="idc-zoom-in">+</button>
      </div>
    </div>

    <div class="grp">
      <label>&nbsp;</label>
      <div class="idc-seg"><button type="button" id="idc-compare">Compare all 4</button></div>
    </div>

    <div class="idc-actions">
      <span class="idc-status" id="idc-status" style="background:#e6f4ec;color:#2f7d4f;">—</span>
      <button class="idc-btn alt" id="idc-png-front">PNG</button>
      <button class="idc-btn alt" id="idc-print">Print / PDF</button>
      <?php if ($canManage): ?>
        <button class="idc-btn" id="idc-generate">Generate / Regenerate</button>
        <button class="idc-btn warn" id="idc-revoke">Revoke</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="idc-stage" id="idc-stage" style="--zoom:1;">
    <div class="idc-empty">Loading preview…</div>
  </div>
</div>

<!-- qrcodejs loaded here; idcard.js is auto-loaded by the layout footer (path_url=idcard) -->
<script src="<?= base_url('public/assets/plugins/qrcode/qrcode.min.js') ?>"></script>
<script>
(function(){
  var wrap = document.querySelector('.idc-wrap');
  var stage = document.getElementById('idc-stage');
  var facesUrl = "<?= site_url('erp/id-cards/faces') ?>";
  var genUrl   = "<?= site_url('erp/id-cards/generate') ?>";
  var revUrl   = "<?= site_url('erp/id-cards/revoke') ?>";
  var printBase= "<?= site_url('erp/id-card') ?>";
  var canManage = wrap.getAttribute('data-can-manage') === '1';
  var state = { userId: parseInt(wrap.getAttribute('data-preview-id'),10),
                orient: wrap.getAttribute('data-def-orient') || 'portrait',
                view: 'both', zoom: 1, compare:false, faces:{}, verify:'', status:'active' };

  function csrf(){ return { field: document.getElementById('idc_csrf_field').value, hash: document.getElementById('idc_csrf_hash').value }; }
  function setHash(h){ if(h) document.getElementById('idc_csrf_hash').value = h; }

  var STATUS = { active:['#2f7d4f','#e6f4ec','ACTIVE'], expired:['#a5741b','#fbf1dc','EXPIRED'],
    revoked:['#b3352f','#fce8e6','REVOKED'], inactive:['#5b5b5b','#ececec','INACTIVE'],
    not_issued:['#5b5b5b','#ececec','NOT ISSUED'] };
  function paintStatus(s){ var b=STATUS[s]||STATUS.active; var el=document.getElementById('idc-status');
    el.textContent=b[2]; el.style.color=b[0]; el.style.background=b[1]; }

  function seg(id, attr, val){ document.querySelectorAll('#'+id+' button').forEach(function(b){
    b.classList.toggle('on', b.getAttribute(attr)===val); }); }

  function faceBox(key, cap){
    var o = key.indexOf('landscape')===0 ? 'landscape':'portrait';
    var d=document.createElement('div'); d.className='idc-face-box';
    var card=document.createElement('div'); card.className='idc-card '+o;
    card.innerHTML = state.faces[key] || '';
    var c=document.createElement('div'); c.className='cap'; c.textContent=cap;
    d.appendChild(card); d.appendChild(c); return d;
  }

  function render(){
    stage.style.setProperty('--zoom', state.zoom);
    stage.innerHTML='';
    if(!state.faces || !Object.keys(state.faces).length){ stage.innerHTML='<div class="idc-empty">No preview available.</div>'; return; }
    if(state.compare){
      [['portrait','Portrait'],['landscape','Landscape']].forEach(function(g){
        var grp=document.createElement('div'); grp.className='idc-group';
        var gl=document.createElement('div'); gl.className='glabel'; gl.textContent=g[1]; grp.appendChild(gl);
        var fs=document.createElement('div'); fs.className='idc-faces';
        fs.appendChild(faceBox(g[0]+'_front','Front')); fs.appendChild(faceBox(g[0]+'_back','Back'));
        grp.appendChild(fs); stage.appendChild(grp);
      });
    } else {
      var fs=document.createElement('div'); fs.className='idc-faces';
      if(state.view==='both'||state.view==='front') fs.appendChild(faceBox(state.orient+'_front','Front'));
      if(state.view==='both'||state.view==='back')  fs.appendChild(faceBox(state.orient+'_back','Back'));
      stage.appendChild(fs);
    }
    if(window.IdCard) IdCard.renderQR(stage);
  }

  function load(){
    stage.innerHTML='<div class="idc-empty">Loading preview…</div>';
    var q = facesUrl + '?user_id=' + encodeURIComponent(state.userId);
    fetch(q, {credentials:'same-origin'}).then(function(r){ return r.json(); }).then(function(j){
      if(!j || !j.ok){ stage.innerHTML='<div class="idc-empty">Preview unavailable for this employee.</div>'; return; }
      state.faces = j.faces; state.verify=j.verify_url; state.status=j.status;
      setHash(j.csrf_hash); paintStatus(j.status); render();
    }).catch(function(){ stage.innerHTML='<div class="idc-empty">Failed to load preview.</div>'; });
  }

  // controls
  var empSel=document.getElementById('idc-emp');
  if(empSel) empSel.addEventListener('change', function(){ state.userId=parseInt(this.value,10); load(); });
  document.getElementById('idc-orient').addEventListener('click', function(e){ var b=e.target.closest('button'); if(!b)return;
    state.orient=b.getAttribute('data-o'); state.compare=false; seg('idc-orient','data-o',state.orient); render(); });
  document.getElementById('idc-view').addEventListener('click', function(e){ var b=e.target.closest('button'); if(!b)return;
    state.view=b.getAttribute('data-v'); seg('idc-view','data-v',state.view); render(); });
  document.getElementById('idc-compare').addEventListener('click', function(){ state.compare=!state.compare;
    this.classList.toggle('on', state.compare); render(); });
  function zoom(z){ state.zoom=Math.min(2, Math.max(0.5, Math.round((state.zoom+z)*10)/10));
    document.getElementById('idc-zoom-val').textContent=Math.round(state.zoom*100)+'%'; render(); }
  document.getElementById('idc-zoom-in').addEventListener('click', function(){ zoom(0.1); });
  document.getElementById('idc-zoom-out').addEventListener('click', function(){ zoom(-0.1); });

  document.getElementById('idc-png-front').addEventListener('click', function(){
    var svg=stage.querySelector('.idc-card svg'); if(svg && window.IdCard) IdCard.exportPNG(svg, 'staff-id-'+state.orient+'.png', 300); });
  document.getElementById('idc-print').addEventListener('click', function(){
    window.open(printBase + '/' + encodeURIComponent("<?= '' ?>") + '?o=' + state.orient + '&uid=' + state.userId, '_blank'); });

  // NOTE: print route takes an encoded id; we pass uid via query for managers.
  document.getElementById('idc-print').onclick = function(){
    window.open(printBase + '/0?o=' + state.orient + '&uid=' + state.userId, '_blank');
  };

  if(canManage){
    document.getElementById('idc-generate').addEventListener('click', function(){
      var body=new FormData(); body.append('user_id', state.userId); body.append('orientation', state.orient);
      body.append('regenerate', 1); body.append(csrf().field, csrf().hash);
      fetch(genUrl,{method:'POST',credentials:'same-origin',body:body}).then(function(r){return r.json();}).then(function(j){
        setHash(j.csrf_hash);
        if(j.ok){ if(window.toastr) toastr.success('Card issued: '+j.card_number); load(); }
        else if(window.toastr) toastr.error(j.error||'Failed'); });
    });
    document.getElementById('idc-revoke').addEventListener('click', function(){
      if(!confirm('Revoke this employee\'s ID card? The QR will immediately show CARD REVOKED.')) return;
      var body=new FormData(); body.append('user_id', state.userId); body.append(csrf().field, csrf().hash);
      fetch(revUrl,{method:'POST',credentials:'same-origin',body:body}).then(function(r){return r.json();}).then(function(j){
        setHash(j.csrf_hash);
        if(j.ok){ if(window.toastr) toastr.success('Card revoked'); load(); }
        else if(window.toastr) toastr.error('Failed to revoke'); });
    });
  }

  load();
})();
</script>
