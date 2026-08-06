<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
/**
 * ID Card Template Builder (embedded in layout_main). Live-preview form: colours,
 * fields, logo, QR, orientation, ID-number format, validity and terms. Uses the
 * same faces() endpoint with unsaved overrides so the preview updates instantly.
 * Vars: $settings, $employees, $previewId.
 */
$s = $settings; $f = $s['fields'];
$csrfField = csrf_token(); $csrfHash = csrf_hash();
$colorRows = [
  'color_primary'=>'Primary (ribbons)', 'color_secondary'=>'Secondary (circles / ID strip)',
  'color_accent'=>'Accent (dots)', 'color_dark'=>'Dark forms', 'color_light'=>'Neutral blobs',
  'color_bg'=>'Card background', 'color_text'=>'Text', 'color_muted'=>'Muted text',
];
$fieldLabels = [
  'photo'=>'Employee photo','name'=>'Full name','position'=>'Job position','staff_id'=>'Staff ID',
  'join_date'=>'Join date','expiry_date'=>'Expiry date','date_of_birth'=>'Date of birth',
  'department'=>'Department','phone'=>'Phone','blood_group'=>'Blood group',
];
?>
<style>
  .bld { display:grid; grid-template-columns: 420px 1fr; gap:22px; align-items:start; }
  @media (max-width:1100px){ .bld { grid-template-columns:1fr; } }
  .bld .panel { background:#fff; border:1px solid #ebe8e2; border-radius:12px; padding:18px 20px; }
  .bld h6 { font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#8a857c; margin:18px 0 10px; font-weight:700; }
  .bld h6:first-child { margin-top:0; }
  .bld .fg { margin-bottom:12px; }
  .bld label.l { display:block; font-size:12px; color:#5a564e; margin-bottom:4px; font-weight:600; }
  .bld input[type=text], .bld input[type=number], .bld select, .bld textarea {
     width:100%; border:1px solid #d9d5cd; border-radius:8px; padding:8px 10px; font-size:13px; }
  .bld .row2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
  .bld .chk { display:flex; align-items:center; gap:8px; font-size:13px; padding:4px 0; }
  .bld .grid-chk { display:grid; grid-template-columns:1fr 1fr; gap:2px 14px; }
  .bld .colorrow { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
  .bld .colorrow input[type=color]{ width:38px; height:30px; border:1px solid #d9d5cd; border-radius:6px; background:#fff; padding:2px; }
  .bld .colorrow span { font-size:12px; color:#5a564e; }
  .bld .save { background:#3B5A45; color:#fff; border:0; border-radius:9px; padding:11px 18px; font-weight:600; font-size:14px; cursor:pointer; width:100%; margin-top:8px; }
  .bld .muted { font-size:11px; color:#a49f96; }
  .prevbar { display:flex; gap:12px; align-items:center; margin-bottom:14px; flex-wrap:wrap; }
  .idc-seg { display:inline-flex; border:1px solid #d9d5cd; border-radius:8px; overflow:hidden; }
  .idc-seg button { border:0; background:#fff; padding:7px 13px; font-size:13px; font-weight:600; cursor:pointer; color:#4a463f; }
  .idc-seg button.on { background:#3B5A45; color:#fff; }
  .prevstage { background:#efece6; border-radius:14px; padding:28px; display:flex; gap:20px; justify-content:center; flex-wrap:wrap; min-height:420px; }
  .idc-card { overflow:hidden; border-radius:10px; box-shadow:0 8px 30px rgba(0,0,0,.14); background:#ECE8E1; }
  .idc-card.portrait { width:260px; aspect-ratio:540/856; } .idc-card.landscape { width:400px; aspect-ratio:856/540; }
  .idc-card svg { display:block; width:100%; height:100%; }
  .idc-face-box{ display:flex; flex-direction:column; align-items:center; gap:8px; } .idc-face-box .cap{ font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#a49f96; }
</style>

<div class="bld">
  <!-- ============ CONTROLS ============ -->
  <div class="panel" id="bld-form">
    <input type="hidden" id="idc_csrf_field" value="<?= esc($csrfField) ?>">
    <input type="hidden" id="idc_csrf_hash" value="<?= esc($csrfHash) ?>">

    <h6>Template</h6>
    <div class="fg"><strong>Abstract Organic</strong> <span class="muted">(default)</span></div>

    <h6>Orientation</h6>
    <div class="fg">
      <label class="l">Default orientation</label>
      <select id="f_default_orientation">
        <option value="portrait"  <?= $s['default_orientation']==='portrait'?'selected':'' ?>>Portrait</option>
        <option value="landscape" <?= $s['default_orientation']==='landscape'?'selected':'' ?>>Landscape</option>
      </select>
    </div>
    <label class="chk"><input type="checkbox" id="f_allow_choice" <?= (int)$s['allow_orientation_choice']===1?'checked':'' ?>> Allow choosing orientation at generation</label>

    <h6>Branding colours</h6>
    <label class="chk"><input type="checkbox" id="f_use_theme" <?= empty($s['_custom_colors'])?'checked':'' ?>> Use my system theme colours (recommended)</label>
    <div id="colorbox" style="margin-top:10px;">
      <?php foreach ($colorRows as $ck=>$clabel): ?>
        <div class="colorrow"><input type="color" class="fcolor" id="f_<?= $ck ?>" value="<?= esc($s[$ck]) ?>"><span><?= esc($clabel) ?></span></div>
      <?php endforeach; ?>
      <div class="muted">Colours change branding only — the pattern geometry never changes.</div>
    </div>

    <h6>Logo &amp; QR</h6>
    <label class="chk"><input type="checkbox" id="f_show_logo" <?= (int)$s['show_logo']===1?'checked':'' ?>> Show company logo <span class="muted">(upload in Theme settings)</span></label>
    <label class="chk"><input type="checkbox" id="f_enable_qr" <?= (int)$s['enable_qr']===1?'checked':'' ?>> Enable QR verification</label>

    <h6>Employee fields</h6>
    <div class="grid-chk">
      <?php foreach ($fieldLabels as $fk=>$fl): ?>
        <label class="chk"><input type="checkbox" class="ffield" data-k="<?= $fk ?>" <?= !empty($f[$fk])?'checked':'' ?>> <?= esc($fl) ?></label>
      <?php endforeach; ?>
    </div>

    <h6>ID number format</h6>
    <div class="row2">
      <div class="fg"><label class="l">Prefix</label><input type="text" id="f_id_prefix" value="<?= esc($s['id_prefix']) ?>"></div>
      <div class="fg"><label class="l">Sequence length</label><input type="number" id="f_seq_length" min="1" max="10" value="<?= (int)$s['seq_length'] ?>"></div>
    </div>
    <div class="fg"><label class="l">Pattern</label><input type="text" id="f_id_pattern" value="<?= esc($s['id_pattern']) ?>"><div class="muted">Tokens: {PREFIX} {YEAR} {SEQUENCE} · Example: <span id="id_example">—</span></div></div>

    <h6>Validity &amp; terms</h6>
    <div class="fg"><label class="l">Card validity (years)</label><input type="number" id="f_validity_years" min="1" max="20" value="<?= (int)$s['validity_years'] ?>"></div>
    <div class="fg"><label class="l">Terms &amp; conditions</label><textarea id="f_terms" rows="4"><?= esc($s['terms']) ?></textarea></div>

    <button class="save" id="bld-save">Save template</button>
  </div>

  <!-- ============ LIVE PREVIEW ============ -->
  <div class="panel">
    <div class="prevbar">
      <?php if (! empty($employees)): ?>
      <div><label class="l">Preview employee</label>
        <select id="p_emp" style="border:1px solid #d9d5cd;border-radius:8px;padding:7px 10px;min-width:180px;">
          <?php foreach ($employees as $e): ?>
            <option value="<?= (int)$e['user_id'] ?>" <?= (int)$e['user_id']===(int)$previewId?'selected':'' ?>><?= esc(trim($e['first_name'].' '.$e['last_name'])) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div><label class="l">Orientation</label>
        <div class="idc-seg" id="p_orient">
          <button data-o="portrait" class="<?= $s['default_orientation']==='portrait'?'on':'' ?>">Portrait</button>
          <button data-o="landscape" class="<?= $s['default_orientation']==='landscape'?'on':'' ?>">Landscape</button>
        </div>
      </div>
      <div><label class="l">Side</label>
        <div class="idc-seg" id="p_view">
          <button data-v="both" class="on">Front + Back</button><button data-v="front">Front</button><button data-v="back">Back</button>
        </div>
      </div>
    </div>
    <div class="prevstage" id="p_stage"><div class="muted">Loading preview…</div></div>
  </div>
</div>

<script src="<?= base_url('public/assets/plugins/qrcode/qrcode.min.js') ?>"></script>
<script>
(function(){
  var facesUrl="<?= site_url('erp/id-cards/faces') ?>", saveUrl="<?= site_url('erp/id-cards/save-settings') ?>";
  var stage=document.getElementById('p_stage');
  var st={ userId: <?= (int)$previewId ?>, orient:"<?= $s['default_orientation'] ?>", view:'both', faces:{} };
  function csrf(){ return {field:document.getElementById('idc_csrf_field').value, hash:document.getElementById('idc_csrf_hash').value}; }
  function setHash(h){ if(h) document.getElementById('idc_csrf_hash').value=h; }
  var COLORS=['color_primary','color_secondary','color_accent','color_dark','color_light','color_bg','color_text','color_muted'];
  var FIELDS=<?= json_encode(array_keys($fieldLabels)) ?>;

  function useTheme(){ return document.getElementById('f_use_theme').checked; }
  function toggleColorBox(){ document.getElementById('colorbox').style.opacity = useTheme()?0.45:1;
    document.querySelectorAll('.fcolor').forEach(function(i){ i.disabled=useTheme(); }); }

  function overrides(){
    var p=new URLSearchParams(); p.set('user_id', st.userId);
    if(!useTheme()){ COLORS.forEach(function(c){ p.set(c, document.getElementById('f_'+c).value); }); }
    p.set('terms', document.getElementById('f_terms').value);
    p.set('show_logo', document.getElementById('f_show_logo').checked?1:0);
    p.set('enable_qr', document.getElementById('f_enable_qr').checked?1:0);
    FIELDS.forEach(function(k){ var el=document.querySelector('.ffield[data-k="'+k+'"]'); p.set('fields['+k+']', el&&el.checked?1:0); });
    return p.toString();
  }
  function example(){
    var pre=(document.getElementById('f_id_prefix').value||<?= json_encode($s['id_prefix'] ?? 'ID') ?>).toUpperCase();
    var len=parseInt(document.getElementById('f_seq_length').value,10)||4;
    var pat=document.getElementById('f_id_pattern').value||'{PREFIX}-{YEAR}-{SEQUENCE}';
    var seq=('0'.repeat(len)+'47').slice(-len);
    document.getElementById('id_example').textContent = pat.replace('{PREFIX}',pre).replace('{YEAR}','<?= date('Y') ?>').replace('{SEQUENCE}',seq).replace('{SEQ}',seq);
  }
  function faceBox(key,cap){ var o=key.indexOf('landscape')===0?'landscape':'portrait';
    var d=document.createElement('div'); d.className='idc-face-box';
    var card=document.createElement('div'); card.className='idc-card '+o; card.innerHTML=st.faces[key]||'';
    var c=document.createElement('div'); c.className='cap'; c.textContent=cap; d.appendChild(card); d.appendChild(c); return d; }
  function render(){ stage.innerHTML=''; if(!st.faces||!Object.keys(st.faces).length){ stage.innerHTML='<div class="muted">No preview.</div>'; return; }
    var fs=document.createElement('div'); fs.style.display='flex'; fs.style.gap='20px'; fs.style.flexWrap='wrap'; fs.style.justifyContent='center';
    if(st.view==='both'||st.view==='front') fs.appendChild(faceBox(st.orient+'_front','Front'));
    if(st.view==='both'||st.view==='back')  fs.appendChild(faceBox(st.orient+'_back','Back'));
    stage.appendChild(fs); if(window.IdCard) IdCard.renderQR(stage); }

  var t; function reload(){ clearTimeout(t); t=setTimeout(function(){
    fetch(facesUrl+'?'+overrides(), {credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
      if(j&&j.ok){ st.faces=j.faces; setHash(j.csrf_hash); render(); } }); }, 180); }

  // wire inputs → live preview
  ['f_terms','f_show_logo','f_enable_qr','f_id_prefix','f_seq_length','f_id_pattern','f_validity_years','f_default_orientation','f_allow_choice','f_use_theme']
    .forEach(function(id){ var el=document.getElementById(id); if(el) el.addEventListener('input', function(){ example(); if(id==='f_use_theme') toggleColorBox(); reload(); }); });
  document.querySelectorAll('.fcolor,.ffield').forEach(function(el){ el.addEventListener('input', reload); });
  var pe=document.getElementById('p_emp'); if(pe) pe.addEventListener('change', function(){ st.userId=parseInt(this.value,10); reload(); });
  function seg(id,attr,val){ document.querySelectorAll('#'+id+' button').forEach(function(b){ b.classList.toggle('on', b.getAttribute(attr)===val); }); }
  document.getElementById('p_orient').addEventListener('click', function(e){ var b=e.target.closest('button'); if(!b)return; st.orient=b.getAttribute('data-o'); seg('p_orient','data-o',st.orient); render(); });
  document.getElementById('p_view').addEventListener('click', function(e){ var b=e.target.closest('button'); if(!b)return; st.view=b.getAttribute('data-v'); seg('p_view','data-v',st.view); render(); });

  document.getElementById('bld-save').addEventListener('click', function(){
    var b=new FormData();
    b.append('default_orientation', document.getElementById('f_default_orientation').value);
    b.append('allow_orientation_choice', document.getElementById('f_allow_choice').checked?1:0);
    b.append('show_logo', document.getElementById('f_show_logo').checked?1:0);
    b.append('enable_qr', document.getElementById('f_enable_qr').checked?1:0);
    b.append('id_prefix', document.getElementById('f_id_prefix').value);
    b.append('id_pattern', document.getElementById('f_id_pattern').value);
    b.append('seq_length', document.getElementById('f_seq_length').value);
    b.append('validity_years', document.getElementById('f_validity_years').value);
    b.append('terms', document.getElementById('f_terms').value);
    FIELDS.forEach(function(k){ var el=document.querySelector('.ffield[data-k="'+k+'"]'); b.append('field_'+k, el&&el.checked?1:0); });
    if(!useTheme()){ COLORS.forEach(function(c){ b.append(c, document.getElementById('f_'+c).value); }); }
    b.append(csrf().field, csrf().hash);
    fetch(saveUrl,{method:'POST',credentials:'same-origin',body:b}).then(function(r){return r.json();}).then(function(j){
      setHash(j.csrf_hash); if(j.ok){ if(window.toastr) toastr.success('Template saved'); } else if(window.toastr) toastr.error(j.error||'Save failed'); });
  });

  example(); toggleColorBox(); reload();
})();
</script>
