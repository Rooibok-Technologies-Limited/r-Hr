<div id="smartwizard-2" class="border-bottom smartwizard-example sw-main sw-theme-default mt-2">
    <ul class="nav nav-tabs step-anchor">
        <li class="nav-item clickable">
            <a href="<?= site_url('erp/timesheet-dashboard');?>" class="mb-3 nav-link">
                <span class="sw-done-icon feather icon-check-circle"></span>
                <span class="sw-icon fas fa-user-friends"></span><?= lang('Dashboard.dashboard_hr');?>
                <div class="text-muted small">Dashboard</div>
            </a>
        </li>
        <li class="nav-item clickable">
            <a href="<?= site_url('erp/attendance-list');?>" class="mb-3 nav-link">
                <span class="sw-done-icon feather icon-check-circle"></span>
                <span class="sw-icon fas fa-user-lock"></span><?= lang('Dashboard.left_attendance');?>
                <div class="text-muted small">Attendance records</div>
            </a>
        </li>
        <li class="nav-item active">
            <a href="<?= site_url('erp/attendance-live');?>" class="mb-3 nav-link">
                <span class="sw-done-icon feather icon-check-circle"></span>
                <span class="sw-icon fas fa-broadcast-tower"></span>Live Attendance
                <div class="text-muted small">Real-time view</div>
            </a>
        </li>
    </ul>
</div>
<hr class="border-light m-0 mb-3">

<style>
    .live-stat-card {
        transition: all 0.4s ease;
        border-radius: 8px;
        overflow: hidden;
    }
    .live-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .stat-number {
        font-size: 2.2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .stat-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
    }
    .employee-card {
        transition: all 0.3s ease;
        border-left: 3px solid #28a745;
        margin-bottom: 8px;
    }
    .employee-card:hover {
        background-color: #f8f9fa;
    }
    .employee-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    .employee-avatar-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #6c757d;
        font-size: 0.9rem;
    }
    .activity-item {
        padding: 10px 15px;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.3s ease;
    }
    .activity-item:hover {
        background-color: #f8f9fa;
    }
    .activity-item:last-child {
        border-bottom: none;
    }
    .clock-in-badge { background-color: #28a745; color: #fff; }
    .clock-out-badge { background-color: #dc3545; color: #fff; }
    .pulse-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #28a745;
        animation: pulse 2s infinite;
        margin-right: 6px;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(40,167,69,0.7); }
        70% { box-shadow: 0 0 0 6px rgba(40,167,69,0); }
        100% { box-shadow: 0 0 0 0 rgba(40,167,69,0); }
    }
    .fade-update {
        animation: fadeIn 0.5s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0.5; }
        to { opacity: 1; }
    }
    #live-status-bar {
        font-size: 0.8rem;
        color: #6c757d;
    }
</style>

