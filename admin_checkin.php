<?php
require_once 'admin_auth_check.php';
require_once 'db.php';

// Handle POST request to check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkin') {
    $booking_id = intval($_POST['booking_id']);
    $print_params = '';
    
    // Check if there is a payment to record
    if (isset($_POST['collect_payment']) && $_POST['collect_payment'] === '1') {
        $payment_method = $_POST['payment_method'] ?? 'Front Desk Cash';
        $balance_amount = floatval($_POST['balance_amount'] ?? 0);
        
        $amount_tendered = isset($_POST['amount_tendered']) && $_POST['amount_tendered'] !== '' ? floatval($_POST['amount_tendered']) : $balance_amount;
        $change_amount = isset($_POST['change_amount']) && $_POST['change_amount'] !== '' ? floatval($_POST['change_amount']) : 0;
        $ref_no = trim($_POST['reference_number'] ?? '');
        
        if ($balance_amount > 0) {
            // Fetch guest info for payment record
            $g_stmt = $conn->prepare("SELECT guest_name, guest_email FROM bookings WHERE id = ?");
            $g_stmt->bind_param("i", $booking_id);
            $g_stmt->execute();
            $g_res = $g_stmt->get_result()->fetch_assoc();
            $g_stmt->close();
            
            if ($g_res) {
                $guest_name = $g_res['guest_name'];
                $guest_email = $g_res['guest_email'];
                $txn_id = (!empty($ref_no) && $payment_method !== 'Front Desk Cash') ? $ref_no : 'TXN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                
                $p_stmt = $conn->prepare("INSERT INTO payments (booking_id, guest_name, guest_email, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, ?, ?, 'verified')");
                $p_stmt->bind_param("issdss", $booking_id, $guest_name, $guest_email, $balance_amount, $payment_method, $txn_id);
                $p_stmt->execute();
                $p_stmt->close();
                
                $print_params = "&print_rcpt=1&bid=" . urlencode($booking_id) . "&txn=" . urlencode($txn_id) . "&method=" . urlencode($payment_method) . "&amount=" . urlencode($balance_amount) . "&tendered=" . urlencode($amount_tendered) . "&change=" . urlencode($change_amount);
                
                // Add a notification about balance collected
                $notif_title = 'Balance Payment Collected';
                $notif_type = 'info';
                $notif_message = 'Collected remaining balance of ₱' . number_format($balance_amount, 2) . ' via ' . $payment_method . ' for guest ' . htmlspecialchars($guest_name) . ' (REF-' . str_pad($booking_id, 3, '0', STR_PAD_LEFT) . ') at check-in.';
                
                $n_stmt = $conn->prepare("INSERT INTO notifications (title, message, type, booking_id) VALUES (?, ?, ?, ?)");
                $n_stmt->bind_param("sssi", $notif_title, $notif_message, $notif_type, $booking_id);
                $n_stmt->execute();
                $n_stmt->close();
            }
        }
    }

    $stmt = $conn->prepare("UPDATE bookings SET status = 'Checked In' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->close();

    $room_query = $conn->query("SELECT room_id FROM bookings WHERE id = $booking_id");
    if ($room_query && $room_query->num_rows > 0) {
        $room_id = $room_query->fetch_assoc()['room_id'];
        if ($room_id) {
            $conn->query("UPDATE rooms SET status = 'occupied' WHERE id = $room_id");
        }
    }
    
    // Log activity
    $admin_user = $_SESSION['admin_username'] ?? 'System';
    log_activity($conn, $admin_user, 'Check-in Guest', 'Checked in booking ID #' . $booking_id);
    
    if (isset($_POST['format']) && $_POST['format'] === 'json') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    header("Location: admin_checkin.php?success=1" . $print_params);
    exit;
}

$ref = isset($_GET['ref']) ? $_GET['ref'] : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';
$specific_booking = null;

