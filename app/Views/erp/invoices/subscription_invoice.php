<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= esc($invoice_number) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #333;
            line-height: 1.5;
            background: #fff;
        }
        .invoice-container {
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
            padding: 40px;
        }

        /* Header */
        .invoice-header {
            width: 100%;
            margin-bottom: 30px;
            overflow: hidden;
        }
        .invoice-header-left {
            float: left;
            width: 50%;
        }
        .invoice-header-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        .invoice-logo {
            max-height: 60px;
            max-width: 200px;
            margin-bottom: 10px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .invoice-meta {
            font-size: 13px;
            color: #666;
        }
        .invoice-meta strong {
            color: #333;
        }

        /* Company & Client Info */
        .invoice-parties {
            width: 100%;
            margin-bottom: 30px;
            overflow: hidden;
        }
        .invoice-from {
            float: left;
            width: 50%;
        }
        .invoice-to {
            float: right;
            width: 50%;
        }
        .section-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .party-name {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        .party-detail {
            font-size: 12px;
            color: #666;
        }

        /* Line Items Table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .invoice-table thead th {
            background-color: #2c3e50;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .invoice-table thead th:last-child {
            text-align: right;
        }
        .invoice-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        .invoice-table tbody td:last-child {
            text-align: right;
            font-weight: 600;
        }

        /* Totals */
        .invoice-totals {
            width: 100%;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .totals-table {
            float: right;
            width: 300px;
        }
        .totals-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 12px;
            font-size: 13px;
        }
        .totals-table .total-row td {
            border-top: 2px solid #2c3e50;
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            padding-top: 12px;
        }
        .totals-table td:last-child {
            text-align: right;
        }

        /* Payment Info */
        .payment-info {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 18px 20px;
            margin-bottom: 30px;
        }
        .payment-info-title {
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .payment-info-row {
            font-size: 12px;
            color: #555;
            margin-bottom: 4px;
        }
        .payment-info-row strong {
            display: inline-block;
            width: 150px;
            color: #333;
        }

        /* Footer */
        .invoice-footer {
            text-align: center;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }
        .footer-thanks {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .footer-note {
            font-size: 11px;
            color: #999;
        }

        /* Status Badge */
        .status-paid {
            display: inline-block;
            background-color: #27ae60;
            color: #fff;
            padding: 3px 12px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">

        <!-- Header -->
        <div class="invoice-header">
            <div class="invoice-header-left">
                <?php if (file_exists($logo_path)): ?>
                    <img src="<?= $logo_path ?>" alt="Logo" class="invoice-logo">
                <?php else: ?>
                    <div style="font-size:22px; font-weight:700; color:#2c3e50; margin-bottom:10px;">
                        <?= esc($company_name) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="invoice-header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">
                    <strong><?= esc($invoice_number) ?></strong><br>
                    Date: <?= esc($invoice_date) ?><br>
                    <span class="status-paid">PAID</span>
                </div>
            </div>
        </div>

        <!-- From / To -->
        <div class="invoice-parties">
            <div class="invoice-from">
                <div class="section-label">From</div>
                <div class="party-name"><?= esc($company_name) ?></div>
                <?php if (!empty($company_address)): ?>
                    <div class="party-detail"><?= esc($company_address) ?></div>
                <?php endif; ?>
                <?php if (!empty($company_city)): ?>
                    <div class="party-detail"><?= esc($company_city) ?></div>
                <?php endif; ?>
                <?php if (!empty($company_email)): ?>
                    <div class="party-detail"><?= esc($company_email) ?></div>
                <?php endif; ?>
            </div>
            <div class="invoice-to">
                <div class="section-label">Bill To</div>
                <div class="party-name"><?= esc($client_company) ?></div>
                <?php if (!empty($client_email)): ?>
                    <div class="party-detail"><?= esc($client_email) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Line Items -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Duration</th>
                    <th>Amount (<?= esc($currency) ?>)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= esc($plan_name) ?> Subscription Plan</td>
                    <td><?= esc($duration) ?></td>
                    <td><?= esc($amount) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="invoice-totals">
            <div class="totals-table">
                <table>
                    <tr>
                        <td>Subtotal</td>
                        <td><?= esc($currency) ?> <?= esc($amount) ?></td>
                    </tr>
                    <tr class="total-row">
                        <td>Total</td>
                        <td><?= esc($currency) ?> <?= esc($amount) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="payment-info">
            <div class="payment-info-title">Payment Details</div>
            <div class="payment-info-row"><strong>Payment Method:</strong> <?= esc($payment_method) ?></div>
            <div class="payment-info-row"><strong>Transaction Ref:</strong> <?= esc($tx_ref) ?></div>
            <div class="payment-info-row"><strong>New Expiry Date:</strong> <?= esc($expiry_date) ?></div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <div class="footer-thanks">Thank you for your subscription!</div>
            <div class="footer-note">This is a computer-generated invoice. No signature is required.</div>
            <div class="footer-note" style="margin-top:4px;"><?= esc($company_name) ?> &mdash; <?= esc($company_email) ?></div>
        </div>

    </div>
</body>
</html>
