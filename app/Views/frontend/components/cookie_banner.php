<!-- Cookie consent banner — shown on public pages only -->
<div id="cookie-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;background:#1a1a2e;color:#f0f0f0;padding:16px 24px;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap;box-shadow:0 -2px 12px rgba(0,0,0,.25);font-size:14px;">
    <p style="margin:0;flex:1 1 auto;">
        Rooibok HR System uses essential cookies to ensure the platform functions correctly.
        We also use analytics cookies to understand how you interact with the system and improve your experience.
        By clicking "Accept" you consent to our use of cookies.
    </p>
    <div style="display:flex;gap:10px;align-items:center;flex-shrink:0;">
        <a href="<?= site_url('cookies'); ?>" style="color:#1D9E75;text-decoration:underline;white-space:nowrap;">Learn more</a>
        <button onclick="acceptCookies()" style="background:#1D9E75;color:#fff;border:none;padding:8px 22px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:600;white-space:nowrap;">Accept</button>
    </div>
</div>
<script>
function acceptCookies() {
    document.cookie = 'consent_accepted=1;max-age=31536000;path=/;SameSite=Lax;Secure';
    document.getElementById('cookie-banner').style.display = 'none';
}
if (!document.cookie.includes('consent_accepted=1')) {
    document.getElementById('cookie-banner').style.display = 'flex';
}
</script>
