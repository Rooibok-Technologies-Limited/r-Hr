<?php
/**
 * Landing Page CMS Editor
 * Tabs: Hero, Features, Stats, Testimonials, FAQ, Contact, Footer, SEO
 */
use App\Models\UsersModel;
use App\Models\SystemModel;
use App\Models\LandingContentModel;

$UsersModel          = new UsersModel();
$SystemModel         = new SystemModel();
$LandingContentModel = new LandingContentModel();

$session  = \Config\Services::session();
$usession = $session->get('sup_username');
$user     = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
$xin_system = $SystemModel->where('setting_id', 1)->first();

// Helpers to pull values from section rows
function _lv($rows, $key, $default = '') {
    if (is_array($rows)) {
        foreach ($rows as $r) {
            if (($r['content_key'] ?? '') === $key) {
                return $r['content_value'] ?? $default;
            }
        }
    }
    return $default;
}

$heroRows    = $hero    ?? [];
$contactRows = $contact ?? [];
$footerRows  = $footer  ?? [];
$seoRows     = $seo     ?? [];

$featuresData     = $features     ?? [];
$statsData        = $stats        ?? [];
$testimonialsData = $testimonials ?? [];
$faqData          = $faq          ?? [];
?>

<div class="card">
  <div class="card-body">
    <h4 class="card-title mb-4">Landing Page CMS</h4>

    <!-- Nav Tabs -->
    <ul class="nav nav-tabs" id="landingCmsTabs" role="tablist">
      <li class="nav-item"><a class="nav-link active" id="tab-hero" data-toggle="tab" href="#pane-hero" role="tab">Hero</a></li>
      <li class="nav-item"><a class="nav-link" id="tab-features" data-toggle="tab" href="#pane-features" role="tab">Features</a></li>
      <li class="nav-item"><a class="nav-link" id="tab-stats" data-toggle="tab" href="#pane-stats" role="tab">Stats</a></li>
      <li class="nav-item"><a class="nav-link" id="tab-testimonials" data-toggle="tab" href="#pane-testimonials" role="tab">Testimonials</a></li>
      <li class="nav-item"><a class="nav-link" id="tab-faq" data-toggle="tab" href="#pane-faq" role="tab">FAQ</a></li>
      <li class="nav-item"><a class="nav-link" id="tab-contact" data-toggle="tab" href="#pane-contact" role="tab">Contact</a></li>
      <li class="nav-item"><a class="nav-link" id="tab-footer" data-toggle="tab" href="#pane-footer" role="tab">Footer</a></li>
      <li class="nav-item"><a class="nav-link" id="tab-seo" data-toggle="tab" href="#pane-seo" role="tab">SEO</a></li>
    </ul>

    <div class="tab-content mt-3" id="landingCmsTabContent">

      <!-- ============================================================== -->
      <!-- HERO -->
      <!-- ============================================================== -->
      <div class="tab-pane fade show active" id="pane-hero" role="tabpanel">
        <form id="form-hero" class="cms-section-form" data-section="hero">
          <div class="form-group">
            <label>Headline</label>
            <input type="text" name="headline" class="form-control" value="<?= esc(_lv($heroRows, 'headline')) ?>" />
          </div>
          <div class="form-group">
            <label>Subtitle</label>
            <textarea name="subtitle" class="form-control" rows="3"><?= esc(_lv($heroRows, 'subtitle')) ?></textarea>
          </div>
          <div class="form-group">
            <label>CTA Button Text</label>
            <input type="text" name="cta_text" class="form-control" value="<?= esc(_lv($heroRows, 'cta_text', 'Get Started')) ?>" />
          </div>
          <div class="form-group">
            <label>Hero Image</label>
            <input type="file" id="hero-image-upload" class="form-control-file" accept="image/*" />
            <?php $heroImg = _lv($heroRows, 'hero_image'); ?>
            <?php if ($heroImg): ?>
              <img src="<?= esc($heroImg) ?>" class="img-thumbnail mt-2" style="max-height:150px;" />
            <?php endif; ?>
          </div>
          <button type="submit" class="btn btn-primary">Save Hero</button>
        </form>
      </div>

      <!-- ============================================================== -->
      <!-- FEATURES (up to 8 cards) -->
      <!-- ============================================================== -->
      <div class="tab-pane fade" id="pane-features" role="tabpanel">
        <form id="form-features" class="cms-json-form" data-section="features" data-key="cards">
          <p class="text-muted">Up to 8 feature cards. Leave empty rows to remove.</p>
          <?php for ($i = 0; $i < 8; $i++): ?>
            <div class="card mb-3">
              <div class="card-body">
                <h6>Feature <?= $i + 1 ?></h6>
                <div class="form-group">
                  <label>Title</label>
                  <input type="text" name="features[<?= $i ?>][title]" class="form-control" value="<?= esc($featuresData[$i]['title'] ?? '') ?>" />
                </div>
                <div class="form-group">
                  <label>Description</label>
                  <textarea name="features[<?= $i ?>][description]" class="form-control" rows="2"><?= esc($featuresData[$i]['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                  <label>Icon (CSS class, e.g. <code>feather icon-users</code>)</label>
                  <input type="text" name="features[<?= $i ?>][icon]" class="form-control" value="<?= esc($featuresData[$i]['icon'] ?? '') ?>" />
                </div>
              </div>
            </div>
          <?php endfor; ?>
          <button type="submit" class="btn btn-primary">Save Features</button>
        </form>
      </div>

      <!-- ============================================================== -->
      <!-- STATS (3-4 figures) -->
      <!-- ============================================================== -->
      <div class="tab-pane fade" id="pane-stats" role="tabpanel">
        <form id="form-stats" class="cms-json-form" data-section="stats" data-key="figures">
          <p class="text-muted">Up to 4 stat figures.</p>
          <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="row mb-3">
              <div class="col-md-4">
                <label>Label</label>
                <input type="text" name="stats[<?= $i ?>][label]" class="form-control" value="<?= esc($statsData[$i]['label'] ?? '') ?>" />
              </div>
              <div class="col-md-4">
                <label>Value</label>
                <input type="text" name="stats[<?= $i ?>][value]" class="form-control" value="<?= esc($statsData[$i]['value'] ?? '') ?>" />
              </div>
              <div class="col-md-4">
                <label>Icon</label>
                <input type="text" name="stats[<?= $i ?>][icon]" class="form-control" value="<?= esc($statsData[$i]['icon'] ?? '') ?>" />
              </div>
            </div>
          <?php endfor; ?>
          <button type="submit" class="btn btn-primary">Save Stats</button>
        </form>
      </div>

      <!-- ============================================================== -->
      <!-- TESTIMONIALS (up to 6) -->
      <!-- ============================================================== -->
      <div class="tab-pane fade" id="pane-testimonials" role="tabpanel">
        <form id="form-testimonials" class="cms-json-form" data-section="testimonials" data-key="items">
          <p class="text-muted">Up to 6 testimonials.</p>
          <?php for ($i = 0; $i < 6; $i++): ?>
            <div class="card mb-3">
              <div class="card-body">
                <h6>Testimonial <?= $i + 1 ?></h6>
                <div class="form-group">
                  <label>Name</label>
                  <input type="text" name="testimonials[<?= $i ?>][name]" class="form-control" value="<?= esc($testimonialsData[$i]['name'] ?? '') ?>" />
                </div>
                <div class="form-group">
                  <label>Company</label>
                  <input type="text" name="testimonials[<?= $i ?>][company]" class="form-control" value="<?= esc($testimonialsData[$i]['company'] ?? '') ?>" />
                </div>
                <div class="form-group">
                  <label>Quote</label>
                  <textarea name="testimonials[<?= $i ?>][quote]" class="form-control" rows="2"><?= esc($testimonialsData[$i]['quote'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                  <label>Photo</label>
                  <input type="file" class="form-control-file testimonial-photo-upload" data-index="<?= $i ?>" accept="image/*" />
                  <?php if (! empty($testimonialsData[$i]['photo'])): ?>
                    <img src="<?= esc($testimonialsData[$i]['photo']) ?>" class="img-thumbnail mt-2" style="max-height:80px;" />
                  <?php endif; ?>
                  <input type="hidden" name="testimonials[<?= $i ?>][photo]" value="<?= esc($testimonialsData[$i]['photo'] ?? '') ?>" />
                </div>
              </div>
            </div>
          <?php endfor; ?>
          <button type="submit" class="btn btn-primary">Save Testimonials</button>
        </form>
      </div>

      <!-- ============================================================== -->
      <!-- FAQ (up to 10) -->
      <!-- ============================================================== -->
      <div class="tab-pane fade" id="pane-faq" role="tabpanel">
        <form id="form-faq" class="cms-json-form" data-section="faq" data-key="items">
          <p class="text-muted">Up to 10 FAQ items.</p>
          <?php for ($i = 0; $i < 10; $i++): ?>
            <div class="row mb-3">
              <div class="col-md-5">
                <label>Question</label>
                <input type="text" name="faq[<?= $i ?>][question]" class="form-control" value="<?= esc($faqData[$i]['question'] ?? '') ?>" />
              </div>
              <div class="col-md-7">
                <label>Answer</label>
                <textarea name="faq[<?= $i ?>][answer]" class="form-control" rows="2"><?= esc($faqData[$i]['answer'] ?? '') ?></textarea>
              </div>
            </div>
          <?php endfor; ?>
          <button type="submit" class="btn btn-primary">Save FAQ</button>
        </form>
      </div>

      <!-- ============================================================== -->
      <!-- CONTACT -->
      <!-- ============================================================== -->
      <div class="tab-pane fade" id="pane-contact" role="tabpanel">
        <form id="form-contact" class="cms-section-form" data-section="contact">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= esc(_lv($contactRows, 'email')) ?>" />
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= esc(_lv($contactRows, 'phone')) ?>" />
          </div>
          <div class="form-group">
            <label>WhatsApp</label>
            <input type="text" name="whatsapp" class="form-control" value="<?= esc(_lv($contactRows, 'whatsapp')) ?>" />
          </div>
          <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="3"><?= esc(_lv($contactRows, 'address')) ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Save Contact</button>
        </form>
      </div>

      <!-- ============================================================== -->
      <!-- FOOTER -->
      <!-- ============================================================== -->
      <div class="tab-pane fade" id="pane-footer" role="tabpanel">
        <form id="form-footer" class="cms-section-form" data-section="footer">
          <div class="form-group">
            <label>Copyright Text</label>
            <input type="text" name="copyright" class="form-control" value="<?= esc(_lv($footerRows, 'copyright')) ?>" />
          </div>
          <div class="form-group">
            <label>Facebook URL</label>
            <input type="url" name="facebook" class="form-control" value="<?= esc(_lv($footerRows, 'facebook')) ?>" />
          </div>
          <div class="form-group">
            <label>Twitter / X URL</label>
            <input type="url" name="twitter" class="form-control" value="<?= esc(_lv($footerRows, 'twitter')) ?>" />
          </div>
          <div class="form-group">
            <label>LinkedIn URL</label>
            <input type="url" name="linkedin" class="form-control" value="<?= esc(_lv($footerRows, 'linkedin')) ?>" />
          </div>
          <div class="form-group">
            <label>Instagram URL</label>
            <input type="url" name="instagram" class="form-control" value="<?= esc(_lv($footerRows, 'instagram')) ?>" />
          </div>
          <button type="submit" class="btn btn-primary">Save Footer</button>
        </form>
      </div>

      <!-- ============================================================== -->
      <!-- SEO -->
      <!-- ============================================================== -->
      <div class="tab-pane fade" id="pane-seo" role="tabpanel">
        <form id="form-seo" class="cms-section-form" data-section="seo">
          <div class="form-group">
            <label>Page Title</label>
            <input type="text" name="page_title" class="form-control" value="<?= esc(_lv($seoRows, 'page_title')) ?>" />
          </div>
          <div class="form-group">
            <label>Meta Description</label>
            <textarea name="meta_description" class="form-control" rows="3"><?= esc(_lv($seoRows, 'meta_description')) ?></textarea>
          </div>
          <div class="form-group">
            <label>OG Image</label>
            <input type="file" id="og-image-upload" class="form-control-file" accept="image/*" />
            <?php $ogImg = _lv($seoRows, 'og_image'); ?>
            <?php if ($ogImg): ?>
              <img src="<?= esc($ogImg) ?>" class="img-thumbnail mt-2" style="max-height:100px;" />
            <?php endif; ?>
          </div>
          <button type="submit" class="btn btn-primary">Save SEO</button>
        </form>
      </div>

    </div><!-- /.tab-content -->
  </div><!-- /.card-body -->
</div><!-- /.card -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    var csrfName  = '<?= csrf_token() ?>';
    var csrfHash  = '<?= csrf_hash() ?>';
    var saveUrl   = '<?= site_url("erp/landing-page/save/") ?>';
    var uploadUrl = '<?= site_url("erp/landing-page/upload/") ?>';

    // ---- Save scalar section forms (hero, contact, footer, seo) ----
    document.querySelectorAll('.cms-section-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var section = form.dataset.section;
            var inputs  = form.querySelectorAll('input[type="text"], input[type="email"], input[type="url"], textarea');

            var promises = [];
            inputs.forEach(function (inp) {
                var fd = new FormData();
                fd.append(csrfName, csrfHash);
                fd.append('section', section);
                fd.append('content_key', inp.name);
                fd.append('content_value', inp.value);
                promises.push(fetch(saveUrl, { method: 'POST', body: fd, credentials: 'same-origin' }));
            });

            Promise.all(promises).then(function () {
                alert(section.charAt(0).toUpperCase() + section.slice(1) + ' saved!');
                location.reload();
            });
        });
    });

    // ---- Save JSON section forms (features, stats, testimonials, faq) ----
    document.querySelectorAll('.cms-json-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var section = form.dataset.section;
            var key     = form.dataset.key;

            // Collect form data into array, filtering empty rows
            var formData = new FormData(form);
            var items    = {};
            for (var pair of formData.entries()) {
                var match = pair[0].match(/^(\w+)\[(\d+)\]\[(\w+)\]$/);
                if (match) {
                    var idx   = match[2];
                    var field = match[3];
                    if (! items[idx]) items[idx] = {};
                    items[idx][field] = pair[1];
                }
            }

            // Filter out empty items
            var filtered = Object.values(items).filter(function (item) {
                return Object.values(item).some(function (v) { return v.trim() !== ''; });
            });

            var fd = new FormData();
            fd.append(csrfName, csrfHash);
            fd.append('section', section);
            fd.append('content_key', key);
            fd.append('content_json', JSON.stringify(filtered));

            fetch(saveUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function () {
                    alert(section.charAt(0).toUpperCase() + section.slice(1) + ' saved!');
                    location.reload();
                });
        });
    });

    // ---- Image uploads ----
    function uploadImage(fileInput, section, contentKey) {
        if (! fileInput.files.length) return;
        var fd = new FormData();
        fd.append(csrfName, csrfHash);
        fd.append('image', fileInput.files[0]);
        fd.append('section', section);
        fd.append('content_key', contentKey);

        fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    alert('Image uploaded!');
                    location.reload();
                } else {
                    alert('Upload error: ' + data.message);
                }
            });
    }

    var heroUpload = document.getElementById('hero-image-upload');
    if (heroUpload) {
        heroUpload.addEventListener('change', function () {
            uploadImage(this, 'hero', 'hero_image');
        });
    }

    var ogUpload = document.getElementById('og-image-upload');
    if (ogUpload) {
        ogUpload.addEventListener('change', function () {
            uploadImage(this, 'seo', 'og_image');
        });
    }

    document.querySelectorAll('.testimonial-photo-upload').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var idx = this.dataset.index;
            var fd  = new FormData();
            fd.append(csrfName, csrfHash);
            fd.append('image', this.files[0]);

            fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.status === 'success') {
                        // Set the hidden input value for the testimonial photo
                        var hidden = document.querySelector('input[name="testimonials[' + idx + '][photo]"]');
                        if (hidden) hidden.value = data.url;
                        alert('Photo uploaded! Remember to save testimonials.');
                    } else {
                        alert('Upload error: ' + data.message);
                    }
                });
        });
    });
});
</script>
