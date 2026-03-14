<?php
/**
 * Rooibok HR System — Archive Export Controller
 * Phase 10.10: Unified export endpoint for archive data.
 *
 * Supports PDF, XLSX, CSV, JSON, DOCX, and ZIP formats.
 */
namespace App\Controllers\Erp;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\SystemModel;

class ArchiveExport extends BaseController
{
    /**
     * Unified export endpoint.
     * GET erp/archive/export?type={contacts|attendance|payroll|companies}&format={pdf|xlsx|csv|json|docx|zip}&{filters}
     */
    public function export()
    {
        $session  = \Config\Services::session();
        $usession = $session->get('sup_username');

        if (!$session->has('sup_username')) {
            $session->setFlashdata('err_not_logged_in', lang('Dashboard.err_not_logged_in'));
            return redirect()->to(site_url('erp/login'));
        }

        $UsersModel = new UsersModel();
        $user_info  = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

        // Super Admin can export all archive data; Company Admin can export own reports
        $allowedTypes = ['contacts', 'attendance', 'payroll', 'companies'];

        $type    = $this->request->getGet('type');
        $format  = $this->request->getGet('format') ?? 'csv';
        $filters = $this->request->getGet();

        if (!in_array($type, $allowedTypes)) {
            $session->setFlashdata('unauthorized_module', 'Invalid export type.');
            return redirect()->to(site_url('erp/desk'));
        }

        // Company admins may only export their own company data
        if ($user_info['user_type'] !== 'super_user') {
            if ($type === 'companies') {
                $session->setFlashdata('unauthorized_module', lang('Dashboard.xin_error_unauthorized_module'));
                return redirect()->to(site_url('erp/desk'));
            }
            // Scope filters to current company
            $filters['company_id'] = $user_info['company_id'] ?? null;
        }

        $data  = $this->fetchData($type, $filters);
        $label = 'Rooibok_HR_' . ucfirst($type) . '_' . date('Y-m-d');

        match ($format) {
            'pdf'   => $this->exportPdf($data, $type, $label),
            'xlsx'  => $this->exportXlsx($data, $type, $label),
            'csv'   => $this->exportCsv($data, $type, $label),
            'json'  => $this->exportJson($data, $label),
            'docx'  => $this->exportDocx($data, $type, $label),
            'zip'   => $this->exportZip($data, $type, $label),
            default => $this->exportCsv($data, $type, $label),
        };
    }

    // ----------------------------------------------------------------
    //  Data fetcher
    // ----------------------------------------------------------------

