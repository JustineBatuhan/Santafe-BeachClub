<?php
require_once 'auth_check.php';
require_once 'db.php';

// Fetch all reservations
$bookings_query = $conn->query("SELECT id, guest_name, guest_email, guest_type, accommodation_name, check_in, check_out, status FROM bookings ORDER BY id DESC");

// Page header setup
$page_title = 'Reservations';
$page_subtitle = 'All Bookings';
$header_extra_html = '
    <div class="search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" placeholder="Search guests..." class="search-input" id="reservationSearch">
    </div>
';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - Santa Fe Beach Club</title>
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

    <!-- Main Dashboard Panel -->
    <main class="main-content">
        <?php include '_page_header.php'; ?>

        <section class="dashboard-grid" style="grid-template-columns: 1fr;">
            <div class="card">
                <div class="card-header">
                    <h2>All Reservations</h2>
                </div>

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
    </main>
<script src="sidebar-toggle.js"></script>
</body>
</html>
