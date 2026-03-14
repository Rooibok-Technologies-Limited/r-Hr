<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Live Attendance'); ?> - <?= esc($app_name ?? 'Rooibok HR'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
            background: #1a1a2e;
            color: #e0e0e0;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Header */
        .tv-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: linear-gradient(135deg, #16213e 0%, #0f3460 100%);
            border-bottom: 2px solid #e94560;
        }
        .tv-header .company-name {
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
        }
        .tv-header .live-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #e94560;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .tv-header .live-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e94560;
            animation: blink 1.5s infinite;
        }
        .tv-clock {
            font-size: 2.8rem;
            font-weight: 300;
            color: #fff;
            font-variant-numeric: tabular-nums;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Stats Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 25px 40px;
        }
        .stat-card {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.08);
            transition: all 0.4s ease;
        }
        .stat-card .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .stat-card .stat-value {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        .stat-card .stat-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #888;
        }
        .stat-total .stat-icon, .stat-total .stat-value { color: #5dade2; }
        .stat-in .stat-icon, .stat-in .stat-value { color: #2ecc71; }
        .stat-out .stat-icon, .stat-out .stat-value { color: #f39c12; }
        .stat-absent .stat-icon, .stat-absent .stat-value { color: #e74c3c; }

        /* Main Content */
        .tv-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            padding: 0 40px 20px;
            height: calc(100vh - 260px);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 12px;
        }

        /* Employee Grid */
        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            overflow-y: auto;
            padding-right: 5px;
            max-height: calc(100vh - 340px);
        }
        .employee-grid::-webkit-scrollbar { width: 4px; }
        .employee-grid::-webkit-scrollbar-track { background: transparent; }
        .employee-grid::-webkit-scrollbar-thumb { background: #333; border-radius: 2px; }

        .emp-tile {
            background: rgba(255,255,255,0.04);
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.06);
            transition: all 0.3s ease;
        }
        .emp-tile:hover {
            background: rgba(255,255,255,0.08);
        }
        .emp-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            margin: 0 auto 10px;
            object-fit: cover;
            border: 2px solid #2ecc71;
        }
        .emp-avatar-placeholder {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            margin: 0 auto 10px;
            background: #2c3e50;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            color: #5dade2;
            border: 2px solid #2ecc71;
        }
        .emp-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #e0e0e0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .emp-dept {
            font-size: 0.75rem;
            color: #777;
            margin-top: 2px;
        }
        .emp-time {
            font-size: 0.8rem;
            color: #2ecc71;
            margin-top: 6px;
        }

        /* Activity Feed */
        .activity-feed {
            overflow-y: auto;
            max-height: calc(100vh - 340px);
        }
        .activity-feed::-webkit-scrollbar { width: 4px; }
        .activity-feed::-webkit-scrollbar-track { background: transparent; }
        .activity-feed::-webkit-scrollbar-thumb { background: #333; border-radius: 2px; }

        .feed-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }
        .feed-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .feed-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2c3e50;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            color: #5dade2;
            flex-shrink: 0;
        }
        .feed-info {
            flex: 1;
            min-width: 0;
        }
        .feed-name {
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .feed-time {
            font-size: 0.75rem;
            color: #777;
        }
        .feed-badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            flex-shrink: 0;
        }
        .badge-in { background: rgba(46,204,113,0.2); color: #2ecc71; }
        .badge-out { background: rgba(231,76,60,0.2); color: #e74c3c; }

        /* Status Footer */
        .tv-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px 40px;
            background: rgba(0,0,0,0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #555;
        }

        /* Animations */
        .fade-in { animation: fadeIn 0.5s ease; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* No-employees message */
        .empty-message {
            text-align: center;
            padding: 40px;
            color: #555;
            font-size: 1.1rem;
        }
        .empty-message i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="tv-header">
        <div>
            <div class="company-name"><?= esc($app_name ?? 'Rooibok HR'); ?></div>
            <div class="live-badge">
                <span class="live-dot"></span> Live Attendance
            </div>
        </div>
        <div class="tv-clock" id="tv-clock">--:--:--</div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card stat-total">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value" id="tv-stat-total">--</div>
            <div class="stat-label">Total Staff</div>
        </div>
        <div class="stat-card stat-in">
            <div class="stat-icon"><i class="fas fa-sign-in-alt"></i></div>
            <div class="stat-value" id="tv-stat-in">--</div>
            <div class="stat-label">Currently In</div>
        </div>
        <div class="stat-card stat-out">
            <div class="stat-icon"><i class="fas fa-sign-out-alt"></i></div>
            <div class="stat-value" id="tv-stat-out">--</div>
            <div class="stat-label">Clocked Out</div>
        </div>
        <div class="stat-card stat-absent">
            <div class="stat-icon"><i class="fas fa-user-slash"></i></div>
            <div class="stat-value" id="tv-stat-absent">--</div>
            <div class="stat-label">Absent</div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="tv-content">
        <div>
            <div class="section-title"><i class="fas fa-building" style="margin-right:8px;"></i>Currently in Building</div>
            <div class="employee-grid" id="tv-employee-grid">
                <div class="empty-message">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading...
                </div>
            </div>
        </div>
        <div>
            <div class="section-title"><i class="fas fa-history" style="margin-right:8px;"></i>Recent Activity</div>
            <div class="activity-feed" id="tv-activity-feed">
                <div class="empty-message">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="tv-footer">
        <span id="tv-connection-status"><i class="fas fa-sync-alt fa-spin"></i> Connecting...</span>
        <span id="tv-last-update">--</span>
    </div>

    <script>
    (function() {
        var streamUrl = '<?= esc($stream_url ?? '', 'js'); ?>';
        var evtSource = null;

        // Clock
        function updateClock() {
            var now = new Date();
            var h = String(now.getHours()).padStart(2, '0');
            var m = String(now.getMinutes()).padStart(2, '0');
            var s = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('tv-clock').textContent = h + ':' + m + ':' + s;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function getInitials(first, last) {
            return ((first || '').charAt(0) + (last || '').charAt(0)).toUpperCase();
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

        function updateTV(data) {
            // Stats
            updateStat('tv-stat-total', data.total_staff);
            updateStat('tv-stat-in', data.clocked_in);
            updateStat('tv-stat-out', data.clocked_out);
            updateStat('tv-stat-absent', data.absent);

            // Employee grid
            var gridHtml = '';
            if (data.in_building && data.in_building.length > 0) {
                data.in_building.forEach(function(emp) {
                    var avatar = '';
                    if (emp.profile_photo && emp.profile_photo !== '' && emp.profile_photo !== 'default.jpg') {
                        avatar = '<img src="<?= site_url(); ?>uploads/profile/' + emp.profile_photo + '" class="emp-avatar" alt="">';
                    } else {
                        avatar = '<div class="emp-avatar-placeholder">' + getInitials(emp.first_name, emp.last_name) + '</div>';
                    }
                    gridHtml += '<div class="emp-tile fade-in">' +
                        avatar +
                        '<div class="emp-name">' + (emp.first_name || '') + ' ' + (emp.last_name || '') + '</div>' +
                        '<div class="emp-dept">' + (emp.department_name || '') + '</div>' +
                        '<div class="emp-time"><i class="fas fa-clock" style="margin-right:4px;"></i>' + formatTime(emp.clock_in) + '</div>' +
                    '</div>';
                });
            } else {
                gridHtml = '<div class="empty-message"><i class="fas fa-door-open"></i>No one currently in the building</div>';
            }
            document.getElementById('tv-employee-grid').innerHTML = gridHtml;

            // Activity feed
            var feedHtml = '';
            if (data.recent_events && data.recent_events.length > 0) {
                data.recent_events.forEach(function(evt) {
                    var isOut = (evt.clock_in_out == 1);
                    var avatar = '';
                    if (evt.profile_photo && evt.profile_photo !== '' && evt.profile_photo !== 'default.jpg') {
                        avatar = '<img src="<?= site_url(); ?>uploads/profile/' + evt.profile_photo + '" class="feed-avatar" alt="">';
                    } else {
                        avatar = '<div class="feed-avatar-placeholder">' + getInitials(evt.first_name, evt.last_name) + '</div>';
                    }
                    feedHtml += '<div class="feed-item fade-in">' +
                        avatar +
                        '<div class="feed-info">' +
                            '<div class="feed-name">' + (evt.first_name || '') + ' ' + (evt.last_name || '') + '</div>' +
                            '<div class="feed-time">' + (isOut ? formatTime(evt.clock_out) : formatTime(evt.clock_in)) + '</div>' +
                        '</div>' +
                        '<span class="feed-badge ' + (isOut ? 'badge-out' : 'badge-in') + '">' + (isOut ? 'Out' : 'In') + '</span>' +
                    '</div>';
                });
            } else {
                feedHtml = '<div class="empty-message"><i class="fas fa-clock"></i>No activity today</div>';
            }
            document.getElementById('tv-activity-feed').innerHTML = feedHtml;

            // Footer
            document.getElementById('tv-connection-status').innerHTML = '<i class="fas fa-check-circle" style="color:#2ecc71;margin-right:4px;"></i> Connected';
            document.getElementById('tv-last-update').textContent = 'Updated: ' + (data.updated_at || '--:--:--');
        }

        function updateStat(id, value) {
            var el = document.getElementById(id);
            if (el && el.textContent != value) {
                el.textContent = value;
                el.classList.add('fade-in');
                setTimeout(function() { el.classList.remove('fade-in'); }, 600);
            }
        }

        function connectSSE() {
            if (evtSource) evtSource.close();

            document.getElementById('tv-connection-status').innerHTML = '<i class="fas fa-sync-alt fa-spin" style="margin-right:4px;"></i> Connecting...';
            evtSource = new EventSource(streamUrl);

            evtSource.onmessage = function(event) {
                try {
                    var data = JSON.parse(event.data);
                    updateTV(data);
                } catch (e) {
                    console.error('SSE parse error:', e);
                }
            };

            evtSource.onerror = function() {
                document.getElementById('tv-connection-status').innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#f39c12;margin-right:4px;"></i> Reconnecting...';
                evtSource.close();
                setTimeout(connectSSE, 5000);
            };
        }

        connectSSE();
    })();
    </script>
</body>
</html>
