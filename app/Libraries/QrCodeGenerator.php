<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Libraries;

class QrCodeGenerator
{
    /**
     * DEPRECATED: returns a Google Charts QR image URL, but that API was shut down
     * by Google in 2024 so the image will not load. Prefer rendering QR codes
     * client-side from the raw data (see uencode() + the bundled qrcode.min.js in
     * setup_2fa / employee_qr_card / visitor_kiosk). Kept only for compatibility.
     */
    public function getQrUrl(string $data, int $size = 200): string
    {
        return 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size
             . '&cht=qr&chl=' . urlencode($data) . '&choe=UTF-8';
    }

    /**
     * Generate a QR code URL that encodes an employee ID (encrypted).
     */
    public function getEmployeeQrUrl(int $employeeId, int $size = 200): string
    {
        $encoded = uencode($employeeId);
        return $this->getQrUrl($encoded, $size);
    }
}
