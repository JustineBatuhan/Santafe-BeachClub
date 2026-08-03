<?php
require_once 'admin_auth_check.php';
require_once 'db.php';

// Optional status filter
$allowed_statuses = ['Pending', 'Checked In', 'Checked Out', 'Cancelled'];
$selected_status = isset($_GET['status']) ? trim($_GET['status']) : '';
if (!in_array($selected_status, $allowed_statuses, true)) {
    $selected_status = '';
}

// Fetch all reservations
$sql = "SELECT id, guest_name, guest_email, guest_type, accommodation_name, check_in, check_out, status FROM bookings";
if ($selected_status !== '') {
    $safe_status = $conn->real_escape_string($selected_status);
    $sql .= " WHERE status = '{$safe_status}'";
}
$sql .= " ORDER BY id DESC";
$bookings_query = $conn->query($sql);

// Page header setup
$page_title = 'Admin Reservations';
$page_subtitle = 'Manage and monitor all beach reservations';
$header_extra_html = '
    <div class="search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" placeholder="Search reservations..." class="search-input" id="reservationSearch">
    </div>
';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reservations - Santa Fe Beach Club</title>
    <link rel="stylesheet" href="admin.css?v=2">
    <link rel="stylesheet" href="dashboard.css?v=2">
    <style>
        .reservations-table {
            width: 100%;
            border-collapse: collapse;
        }
        .reservations-table th, .reservations-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .reservations-table th {
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background-color: #FFF3E0; color: #E65100; }
        .status-checked-in { background-color: #E8F5E9; color: #2E7D32; }
        .status-checked-out { background-color: #ECEFF1; color: #546E7A; }
        .status-cancelled { background-color: #FFEBEE; color: #C62828; }
    </style>
</head>
<body>

    <?php $active_page = 'reservations'; include '_sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-body">
        <?php include '_page_header.php'; ?>

        <section class="lower-grid" style="grid-template-columns: 1fr;">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>All Reservations</h3>
                </div>
                <form method="GET" action="admin_reservations.php" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin: 0 0 18px 0;">
                    <label for="statusFilter" style="font-size:13px; font-weight:600; color:#666;">Filter by status</label>
                    <select id="statusFilter" name="status" onchange="this.form.submit()" style="padding:10px 12px; border:1px solid #ddd; border-radius:8px; font-size:14px;">
                        <option value="" <?php echo $selected_status === '' ? 'selected' : ''; ?>>All statuses</option>
                        <option value="Pending" <?php echo $selected_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Checked In" <?php echo $selected_status === 'Checked In' ? 'selected' : ''; ?>>Checked In</option>
                        <option value="Checked Out" <?php echo $selected_status === 'Checked Out' ? 'selected' : ''; ?>>Checked Out</option>
                        <option value="Cancelled" <?php echo $selected_status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <?php if ($selected_status !== ''): ?>
                        <a href="admin_reservations.php" style="font-size:13px; color:#8B5E3C; font-weight:600; text-decoration:none;">Reset</a>
                    <?php endif; ?>
                </form>

                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Guest Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Accommodation</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($bookings_query && $bookings_query->num_rows > 0) {
                            while ($row = $bookings_query->fetch_assoc()) {
                                $id = htmlspecialchars($row['id']);
                                $name = htmlspecialchars($row['guest_name']);
                                $email = htmlspecialchars($row['guest_email']);
                                $type = htmlspecialchars($row['guest_type']);
                                $accommodation = htmlspecialchars($row['accommodation_name']);
                                $checkin = htmlspecialchars($row['check_in']);
                                $checkout = htmlspecialchars($row['check_out']);
                                $status = htmlspecialchars($row['status']);

                                $statusClass = 'status-pending';
                                if ($status === 'Checked In') $statusClass = 'status-checked-in';
                                else if ($status === 'Checked Out') $statusClass = 'status-checked-out';
                                else if ($status === 'Cancelled') $statusClass = 'status-cancelled';

                                echo "<tr>";
                                echo "<td>#{$id}</td>";
                                echo "<td><strong>{$name}</strong></td>";
                                echo "<td>{$email}</td>";
                                echo "<td>{$type}</td>";
                                echo "<td>{$accommodation}</td>";
                                echo "<td>{$checkin}</td>";
                                echo "<td>{$checkout}</td>";
                                echo "<td><span class='status-badge {$statusClass}'>{$status}</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align: center; color: #888; padding: 20px;'>No reservations found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </main>
<script src="sidebar-toggle.js"></script>
</body>
</html>
