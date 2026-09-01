<?php
require_once __DIR__ . '/../backend/helpers/auth_check.php';
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/../backend/helpers/room_status_helper.php';

// Calculate live dashboard metrics
$checkins_today = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(check_in) = CURDATE()")->fetch_assoc()['count'] ?? 0;
$checkouts_today = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(check_out) = CURDATE()")->fetch_assoc()['count'] ?? 0;
$pending_rsvps = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'Pending'")->fetch_assoc()['count'] ?? 0;
$total_guests = $conn->query("SELECT SUM(guests_count) as count FROM bookings WHERE status = 'Checked In'")->fetch_assoc()['count'] ?? 0;
$total_guests = (int)($total_guests ?? 0);

// Fetch current arrivals list
$bookings_query = $conn->query("SELECT id, guest_name, guest_type, accommodation_name, eta, status FROM bookings WHERE status = 'Pending' AND DATE(check_in) = CURDATE() ORDER BY id DESC");

$checked_in_room_ids = sf_get_checked_in_room_ids($conn);
$reserved_room_ids = sf_get_reserved_room_ids($conn);
$room_status_rows = [];
$occupied_rooms = 0;
$available_rooms = 0;

$rooms_query = $conn->query("SELECT id, room_number, status FROM rooms ORDER BY room_number ASC");
if ($rooms_query && $rooms_query->num_rows > 0) {
    while ($room = $rooms_query->fetch_assoc()) {
        $room['resolved_status'] = sf_resolve_room_display_status($room, $checked_in_room_ids, $reserved_room_ids);
        $room_status_rows[] = $room;

        if ($room['resolved_status'] === 'occupied') {
            $occupied_rooms++;
        } elseif ($room['resolved_status'] === 'ready') {
            $available_rooms++;
        }
    }
}