if (!empty($ref) && !empty($token)) {
    // Validate token and compute cost details
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            DATEDIFF(b.check_out, b.check_in) AS nights,
            COALESCE(r.price_per_night, rt.price, 0) AS price_per_night,
            (DATEDIFF(b.check_out, b.check_in) * COALESCE(r.price_per_night, rt.price, 0)) AS total_cost,
            (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = 'verified') AS amount_paid
        FROM bookings b
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN room_types rt ON b.room_type_id = rt.id
        WHERE b.checkin_token = ? AND b.status IN ('Pending', 'Confirmed')
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $specific_booking = $result->fetch_assoc();
    }
    $stmt->close();
}

$bookings_query = $conn->query("
    SELECT 
        b.id, b.guest_name, b.guest_email, b.guest_type, b.accommodation_name, b.check_in, b.check_out, b.status,
        DATEDIFF(b.check_out, b.check_in) AS nights,
        COALESCE(r.price_per_night, rt.price, 0) AS price_per_night,
        (DATEDIFF(b.check_out, b.check_in) * COALESCE(r.price_per_night, rt.price, 0)) AS total_cost,
        (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = 'verified') AS amount_paid
    FROM bookings b
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN room_types rt ON b.room_type_id = rt.id
    WHERE b.status IN ('Pending', 'Confirmed') 
    ORDER BY b.check_in ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Check-in - Santa Fe Beach Club</title>
    <link rel="stylesheet" href="admin.css?v=2">
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
        .btn-checkin {
            background-color: #2E7D32;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-checkin:hover {
            background-color: #1B5E20;
        }
        .alert-success {
            background-color: #E8F5E9;
            color: #2E7D32;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .specific-checkin-card {
            background-color: #FDF4EC;
            border: 2px solid var(--primary);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        /* Balance payment modal and method selector */
        .gcash-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .gcash-overlay.active { display: flex; }
        .gcash-modal {
            background: #fff;
            border-radius: 16px;
            padding: 32px 36px;
            max-width: 440px;
            width: 90%;
            text-align: left;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
            animation: popIn 0.22s ease;
            max-height: 90vh;
            overflow-y: auto;
        }
        @keyframes popIn {
            from { transform: scale(0.88); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }
        .btn-receipt {
            background-color: transparent;
            color: #666;
            border: 1px solid #ccc;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-receipt:hover {
            background-color: #f5f5f5;
        }
        .pay-method-card {
            cursor: pointer;
            display: block;
        }
        .pay-method-card input:checked + .pay-card-inner {
            border-color: #2E7D32;
            background: #E8F5E9;
            color: #1B5E20;
        }
        .pay-card-inner {
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            padding: 12px 8px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .pay-card-inner:hover {
            border-color: #BDC3C7;
            background: #FAFAFA;
        }
        .pay-card-inner .icon { font-size: 20px; }
        .pay-card-inner .label { font-size: 12px; }
    </style>
</head>
<body>

    <?php $active_page = 'checkin'; include '_sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-body">
        <?php
        $page_title = 'Admin Check-in';
        $page_subtitle = 'Process arriving guests';
        $header_extra_html = '
            <div class="search-wrapper">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" placeholder="Search by name or ref..." class="search-input" id="reservationSearch">
            </div>
        ';
        include '_page_header.php';
        ?>

        <section class="dashboard-grid" style="grid-template-columns: 1fr;">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success">Guest successfully checked in!</div>
            <?php endif; ?>

            <?php if ($specific_booking): ?>
                <?php
                $spec_id = htmlspecialchars($specific_booking['id']);
                $spec_name = htmlspecialchars($specific_booking['guest_name']);
                $spec_acc = htmlspecialchars($specific_booking['accommodation_name']);
                $spec_total = floatval($specific_booking['total_cost']);
                $spec_paid = floatval($specific_booking['amount_paid']);
                $spec_balance = max(0, $spec_total - $spec_paid);
                ?>
                <div class="specific-checkin-card">
                    <h2 style="margin-bottom: 15px;">QR Code Check-in Scanned</h2>
                    <p><strong>Guest:</strong> <?php echo $spec_name; ?></p>
                    <p><strong>Accommodation:</strong> <?php echo $spec_acc; ?></p>
                    <p><strong>Ref:</strong> <?php echo htmlspecialchars($ref); ?></p>
                    
                    <?php if ($spec_balance > 0): ?>
                        <div style="background: #FFF3E0; border: 1px solid #FFE0B2; border-radius: 8px; padding: 12px; margin: 15px 0; color: #E65100;">
                            <strong>⚠️ Balance Due: ₱<?php echo number_format($spec_balance, 2); ?></strong>
                            <p style="font-size: 12px; margin-top: 4px;">Please collect the balance payment before check-in.</p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="admin_checkin.php" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="checkin">
                        <input type="hidden" name="booking_id" value="<?php echo $spec_id; ?>">
                        <button type="submit" class="btn-checkin" style="font-size: 16px; padding: 12px 24px;" onclick="handleCheckinClick(event, <?php echo $spec_id; ?>, '<?php echo addslashes($spec_name); ?>', '<?php echo addslashes($spec_acc); ?>', <?php echo $spec_total; ?>, <?php echo $spec_balance; ?>)">Confirm Check-in</button>
                    </form>
                </div>
            <?php elseif (!empty($ref)): ?>
                <div class="alert-success" style="background-color: #FFEBEE; color: #C62828;">
                    Invalid or already processed QR code token.
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Pending Arrivals</h2>
                </div>

                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Guest Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Accommodation</th>
                            <th>Check-in Date</th>
                            <th>Balance Status</th>
                            <th>Action</th>
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
                                $total_cost = floatval($row['total_cost']);
                                $amount_paid = floatval($row['amount_paid']);
                                $balance_due = max(0, $total_cost - $amount_paid);

                                echo "<tr>";
                                echo "<td>#$id</td>";
                                echo "<td><strong>{$name}</strong></td>";
                                echo "<td>{$email}</td>";
                                echo "<td>{$type}</td>";
                                echo "<td>{$accommodation}</td>";
                                echo "<td>{$checkin}</td>";
                                
                                // Render balance badge
                                if ($balance_due > 0) {
                                    echo "<td><span style='background: #FFF3E0; color: #E65100; padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 12px;'>₱" . number_format($balance_due, 2) . " Due</span></td>";
                                } else {
                                    echo "<td><span style='background: #E8F5E9; color: #2E7D32; padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 12px;'>Paid In Full</span></td>";
                                }
                                
                                echo "<td>
                                    <form method='POST' action='admin_checkin.php' style='display:inline;'>
                                        <input type='hidden' name='action' value='checkin'>
                                        <input type='hidden' name='booking_id' value='{$id}'>
                                        <button type='submit' class='btn-checkin' onclick=\"handleCheckinClick(event, {$id}, '" . addslashes($name) . "', '" . addslashes($accommodation) . "', {$total_cost}, {$balance_due})\">Check-in</button>
                                    </form>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align: center; color: #888; padding: 20px;'>No pending arrivals</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </main>

    <!-- Balance Collection Modal -->
    <div class="gcash-overlay" id="balanceModalOverlay">
        <div class="gcash-modal">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div style="width: 40px; height: 40px; background: #E8F5E9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #2E7D32; flex-shrink: 0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size: 20px; font-weight: 800; color: #333; margin: 0;">Collect Balance Payment</h2>
                    <p style="color: #666; font-size: 13px; margin: 2px 0 0;">Verify payment details before guest check-in</p>
                </div>
            </div>
            
            <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px; margin-bottom: 20px; color: #333;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                    <span style="color: #666;">Guest Name:</span>
                    <strong id="modalGuestName">John Doe</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                    <span style="color: #666;">Accommodation:</span>
                    <strong id="modalAccName">Standard Room</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; border-bottom: 1px dashed #E5E7EB; padding-bottom: 12px;">
                    <span style="color: #666;">Total Cost:</span>
                    <strong id="modalTotalCost">&#8369;0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #D32F2F; font-weight: 700; font-size: 15px;">Balance Due:</span>
                    <strong style="color: #D32F2F; font-size: 22px; font-weight: 800;" id="modalBalanceDue">&#8369;0.00</strong>
                </div>
            </div>
            
            <form method="POST" id="balanceForm" action="admin_checkin.php">
                <input type="hidden" name="action" value="checkin">
                <input type="hidden" name="booking_id" id="modalBookingId" value="">
                <input type="hidden" name="collect_payment" value="1">
                <input type="hidden" name="balance_amount" id="modalBalanceAmountInput" value="">
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #555; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Select Payment Method</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                        <label class="pay-method-card">
                            <input type="radio" name="payment_method" value="Front Desk Cash" checked style="display:none;" onchange="updatePaySelectors()">
                            <div class="pay-card-inner">
                                <span class="icon">💵</span>
                                <span class="label">Cash</span>
                            </div>
                        </label>
                        <label class="pay-method-card">
                            <input type="radio" name="payment_method" value="GCash QR" style="display:none;" onchange="updatePaySelectors()">
                            <div class="pay-card-inner">
                                <span class="icon">📱</span>
                                <span class="label">GCash</span>
                            </div>
                        </label>
                        <label class="pay-method-card">
                            <input type="radio" name="payment_method" value="Front Desk Card" style="display:none;" onchange="updatePaySelectors()">
                            <div class="pay-card-inner">
                                <span class="icon">💳</span>
                                <span class="label">Card</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Cash Section -->
                <div id="cashSection" style="margin-bottom:18px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:#555; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Amount Tendered</label>
                    <input type="number" step="0.01" id="amountTendered" name="amount_tendered" placeholder="0.00" style="width:100%; padding:10px 12px; border:1.5px solid #E5E7EB; border-radius:8px; font-size:15px; font-weight:600; box-sizing:border-box;" oninput="calculateChange()">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px; background:#F0FFF4; border:1px solid #A7F3D0; border-radius:8px; padding:10px 14px;">
                        <span style="font-size:13px; color:#065F46; font-weight:600;">Change</span>
                        <div style="display:flex; align-items:center;">
                            <span style="font-size:18px; font-weight:800; color:#065F46;">&#8369;</span>
                            <input type="text" id="changeAmount" name="change_amount" value="0.00" readonly style="border:none; background:transparent; font-size:18px; font-weight:800; color:#065F46; width:80px; text-align:right;">
                        </div>
                    </div>
                </div>

                <!-- GCash Section -->
                <div id="gcashSection" style="display:none; margin-bottom:18px; text-align:center;">
                    <p style="font-size:13px; color:#555; margin-bottom:12px;">Ask guest to scan the QR code or send to the number below, then enter the reference number.</p>
                    <div style="display:inline-block; background:#f0f4ff; border:2px dashed #007AFF; border-radius:12px; padding:14px; margin-bottom:12px;">
                        <img src="assets/gcash_qr.png?v=<?= time(); ?>" alt="GCash QR Code" width="160" height="160" style="display:block;" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=160x160&amp;data=GCash%3A09505223146%20Santa+Fe+Beach+Club'">
                    </div>
                    <div style="background:#f0f4ff; border-radius:10px; padding:10px 20px; margin:0 auto 12px; max-width:260px;">
                        <div style="font-size:11px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">GCash Number</div>
                        <div style="font-size:22px; font-weight:800; color:#007AFF; letter-spacing:2px;">09505223146</div>
                        <div style="font-size:13px; color:#333; font-weight:600; margin-top:2px;">Santa Fe Beach Club</div>
                    </div>
                    <div style="text-align:left; margin-bottom:6px;">
                        <label style="display:block; font-size:11px; font-weight:700; color:#555; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">GCash Reference Number</label>
                        <input type="text" name="reference_number_gcash" placeholder="e.g. 1234 5678 9012" style="width:100%; padding:10px 12px; border:1.5px solid #E5E7EB; border-radius:8px; font-size:14px; box-sizing:border-box;">
                    </div>
                </div>

                <!-- Card Section -->
                <div id="cardSection" style="display:none; margin-bottom:18px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:#555; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Card Reference / Approval Code</label>
                    <input type="text" name="reference_number_card" placeholder="e.g. AUTH-123456" style="width:100%; padding:10px 12px; border:1.5px solid #E5E7EB; border-radius:8px; font-size:14px; box-sizing:border-box;">
                </div>

                <input type="hidden" name="reference_number" id="finalReferenceNumber" value="">

                <button type="submit" style="width:100%; background:#2E7D32; color:#fff; border:none; padding:14px; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; margin-bottom:10px;">
                    &#10003; Confirm Check-in &amp; Record Payment
                </button>
                <button type="button" onclick="closeBalanceModal()" style="width:100%; background:transparent; border:1px solid #E5E7EB; color:#666; padding:10px; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer;">
                    Cancel
                </button>
            </form>
        </div>
    </div>

<script src="sidebar-toggle.js"></script>
<script>
function handleCheckinClick(event, bookingId, guestName, accName, totalCost, balanceDue) {
    if (balanceDue > 0.01) {
        event.preventDefault();
        document.getElementById('modalBookingId').value = bookingId;
        document.getElementById('modalGuestName').textContent = guestName;
        document.getElementById('modalAccName').textContent = accName;
        document.getElementById('modalTotalCost').textContent = '₱' + totalCost.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('modalBalanceDue').textContent = '₱' + balanceDue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('modalBalanceAmountInput').value = balanceDue;
        const radios = document.getElementsByName('payment_method');
        radios[0].checked = true;
        updatePaySelectors();
        document.getElementById('balanceModalOverlay').classList.add('active');
    }
}

function closeBalanceModal() {
    document.getElementById('balanceModalOverlay').classList.remove('active');
}

function updatePaySelectors() {
    const radios = document.getElementsByName('payment_method');
    document.getElementById('cashSection').style.display = 'none';
    document.getElementById('gcashSection').style.display = 'none';
    document.getElementById('cardSection').style.display = 'none';
    radios.forEach(radio => {
        const inner = radio.nextElementSibling;
        if (radio.checked) {
            inner.style.borderColor = '#2E7D32';
            inner.style.background = '#E8F5E9';
            inner.style.color = '#1B5E20';
            if (radio.value === 'Front Desk Cash') document.getElementById('cashSection').style.display = 'block';
            if (radio.value === 'GCash QR') document.getElementById('gcashSection').style.display = 'block';
            if (radio.value === 'Front Desk Card') document.getElementById('cardSection').style.display = 'block';
        } else {
            inner.style.borderColor = '#E5E7EB';
            inner.style.background = '#fff';
            inner.style.color = '#333';
        }
    });
}

function calculateChange() {
    const balanceDue = parseFloat(document.getElementById('modalBalanceAmountInput').value) || 0;
    const tendered = parseFloat(document.getElementById('amountTendered').value) || 0;
    let change = tendered - balanceDue;
    if (change < 0) change = 0;
    document.getElementById('changeAmount').value = change.toFixed(2);
}

document.getElementById('balanceForm').addEventListener('submit', function(e) {
    const radios = document.getElementsByName('payment_method');
    let selected = '';
    radios.forEach(r => { if(r.checked) selected = r.value; });
    let refVal = '';
    if (selected === 'GCash QR') {
        refVal = document.querySelector('input[name="reference_number_gcash"]').value;
    } else if (selected === 'Front Desk Card') {
        refVal = document.querySelector('input[name="reference_number_card"]').value;
    }
    document.getElementById('finalReferenceNumber').value = refVal;
});

document.getElementById('balanceModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeBalanceModal();
});

// Search filter
const searchInput = document.getElementById('reservationSearch');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.reservations-table tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}
</script>

<?php if (isset($_GET['print_rcpt']) && $_GET['print_rcpt'] == '1'):
    $bid = $_GET['bid'] ?? '';
    $txn = $_GET['txn'] ?? '';
    $method = $_GET['method'] ?? '';
    $amountDue = (float)($_GET['amount'] ?? 0);
    $tendered = (float)($_GET['tendered'] ?? $amountDue);
    $change = (float)($_GET['change'] ?? 0);
    
    // Get basic booking details for print
    $pr_stmt = $conn->prepare("SELECT guest_name, accommodation_name FROM bookings WHERE id = ?");
    $pr_stmt->bind_param("i", $bid);
    $pr_stmt->execute();
    $pr_res = $pr_stmt->get_result()->fetch_assoc();
    $pr_stmt->close();
    
    if ($pr_res) {
        $guest_name = htmlspecialchars($pr_res['guest_name']);
        $room = htmlspecialchars($pr_res['accommodation_name']);
        $rcpt = str_pad($bid, 4, '0', STR_PAD_LEFT);
        $dateStr = date('M d, Y');
        $timeStr = date('h:i A');
        $admin_name = $_SESSION['admin_username'] ?? 'Reception';
?>
<script>
window.onload = function() {
    const fmt = val => '₱' + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const sep = '-'.repeat(32);
    const printWin = window.open('', '', 'width=420,height=700');
    printWin.document.write(`<html>
    <head>
        <title>Receipt - <?php echo $rcpt; ?></title>
        <style>
            @page { size: 80mm auto; margin: 0; }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Courier New', Courier, monospace; background: #fff; color: #000; padding: 16px 8px; font-size: 13px; line-height: 1.6; width: 80mm; margin: 0; }
            .center { text-align: center; }
            .sep { color: #000; letter-spacing: 2px; margin: 6px 0; text-align: center; }
            .row { display: flex; justify-content: space-between; }
            .row strong { text-align: right; }
            .brand { font-size: 16px; font-weight: 800; letter-spacing: 1px; }
            .subtitle { font-size: 12px; color: #666; }
            .total-label { font-size: 15px; font-weight: 800; letter-spacing: 1px; margin-top: 10px; }
            .total-amount { font-size: 20px; font-weight: 900; letter-spacing: 1px; margin-bottom: 6px; }
            .footer { color: #666; font-size: 12px; margin-top: 10px; }
            .footer-note { color: #888; font-size: 11px; margin-top: 8px; }
        </style>
    </head>
    <body>
        <div class="center">
            <div class="brand">SANTA FE BEACH CLUB</div>
            <div class="subtitle">Bantayan Island, Cebu</div>
            <div class="subtitle">Official Payment Receipt</div>
        </div>
        <div class="sep">${sep}</div>
        <div class="row"><span>Receipt #:</span><strong><?php echo $rcpt; ?></strong></div>
        <div class="row"><span>Date &amp; Time:</span><strong><?php echo "$dateStr, $timeStr"; ?></strong></div>
        <div class="sep">${sep}</div>
        <div class="row"><span>Guest Name:</span><strong><?php echo addslashes($guest_name); ?></strong></div>
        <div class="row"><span>Accommodation:</span><strong><?php echo addslashes($room); ?></strong></div>
        <div class="sep">${sep}</div>
        <div class="row"><span>Payment Type:</span><strong><?php echo addslashes($method); ?></strong></div>
        <div class="row"><span>Ref/Txn #:</span><strong><?php echo addslashes($txn); ?></strong></div>
        <div class="sep">${sep}</div>
        <div class="row"><span>Amount Due:</span><strong>${fmt(<?php echo $amountDue; ?>)}</strong></div>
        <div class="row"><span>Amount Tendered:</span><strong>${fmt(<?php echo $tendered; ?>)}</strong></div>
        <div class="row"><span>Change:</span><strong>${fmt(<?php echo $change; ?>)}</strong></div>
        <div class="sep">${sep}</div>
        <div class="center">
            <div class="total-label">TOTAL PAID</div>
            <div class="total-amount">${fmt(<?php echo $amountDue; ?>)}</div>
        </div>
        <div class="sep">${sep}</div>
        <div class="row"><span>Receptionist:</span><strong><?php echo addslashes($admin_name); ?></strong></div>
        <div class="sep">${sep}</div>
        <div class="center footer">Thank you for staying with us!<br>Have a safe trip!</div>
        <div class="center footer-note">This is an official receipt.</div>
        <script>window.onload = function() { window.print(); }<\/script>
    </body>
    </html>`);
    printWin.document.close();
};
</script>
<?php 
    }
endif; 
?>

</body>
</html>
