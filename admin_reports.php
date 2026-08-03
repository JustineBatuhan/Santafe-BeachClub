<?php
require_once 'admin_auth_check.php';
require_once 'db.php';

$admin = $_SESSION['admin_username'];

// ── Full Report Metrics ────────────────────────────────────────────────────────
$total_revenue  = (float)$conn->query("SELECT COALESCE(SUM(amount),0) AS v FROM payments WHERE status='verified'")->fetch_assoc()['v'];
$total_bookings = (int)$conn->query("SELECT COUNT(*) AS v FROM bookings")->fetch_assoc()['v'];
$checked_in     = (int)$conn->query("SELECT COUNT(*) AS v FROM bookings WHERE status='Checked In'")->fetch_assoc()['v'];
$checked_out    = (int)$conn->query("SELECT COUNT(*) AS v FROM bookings WHERE status='Checked Out'")->fetch_assoc()['v'];
$cancelled      = (int)$conn->query("SELECT COUNT(*) AS v FROM bookings WHERE status='Cancelled'")->fetch_assoc()['v'];
$pending        = (int)$conn->query("SELECT COUNT(*) AS v FROM bookings WHERE status='Pending'")->fetch_assoc()['v'];

$total_guests   = (int)$conn->query("SELECT COALESCE(SUM(guests_count),0) AS v FROM bookings")->fetch_assoc()['v'];
$avg_stay       = $conn->query("SELECT ROUND(AVG(DATEDIFF(check_out,check_in)),1) AS v FROM bookings WHERE status != 'Cancelled'")->fetch_assoc()['v'];
$avg_stay       = $avg_stay ?? 0;

// Revenue by month (last 6 months)
$monthly_rev = [];
$monthly_labels = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $val = (float)$conn->query("SELECT COALESCE(SUM(amount),0) AS v FROM payments WHERE status='verified' AND DATE_FORMAT(paid_at,'%Y-%m')='$m'")->fetch_assoc()['v'];
    $monthly_labels[] = $label;
    $monthly_rev[]    = $val;
}

// Top rooms by booking count
$top_rooms = $conn->query("SELECT accommodation_name, COUNT(*) AS cnt, SUM(guests_count) AS guests FROM bookings GROUP BY accommodation_name ORDER BY cnt DESC LIMIT 8");

// Revenue by payment method
$pay_methods = $conn->query("SELECT payment_method, COUNT(*) AS cnt, SUM(amount) AS total FROM payments WHERE status='verified' GROUP BY payment_method ORDER BY total DESC");

// Guest types breakdown
$guest_types = $conn->query("SELECT guest_type, COUNT(*) AS cnt FROM bookings GROUP BY guest_type ORDER BY cnt DESC");