// Fetch room types for the booking form dropdown
$room_types = [];
$rt_q = $conn->query("SELECT id, name FROM room_types ORDER BY id ASC");
if ($rt_q && $rt_q->num_rows > 0) {
    while ($row = $rt_q->fetch_assoc()) {
        $room_types[] = $row;
    }
} else {
    $room_types = [
        ['id' => 1, 'name' => 'beachview_duplex'],
        ['id' => 2, 'name' => 'seaview_duplex'],
        ['id' => 3, 'name' => 'beach_villa'],
        ['id' => 4, 'name' => 'standard_room'],
        ['id' => 5, 'name' => 'standard_king']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reception Cockpit — Santa Fe Beach Club</title>
    <link rel="icon" type="image/jpeg" href="assets/logo.jpg">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=4">
</head>
<body>

    <?php $active_page = 'dashboard'; include __DIR__ . '/partials/_sidebar.php'; ?>

    <main class="main-content">
        <?php
        $page_title = 'Reception Console';
        $page_subtitle = "Real-time front desk operations • " . date('l, F j, Y');
        $header_extra_html = '
            <div class="search-wrapper">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" placeholder="Search arrivals..." class="search-input" id="dashboardSearch" onkeyup="filterArrivalsTable()">
            </div>
            <button class="btn-new-res-top" onclick="openReservationModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Reservation
            </button>
        ';
        include __DIR__ . '/partials/_page_header.php';
        ?>

        <div class="admin-body">
            <!-- ═══ METRICS ROW (6 KPIs) ═══ -->
            <section class="metrics-row">
                <div class="metric-card">
                    <div class="metric-info">
                        <h3>Today's Check-ins</h3>
                        <div class="metric-number" id="arrivalsCount"><?php echo (int)$checkins_today; ?></div>
                    </div>
                    <div class="metric-icon-wrapper brown">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h3>Today's Check-outs</h3>
                        <div class="metric-number"><?php echo (int)$checkouts_today; ?></div>
                    </div>
                    <div class="metric-icon-wrapper" style="background:#F1F5F9; color:#475569;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h3>Pending RSVPs</h3>
                        <div class="metric-number" style="color:var(--orange);"><?php echo (int)$pending_rsvps; ?></div>
                    </div>
                    <div class="metric-icon-wrapper orange">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h3>Occupied Units</h3>
                        <div class="metric-number" id="occupiedRoomsCount" style="color:var(--blue);"><?php echo (int)$occupied_rooms; ?></div>
                    </div>
                    <div class="metric-icon-wrapper blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/><circle cx="8" cy="14" r="2"/><circle cx="16" cy="14" r="2"/></svg>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h3>Ready / Available</h3>
                        <div class="metric-number" id="availableRoomsCount" style="color:var(--green);"><?php echo (int)$available_rooms; ?></div>
                    </div>
                    <div class="metric-icon-wrapper green">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h3>In-House Guests</h3>
                        <div class="metric-number"><?php echo (int)$total_guests; ?></div>
                    </div>
                    <div class="metric-icon-wrapper purple">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                </div>
            </section>

            <!-- ═══ LOWER GRID: ARRIVALS TABLE + LIVE ROOM MATRIX ═══ -->
            <section class="dashboard-grid">
                <!-- Today's Arrivals -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2>Expected Arrivals Today</h2>
                            <p style="font-size:12px; color:var(--text-muted); margin-top:2px;">Pending arrivals scheduled for today</p>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <select id="arrivalTypeFilter" onchange="filterArrivalsTable()" style="padding:6px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:12.5px; background:var(--input-bg); color:var(--text-main);">
                                <option value="">All Guest Tiers</option>
                                <option value="First Visit">First Visit</option>
                                <option value="Returning Guest">Returning Guest</option>
                                <option value="VIP Member">VIP Member</option>
                                <option value="Corporate">Corporate</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="arrivals-table" id="arrivalsTable">
                            <thead>
                                <tr>
                                    <th>Guest Details</th>
                                    <th>Accommodation</th>
                                    <th>ETA</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($bookings_query && $bookings_query->num_rows > 0) {
                                    $idx = 0;
                                    while ($row = $bookings_query->fetch_assoc()) {
                                        $row_id = 'row-' . $row['id'];
                                        $name_raw = $row['guest_name'];
                                        $type_raw = $row['guest_type'];
                                        $accommodation_raw = $row['accommodation_name'];
                                        $eta = htmlspecialchars($row['eta'] ?? '14:00');
                                        $name = htmlspecialchars($name_raw);
                                        $type = htmlspecialchars($type_raw);
                                        $accommodation = htmlspecialchars($accommodation_raw);
                                        
                                        $initials = '';
                                        $parts = explode(' ', $name_raw);
                                        foreach ($parts as $p) {
                                            $initials .= strtoupper($p[0] ?? '');
                                        }
                                        $initials = substr($initials, 0, 2);
                                        
                                        $btnClass = ($idx === 0) ? 'primary' : 'secondary';
                                        $idx++;
                                        
                                        echo "<tr id='{$row_id}' data-guest-type='" . htmlspecialchars($type_raw, ENT_QUOTES) . "'>";
                                        echo "<td>
                                                <div class='guest-profile'>
                                                    <div class='avatar-letter'>{$initials}</div>
                                                    <div class='guest-info'>
                                                        <h4>{$name}</h4>
                                                        <p>{$type}</p>
                                                    </div>
                                                </div>
                                              </td>";
                                        echo "<td style='color:var(--text-muted); font-size:13px;'>{$accommodation}</td>";
                                        echo "<td class='eta-cell' style='font-weight:600; color:var(--text-main);'>{$eta}</td>";
                                        echo "<td style='text-align: right;'>
                                                <button class='btn-table-action {$btnClass}' onclick='checkInGuest(" . json_encode($row_id) . ", " . json_encode($name_raw) . ", " . json_encode($accommodation_raw) . ")'>
                                                    <svg width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5'><polyline points='20 6 9 17 4 12'/></svg>
                                                    Express Check-in
                                                </button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='text-align: center; color: var(--text-muted); padding: 36px 20px;'>
                                        <svg width='32' height='32' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.5' style='opacity:0.4; margin-bottom:8px;'><circle cx='12' cy='12' r='10'/><polyline points='12 6 12 12 14 14'/></svg>
                                        <p>No pending arrivals scheduled for today</p>
                                    </td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Live Accommodation Status Grid -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2>Live Unit Availability</h2>
                            <p style="font-size:12px; color:var(--text-muted); margin-top:2px;">Real-time room status matrix</p>
                        </div>
                        <a href="admin_calendar" class="btn-view-all">Full Matrix &rarr;</a>
                    </div>

                    <div class="room-grid" id="roomStatusGrid">
                        <?php
                        if (count($room_status_rows) > 0) {
                            foreach ($room_status_rows as $room) {
                                $num = htmlspecialchars($room['room_number']);
                                $status = htmlspecialchars($room['resolved_status']);
                                echo "<div class='room-block {$status}' data-room='{$num}' data-status='{$status}' title='Room {$num} • " . ucfirst($status) . "'>{$num}</div>";
                            }
                        }
                        ?>
                    </div>

                    <div class="legend-container">
                        <div class="legend-item">
                            <span class="legend-dot ready"></span>
                            Ready (Available)
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot occupied"></span>
                            Occupied (In-House)
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot reserved"></span>
                            Reserved (Booked)
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot maintenance"></span>
                            Turnover / Maintenance
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Quick Reservation Modal -->
    <div class="modal-overlay" id="newReservationModal">
        <div class="modal-box">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <div>
                    <h3>Express Reservation</h3>
                    <p class="modal-sub">Create and immediately confirm front-desk bookings</p>
                </div>
                <button class="modal-close" onclick="closeReservationModal()">&times;</button>
            </div>
            
            <form onsubmit="handleNewReservationSubmit(event)">
                <div class="admin-form-group">
                    <label for="newGuestName">Guest Full Name</label>
                    <input type="text" id="newGuestName" required placeholder="e.g. Maria Santos">
                </div>
                
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="newGuestEmail">Email (Optional)</label>
                        <input type="email" id="newGuestEmail" placeholder="guest@example.com">
                    </div>
                    <div class="admin-form-group">
                        <label for="newGuestType">Guest Tier</label>
                        <select id="newGuestType">
                            <option value="First Visit">First Visit</option>
                            <option value="Returning Guest">Returning Guest</option>
                            <option value="VIP Member">VIP Member</option>
                            <option value="Corporate">Corporate</option>
                        </select>
                    </div>
                </div>

                <div class="admin-form-group">
                    <label for="newRoomType">Accommodation Type</label>
                    <select id="newRoomType">
                        <?php foreach ($room_types as $rt): ?>
                            <option value="<?php echo $rt['id']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $rt['name'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="newCheckin">Check-in Date</label>
                        <input type="date" id="newCheckin" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="admin-form-group">
                        <label for="newCheckout">Check-out Date</label>
                        <input type="date" id="newCheckout" required value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="newGuestsCount">Guests</label>
                        <input type="number" id="newGuestsCount" required min="1" max="12" value="2">
                    </div>
                    <div class="admin-form-group">
                        <label for="newETA">Expected Arrival (ETA)</label>
                        <input type="time" id="newETA" required value="14:00">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="submitReservation('Pending')">
                        Save as Pending
                    </button>
                    <button type="button" class="btn-primary" onclick="submitReservation('Checked In')" style="justify-content:center;">
                        Direct Check-in
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function openReservationModal() {
            document.getElementById('newReservationModal').classList.add('open');
        }

        function closeReservationModal() {
            document.getElementById('newReservationModal').classList.remove('open');
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeReservationModal();
        });

        function submitReservation(status) {
            const name = document.getElementById('newGuestName').value.trim();
            if (!name) {
                alert('Please enter guest name.');
                return;
            }
            const email = document.getElementById('newGuestEmail').value;
            const type = document.getElementById('newGuestType').value;
            const roomTypeId = document.getElementById('newRoomType').value;
            const checkin = document.getElementById('newCheckin').value;
            const checkout = document.getElementById('newCheckout').value;
            const guests = document.getElementById('newGuestsCount').value;
            const eta = document.getElementById('newETA').value;
            
            const formData = new FormData();
            formData.append('guest_name', name);
            formData.append('guest_email', email);
            formData.append('guest_type', type);
            formData.append('room_type_id', roomTypeId);
            formData.append('check_in', checkin);
            formData.append('check_out', checkout);
            formData.append('guests_count', guests);
            formData.append('eta', eta);
            formData.append('status', status);
            
            fetch('../backend/api/create_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || data.error || 'Failed to save reservation.'));
                }
            })
            .catch(err => {
                console.error('Error saving reservation:', err);
                alert('An error occurred while creating the reservation.');
            });
        }

        function checkInGuest(rowId, name, room) {
            const bookingId = rowId.split('-')[1];
            const formData = new FormData();
            formData.append('action', 'checkin');
            formData.append('booking_id', bookingId);
            formData.append('format', 'json');

            fetch('checkin', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(rowId);
                    if (row) row.remove();
                    window.location.reload();
                } else {
                    alert('Check-in failed. Please try again.');
                }
            })
            .catch(err => {
                console.error('Check-in error:', err);
                alert('An error occurred during check-in.');
            });
        }

        function filterArrivalsTable() {
            const query = (document.getElementById('dashboardSearch')?.value || '').toLowerCase();
            const selectedType = document.getElementById('arrivalTypeFilter').value;
            const rows = document.querySelectorAll('#arrivalsTable tbody tr');
            
            rows.forEach(row => {
                const guestNameEl = row.querySelector('.guest-info h4');
                if (!guestNameEl) return;
                const guestName = guestNameEl.innerText.toLowerCase();
                const guestType = row.getAttribute('data-guest-type') || '';
                const matchesType = !selectedType || guestType === selectedType;
                 
                if (guestName.includes(query) && matchesType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
