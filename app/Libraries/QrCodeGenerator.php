<?php

namespace App\Libraries;

class QrCodeGenerator
{
    /**
     * Return a Google Charts QR code image URL (no server-side dependency).
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
