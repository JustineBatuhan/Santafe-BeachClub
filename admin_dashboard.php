<?php
require_once 'admin_auth_check.php';
require_once 'db.php';

$admin = $_SESSION['admin_username'];

// ── KPI Metrics ────────────────────────────────────────────────────────────────
$total_revenue    = $conn->query("SELECT COALESCE(SUM(amount),0) AS v FROM payments WHERE status='verified'")->fetch_assoc()['v'];
$daily_revenue    = $conn->query("SELECT COALESCE(SUM(amount),0) AS v FROM payments WHERE status='verified' AND DATE(paid_at) = CURDATE()")->fetch_assoc()['v'];
$weekly_revenue   = $conn->query("SELECT COALESCE(SUM(amount),0) AS v FROM payments WHERE status='verified' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['v'];
$monthly_revenue  = $conn->query("SELECT COALESCE(SUM(amount),0) AS v FROM payments WHERE status='verified' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetch_assoc()['v'];
$total_bookings   = $conn->query("SELECT COUNT(*) AS v FROM bookings")->fetch_assoc()['v'];
$occupied_rooms   = $conn->query("SELECT COUNT(DISTINCT room_id) AS v FROM bookings WHERE status='Checked In' AND room_id IS NOT NULL")->fetch_assoc()['v'];
$total_rooms      = $conn->query("SELECT COUNT(*) AS v FROM rooms")->fetch_assoc()['v'];
$checkins_today   = $conn->query("SELECT COUNT(*) AS v FROM bookings WHERE DATE(check_in)=CURDATE()")->fetch_assoc()['v'];
$pending_bookings = $conn->query("SELECT COUNT(*) AS v FROM bookings WHERE status='Pending'")->fetch_assoc()['v'];
$total_staff      = $conn->query("SELECT COUNT(*) AS v FROM admins")->fetch_assoc()['v'];
$occupancy_rate   = $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100) : 0;

// ── Revenue Last 7 Days (for line chart) ───────────────────────────────────────
$revenue_labels = [];
$revenue_data   = [];
$temp_revenue   = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('M j', strtotime("-$i days"));
    $revenue_labels[] = $label;
    $temp_revenue[$date] = 0.0;
}

