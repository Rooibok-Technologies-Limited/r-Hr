<?php
use App\Models\UsersModel;
use App\Models\SystemModel;
use App\Models\DepartmentModel;
use App\Models\DesignationModel;
use App\Models\StaffdetailsModel;
use App\Libraries\QrCodeGenerator;

$UsersModel         = new UsersModel();
$SystemModel        = new SystemModel();
$DepartmentModel    = new DepartmentModel();
$DesignationModel   = new DesignationModel();
$StaffdetailsModel  = new StaffdetailsModel();
$QrCodeGenerator    = new QrCodeGenerator();

$xin_system      = $SystemModel->where('setting_id', 1)->first();
$employee        = $UsersModel->where('user_id', $employee_id)->first();
$employee_detail = $StaffdetailsModel->where('user_id', $employee_id)->first();

$department  = $DepartmentModel->where('department_id', $employee_detail['department_id'] ?? 0)->first();
$designation = $DesignationModel->where('designation_id', $employee_detail['designation_id'] ?? 0)->first();

$qr_url = $QrCodeGenerator->getEmployeeQrUrl((int)$employee_id, 150);

$photo = (!empty($employee['profile_photo']) && $employee['profile_photo'] !== 'no-photo.jpg')
    ? base_url('uploads/profile/' . $employee['profile_photo'])
    : base_url('uploads/profile/no-photo.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee ID Card - <?= esc($employee['first_name'] . ' ' . $employee['last_name']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .id-card {
            width: 105mm;
            height: 148mm;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12mm 8mm;
            position: relative;
            overflow: hidden;
        }
        .id-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 30mm;
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
        }
        .photo-container {
            position: relative;
            z-index: 2;
            width: 30mm;
            height: 30mm;
            border-radius: 50%;
            border: 3px solid #fff;
            overflow: hidden;
            margin-top: 10mm;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .employee-name {
            margin-top: 5mm;
            font-size: 16pt;
            font-weight: 700;
            color: #212121;
            text-align: center;
        }
        .employee-designation {
            margin-top: 2mm;
            font-size: 10pt;
            color: #1a73e8;
            font-weight: 600;
            text-align: center;
        }
        .employee-department {
            margin-top: 1mm;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }
        .qr-container {
            margin-top: 6mm;
            padding: 3mm;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
        }
        .qr-container img {
            display: block;
            width: 35mm;
            height: 35mm;
        }
        .company-name {
            margin-top: auto;
            font-size: 10pt;
            font-weight: 600;
            color: #333;
            text-align: center;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .btn-print {
            margin-top: 20px;
            padding: 12px 36px;
            background: #1a73e8;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-print:hover { background: #0d47a1; }

        @media print {
            body { background: #fff; }
            .btn-print { display: none; }
            .id-card {
                box-shadow: none;
                border: 1px solid #ccc;
            }
            @page {
                size: A6 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="id-card">
        <div class="photo-container">
            <img src="<?= $photo ?>" alt="Employee Photo">
        </div>
        <div class="employee-name"><?= esc($employee['first_name'] . ' ' . $employee['last_name']) ?></div>
        <div class="employee-designation"><?= esc($designation['designation_name'] ?? 'N/A') ?></div>
        <div class="employee-department"><?= esc($department['department_name'] ?? 'N/A') ?></div>
        <div class="qr-container">
            <img src="<?= $qr_url ?>" alt="Employee QR Code">
        </div>
        <div class="company-name"><?= esc($xin_system['company_name'] ?? $xin_system['application_name'] ?? 'Company') ?></div>
    </div>
    <button class="btn-print" onclick="window.print()">Print ID Card</button>
</body>
</html>
