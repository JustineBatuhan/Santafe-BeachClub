<?php
require_once __DIR__ . '/../backend/helpers/admin_auth_check.php';
$admin = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial & Occupancy Reports — Santa Fe Beach Club</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=4">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="config.js"></script>
    <style>
        .report-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .kpi-mini { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 18px 20px; box-shadow: var(--shadow-sm); }
        .kpi-mini .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); margin-bottom: 6px; }
        .kpi-mini .value { font-family: var(--font-heading); font-size: 26px; font-weight: 700; color: var(--text-main); }
        .kpi-mini .sub   { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
        
        .loading { text-align: center; padding: 20px; color: var(--text-muted); font-size: 14px; }
        .error { color: #EF4444; text-align: center; padding: 20px; }

        @media (max-width: 992px) {
            .report-kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .two-col { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php $active_page = 'reports'; include __DIR__ . '/partials/_sidebar.php'; ?>

    <main class="admin-main">
        <?php
        $page_title = 'Financial & Business Reports';
        $page_subtitle = 'Audit trails, income performance, and guest volume metrics';
        $header_extra_html = '
            <div style="display:flex; gap:8px; align-items:center;">
                <a href="../backend/api/availability.php?action=export_report_csv&type=bookings" class="header-icon-btn" style="width:auto; padding:0 14px; height:40px; font-size:12.5px; font-weight:700; text-decoration:none; gap:6px; color:#334155;" title="Export Bookings CSV">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Bookings CSV</span>
                </a>
                <a href="../backend/api/availability.php?action=export_report_csv&type=payments" class="header-icon-btn" style="width:auto; padding:0 14px; height:40px; font-size:12.5px; font-weight:700; text-decoration:none; gap:6px; color:#059669; border-color:#A7F3D0; background:#ECFDF5;" title="Export Payments CSV">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Payments CSV</span>
                </a>
                <button class="btn-primary" onclick="window.print()" style="height:40px; padding:0 16px; border-radius:12px; font-size:13px; font-weight:700;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Print
                </button>
            </div>
        ';
        include __DIR__ . '/partials/_page_header.php';
        ?>

        <div class="admin-body">
            
            <div id="api-connection-error" style="display: none; background: #FEF2F2; color: #991B1B; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #F87171;">
                <strong>⚠️ API Connection Failed!</strong> Could not connect to the local Flask API. Please ensure your PC is running <code>start_tunnel.bat</code> and that the URL in <code>config.js</code> is correct.
            </div>

            <!-- KPI Strip -->
            <div class="report-kpi-grid">
                <div class="kpi-mini">
                    <div class="label">Total Paid Revenue</div>
                    <div class="value" id="kpi-revenue" style="color:var(--green);">...</div>
                    <div class="sub">All-time verified payments</div>
                </div>
                <div class="kpi-mini">
                    <div class="label">Total Bookings</div>
                    <div class="value" id="kpi-bookings">...</div>
                    <div class="sub"><span id="kpi-pending" style="color:var(--orange); font-weight:600;">...</span> currently pending</div>
                </div>
                <div class="kpi-mini">
                    <div class="label">Total Guests Hosted</div>
                    <div class="value" id="kpi-guests" style="color:var(--blue);">...</div>
                    <div class="sub">Across all stays</div>
                </div>
                <div class="kpi-mini">
                    <div class="label">Average Stay</div>
                    <div class="value" style="color:var(--primary);"><span id="kpi-avg-stay">...</span></div>
                    <div class="sub">Excluding cancellations</div>
                </div>
            </div>

            <!-- Monthly Revenue & Status Breakdown -->
            <div class="two-col">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <h3>Monthly Revenue Trend (Last 6 Months)</h3>
                            <p style="font-size:12px; color:var(--text-muted); margin-top:2px;">Verified revenue grouped by billing month</p>
                        </div>
                    </div>
                    <div id="chart-rev-container" style="height:230px; position:relative;">
                        <div class="loading" id="loading-rev">Loading chart data...</div>
                        <canvas id="monthlyRevChart" style="display:none;"></canvas>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <h3>Reservation Status Breakdown</h3>
                            <p style="font-size:12px; color:var(--text-muted); margin-top:2px;">Overall bookings outcome distribution</p>
                        </div>
                    </div>
                    <div id="chart-status-container" style="height:230px; display:flex; align-items:center; justify-content:center;">
                        <div class="loading" id="loading-status">Loading chart data...</div>
                        <canvas id="statusBreakdownChart" style="display:none;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Revenue by Payment Channel & Top Accommodations -->
            <div class="two-col">
                <!-- Payment Channels Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <h3>Payment Methods</h3>
                            <p style="font-size:12px; color:var(--text-muted); margin-top:2px;">Settlement volume by gateway/method</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Channel / Method</th>
                                    <th>Transactions</th>
                                    <th style="text-align:right;">Total Collected</th>
                                </tr>
                            </thead>
                            <tbody id="payment-methods-tbody">
                                <tr><td colspan="3" class="loading">Fetching data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Accommodations Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <h3>Top Performing Accommodations</h3>
                            <p style="font-size:12px; color:var(--text-muted); margin-top:2px;">Most booked units by volume</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Unit Name</th>
                                    <th>Stays</th>
                                    <th style="text-align:right;">Guests Hosted</th>
                                </tr>
                            </thead>
                            <tbody id="top-rooms-tbody">
                                <tr><td colspan="3" class="loading">Fetching data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(15, 23, 42, 0.06)';
    const tickColor = isDark ? '#94A3B8' : '#64748B';

    // Proxy to Python Analytics Service (via PHP analytics_proxy.php)
    async function fetchAPI(endpoint) {
        try {
            const action = endpoint.replace('/api/', '').replace(/^\//, '');
            const res = await fetch(`../backend/api/analytics_proxy.php?action=${encodeURIComponent(action)}`, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('API Error: ' + res.statusText);
            return await res.json();
        } catch (error) {
            console.error('Fetch error:', error);
            const err = document.getElementById('api-connection-error');
            if (err) err.style.display = 'block';
            throw error;
        }
    }


    async function loadDashboardData() {
        try {
            // 1. Dashboard Stats
            const stats = await fetchAPI('/api/dashboard-stats');
            document.getElementById('kpi-revenue').innerText = '₱' + Number(stats.total_revenue).toLocaleString();
            document.getElementById('kpi-bookings').innerText = Number(stats.total_bookings).toLocaleString();
            document.getElementById('kpi-pending').innerText = stats.pending_bookings;
            document.getElementById('kpi-guests').innerText = Number(stats.total_guests).toLocaleString();
            document.getElementById('kpi-avg-stay').innerText = stats.avg_stay;

            // 2. Monthly Revenue Chart
            const revData = await fetchAPI('/api/monthly-revenue');
            document.getElementById('loading-rev').style.display = 'none';
            const revCanvas = document.getElementById('monthlyRevChart');
            revCanvas.style.display = 'block';
            new Chart(revCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: revData.labels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: revData.revenue,
                        backgroundColor: 'rgba(132, 86, 60, 0.85)',
                        hoverBackgroundColor: '#84563C',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ₱' + Number(c.parsed.y).toLocaleString() } } },
                    scales: {
                        x: { grid: { color: gridColor }, ticks: { color: tickColor, font: { family: 'Plus Jakarta Sans', size: 11.5 } } },
                        y: { grid: { color: gridColor }, ticks: { color: tickColor, font: { family: 'Plus Jakarta Sans', size: 11.5 }, callback: v => '₱' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v) }, beginAtZero: true }
                    }
                }
            });

            // 3. Status Breakdown Chart
            const statusData = await fetchAPI('/api/status-breakdown');
            document.getElementById('loading-status').style.display = 'none';
            const statusCanvas = document.getElementById('statusBreakdownChart');
            statusCanvas.style.display = 'block';
            new Chart(statusCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Checked In', 'Checked Out', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [
                            statusData['Checked In'] || 0,
                            statusData['Checked Out'] || 0,
                            statusData['Pending'] || 0,
                            statusData['Cancelled'] || 0
                        ],
                        backgroundColor: ['#10B981', '#64748B', '#F59E0B', '#EF4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { position: 'bottom', labels: { color: tickColor, font: { family: 'Plus Jakarta Sans', size: 11.5, weight: '600' }, padding: 14, usePointStyle: true } } }
                }
            });

            // 4. Payment Methods Table
            const payments = await fetchAPI('/api/payment-methods');
            const pTbody = document.getElementById('payment-methods-tbody');
            if (payments.length > 0) {
                pTbody.innerHTML = payments.map(p => `
                    <tr>
                        <td style="font-weight:600;">${p.payment_method || 'Front Desk Cash'}</td>
                        <td style="color:var(--text-muted);">${p.cnt} payments</td>
                        <td style="text-align:right; font-weight:700; color:var(--text-main);">₱${Number(p.total).toLocaleString()}</td>
                    </tr>
                `).join('');
            } else {
                pTbody.innerHTML = '<tr><td colspan="3" class="loading">No payment records.</td></tr>';
            }

            // 5. Top Accommodations Table
            const rooms = await fetchAPI('/api/top-accommodations');
            const rTbody = document.getElementById('top-rooms-tbody');
            if (rooms.length > 0) {
                rTbody.innerHTML = rooms.map(r => `
                    <tr>
                        <td style="font-weight:600;">${r.accommodation_name}</td>
                        <td style="color:var(--text-muted);">${r.cnt} stays</td>
                        <td style="text-align:right; font-weight:700; color:var(--primary);">${r.guests} guests</td>
                    </tr>
                `).join('');
            } else {
                rTbody.innerHTML = '<tr><td colspan="3" class="loading">No room booking records.</td></tr>';
            }

        } catch (e) {
            console.error("Failed to load dashboard data.", e);
            document.querySelectorAll('.loading').forEach(el => el.innerHTML = '<span class="error">Failed to load data.</span>');
        }
    }

    // Trigger load on startup
    window.addEventListener('DOMContentLoaded', loadDashboardData);
    </script>
</body>
</html>
