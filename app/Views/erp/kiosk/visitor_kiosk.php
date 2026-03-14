<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Visitor Check-In</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 100%; height: 100%;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .kiosk-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .kiosk-brand {
            text-align: center;
            margin-bottom: 30px;
        }
        .kiosk-brand img {
            max-height: 50px;
            margin-bottom: 10px;
        }
        .kiosk-brand h1 {
            font-size: 26px;
            font-weight: 700;
            color: #1a73e8;
        }
        .kiosk-brand p {
            font-size: 14px;
            color: #888;
            margin-top: 4px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #555;
        }
        .form-group label .required {
            color: #e53935;
        }
        .form-control {
            width: 100%;
            padding: 16px;
            font-size: 18px;
            border: 2px solid #ddd;
            border-radius: 10px;
            background: #fff;
            color: #333;
            transition: border-color 0.2s;
            -webkit-appearance: none;
        }
        .form-control:focus {
            outline: none;
            border-color: #1a73e8;
        }
        select.form-control {
            cursor: pointer;
        }
        .photo-capture {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .photo-capture input[type="file"] {
            display: none;
        }
        .photo-capture .capture-btn {
            padding: 14px 24px;
            background: #f0f0f0;
            border: 2px dashed #bbb;
            border-radius: 10px;
            font-size: 16px;
            color: #666;
            cursor: pointer;
            text-align: center;
            flex: 1;
        }
        .photo-capture .capture-btn:active {
            background: #e0e0e0;
        }
        .photo-capture .preview-thumb {
            width: 60px; height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #1a73e8;
            display: none;
        }
        .btn-submit {
            width: 100%;
            padding: 18px;
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            background: #1a73e8;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #0d47a1; }
        .btn-submit:disabled {
            background: #90caf9;
            cursor: not-allowed;
        }

        /* Badge overlay */
        .badge-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        .badge-overlay.active { display: flex; }
        .visitor-badge {
            background: #fff;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            max-width: 400px;
            width: 100%;
            color: #333;
        }
        .visitor-badge h2 {
            color: #2e7d32;
            font-size: 22px;
            margin-bottom: 6px;
        }
        .visitor-badge .badge-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        .visitor-badge .visitor-name-display {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .visitor-badge .visit-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        .visitor-badge .qr-image {
            margin: 10px auto;
            display: block;
        }
        .visitor-badge .qr-note {
            font-size: 12px;
            color: #aaa;
            margin-top: 8px;
        }
        .btn-new-visitor {
            margin-top: 20px;
            padding: 14px 30px;
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            background: #1a73e8;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        /* Error toast */
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%; transform: translateX(-50%);
            background: #c62828;
            color: #fff;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 16px;
            display: none;
            z-index: 2000;
        }
        .toast.active { display: block; }
    </style>
</head>
<body>
    <div class="kiosk-container">
        <div class="kiosk-brand">
            <img src="<?= base_url('uploads/logo.png') ?>" alt="Logo"
                 onerror="this.style.display='none'">
            <h1>Visitor Check-In</h1>
            <p>Welcome! Please fill in your details below.</p>
        </div>

        <form id="visitorForm" novalidate>
            <div class="form-group">
                <label>Visitor Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="visitor_name" name="visitor_name"
                       placeholder="Enter your full name" required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" class="form-control" id="phone" name="phone"
                       placeholder="e.g. +27 71 234 5678" autocomplete="off">
            </div>

            <div class="form-group">
                <label>National ID (optional)</label>
                <input type="text" class="form-control" id="national_id" name="national_id"
                       placeholder="ID / Passport number" autocomplete="off">
            </div>

            <div class="form-group">
                <label>Whom are you visiting? <span class="required">*</span></label>
                <select class="form-control" id="whom_visiting" name="whom_visiting" required>
                    <option value="">-- Select Staff Member --</option>
                </select>
            </div>

            <div class="form-group">
                <label>Purpose of Visit <span class="required">*</span></label>
                <select class="form-control" id="visit_purpose" name="visit_purpose" required>
                    <option value="">-- Select Purpose --</option>
                    <option value="Meeting">Meeting</option>
                    <option value="Interview">Interview</option>
                    <option value="Delivery">Delivery</option>
                    <option value="Personal">Personal</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Photo (optional)</label>
                <div class="photo-capture">
                    <label class="capture-btn" for="photo_input" id="captureLabel">
                        Tap to take photo
                    </label>
                    <input type="file" id="photo_input" accept="image/*" capture="camera">
                    <img class="preview-thumb" id="photoPreview" src="" alt="">
                </div>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">Check In</button>
        </form>
    </div>

    <!-- Badge overlay -->
    <div class="badge-overlay" id="badgeOverlay">
        <div class="visitor-badge">
            <h2>Check-In Successful</h2>
            <div class="badge-label">Visitor Badge</div>
            <div class="visitor-name-display" id="badgeName"></div>
            <div class="visit-info" id="badgeInfo"></div>
            <img class="qr-image" id="badgeQr" src="" alt="Visitor QR" width="160" height="160">
            <div class="qr-note">Show this QR code when checking out</div>
            <button class="btn-new-visitor" id="btnNewVisitor" onclick="resetKiosk()">New Visitor</button>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
    (function () {
        const BASE = '<?= site_url() ?>';
        const form = document.getElementById('visitorForm');

        // ---- Load staff dropdown via AJAX ----
        async function loadStaff() {
            try {
                const resp = await fetch(BASE + 'erp/employees/employees_list', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                if (!resp.ok) return;
                const data = await resp.json();
                const sel = document.getElementById('whom_visiting');
                if (Array.isArray(data)) {
                    data.forEach(function (emp) {
                        const opt = document.createElement('option');
                        opt.value = emp.user_id || emp.employee_id || '';
                        opt.textContent = (emp.first_name || '') + ' ' + (emp.last_name || '');
                        sel.appendChild(opt);
                    });
                }
            } catch (e) {
                // Staff list may not be available without auth; fail silently
            }
        }
        loadStaff();

        // ---- Photo preview ----
        document.getElementById('photo_input').addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const preview = document.getElementById('photoPreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    document.getElementById('captureLabel').textContent = 'Retake';
                };
                reader.readAsDataURL(file);
            }
        });

        // ---- Form submit ----
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const name    = document.getElementById('visitor_name').value.trim();
            const purpose = document.getElementById('visit_purpose').value;
            if (!name) { showToast('Please enter your name.'); return; }
            if (!purpose) { showToast('Please select a purpose.'); return; }

            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.textContent = 'Checking in...';

            const fd = new FormData();
            fd.append('visitor_name', name);
            fd.append('phone', document.getElementById('phone').value.trim());
            fd.append('national_id', document.getElementById('national_id').value.trim());
            fd.append('visit_purpose', purpose);
            fd.append('whom_visiting', document.getElementById('whom_visiting').value);

            const photoFile = document.getElementById('photo_input').files[0];
            if (photoFile) fd.append('photo', photoFile);

            try {
                const resp = await fetch(BASE + 'api/v1/visitors/check-in', {
                    method: 'POST',
                    body: fd
                });
                const json = await resp.json();

                if (resp.ok && json.visitor_id) {
                    showBadge(json, name, purpose);
                } else {
                    showToast(json.message || 'Check-in failed. Please try again.');
                }
            } catch (err) {
                showToast('Network error. Please try again.');
            }

            btn.disabled = false;
            btn.textContent = 'Check In';
        });

        function showBadge(data, name, purpose) {
            document.getElementById('badgeName').textContent = name;
            document.getElementById('badgeInfo').textContent = purpose + ' - ' + new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
            // QR encodes visitor_id for checkout scanning
            const qrUrl = 'https://chart.googleapis.com/chart?chs=160x160&cht=qr&chl='
                + encodeURIComponent('visitor:' + data.visitor_id) + '&choe=UTF-8';
            document.getElementById('badgeQr').src = qrUrl;
            document.getElementById('badgeOverlay').classList.add('active');
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('active');
            setTimeout(function () { t.classList.remove('active'); }, 3000);
        }

        window.resetKiosk = function () {
            document.getElementById('badgeOverlay').classList.remove('active');
            form.reset();
            document.getElementById('photoPreview').style.display = 'none';
            document.getElementById('captureLabel').textContent = 'Tap to take photo';
        };
    })();
    </script>
</body>
</html>
