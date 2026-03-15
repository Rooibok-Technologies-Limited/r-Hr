<?php echo view('frontend/components/htmlhead'); ?>
<body style="background: #f5f5f5;">
<div style="max-width: 960px; margin: 40px auto; padding: 0 20px;">
  <div style="background: #fff; border-radius: 8px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h1 style="color: #333; margin-bottom: 10px;">Rooibok HR — REST API v1</h1>
    <p style="color: #666; margin-bottom: 30px;">Base URL: <code style="background:#f0f0f0;padding:3px 8px;border-radius:4px;"><?= base_url('api/v1') ?></code></p>

    <h2 style="color: #444; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px;">Authentication</h2>
    <table style="width:100%; border-collapse:collapse; margin-bottom: 30px;">
      <tr style="background:#f8f9fa;"><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Method</th><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Endpoint</th><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Description</th></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#49cc90;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">POST</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/auth/token</code></td><td style="padding:10px;border:1px solid #dee2e6;">Get JWT token (username + password)</td></tr>
    </table>

    <h2 style="color: #444; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px;">Attendance</h2>
    <table style="width:100%; border-collapse:collapse; margin-bottom: 30px;">
      <tr style="background:#f8f9fa;"><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Method</th><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Endpoint</th><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Description</th></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#49cc90;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">POST</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/attendance/clock-in</code></td><td style="padding:10px;border:1px solid #dee2e6;">Clock in (requires JWT + GPS)</td></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#49cc90;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">POST</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/attendance/clock-out</code></td><td style="padding:10px;border:1px solid #dee2e6;">Clock out (requires JWT + GPS)</td></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#61affe;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">GET</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/attendance/status</code></td><td style="padding:10px;border:1px solid #dee2e6;">Current attendance status</td></tr>
    </table>

    <h2 style="color: #444; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px;">Employees</h2>
    <table style="width:100%; border-collapse:collapse; margin-bottom: 30px;">
      <tr style="background:#f8f9fa;"><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Method</th><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Endpoint</th><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Description</th></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#61affe;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">GET</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/employee/{id}</code></td><td style="padding:10px;border:1px solid #dee2e6;">Get employee details</td></tr>
    </table>

    <h2 style="color: #444; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px;">Webhooks</h2>
    <table style="width:100%; border-collapse:collapse; margin-bottom: 30px;">
      <tr style="background:#f8f9fa;"><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Method</th><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Endpoint</th><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Description</th></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#49cc90;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">POST</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/webhooks/stripe</code></td><td style="padding:10px;border:1px solid #dee2e6;">Stripe payment webhook</td></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#49cc90;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">POST</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/webhooks/mtn</code></td><td style="padding:10px;border:1px solid #dee2e6;">MTN Mobile Money webhook</td></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#49cc90;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">POST</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/webhooks/airtel</code></td><td style="padding:10px;border:1px solid #dee2e6;">Airtel Money webhook</td></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#49cc90;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">POST</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/webhooks/zkteco</code></td><td style="padding:10px;border:1px solid #dee2e6;">ZKTeco biometric device webhook</td></tr>
    </table>

    <h2 style="color: #444; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px;">Utilities</h2>
    <table style="width:100%; border-collapse:collapse; margin-bottom: 30px;">
      <tr style="background:#f8f9fa;"><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Method</th><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Endpoint</th><th style="text-align:left;padding:10px;border:1px solid #dee2e6;">Description</th></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#61affe;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">GET</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/health</code></td><td style="padding:10px;border:1px solid #dee2e6;">API health check</td></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#61affe;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">GET</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/subscription/status</code></td><td style="padding:10px;border:1px solid #dee2e6;">Company subscription status</td></tr>
      <tr><td style="padding:10px;border:1px solid #dee2e6;"><span style="background:#49cc90;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">POST</span></td><td style="padding:10px;border:1px solid #dee2e6;"><code>/visitors/check-in</code></td><td style="padding:10px;border:1px solid #dee2e6;">Visitor self-check-in</td></tr>
    </table>

    <div style="background:#f8f9fa; padding: 20px; border-radius: 6px; margin-top: 20px;">
      <h3 style="margin-top:0;">Authentication Header</h3>
      <code style="display:block; background:#272822; color:#f8f8f2; padding:15px; border-radius:4px;">Authorization: Bearer &lt;your-jwt-token&gt;</code>
      <p style="color:#666; margin-top:10px;">All endpoints except <code>/auth/token</code>, <code>/health</code>, and webhooks require a valid JWT token.</p>
    </div>
  </div>
</div>
</body>
</html>
