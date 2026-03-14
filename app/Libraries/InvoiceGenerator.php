<?php

namespace App\Libraries;

use App\Models\SystemModel;
use App\Models\UsersModel;
use App\Models\MembershipModel;
use App\Models\CompanymembershipModel;

/**
 * Subscription Invoice PDF Generator
 *
 * Generates PDF invoices for subscription payments using DOMPDF.
 * Phase 4.5 - UPGRADE.md
 *
 * IMPORTANT: Requires dompdf/dompdf package.
 * Run: composer require dompdf/dompdf
 */
class InvoiceGenerator
{
    /**
     * Generate a subscription invoice PDF
     *
     * @param int    $companyId     Company user_id from ci_erp_users
     * @param int    $membershipId  Membership plan ID from ci_membership
     * @param string $txRef         Transaction reference number
     * @param string $paymentMethod Payment method used (Stripe / MTN MoMo / Airtel Money)
     * @return string The saved PDF file path (relative to public/)
     */
    public function generate(int $companyId, int $membershipId, string $txRef, string $paymentMethod): string
    {
        $db = \Config\Database::connect();

        // 1. Get company info
        $UsersModel = new UsersModel();
        $company = $UsersModel->where('user_id', $companyId)->first();
        if (!$company) {
            throw new \RuntimeException("Company not found: {$companyId}");
        }

        // 2. Get membership/plan info
        $MembershipModel = new MembershipModel();
        $membership = $MembershipModel->where('membership_id', $membershipId)->first();
        if (!$membership) {
            throw new \RuntimeException("Membership plan not found: {$membershipId}");
        }

        // 3. Get system settings for Rooibok company details
        $SystemModel = new SystemModel();
        $settings = $SystemModel->where('setting_id', 1)->first();

        // 4. Get company membership for expiry date
        $CompanymembershipModel = new CompanymembershipModel();
        $companyMembership = $CompanymembershipModel
            ->where('company_id', $companyId)
            ->where('membership_id', $membershipId)
            ->orderBy('company_membership_id', 'DESC')
            ->first();

        $expiryDate = $companyMembership['expiry_date'] ?? date('Y-m-d', strtotime('+1 month'));

        // 5. Determine plan duration label
        if ($membership['plan_duration'] == 1) {
            $durationLabel = 'Monthly';
        } elseif ($membership['plan_duration'] == 2) {
            $durationLabel = 'Annual';
        } else {
            $durationLabel = 'Unlimited';
        }

        // 6. Generate invoice number
        $invoiceNumber = $this->generateInvoiceNumber();

        // 7. Prepare template data
        $invoiceData = [
            'invoice_number'  => $invoiceNumber,
            'invoice_date'    => date('d M Y'),
            'company_name'    => $settings['company_name'] ?? 'Rooibok HR System',
            'company_address' => $settings['address_1'] ?? '',
            'company_city'    => $settings['city'] ?? '',
            'company_email'   => $settings['email'] ?? '',
            'client_company'  => $company['company_name'] ?? ($company['first_name'] . ' ' . $company['last_name']),
            'client_email'    => $company['email'] ?? '',
            'plan_name'       => $membership['membership_type'],
            'duration'        => $durationLabel,
            'amount'          => number_format((float)$membership['price'], 0, '.', ','),
            'currency'        => $settings['default_currency'] ?? 'UGX',
            'payment_method'  => $paymentMethod,
            'tx_ref'          => $txRef,
            'expiry_date'     => date('d M Y', strtotime($expiryDate)),
            'logo_path'       => FCPATH . 'uploads/logo/rooibok-logo-main.png',
        ];

        // 8. Render the template view
        $html = view('erp/invoices/subscription_invoice', $invoiceData);

        // 9. Use DOMPDF to convert HTML to PDF
        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => true,
            'defaultFont'     => 'sans-serif',
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        // 10. Save PDF to public/uploads/invoices/{companyId}/
        $invoiceDir = FCPATH . 'uploads/invoices/' . $companyId;
        if (!is_dir($invoiceDir)) {
            mkdir($invoiceDir, 0755, true);
        }

        $fileName = $invoiceNumber . '.pdf';
        $pdfPath  = $invoiceDir . '/' . $fileName;
        file_put_contents($pdfPath, $pdfContent);

        // 11. Insert record into ci_subscription_invoices table
        $db->table('ci_subscription_invoices')->insert([
            'invoice_number'  => $invoiceNumber,
            'company_id'      => $companyId,
            'membership_id'   => $membershipId,
            'plan_name'       => $membership['membership_type'],
            'duration'        => $durationLabel,
            'amount'          => $membership['price'],
            'currency'        => $settings['default_currency'] ?? 'UGX',
            'payment_method'  => $paymentMethod,
            'tx_ref'          => $txRef,
            'expiry_date'     => $expiryDate,
            'pdf_path'        => 'uploads/invoices/' . $companyId . '/' . $fileName,
            'status'          => 'paid',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        // 12. Return the PDF file path (relative)
        return 'uploads/invoices/' . $companyId . '/' . $fileName;
    }

    /**
     * Generate next invoice number for the current year
     *
     * Format: RBHR-{YYYY}-{00001}
     */
    private function generateInvoiceNumber(): string
    {
        $db   = \Config\Database::connect();
        $year = date('Y');

        $last = $db->table('ci_subscription_invoices')
            ->like('invoice_number', "RBHR-{$year}-")
            ->orderBy('invoice_id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($last) {
            $lastNum = (int) substr($last['invoice_number'], -5);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return "RBHR-{$year}-" . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }
}