<!-- Status Bar -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span class="pulse-dot"></span>
        <strong>Live Attendance Monitor</strong>
        <span id="live-status-bar" class="ml-2">Connecting...</span>
    </div>
    <div>
        <a href="<?= site_url('erp/attendance-live/?display=tv');?>" class="btn btn-sm btn-outline-dark" target="_blank">
            <i class="fas fa-tv mr-1"></i> TV Display
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row" id="stat-cards">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card live-stat-card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-users fa-2x text-primary"></i>
                </div>
                <div class="stat-number text-primary" id="stat-total">--</div>
                <div class="stat-label">Total Staff</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card live-stat-card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-sign-in-alt fa-2x text-success"></i>
                </div>
                <div class="stat-number text-success" id="stat-in">--</div>
                <div class="stat-label">Currently In</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card live-stat-card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-sign-out-alt fa-2x text-warning"></i>
                </div>
                <div class="stat-number text-warning" id="stat-out">--</div>
                <div class="stat-label">Clocked Out</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card live-stat-card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-user-slash fa-2x text-danger"></i>
                </div>
                <div class="stat-number text-danger" id="stat-absent">--</div>
                <div class="stat-label">Absent</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Currently in Building -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-building text-success mr-2"></i>Currently in Building</h6>
                <span class="badge badge-success" id="in-building-count">0</span>
            </div>
            <div class="card-body p-0" id="in-building-list" style="max-height: 500px; overflow-y: auto;">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <p>Loading live data...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-history text-primary mr-2"></i>Recent Activity</h6>
            </div>
            <div class="card-body p-0" id="recent-activity" style="max-height: 500px; overflow-y: auto;">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <p>Loading live data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var streamUrl = '<?= $stream_url ?? site_url("erp/attendance-live/stream/"); ?>';
    var statusBar = document.getElementById('live-status-bar');
    var evtSource = null;

    function getInitials(first, last) {
        return ((first || '').charAt(0) + (last || '').charAt(0)).toUpperCase();
    }

    function getPhotoHtml(photo, first, last) {
        if (photo && photo !== '' && photo !== 'default.jpg') {
            return '<img src="' + siteUrl + 'uploads/profile/' + photo + '" class="employee-avatar" alt="">';
        }
        return '<div class="employee-avatar-placeholder">' + getInitials(first, last) + '</div>';
    }

    function formatTime(datetime) {
        if (!datetime) return '--:--';
        var parts = datetime.split(' ');
        if (parts.length > 1) {
            var t = parts[1].split(':');
            return t[0] + ':' + t[1];
        }
        return datetime;
    }

    function updateDashboard(data) {
        // Update stat cards with animation
        animateStat('stat-total', data.total_staff);
        animateStat('stat-in', data.clocked_in);
        animateStat('stat-out', data.clocked_out);
        animateStat('stat-absent', data.absent);

        // Update in-building list
        var inBuildingHtml = '';
        document.getElementById('in-building-count').textContent = data.in_building ? data.in_building.length : 0;

        if (data.in_building && data.in_building.length > 0) {
            data.in_building.forEach(function(emp) {
                inBuildingHtml += '<div class="employee-card d-flex align-items-center p-2 px-3">' +
                    '<div class="mr-3">' + getPhotoHtml(emp.profile_photo, emp.first_name, emp.last_name) + '</div>' +
                    '<div class="flex-grow-1">' +
                        '<div class="font-weight-bold">' + (emp.first_name || '') + ' ' + (emp.last_name || '') + '</div>' +
                        '<small class="text-muted">' + (emp.department_name || 'No Department') + '</small>' +
                    '</div>' +
                    '<div class="text-right">' +
                        '<small class="text-success"><i class="fas fa-clock mr-1"></i>' + formatTime(emp.clock_in) + '</small>' +
                    '</div>' +
                '</div>';
            });
        } else {
            inBuildingHtml = '<div class="text-center text-muted py-4"><i class="fas fa-door-open fa-2x mb-2"></i><p>No one currently in the building</p></div>';
        }
        var inBuildingEl = document.getElementById('in-building-list');
        inBuildingEl.innerHTML = inBuildingHtml;
        inBuildingEl.classList.add('fade-update');
        setTimeout(function() { inBuildingEl.classList.remove('fade-update'); }, 600);

        // Update recent activity
        var activityHtml = '';
        if (data.recent_events && data.recent_events.length > 0) {
            data.recent_events.forEach(function(evt) {
                var isClockOut = (evt.clock_in_out == 1);
                var badgeClass = isClockOut ? 'clock-out-badge' : 'clock-in-badge';
                var badgeText  = isClockOut ? 'OUT' : 'IN';
                var time       = isClockOut ? formatTime(evt.clock_out) : formatTime(evt.clock_in);

                activityHtml += '<div class="activity-item d-flex align-items-center">' +
                    '<div class="mr-3">' + getPhotoHtml(evt.profile_photo, evt.first_name, evt.last_name) + '</div>' +
                    '<div class="flex-grow-1">' +
                        '<div class="font-weight-bold">' + (evt.first_name || '') + ' ' + (evt.last_name || '') + '</div>' +
                        '<small class="text-muted">' + time + '</small>' +
                    '</div>' +
                    '<span class="badge ' + badgeClass + ' px-2 py-1">' + badgeText + '</span>' +
                '</div>';
            });
        } else {
            activityHtml = '<div class="text-center text-muted py-4"><i class="fas fa-clock fa-2x mb-2"></i><p>No activity today</p></div>';
        }
        var activityEl = document.getElementById('recent-activity');
        activityEl.innerHTML = activityHtml;
        activityEl.classList.add('fade-update');
        setTimeout(function() { activityEl.classList.remove('fade-update'); }, 600);

        // Update status bar
        statusBar.innerHTML = '<i class="fas fa-check-circle text-success mr-1"></i> Last updated: ' + (data.updated_at || '--:--:--');
    }

    function animateStat(elementId, value) {
        var el = document.getElementById(elementId);
        if (el.textContent != value) {
            el.textContent = value;
            el.classList.add('fade-update');
            setTimeout(function() { el.classList.remove('fade-update'); }, 600);
        }
    }

    function connectSSE() {
        if (evtSource) {
            evtSource.close();
        }

        statusBar.innerHTML = '<i class="fas fa-sync-alt fa-spin mr-1"></i> Connecting...';
        evtSource = new EventSource(streamUrl);

        evtSource.onmessage = function(event) {
            try {
                var data = JSON.parse(event.data);
                updateDashboard(data);
            } catch (e) {
                console.error('Failed to parse SSE data:', e);
            }
        };

        evtSource.onerror = function() {
            statusBar.innerHTML = '<i class="fas fa-exclamation-triangle text-warning mr-1"></i> Connection lost. Reconnecting...';
            evtSource.close();
            setTimeout(connectSSE, 5000);
        };
    }

    // siteUrl fallback
    var siteUrl = typeof site_url !== 'undefined' ? site_url : '<?= site_url(); ?>';

    connectSSE();

    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (evtSource) evtSource.close();
    });
})();
</script>