    /**
     * Query the appropriate archive table based on $type, applying $filters.
     *
     * @param string $type    One of contacts|attendance|payroll|companies
     * @param array  $filters Query-string filters (date_from, date_to, company_id, etc.)
     * @return array
     */
    private function fetchData(string $type, array $filters): array
    {
        $archDb = \Config\Database::connect('archive');

        $tableMap = [
            'contacts'   => 'arc_contacts',
            'attendance' => 'arc_attendance',
            'payroll'    => 'arc_payroll',
            'companies'  => 'arc_companies',
        ];

        $table   = $tableMap[$type] ?? null;
        if (!$table) {
            return [];
        }

        $builder = $archDb->table($table);

        // Apply common filters (ignore internal keys)
        $reserved = ['type', 'format', 'csrf_token'];

        if (!empty($filters['company_id'])) {
            $builder->where('company_id', $filters['company_id']);
        }
        if (!empty($filters['date_from'])) {
            $dateCol = ($type === 'attendance') ? 'clock_in' : 'created_at';
            $builder->where("$dateCol >=", $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $dateCol = ($type === 'attendance') ? 'clock_in' : 'created_at';
            $builder->where("$dateCol <=", $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        if (!empty($filters['department_id'])) {
            $builder->where('department_id', $filters['department_id']);
        }

        $builder->orderBy(($type === 'attendance') ? 'clock_in' : 'created_at', 'DESC');
        $builder->limit(10000); // safety cap

        return $builder->get()->getResultArray();
    }

    // ----------------------------------------------------------------
    //  Column helpers
    // ----------------------------------------------------------------

    /**
     * Return user-friendly column headers for a given type.
     */
    private function getHeaders(string $type): array
    {
        if (empty($this->cachedData)) {
            return [];
        }
        return array_keys($this->cachedData[0]);
    }

    /**
     * Humanise a snake_case column name.
     */
    private function humanise(string $col): string
    {
        return ucwords(str_replace('_', ' ', $col));
    }

    // ----------------------------------------------------------------
    //  PDF export (DOMPDF)
    // ----------------------------------------------------------------

    private function exportPdf(array $data, string $type, string $label): void
    {
        if (empty($data)) {
            $this->emptyExportRedirect();
            return;
        }

        $columns = array_keys($data[0]);

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>';
        $html .= 'body { font-family: DejaVu Sans, sans-serif; font-size: 9px; margin: 15px; }';
        $html .= '.brand { color: #1D9E75; font-size: 18px; font-weight: bold; margin-bottom: 4px; }';
        $html .= '.subtitle { color: #555; font-size: 11px; margin-bottom: 12px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 10px; }';
        $html .= 'th { background-color: #1D9E75; color: #fff; padding: 6px 4px; text-align: left; font-size: 8px; }';
        $html .= 'td { padding: 5px 4px; border-bottom: 1px solid #e0e0e0; font-size: 8px; }';
        $html .= 'tr:nth-child(even) td { background-color: #f5faf8; }';
        $html .= '.footer { margin-top: 15px; font-size: 8px; color: #999; text-align: right; }';
        $html .= '</style></head><body>';
        $html .= '<div class="brand">Rooibok HR System</div>';
        $html .= '<div class="subtitle">' . ucfirst($type) . ' Export &mdash; ' . date('d M Y H:i') . '</div>';
        $html .= '<table><thead><tr>';

        foreach ($columns as $col) {
            $html .= '<th>' . $this->humanise($col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($columns as $col) {
                $html .= '<td>' . htmlspecialchars((string)($row[$col] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div class="footer">Generated by Rooibok HR System on ' . date('Y-m-d H:i:s') . ' | Records: ' . count($data) . '</div>';
        $html .= '</body></html>';

        // DOMPDF rendering
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $dompdf->stream($label . '.pdf', ['Attachment' => true]);
        exit;
    }

    // ----------------------------------------------------------------
    //  Excel/XLSX export (PhpSpreadsheet)
    // ----------------------------------------------------------------

    private function exportXlsx(array $data, string $type, string $label): void
    {
        if (empty($data)) {
            $this->emptyExportRedirect();
            return;
        }

        $columns = array_keys($data[0]);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ucfirst($type));

        // Rooibok teal for header row
        $tealFill = [
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1D9E75'],
            ],
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
        ];

        // Write header row
        foreach ($columns as $colIdx => $col) {
            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '1';
            $sheet->setCellValue($cellCoord, $this->humanise($col));
            $sheet->getStyle($cellCoord)->applyFromArray($tealFill);
        }

        // Write data rows
        $rowNum = 2;
        foreach ($data as $row) {
            foreach ($columns as $colIdx => $col) {
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $rowNum;
                $sheet->setCellValue($cellCoord, $row[$col] ?? '');
            }
            $rowNum++;
        }

        // Auto-size columns
        foreach ($columns as $colIdx => $col) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Stream download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $label . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ----------------------------------------------------------------
    //  CSV export
    // ----------------------------------------------------------------

    private function exportCsv(array $data, string $type, string $label): void
    {
        if (empty($data)) {
            $this->emptyExportRedirect();
            return;
        }

        $columns = array_keys($data[0]);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $label . '.csv"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Header row
        fputcsv($output, array_map([$this, 'humanise'], $columns));

        // Data rows
        foreach ($data as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($output, $line);
        }

        fclose($output);
        exit;
    }

    // ----------------------------------------------------------------
    //  JSON export
    // ----------------------------------------------------------------

    private function exportJson(array $data, string $label): void
    {
        $payload = [
            'meta' => [
                'system'       => 'Rooibok HR System',
                'exported_at'  => date('Y-m-d\TH:i:sP'),
                'record_count' => count($data),
            ],
            'data' => $data,
        ];

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $label . '.json"');
        header('Cache-Control: max-age=0');

        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ----------------------------------------------------------------
    //  Word/DOCX export (PhpWord)
    // ----------------------------------------------------------------

    private function exportDocx(array $data, string $type, string $label): void
    {
        if (empty($data)) {
            $this->emptyExportRedirect();
            return;
        }

        $columns = array_keys($data[0]);

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->getDefaultFontName();

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop'   => 600,
            'marginBottom' => 600,
            'marginLeft'  => 600,
            'marginRight' => 600,
        ]);

        // Title
        $section->addText(
            'Rooibok HR System — ' . ucfirst($type) . ' Export',
            ['bold' => true, 'size' => 16, 'color' => '1D9E75']
        );
        $section->addText(
            'Exported: ' . date('d M Y H:i') . '  |  Records: ' . count($data),
            ['size' => 9, 'color' => '666666']
        );
        $section->addTextBreak(1);

        // Table
        $tableStyle = [
            'borderSize'  => 4,
            'borderColor' => 'CCCCCC',
            'cellMargin'  => 50,
        ];
        $phpWord->addTableStyle('ExportTable', $tableStyle);
        $table = $section->addTable('ExportTable');

        // Header row — Rooibok teal
        $headerStyle = [
            'bgColor' => '1D9E75',
        ];
        $headerFontStyle = [
            'bold'  => true,
            'color' => 'FFFFFF',
            'size'  => 9,
        ];

        $table->addRow();
        foreach ($columns as $col) {
            $table->addCell(2000, $headerStyle)->addText($this->humanise($col), $headerFontStyle);
        }

        // Data rows — alternating colours
        $rowIndex = 0;
        foreach ($data as $row) {
            $rowStyle = ($rowIndex % 2 === 0) ? [] : ['bgColor' => 'F0F9F5'];
            $table->addRow();
            foreach ($columns as $col) {
                $cell = $table->addCell(2000, $rowStyle);
                $cell->addText(
                    htmlspecialchars((string)($row[$col] ?? ''), ENT_QUOTES, 'UTF-8'),
                    ['size' => 8]
                );
            }
            $rowIndex++;
        }

        // Stream download
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $label . '.docx"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;
    }

    // ----------------------------------------------------------------
    //  ZIP export (all formats bundled)
    // ----------------------------------------------------------------

    private function exportZip(array $data, string $type, string $label): void
    {
        if (empty($data)) {
            $this->emptyExportRedirect();
            return;
        }

        $tmpDir = WRITEPATH . 'exports/' . uniqid('zip_');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $columns = array_keys($data[0]);

        // --- CSV ---
        $csvPath = $tmpDir . '/' . $label . '.csv';
        $fp = fopen($csvPath, 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, array_map([$this, 'humanise'], $columns));
        foreach ($data as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($fp, $line);
        }
        fclose($fp);

        // --- JSON ---
        $jsonPath = $tmpDir . '/' . $label . '.json';
        $payload = [
            'meta' => [
                'system'       => 'Rooibok HR System',
                'exported_at'  => date('Y-m-d\TH:i:sP'),
                'record_count' => count($data),
            ],
            'data' => $data,
        ];
        file_put_contents($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // --- XLSX ---
        $xlsxPath = $tmpDir . '/' . $label . '.xlsx';
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(ucfirst($type));

            $tealFill = [
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1D9E75'],
                ],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            ];

            foreach ($columns as $colIdx => $col) {
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . '1';
                $sheet->setCellValue($cellCoord, $this->humanise($col));
                $sheet->getStyle($cellCoord)->applyFromArray($tealFill);
            }
            $rowNum = 2;
            foreach ($data as $row) {
                foreach ($columns as $colIdx => $col) {
                    $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $rowNum;
                    $sheet->setCellValue($cellCoord, $row[$col] ?? '');
                }
                $rowNum++;
            }
            foreach ($columns as $colIdx => $col) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($xlsxPath);
        } catch (\Throwable $e) {
            // PhpSpreadsheet not installed — skip XLSX in bundle
        }

        // --- DOCX ---
        $docxPath = $tmpDir . '/' . $label . '.docx';
        try {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection(['orientation' => 'landscape']);
            $section->addText('Rooibok HR System — ' . ucfirst($type) . ' Export', ['bold' => true, 'size' => 16, 'color' => '1D9E75']);
            $section->addText('Exported: ' . date('d M Y H:i') . '  |  Records: ' . count($data), ['size' => 9, 'color' => '666666']);
            $section->addTextBreak(1);

            $phpWord->addTableStyle('ZipTable', ['borderSize' => 4, 'borderColor' => 'CCCCCC', 'cellMargin' => 50]);
            $table = $section->addTable('ZipTable');
            $table->addRow();
            foreach ($columns as $col) {
                $table->addCell(2000, ['bgColor' => '1D9E75'])->addText($this->humanise($col), ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
            }
            $ri = 0;
            foreach ($data as $row) {
                $rs = ($ri % 2 === 0) ? [] : ['bgColor' => 'F0F9F5'];
                $table->addRow();
                foreach ($columns as $col) {
                    $table->addCell(2000, $rs)->addText(htmlspecialchars((string)($row[$col] ?? ''), ENT_QUOTES, 'UTF-8'), ['size' => 8]);
                }
                $ri++;
            }
            $docWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $docWriter->save($docxPath);
        } catch (\Throwable $e) {
            // PhpWord not installed — skip DOCX in bundle
        }

        // --- PDF ---
        $pdfPath = $tmpDir . '/' . $label . '.pdf';
        try {
            $html = $this->buildPdfHtml($data, $type);
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            file_put_contents($pdfPath, $dompdf->output());
        } catch (\Throwable $e) {
            // DOMPDF not installed — skip PDF in bundle
        }

        // Build ZIP
        $zipPath = $tmpDir . '/' . $label . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            foreach ([$csvPath, $jsonPath, $xlsxPath, $docxPath, $pdfPath] as $file) {
                if (file_exists($file)) {
                    $zip->addFile($file, basename($file));
                }
            }
            $zip->close();
        }

        // Stream the ZIP
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $label . '.zip"');
        header('Content-Length: ' . filesize($zipPath));
        header('Cache-Control: max-age=0');
        readfile($zipPath);

        // Cleanup
        array_map('unlink', glob($tmpDir . '/*'));
        rmdir($tmpDir);
        exit;
    }

    // ----------------------------------------------------------------
    //  Helpers
    // ----------------------------------------------------------------

    /**
     * Build the HTML string used by DOMPDF (shared between single-PDF and ZIP).
     */
    private function buildPdfHtml(array $data, string $type): string
    {
        $columns = array_keys($data[0]);

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>';
        $html .= 'body{font-family:DejaVu Sans,sans-serif;font-size:9px;margin:15px}';
        $html .= '.brand{color:#1D9E75;font-size:18px;font-weight:bold;margin-bottom:4px}';
        $html .= '.subtitle{color:#555;font-size:11px;margin-bottom:12px}';
        $html .= 'table{width:100%;border-collapse:collapse;margin-top:10px}';
        $html .= 'th{background-color:#1D9E75;color:#fff;padding:6px 4px;text-align:left;font-size:8px}';
        $html .= 'td{padding:5px 4px;border-bottom:1px solid #e0e0e0;font-size:8px}';
        $html .= 'tr:nth-child(even) td{background-color:#f5faf8}';
        $html .= '.footer{margin-top:15px;font-size:8px;color:#999;text-align:right}';
        $html .= '</style></head><body>';
        $html .= '<div class="brand">Rooibok HR System</div>';
        $html .= '<div class="subtitle">' . ucfirst($type) . ' Export &mdash; ' . date('d M Y H:i') . '</div>';
        $html .= '<table><thead><tr>';
        foreach ($columns as $col) {
            $html .= '<th>' . $this->humanise($col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($columns as $col) {
                $html .= '<td>' . htmlspecialchars((string)($row[$col] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '<div class="footer">Generated by Rooibok HR System on ' . date('Y-m-d H:i:s') . ' | Records: ' . count($data) . '</div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Redirect back with a message when export data is empty.
     */
    private function emptyExportRedirect(): void
    {
        $session = \Config\Services::session();
        $session->setFlashdata('unauthorized_module', 'No records found for the selected export criteria.');
        header('Location: ' . site_url('erp/desk'));
        exit;
    }
}
