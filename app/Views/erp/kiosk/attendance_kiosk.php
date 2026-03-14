<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Attendance Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 100%; height: 100%;
            overflow: hidden;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #0a0a0a;
            color: #fff;
        }
        .kiosk-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }
        .kiosk-header {
            text-align: center;
            margin-bottom: 15px;
            flex-shrink: 0;
        }
        .kiosk-header img.logo {
            max-height: 50px;
            margin-bottom: 8px;
        }
        .kiosk-header h1 {
            font-size: 22px;
            font-weight: 600;
            color: #fff;
        }
        .clock-display {
            font-size: 48px;
            font-weight: 300;
            color: #4fc3f7;
            letter-spacing: 2px;
            margin-bottom: 15px;
            flex-shrink: 0;
        }
        .camera-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            overflow: hidden;
            background: #1a1a1a;
            border: 2px solid #333;
        }
        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        #canvas { display: none; }
        .scan-overlay {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 220px; height: 220px;
            border: 3px solid rgba(79, 195, 247, 0.6);
            border-radius: 12px;
            pointer-events: none;
        }
        .scan-overlay::before,
        .scan-overlay::after {
            content: '';
            position: absolute;
            width: 30px; height: 30px;
            border-color: #4fc3f7;
            border-style: solid;
        }
        .scan-overlay::before {
            top: -2px; left: -2px;
            border-width: 4px 0 0 4px;
            border-radius: 8px 0 0 0;
        }
        .scan-overlay::after {
            bottom: -2px; right: -2px;
            border-width: 0 4px 4px 0;
            border-radius: 0 0 8px 0;
        }
        .scan-instruction {
            margin-top: 15px;
            font-size: 16px;
            color: #aaa;
            text-align: center;
            flex-shrink: 0;
        }
        /* Success overlay */
        .success-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(46, 125, 50, 0.95);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .success-overlay.active { display: flex; }
        .success-overlay .employee-photo {
            width: 120px; height: 120px;
            border-radius: 50%;
            border: 4px solid #fff;
            object-fit: cover;
            margin-bottom: 20px;
        }
        .success-overlay .employee-name {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .success-overlay .clock-time {
            font-size: 24px;
            font-weight: 300;
            opacity: 0.9;
        }
        .success-overlay .check-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        /* Error overlay */
        .error-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(198, 40, 40, 0.95);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .error-overlay.active { display: flex; }
        .error-overlay .error-message {
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            padding: 0 30px;
        }
        /* Camera error */
        .camera-error {
            text-align: center;
            padding: 40px;
            color: #e57373;
        }
        .camera-error h2 { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="kiosk-wrapper">
        <div class="kiosk-header">
            <img src="<?= base_url('uploads/logo.png') ?>" alt="Company Logo" class="logo"
                 onerror="this.style.display='none'">
            <h1>Attendance Kiosk</h1>
        </div>

        <div class="clock-display" id="clock">--:--:--</div>

        <div class="camera-container">
            <video id="video" autoplay playsinline muted></video>
            <canvas id="canvas"></canvas>
            <div class="scan-overlay"></div>
        </div>

        <div class="scan-instruction">
            Position your QR ID card within the frame to clock in
        </div>
    </div>

    <!-- Success overlay -->
    <div class="success-overlay" id="successOverlay">
        <div class="check-icon">&#10004;</div>
        <img class="employee-photo" id="successPhoto" src="" alt="">
        <div class="employee-name" id="successName"></div>
        <div class="clock-time" id="successTime"></div>
    </div>

    <!-- Error overlay -->
    <div class="error-overlay" id="errorOverlay">
        <div class="error-message" id="errorMessage">Unable to process scan</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <script>
    (function () {
        const video       = document.getElementById('video');
        const canvas      = document.getElementById('canvas');
        const ctx         = canvas.getContext('2d');
        const clockEl     = document.getElementById('clock');
        const successOvl  = document.getElementById('successOverlay');
        const errorOvl    = document.getElementById('errorOverlay');
        const BASE        = '<?= site_url() ?>';
        let scanning      = true;
        let lastScanTime  = 0;

        // ---- Clock ----
        function updateClock() {
            const now = new Date();
            clockEl.textContent = now.toLocaleTimeString('en-GB', { hour12: false });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // ---- Camera ----
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 480 } }
                });
                video.srcObject = stream;
                video.play();
                requestAnimationFrame(scanFrame);
            } catch (err) {
                document.querySelector('.camera-container').innerHTML =
                    '<div class="camera-error"><h2>Camera Access Required</h2><p>Please allow camera access and reload.</p></div>';
            }
        }

        // ---- QR Scan loop ----
        function scanFrame() {
            if (!scanning) {
                requestAnimationFrame(scanFrame);
                return;
            }
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width  = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });

                if (code && code.data) {
                    const now = Date.now();
                    if (now - lastScanTime > 5000) {       // debounce 5 s
                        lastScanTime = now;
                        handleScan(code.data);
                    }
                }
            }
            requestAnimationFrame(scanFrame);
        }

        // ---- Handle decoded QR data ----
        async function handleScan(qrData) {
            scanning = false;
            try {
                const resp = await fetch(BASE + 'api/v1/attendance/clock-in', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ employee_code: qrData })
                });
                const json = await resp.json();

                if (resp.ok && json.status !== 'error') {
                    showSuccess(json);
                } else {
                    showError(json.message || 'Clock-in failed');
                }
            } catch (e) {
                showError('Network error. Please try again.');
            }
        }

        function showSuccess(data) {
            document.getElementById('successName').textContent  = data.employee_name || 'Employee';
            document.getElementById('successTime').textContent  = 'Clocked In at ' + new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
            const photo = document.getElementById('successPhoto');
            if (data.profile_photo) {
                photo.src = BASE + 'uploads/profile/' + data.profile_photo;
                photo.style.display = 'block';
            } else {
                photo.style.display = 'none';
            }
            successOvl.classList.add('active');
            setTimeout(function () {
                successOvl.classList.remove('active');
                scanning = true;
            }, 3000);
        }

        function showError(msg) {
            document.getElementById('errorMessage').textContent = msg;
            errorOvl.classList.add('active');
            setTimeout(function () {
                errorOvl.classList.remove('active');
                scanning = true;
            }, 3000);
        }

        startCamera();
    })();
    </script>
</body>
</html>
