<?php
require_once 'auth_check.php';
require_once 'db.php';
require_once 'room_status_helper.php';

// Calculate live dashboard metrics
$checkins_today = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(check_in) = CURDATE()")->fetch_assoc()['count'];
$checkouts_today = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(check_out) = CURDATE()")->fetch_assoc()['count'];
$pending_rsvps = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'Pending'")->fetch_assoc()['count'];
$total_guests = $conn->query("SELECT SUM(guests_count) as count FROM bookings WHERE status = 'Checked In'")->fetch_assoc()['count'];
$total_guests = (int)($total_guests ?? 0);

// Fetch current arrivals list
$bookings_query = $conn->query("SELECT id, guest_name, guest_type, accommodation_name, eta, status FROM bookings WHERE status = 'Pending' AND DATE(check_in) = CURDATE() ORDER BY id DESC");

$checked_in_room_ids = sf_get_checked_in_room_ids($conn);
$room_status_rows = [];
$occupied_rooms = 0;
$available_rooms = 0;

$rooms_query = $conn->query("SELECT id, room_number, status FROM rooms ORDER BY room_number ASC");
if ($rooms_query && $rooms_query->num_rows > 0) {
    while ($room = $rooms_query->fetch_assoc()) {
        $room['resolved_status'] = sf_resolve_room_display_status($room, $checked_in_room_ids);
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
    // Fallback if the room_types table is empty
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
    <title>Santa Fe Beach Club - Reception Console</title>
    <link rel="stylesheet" href="dashboard.css?v=2">
</head>
<body>

    <?php $active_page = 'dashboard'; include '_sidebar.php'; ?>

    <!-- Main Dashboard Panel -->
    <main class="main-content">
        <?php
        $page_title = 'Dashboard';
        $page_subtitle = "Overview of today's operations";
        $header_extra_html = '
            <button class="btn-new-res-top" onclick="openReservationModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                New Reservation
            </button>
            <div class="search-wrapper">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" placeholder="Search guests..." class="search-input" id="dashboardSearch" onkeyup="filterArrivalsTable()">
            </div>
        ';
        include '_page_header.php';
        ?>

        <!-- Redesigned V2 Analytics Metrics Row (6 Columns) -->
        <section class="metrics-row">
            <div class="metric-card">
                <div class="metric-info">
                    <h3>Today's Check-ins</h3>
                    <div class="metric-number" id="arrivalsCount"><?php echo $checkins_today; ?></div>
                </div>
                <div class="metric-icon-wrapper" style="background-color: #FDF4EC; color: var(--color-primary);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Today's Check-outs</h3>
                    <div class="metric-number"><?php echo $checkouts_today; ?></div>
                </div>
                <div class="metric-icon-wrapper" style="background-color: #F3F4F6; color: #4B5563;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Pending RSVPs</h3>
                    <div class="metric-number"><?php echo $pending_rsvps; ?></div>
                </div>
                <div class="metric-icon-wrapper" style="background-color: #E0F7FA; color: #00838F;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Occupied</h3>
                    <div class="metric-number" id="occupiedRoomsCount"><?php echo $occupied_rooms; ?></div>
                </div>
                <div class="metric-icon-wrapper" style="background-color: #E3F2FD; color: #1565C0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"></path><path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"></path><circle cx="8" cy="14" r="2"></circle><circle cx="16" cy="14" r="2"></circle></svg>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Available</h3>
                    <div class="metric-number" id="availableRoomsCount"><?php echo $available_rooms; ?></div>
                </div>
                <div class="metric-icon-wrapper" style="background-color: #E8F5E9; color: #2E7D32;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Current Guests</h3>
                    <div class="metric-number"><?php echo $total_guests; ?></div>
                </div>
                <div class="metric-icon-wrapper" style="background-color: #EFEBE9; color: #4E342E;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
            </div>
        </section>

        <!-- Lower Grid Section -->
        <section class="dashboard-grid">
            <!-- Today's Arrivals Table -->
            <div class="card">
                <div class="card-header">
                    <h2>Today's Arrivals</h2>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <label for="arrivalTypeFilter" style="font-size:12px; font-weight:600; color:#666;">Filter</label>
                        <select id="arrivalTypeFilter" onchange="filterArrivalsTable()" style="padding:8px 10px; border:1px solid #ddd; border-radius:8px; font-size:13px; background:#fff;">
                            <option value="">All Guest Types</option>
                            <option value="First Visit">First Visit</option>
                            <option value="Returning Guest">Returning Guest</option>
                            <option value="VIP Member">VIP Member</option>
                            <option value="Corporate">Corporate</option>
                        </select>
                    </div>
                </div>

                <table class="arrivals-table" id="arrivalsTable">
                    <thead>
                        <tr>
                            <th>Guest Name</th>
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
                                $eta = htmlspecialchars($row['eta']);
                                $name = htmlspecialchars($name_raw);
                                $type = htmlspecialchars($type_raw);
                                $accommodation = htmlspecialchars($accommodation_raw);
                                
                                // Get initials
                                $initials = '';
                                $parts = explode(' ', $name_raw);
                                foreach ($parts as $p) {
                                    $initials .= strtoupper($p[0] ?? '');
                                }
                                $initials = substr($initials, 0, 2);
                                
                                $avatarColor = $type_raw === 'VIP Member' ? '#FDF4EC' : ($type_raw === 'Corporate' ? '#E3F2FD' : ($type_raw === 'Returning Guest' ? '#F3E8FF' : '#F3F4F6'));
                                $textColor = $type_raw === 'VIP Member' ? 'var(--color-primary)' : ($type_raw === 'Corporate' ? '#1565C0' : ($type_raw === 'Returning Guest' ? '#7C3AED' : '#4B5563'));
                                
                                // First item is primary brown button, others are secondary outlines in screenshot
                                $btnClass = ($idx === 0) ? 'primary' : 'secondary';
                                $idx++;
                                
                                echo "<tr id='{$row_id}' data-guest-type='" . htmlspecialchars($type_raw, ENT_QUOTES) . "'>";
                                echo "<td>
                                        <div class='guest-profile'>
                                            <div class='avatar-letter' style='background-color: {$avatarColor}; color: {$textColor};'>{$initials}</div>
                                            <div class='guest-info'>
                                                <h4>{$name}</h4>
                                                <p style='text-transform: capitalize;'>{$type}</p>
                                            </div>
                                        </div>
                                      </td>";
                                echo "<td class='eta-cell'>{$eta}</td>";
                                echo "<td style='text-align: right;'>
                                        <button class='btn-table-action {$btnClass}' onclick='checkInGuest(" . json_encode($row_id) . ", " . json_encode($name_raw) . ", " . json_encode($accommodation_raw) . ")'>Verify & Check-in</button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align: center; color: #888; padding: 20px;'>No pending arrivals today</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Accommodation Status Card -->
            <div class="card">
                <div class="card-header">
                    <h2>Accommodation Status</h2>
                    <div style="font-weight: bold; cursor: pointer; color: #888;">•••</div>
                </div>

                <div class="room-grid" id="roomStatusGrid">
                    <?php
                    if (count($room_status_rows) > 0) {
                        foreach ($room_status_rows as $room) {
                            $num = htmlspecialchars($room['room_number']);
                            $status = htmlspecialchars($room['resolved_status']);
                            echo "<div class='room-block {$status}' data-room='{$num}' data-status='{$status}'>{$num}</div>";
                        }
                    }
                    ?>
                </div>

                <div class="legend-container">
                    <div class="legend-item">
                        <span class="legend-dot ready"></span>
                        Available
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot occupied"></span>
                        Occupied
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot maintenance"></span>
                        Maintenance
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Quick Reservation Modal -->
    <div class="modal" id="newReservationModal">
        <div class="modal-content" style="max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3>New Reception Reservation</h3>
                <button class="close-btn" onclick="closeReservationModal()">&times;</button>
            </div>
            <!-- Submit values directly into MySQL database -->
            <form onsubmit="handleNewReservationSubmit(event)">
                <div class="form-group">
                    <label for="newGuestName">GUEST NAME</label>
                    <input type="text" id="newGuestName" required placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label for="newGuestEmail">GUEST EMAIL</label>
                    <input type="email" id="newGuestEmail" placeholder="guest@example.com (optional)">
                </div>
                <div class="form-group">
                    <label for="newGuestType">GUEST TYPE</label>
                    <select id="newGuestType">
                        <option value="First Visit">First Visit</option>
                        <option value="Returning Guest">Returning Guest</option>
                        <option value="VIP Member">VIP Member</option>
                        <option value="Corporate">Corporate</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="newRoomType">ACCOMMODATION</label>
                    <select id="newRoomType">
                        <?php foreach ($room_types as $rt): ?>
                            <option value="<?php echo $rt['id']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $rt['name'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label for="newCheckin">CHECK-IN DATE</label>
                        <input type="date" id="newCheckin" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="newCheckout">CHECK-OUT DATE</label>
                        <input type="date" id="newCheckout" required value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label for="newGuestsCount">GUEST COUNT</label>
                        <input type="number" id="newGuestsCount" required min="1" max="10" value="2">
                    </div>
                    <div class="form-group">
                        <label for="newETA">ETA</label>
                        <input type="time" id="newETA" required value="14:00">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                    <button type="button" class="btn-form-submit" onclick="submitReservation('Pending')" style="background-color: #7C533C; color: white;">Save Reservation</button>
                    <button type="button" class="btn-form-submit" onclick="submitReservation('Checked In')" style="background-color: #2E7D32; color: white;">Check-in Now</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Interactive Javascript Logic -->
    <script>
        // Modal toggles
        function openReservationModal() {
            document.getElementById('newReservationModal').style.display = 'flex';
        }

        // Set default dates dynamically on open
        document.addEventListener('DOMContentLoaded', () => {
            const today = new Date().toISOString().split('T')[0];
            const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];
            document.getElementById('newCheckin').value = today;
            document.getElementById('newCheckout').value = tomorrow;
        });

        function closeReservationModal() {
            document.getElementById('newReservationModal').style.display = 'none';
        }

        // Handle Quick Reservation addition (with immediate check-in option)
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
            
            fetch('create_booking.php', {
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

        // Verify and Check-in guest interaction
        function checkInGuest(rowId, name, room) {
            const bookingId = rowId.split('-')[1];
            
            const formData = new FormData();
            formData.append('action', 'checkin');
            formData.append('booking_id', bookingId);
            formData.append('format', 'json');

            fetch('checkin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Remove row from table and refresh to update metrics/room grid
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

        // Search filter arrivals list
        function filterArrivalsTable() {
            const query = document.getElementById('dashboardSearch').value.toLowerCase();
            const selectedType = document.getElementById('arrivalTypeFilter').value;
            const rows = document.querySelectorAll('#arrivalsTable tbody tr');
            
            rows.forEach(row => {
                const guestName = row.querySelector('.guest-info h4').innerText.toLowerCase();
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
<script src="sidebar-toggle.js"></script>
</body>
</html>
