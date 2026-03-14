<?php echo view('frontend/components/htmlhead'); ?>
<body>
    <?php echo view('frontend/components/top_link'); ?>
    <main>
        <section class="pt-120 pb-120">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-6 col-lg-8 col-md-10">
                        <div class="text-center" style="padding:60px 30px;background:#fff;border-radius:12px;box-shadow:0 2px 20px rgba(0,0,0,.08);">
                            <div style="width:72px;height:72px;border-radius:50%;background:#e8f8f0;display:inline-flex;align-items:center;justify-content:center;margin-bottom:24px;">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#1D9E75" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                            <h2 style="color:#333;margin-bottom:16px;font-size:24px;">Unsubscribed Successfully</h2>
                            <p style="color:#666;font-size:16px;line-height:1.6;max-width:440px;margin:0 auto 24px;">
                                You have been successfully unsubscribed from Rooibok HR marketing communications.
                                You will no longer receive promotional emails from us.
                            </p>
                            <p style="color:#999;font-size:13px;">
                                If this was a mistake, please contact your system administrator to re-subscribe.
                            </p>
                            <a href="<?= site_url('/'); ?>" class="w-btn w-btn-blue w-btn-6" style="margin-top:20px;display:inline-block;">Return to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php echo view('frontend/components/footer'); ?>
    <?php echo view('frontend/components/cookie_banner'); ?>
    <?php echo view('frontend/components/htmlfooter'); ?>
</body>
</html>