// Recent paid payments
$recent_payments = $conn->query("SELECT guest_name, amount, payment_method, paid_at FROM payments WHERE status='verified' ORDER BY id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — Santa Fe Beach Club</title>
    <link rel="stylesheet" href="admin.css?v=2">
    <link rel="stylesheet" href="dashboard.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .report-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .kpi-mini { background: white; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .kpi-mini .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-muted); margin-bottom: 6px; }
        .kpi-mini .value { font-size: 26px; font-weight: 700; color: var(--text-main); }
        .kpi-mini .sub   { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
        .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 28px; }
        
        /* Dark Mode Overrides for inline styles */
        [data-theme="dark"] .kpi-mini { background: var(--card-bg, #1A1D27); box-shadow: none; border: 1px solid var(--border, #2D3748); }
        [data-theme="dark"] .kpi-mini .label { color: var(--text-muted, #94A3B8); }
        [data-theme="dark"] .kpi-mini .value { color: var(--text-main, #E2E8F0); }
        [data-theme="dark"] .kpi-mini .sub { color: var(--text-muted, #94A3B8); }
    </style>
</head>
<body>
    <?php $active_page = 'reports'; include '_sidebar.php'; ?>

    <!-- Main Dashboard Panel -->
    <main class="main-content">
        <?php
        $page_title = 'Business Analytics';
        $page_subtitle = 'Comprehensive overview of hotel performance.';
        $header_extra_html = '
            <button class="btn-primary" onclick="window.print()" style="padding: 8px 16px; display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print / Export
            </button>
        ';
        include '_page_header.php';
        ?>

    <div class="admin-body">
        <!-- KPI Strip -->
        <div class="report-kpi-grid">
            <div class="kpi-mini"><div class="label">Total Revenue</div><div class="value">₱<?php echo number_format($total_revenue,0); ?></div><div class="sub">All-time paid</div></div>
            <div class="kpi-mini"><div class="label">Total Bookings</div><div class="value"><?php echo number_format($total_bookings); ?></div><div class="sub"><?php echo $pending; ?> pending</div></div>
            <div class="kpi-mini"><div class="label">Total Guests Served</div><div class="value"><?php echo number_format($total_guests); ?></div><div class="sub">Across all bookings</div></div>
            <div class="kpi-mini"><div class="label">Avg. Stay Duration</div><div class="value"><?php echo $avg_stay; ?> days</div><div class="sub">Excl. cancelled</div></div>
        </div>

        <!-- Monthly Revenue + Booking Status -->
        <div class="two-col">
            <div class="admin-card">
                <div class="admin-card-header"><h3>Monthly Revenue (Last 6 Months)</h3></div>
                <div style="height:220px;"><canvas id="monthlyRevChart"></canvas></div>
            </div>
            <div class="admin-card">
                <div class="admin-card-header"><h3>Booking Status Breakdown</h3></div>
                <div style="height:220px;display:flex;align-items:center;justify-content:center;"><canvas id="statusDonut"></canvas></div>
            </div>
        </div>

        <!-- Top Rooms + Guest Types + Payment Methods -->
        <div class="three-col">
            <!-- Top Rooms -->
            <div class="admin-card">
                <div class="admin-card-header"><h3>Top Rooms by Bookings</h3></div>
                <table class="admin-table">
                    <thead><tr><th>Room</th><th style="text-align:right;">Bookings</th><th style="text-align:right;">Guests</th></tr></thead>
                    <tbody>
                    <?php
                    $top_rooms->data_seek(0);
                    while ($r = $top_rooms->fetch_assoc()):
                    ?>
                    <tr>
                        <td style="font-size:13px;"><?php echo htmlspecialchars($r['accommodation_name']); ?></td>
                        <td style="text-align:right;font-weight:600;"><?php echo $r['cnt']; ?></td>
                        <td style="text-align:right;color:var(--text-muted);"><?php echo $r['guests']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Guest Types -->
            <div class="admin-card">
                <div class="admin-card-header"><h3>Guest Type Distribution</h3></div>
                <table class="admin-table">
                    <thead><tr><th>Guest Type</th><th style="text-align:right;">Count</th></tr></thead>
                    <tbody>
                    <?php while ($g = $guest_types->fetch_assoc()): ?>
                    <tr>
                        <td style="font-size:13px;"><?php echo htmlspecialchars($g['guest_type']); ?></td>
                        <td style="text-align:right;font-weight:600;"><?php echo $g['cnt']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Payment Methods -->
            <div class="admin-card">
                <div class="admin-card-header"><h3>Revenue by Payment Method</h3></div>
                <table class="admin-table">
                    <thead><tr><th>Method</th><th style="text-align:right;">Txns</th><th style="text-align:right;">Revenue</th></tr></thead>
                    <tbody>
                    <?php while ($pm = $pay_methods->fetch_assoc()): ?>
                    <tr>
                        <td style="font-size:13px;"><?php echo htmlspecialchars($pm['payment_method']); ?></td>
                        <td style="text-align:right;color:var(--text-muted);"><?php echo $pm['cnt']; ?></td>
                        <td style="text-align:right;font-weight:600;">₱<?php echo number_format($pm['total'],0); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Recent Paid Transactions</h3>
                <a href="payments.php">View all →</a>
            </div>
            <table class="admin-table">
                <thead><tr><th>Guest</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                <tbody>
                <?php while ($pay = $recent_payments->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($pay['guest_name']); ?></td>
                    <td style="color:var(--green);font-weight:700;">₱<?php echo number_format($pay['amount'],2); ?></td>
                    <td style="color:var(--text-muted);"><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                    <td style="color:var(--text-muted);font-size:13px;"><?php echo date('M j, Y g:i a', strtotime($pay['paid_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

<script>
const brown = '#7C533C', gridColor = 'rgba(0,0,0,0.05)';
const monthlyRev = <?php echo json_encode($monthly_rev); ?>;
const monthlyLabels = <?php echo json_encode($monthly_labels); ?>;

// Monthly Revenue
new Chart(document.getElementById('monthlyRevChart'), {
    type: 'bar',
    data: {
        labels: monthlyLabels,
        datasets: [{ label: 'Revenue (₱)', data: monthlyRev, backgroundColor: 'rgba(124,83,60,0.75)', borderRadius: 6, borderSkipped: false }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { font: { family: 'Outfit', size: 11 }, color: '#94A3B8' } },
            y: { grid: { color: gridColor }, ticks: { callback: v => '₱' + v.toLocaleString(), font: { family: 'Outfit', size: 11 }, color: '#94A3B8' }, beginAtZero: true }
        }
    }
});

// Booking status donut
new Chart(document.getElementById('statusDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Pending','Checked In','Checked Out','Cancelled'],
        datasets: [{ data: [<?php echo "$pending,$checked_in,$checked_out,$cancelled"; ?>], backgroundColor: ['#F59E0B','#10B981','#94A3B8','#EF4444'], borderWidth: 0, hoverOffset: 6 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { font: { family: 'Outfit', size: 11 }, padding: 12 } } }
    }
});
</script>
<script src="sidebar-toggle.js"></script>
</body>
</html>
