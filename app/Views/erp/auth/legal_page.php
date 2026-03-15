<?php
use App\Models\SystemModel;
$SystemModel = new SystemModel();
$xin_system = $SystemModel->where('setting_id', 1)->first();
$favicon = base_url().'/public/uploads/logo/favicon/'.$xin_system['favicon'];
?>
<?= doctype();?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?= esc($title ?? 'Legal'); ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<link rel="icon" type="image/png" href="<?= $favicon; ?>">
<link rel="stylesheet" href="<?= base_url('public/assets/fonts/feather.css'); ?>">
<link rel="stylesheet" href="<?= base_url('public/assets/css/style.css'); ?>">
<style>
  body { background: #f4f7fa; }
  .legal-container { max-width: 800px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
  .legal-container h1 { margin-bottom: 20px; font-size: 28px; }
  .legal-container .content { line-height: 1.8; }
</style>
</head>
<body>
  <div class="legal-container">
    <a href="<?= site_url('/'); ?>" class="mb-3 d-inline-block">&larr; Back to Home</a>
    <h1><?= esc($heading ?? 'Legal'); ?></h1>
    <div class="content">
      <?= $content ?? '<p>Content not yet available.</p>'; ?>
    </div>
  </div>
</body>
</html>