$rev_query = $conn->query("
    SELECT DATE(paid_at) AS pay_date, COALESCE(SUM(amount), 0) AS total 
    FROM payments 
    WHERE status = 'verified' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(paid_at)
");

if ($rev_query) {
    while ($row = $rev_query->fetch_assoc()) {
        $temp_revenue[$row['pay_date']] = (float)$row['total'];
    }
}

foreach ($revenue_labels as $idx => $lbl) {
    $days_ago = 6 - $idx;
    $date = date('Y-m-d', strtotime("-$days_ago days"));
    $revenue_data[] = $temp_revenue[$date] ?? 0.0;
}

// ── Booking Status Distribution (for doughnut) ─────────────────────────────────
$status_data = [];
foreach (['Pending','Checked In','Checked Out','Cancelled'] as $s) {
    $status_data[$s] = (int)$conn->query("SELECT COUNT(*) AS v FROM bookings WHERE status='$s'")->fetch_assoc()['v'];
}

// ── Room Type Occupancy (for bar chart) ────────────────────────────────────────
$occ_labels = $occ_data = [];
$occ_q = $conn->query("
    SELECT 
        r.type,
        COUNT(DISTINCT r.id) AS total_rooms,
        COUNT(DISTINCT CASE WHEN b.status = 'Checked In' THEN r.id END) AS occupied_rooms
    FROM rooms r
    LEFT JOIN bookings b ON r.id = b.room_id
    GROUP BY r.type
    ORDER BY r.type
");

if ($occ_q) {
    while ($row = $occ_q->fetch_assoc()) {
        $occ_labels[] = ucwords(str_replace('_', ' ', $row['type']));
        $total = (int)$row['total_rooms'];
        $used = (int)$row['occupied_rooms'];
        $occ_data[] = $total > 0 ? round(($used / $total) * 100) : 0;
    }
}

// ── Recent Bookings ───────────────────────────────────────────────────────────
$recent_bookings = $conn->query("SELECT id, guest_name, accommodation_name, check_in, check_out, status FROM bookings ORDER BY id DESC LIMIT 8");

// ── Recent Activity ───────────────────────────────────────────────────────────
$recent_logs = $conn->query("SELECT admin_username, action, details, created_at FROM activity_logs ORDER BY id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Santa Fe Beach Club</title>
    <link rel="stylesheet" href="admin.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- ═══ SIDEBAR ═══════════════════════════════════════════════════════════════ -->
<?php
$active_page = 'dashboard';
require_once '_sidebar.php';
?>
<!-- ═══ MAIN ════════════════════════════════════════════════════════════════════ -->
<div class="admin-main">

    <!-- Top Bar -->
    <header class="admin-topbar">
        <button class="sidebar-toggle-btn" aria-label="Toggle menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="topbar-title">Overview</div>
        <div class="topbar-right">
                <!-- Live Search -->
                <div class="admin-search-wrapper">
                    <svg class="admin-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="adminGlobalSearch" class="admin-search-input" placeholder="Search guests, rooms…" oninput="adminSearch(this.value)" autocomplete="off">
                    <div id="adminSearchResults" class="admin-search-dropdown"></div>
                </div>
                <span class="topbar-badge">Admin</span>
                <span class="topbar-date"><?php echo date('l, F j, Y'); ?></span>
                <a href="dashboard.php" class="topbar-logout" title="Switch to Reception View">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/></svg>
                    Reception View
                </a>
            </div>
        </header>

    <!-- Body -->
    <div class="admin-body">

        <div class="page-heading">
            <h1>Good <?php echo (date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening')); ?>, <?php echo htmlspecialchars($admin); ?> 👋</h1>
            <p>Here's what's happening at Santa Fe Beach Club today.</p>
        </div>

        <!-- KPI Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-card-label">Daily Revenue</div>
                    <div class="stat-card-value">₱<?php echo number_format($daily_revenue, 0); ?></div>
                    <div class="stat-card-sub">Today</div>
                </div>
                <div class="stat-icon" style="background-color: #E8F5E9; color: #2E7D32;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-card-label">Weekly Revenue</div>
                    <div class="stat-card-value">₱<?php echo number_format($weekly_revenue, 0); ?></div>
                    <div class="stat-card-sub">Last 7 days</div>
                </div>
                <div class="stat-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-card-label">Monthly Revenue</div>
                    <div class="stat-card-value">₱<?php echo number_format($monthly_revenue, 0); ?></div>
                    <div class="stat-card-sub">Last 30 days</div>
                </div>
                <div class="stat-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-card-label">Total Revenue</div>
                    <div class="stat-card-value">₱<?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-card-sub">All-time paid</div>
                </div>
                <div class="stat-icon brown">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-card-label">Total Bookings</div>
                    <div class="stat-card-value"><?php echo number_format($total_bookings); ?></div>
                    <div class="stat-card-sub"><?php echo $pending_bookings; ?> pending</div>
                </div>
                <div class="stat-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-card-label">Occupancy Rate</div>
                    <div class="stat-card-value"><?php echo $occupancy_rate; ?>%</div>
                    <div class="stat-card-sub"><?php echo $occupied_rooms; ?> of <?php echo $total_rooms; ?> rooms</div>
                </div>
                <div class="stat-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-card-label">Check-ins Today</div>
                    <div class="stat-card-value"><?php echo $checkins_today; ?></div>
                    <div class="stat-card-sub"><?php echo $total_staff; ?> staff accounts</div>
                </div>
                <div class="stat-icon orange">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <!-- Revenue Line Chart -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Revenue (Last 7 Days)</h3>
                        <p>Daily payment totals from confirmed bookings</p>
                    </div>
                </div>
                <div class="chart-container" style="height:220px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Booking Status Doughnut -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Booking Status</h3>
                        <p>All-time distribution</p>
                    </div>
                </div>
                <div class="chart-container" style="height:220px; display:flex; align-items:center; justify-content:center;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Occupancy Chart -->
        <div class="admin-card" style="margin-bottom:28px;">
            <div class="admin-card-header">
                <h3>Room Type Occupancy</h3>
                <a href="accommodations.php">Manage Rooms →</a>
            </div>
            <div class="chart-container" style="height:180px;">
                <canvas id="occupancyChart"></canvas>
            </div>
        </div>

        <!-- Lower Grid: Recent Bookings + Activity Feed -->
        <div class="lower-grid">

            <!-- Recent Bookings -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Recent Bookings</h3>
                    <a href="admin_reservations.php">View all →</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($b = $recent_bookings->fetch_assoc()): ?>
                        <tr>
                            <td style="color:var(--text-muted);">#<?php echo $b['id']; ?></td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($b['guest_name']); ?></td>
                            <td style="color:var(--text-muted);"><?php echo htmlspecialchars($b['accommodation_name']); ?></td>
                            <td style="color:var(--text-muted);"><?php echo date('M j', strtotime($b['check_in'])); ?></td>
                            <td>
                                <?php
                                $sc = ['Pending'=>'badge-pending','Checked In'=>'badge-checkedin','Checked Out'=>'badge-checkedout','Cancelled'=>'badge-cancelled'];
                                $cls = $sc[$b['status']] ?? 'badge-pending';
                                ?>
                                <span class="badge <?php echo $cls; ?>"><?php echo $b['status']; ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Activity Feed -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Recent Activity</h3>
                    <a href="admin_logs.php">View all →</a>
                </div>
                <?php if ($recent_logs->num_rows === 0): ?>
                    <div class="empty-state">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <p>No activity yet.</p>
                    </div>
                <?php else: ?>
                <ul class="activity-list">
                    <?php while ($log = $recent_logs->fetch_assoc()):
                        $dot = 'default';
                        if (stripos($log['action'], 'login') !== false)   $dot = 'login';
                        if (stripos($log['action'], 'booking') !== false) $dot = 'booking';
                        if (stripos($log['action'], 'payment') !== false) $dot = 'payment';
                    ?>
                    <li class="activity-item">
                        <div class="activity-dot <?php echo $dot; ?>"></div>
                        <div>
                            <div class="activity-text">
                                <strong><?php echo htmlspecialchars($log['admin_username']); ?></strong>
                                — <?php echo htmlspecialchars($log['action']); ?>
                                <?php if ($log['details']): ?><span style="color:var(--text-muted);"> · <?php echo htmlspecialchars($log['details']); ?></span><?php endif; ?>
                            </div>
                            <div class="activity-meta"><?php echo date('M j, g:i a', strtotime($log['created_at'])); ?></div>
                        </div>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php endif; ?>
            </div>

        </div><!-- /lower-grid -->
    </div><!-- /admin-body -->
</div><!-- /admin-main -->

<script>
const brown = '#7C533C', brown2 = '#a07055', gridColor = 'rgba(0,0,0,0.05)';

// ── Live Search Implementation ───────────────────────────────────────────────
const statusColors = {
    'Pending':     'background:#FFF7ED;color:#C2410C',
    'Checked In':  'background:#ECFDF5;color:#065F46',
    'Checked Out': 'background:#F1F5F9;color:#475569',
    'Cancelled':   'background:#FEF2F2;color:#991B1B',
    'ready':       'background:#ECFDF5;color:#065F46',
    'occupied':    'background:#EFF6FF;color:#1D4ED8',
    'maintenance': 'background:#FFFBEB;color:#92400E',
};

let searchTimeout = null;

function adminSearch(q) {
    const dropdown = document.getElementById('adminSearchResults');
    q = q.trim();
    if (!q) { dropdown.classList.remove('open'); dropdown.innerHTML = ''; return; }

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetch('admin_search_api.php?q=' + encodeURIComponent(q))
        .then(res => res.json())
        .then(results => {
            if (!results.length) {
                dropdown.innerHTML = '<div class="search-no-results">No results found for "<strong>' + q + '</strong>"</div>';
                dropdown.classList.add('open');
                return;
            }

            const bookings = results.filter(r => r.type === 'booking');
            const rooms    = results.filter(r => r.type === 'room');
            let html = '';

            if (bookings.length) {
                html += '<div class="search-section-label">Bookings</div>';
                bookings.forEach(item => {
                    const initials = item.name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
                    const sc = statusColors[item.status] || '';
                    html += `<a class="search-result-item" href="${item.url}">
                        <div class="search-result-avatar">${initials}</div>
                        <div>
                            <div class="search-result-name">${item.name}</div>
                            <div class="search-result-meta">${item.sub}</div>
                        </div>
                        <span class="search-result-badge" style="${sc}">${item.status}</span>
                    </a>`;
                });
            }

            if (rooms.length) {
                html += '<div class="search-section-label">Rooms</div>';
                rooms.forEach(item => {
                    const sc = statusColors[item.status] || '';
                    html += `<a class="search-result-item" href="${item.url}">
                        <div class="search-result-avatar" style="background:#EFF6FF;color:#1D4ED8;">${item.id}</div>
                        <div>
                            <div class="search-result-name">${item.name}</div>
                            <div class="search-result-meta">${item.sub}</div>
                        </div>
                        <span class="search-result-badge" style="${sc}">${item.status}</span>
                    </a>`;
                });
            }

            dropdown.innerHTML = html;
            dropdown.classList.add('open');
        })
        .catch(err => {
            console.error('Search error:', err);
        });
    }, 200);
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.admin-search-wrapper')) {
        document.getElementById('adminSearchResults').classList.remove('open');
    }
});

