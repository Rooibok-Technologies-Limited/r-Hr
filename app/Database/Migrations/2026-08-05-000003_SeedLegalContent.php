<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Seeds default legal content (Privacy / Terms / Cookies) into ci_landing_content
 * so the public /privacy /terms /cookies pages render real, professional copy
 * instead of an empty stub. Idempotent (skips keys that already exist) — operators
 * edit these via the landing-content admin, and should have counsel review before
 * launch. Written in white-label-neutral language ("the Platform", "we").
 */
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedLegalContent extends Migration
{
    public function up()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $privacy = <<<'HTML'
<p><em>Last updated: August 2026</em></p>
<p>This Privacy Policy explains how the Platform ("we", "us") collects, uses, and protects the personal information of employers and their employees who use our human-resources services.</p>
<h3>Information we collect</h3>
<ul>
  <li><strong>Account &amp; company data</strong> — organisation name, contact details, and billing information provided at registration.</li>
  <li><strong>Employee records</strong> — names, job details, contact information, attendance, leave, payroll and other HR data entered by your employer.</li>
  <li><strong>Usage data</strong> — log data, device and browser information, and actions taken within the service, used to secure and improve the Platform.</li>
</ul>
<h3>How we use your information</h3>
<p>We use personal data to provide and maintain the service, process payroll and payments, authenticate users, prevent fraud and abuse, comply with legal obligations, and communicate service-related notices.</p>
<h3>Data sharing</h3>
<p>We do not sell personal data. We share it only with service providers who process it on our behalf (e.g. payment and messaging providers) under contractual safeguards, or where required by law.</p>
<h3>Data retention &amp; security</h3>
<p>We retain personal data for as long as an account is active or as needed to meet legal and accounting obligations. We apply technical and organisational measures — including encryption in transit, access controls, and audit logging — to protect it.</p>
<h3>Your rights</h3>
<p>Depending on your jurisdiction, you may have the right to access, correct, export, or request deletion of your personal data. Employees should direct such requests to their employer (the data controller); employers may contact us for assistance.</p>
<h3>Contact</h3>
<p>For privacy questions, contact your organisation's administrator, who can escalate to us through their account.</p>
HTML;

        $terms = <<<'HTML'
<p><em>Last updated: August 2026</em></p>
<p>These Terms of Service ("Terms") govern your access to and use of the Platform. By creating an account or using the service, you agree to these Terms.</p>
<h3>Accounts</h3>
<p>You are responsible for the accuracy of the information you provide, for maintaining the confidentiality of your credentials, and for all activity under your account. You must promptly notify us of any unauthorised use.</p>
<h3>Acceptable use</h3>
<p>You agree not to misuse the service, including by attempting to breach security, access data without authorisation, disrupt the service, or use it for unlawful purposes.</p>
<h3>Subscriptions &amp; billing</h3>
<p>Paid plans are billed in advance for the applicable period. Fees are non-refundable except where required by law. We may suspend access to accounts with overdue balances after notice.</p>
<h3>Customer data</h3>
<p>You retain ownership of the data you submit. You grant us a limited licence to process it solely to provide the service. You are responsible for having a lawful basis to process the employee data you upload.</p>
<h3>Availability &amp; warranties</h3>
<p>We work to keep the service available and secure, but it is provided "as is" without warranties of uninterrupted or error-free operation. To the maximum extent permitted by law, our liability is limited to the fees paid for the service in the preceding twelve months.</p>
<h3>Termination</h3>
<p>You may cancel at any time. We may suspend or terminate access for breach of these Terms. On termination, you may export your data for a reasonable period before it is deleted.</p>
<h3>Changes</h3>
<p>We may update these Terms; material changes will be communicated through the service. Continued use after changes take effect constitutes acceptance.</p>
HTML;

        $cookies = <<<'HTML'
<p><em>Last updated: August 2026</em></p>
<p>This Cookie Policy explains how the Platform uses cookies and similar technologies.</p>
<h3>What are cookies</h3>
<p>Cookies are small text files stored on your device that help a website function and remember your preferences.</p>
<h3>How we use cookies</h3>
<ul>
  <li><strong>Strictly necessary</strong> — required for authentication, session security (including CSRF protection), and core functionality. These cannot be switched off.</li>
  <li><strong>Preferences</strong> — remember settings such as language and theme.</li>
  <li><strong>Analytics</strong> — where enabled, help us understand usage so we can improve the service. These are aggregated and non-identifying.</li>
</ul>
<h3>Managing cookies</h3>
<p>You can control or delete cookies through your browser settings. Disabling strictly-necessary cookies may prevent you from signing in or using key features.</p>
<h3>Contact</h3>
<p>For questions about this policy, contact your organisation's administrator.</p>
HTML;

        $rows = [
            ['legal', 'privacy', $privacy],
            ['legal', 'terms',   $terms],
            ['legal', 'cookies', $cookies],
        ];
        foreach ($rows as [$section, $key, $value]) {
            $exists = $db->table('ci_landing_content')
                ->where('section', $section)->where('content_key', $key)->countAllResults();
            if ($exists === 0) {
                $db->table('ci_landing_content')->insert([
                    'section'       => $section,
                    'content_key'   => $key,
                    'content_value' => $value,
                    'updated_at'    => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $this->db->table('ci_landing_content')->where('section', 'legal')
            ->whereIn('content_key', ['privacy', 'terms', 'cookies'])->delete();
    }
}