// Re-open when clicking back into input
document.getElementById('adminGlobalSearch').addEventListener('focus', function() {
    if (this.value.trim()) adminSearch(this.value);
});

// Revenue Line Chart
const rCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(rCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($revenue_labels); ?>,
        datasets: [{
            label: 'Revenue (₱)',
            data: <?php echo json_encode($revenue_data); ?>,
            borderColor: brown,
            backgroundColor: 'rgba(124,83,60,0.08)',
            borderWidth: 2.5,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: brown,
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { font: { family: 'Outfit', size: 12 }, color: '#94A3B8' } },
            y: { grid: { color: gridColor }, ticks: { font: { family: 'Outfit', size: 12 }, color: '#94A3B8', callback: v => '₱' + v.toLocaleString() }, beginAtZero: true }
        }
    }
});

// Booking Status Doughnut
const sCtx = document.getElementById('statusChart').getContext('2d');
const statusData = <?php echo json_encode(array_values($status_data)); ?>;
const statusLabels = <?php echo json_encode(array_keys($status_data)); ?>;
new Chart(sCtx, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData,
            backgroundColor: ['#F59E0B','#10B981','#94A3B8','#EF4444'],
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { position: 'bottom', labels: { font: { family: 'Outfit', size: 11 }, padding: 14 } }
        }
    }
});

// Room Occupancy Bar Chart
const oCtx = document.getElementById('occupancyChart').getContext('2d');
new Chart(oCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($occ_labels); ?>,
        datasets: [{
            label: 'Occupancy %',
            data: <?php echo json_encode($occ_data); ?>,
            backgroundColor: 'rgba(124,83,60,0.75)',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
            x: { max: 100, grid: { color: gridColor }, ticks: { callback: v => v + '%', font: { family: 'Outfit', size: 12 }, color: '#94A3B8' } },
            y: { grid: { display: false }, ticks: { font: { family: 'Outfit', size: 12 }, color: '#374151' } }
        }
    }
});
</script>
<script src="sidebar-toggle.js"></script>
</body>
</html>
