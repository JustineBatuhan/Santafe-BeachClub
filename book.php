<?php
require_once 'db.php';
require_once 'libs/phpqrcode/phpqrcode.php';
require_once __DIR__ . '/booking_service.php';

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_room_types = ['beachview_duplex', 'seaview_duplex', 'beach_villa', 'standard_room', 'standard_king'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && (isset($_GET['rebook']) && $_GET['rebook'] === '1')) {
    $rebook_session_fields = [
        'guest_first_name' => 'first_name',
        'guest_last_name' => 'last_name',
        'guest_email' => 'email',
        'guest_phone' => 'phone',
        'guest_country' => 'country',
        'guest_comments' => 'comments',
    ];

    foreach ($rebook_session_fields as $session_key => $request_key) {
        if (isset($_GET[$request_key])) {
            $_SESSION[$session_key] = trim((string)$_GET[$request_key]);
        }
    }
}

$step = isset($_REQUEST['step']) ? (int)$_REQUEST['step'] : 1;

// Grab values from homepage search or session/request
$checkin = isset($_REQUEST['checkin']) ? $_REQUEST['checkin'] : (isset($_SESSION['book_checkin']) ? $_SESSION['book_checkin'] : date('Y-m-d'));
$checkout = isset($_REQUEST['checkout']) ? $_REQUEST['checkout'] : (isset($_SESSION['book_checkout']) ? $_SESSION['book_checkout'] : date('Y-m-d', strtotime('+1 day')));
$guests = isset($_REQUEST['guests']) ? (int)$_REQUEST['guests'] : (isset($_SESSION['book_guests']) ? (int)$_SESSION['book_guests'] : 2);
$room_type = isset($_REQUEST['room_type']) ? $_REQUEST['room_type'] : (isset($_SESSION['book_room_type']) ? $_SESSION['book_room_type'] : 'beachview_duplex');
if (!in_array($room_type, $allowed_room_types, true)) {
    $room_type = 'standard_room';
}

// Store in session to persist across steps
$_SESSION['book_checkin'] = $checkin;
$_SESSION['book_checkout'] = $checkout;
$_SESSION['book_guests'] = $guests;
$_SESSION['book_room_type'] = $room_type;

$error = '';
$success = false;
$booking_id = 0;

// Restore success state on GET redirect to prevent form resubmission and double notifications
if (isset($_GET['success']) && $_GET['success'] == 1 && isset($_SESSION['booking_success'])) {
    $success = true;
    $step = 4;
    $booking_id = $_SESSION['booking_success']['booking_id'];
    $booking_ref = $_SESSION['booking_success']['booking_ref'];
    $guest_name = $_SESSION['booking_success']['guest_name'];
    $guest_email = $_SESSION['booking_success']['guest_email'];
    $checkin = $_SESSION['booking_success']['checkin'];
    $checkout = $_SESSION['booking_success']['checkout'];
    $accommodation_name = $_SESSION['booking_success']['accommodation'];
    $nights = $_SESSION['booking_success']['nights'];
    $deposit_amount = $_SESSION['booking_success']['deposit_amount'];
    $payment_method = $_SESSION['booking_success']['payment_method'];
    $checkin_token = $_SESSION['booking_success']['checkin_token'];
    $cancel_token = $_SESSION['booking_success']['cancel_token'];
    $guests = $_SESSION['booking_success']['guests'] ?? $guests;
    
    $base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
    $cancel_url = rtrim($base_url, '/') . '/cancel_booking.php?token=' . $cancel_token;
}

// Room mapping
$room_names = [
    'beachview_duplex' => 'BEACHVIEW DUPLEX',
    'seaview_duplex'   => 'SEAVIEW DUPLEX',
    'beach_villa'      => 'BEACH VILLA',
    'standard_room'    => 'STANDARD ROOM',
    'standard_king'    => 'STANDARD FAMILY ROOM'
];
$accommodation_name = isset($room_names[$room_type]) ? $room_names[$room_type] : 'STANDARD ROOM';

// Prices
$room_prices = [
    'beachview_duplex' => 6900.00,
    'seaview_duplex'   => 7900.00,
    'beach_villa'      => 7900.00,
    'standard_room'    => 2900.00,
    'standard_king'    => 4300.00
];
$room_price = isset($room_prices[$room_type]) ? $room_prices[$room_type] : 2900.00;

$breakfast_included_types = [
    'beachview_duplex' => true,
    'seaview_duplex'   => true,
    'beach_villa'      => true,
];
$has_breakfast = !empty($breakfast_included_types[$room_type]);

$room_type_id = 0;
$room_type_stmt = $conn->prepare("SELECT id FROM room_types WHERE name = ? LIMIT 1");
$room_type_stmt->bind_param("s", $room_type);
$room_type_stmt->execute();
$room_type_result = $room_type_stmt->get_result();
if ($room_type_result && $room_type_result->num_rows > 0) {
    $room_type_row = $room_type_result->fetch_assoc();
    $room_type_id = (int) $room_type_row['id'];
}
$room_type_stmt->close();

$available_rooms = null;
if ($room_type_id > 0) {
    try {
        $pdo = getPdoConnection();
        $available_rooms = getAvailableRooms($pdo, $room_type_id, $checkin, $checkout);
    } catch (Throwable $e) {
        $available_rooms = null;
    }
}

$nights = max(1, (strtotime($checkout) - strtotime($checkin)) / 86400);
$total_amount = $room_price * $nights;
$deposit_amount = $total_amount / 2; // Guests pay 50% deposit upon booking

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'go_step_2') {
            $step = 2;
        } elseif ($_POST['action'] === 'go_step_3') {
            // Save guest details
            $_SESSION['guest_first_name'] = $_POST['first_name'] ?? '';
            $_SESSION['guest_last_name'] = $_POST['last_name'] ?? '';
            $_SESSION['guest_email'] = $_POST['email'] ?? '';
            $_SESSION['guest_phone'] = $_POST['phone'] ?? '';
            $_SESSION['guest_country'] = $_POST['country'] ?? '';
            $_SESSION['guest_comments'] = $_POST['comments'] ?? '';
            
            if (empty($_SESSION['guest_first_name']) || empty($_SESSION['guest_last_name']) || empty($_SESSION['guest_email'])) {
                $error = "Please fill out required fields.";
                $step = 2;
            } else {
                $step = 3;
            }
        } elseif ($_POST['action'] === 'confirm_booking') {
            // Check required fields again just in case
            $guest_name = ($_SESSION['guest_first_name'] ?? '') . ' ' . ($_SESSION['guest_last_name'] ?? '');
            $guest_email = $_SESSION['guest_email'] ?? '';
            // Auto-detect guest tier based on previous confirmed bookings
            $prev_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE guest_email = ? AND status NOT IN ('Cancelled','Pending Payment')");
            $prev_stmt->bind_param("s", $guest_email);
            $prev_stmt->execute();
            $prev_count = (int)$prev_stmt->get_result()->fetch_assoc()['cnt'];
            $prev_stmt->close();
            if ($prev_count === 0)      $guest_type = 'First Visit';
            elseif ($prev_count === 1)  $guest_type = 'Returning Guest';
            else                        $guest_type = 'VIP Member';

            if ($room_type_id > 0) {
                try {
                    $pdo = getPdoConnection();
                    $currentAvailability = getAvailableRooms($pdo, $room_type_id, $checkin, $checkout);
                    if ($currentAvailability <= 0) {
                        $error = 'Sorry, this room type is sold out for the selected dates.';
                        $step = 3;
                    }
                } catch (Throwable $e) {
                    $error = 'Unable to check room availability right now. Please try again.';
                    $step = 3;
                }
            }
            
            if (empty($error)) {
                // Find a room that is not under maintenance and has no overlapping active booking.
                $room_id = null;
                $db_acc_name = $accommodation_name;
                $room_stmt = $conn->prepare(
                    "SELECT r.id, r.name
                     FROM rooms r
                     WHERE r.type = ?
                       AND r.status <> 'maintenance'
                       AND NOT EXISTS (
                           SELECT 1
                           FROM bookings b
                           WHERE b.room_id = r.id
                             AND b.status IN ('Confirmed', 'Checked In')
                             AND b.check_in < ?
                             AND b.check_out > ?
                       )
                     ORDER BY CASE WHEN r.status = 'ready' THEN 0 ELSE 1 END, r.room_number ASC
                     LIMIT 1"
                );
                $room_stmt->bind_param("sss", $room_type, $checkout, $checkin);
                $room_stmt->execute();
                $room_query = $room_stmt->get_result();
                if ($room_query && $room_query->num_rows > 0) {
                    $room = $room_query->fetch_assoc();
                    $room_id = (int)$room['id'];
                    $db_acc_name = $room['name'];
                }
                $room_stmt->close();

                if ($room_id === null) {
                    $error = 'No available room matches your selected dates. Please choose different dates or room type.';
                    $step = 3;
                }
            }
            
            $checkin_token = bin2hex(random_bytes(16));
            $cancel_token = bin2hex(random_bytes(16));
            $payment_method = $_POST['payment_method'] ?? 'Card';
            $gcash_reference = isset($_POST['gcash_reference']) ? trim($_POST['gcash_reference']) : '';
            $skip_insert = false;

            // GCash requires the guest to submit their real reference number
            if ($payment_method === 'GCash' && empty($gcash_reference)) {
                $error = 'Please enter your GCash reference number to continue.';
                $step = 3;
                $skip_insert = true;
            }

            $payment_ref = ($payment_method === 'GCash')
                ? $gcash_reference
                : 'TXN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            if (!$skip_insert && empty($error)) {
                try {
                    $guest_phone = $_SESSION['guest_phone'] ?? '';
                    $guest_country = $_SESSION['guest_country'] ?? '';
                    $guest_special_requests = $_SESSION['guest_comments'] ?? '';
                    
                    $room_value = $room_id === null ? "NULL" : (string)$room_id;
                    $sql = "INSERT INTO bookings (guest_name, guest_email, guest_type, check_in, check_out, guests_count, room_id, accommodation_name, eta, status, checkin_token, cancellation_token, payment_method, guest_phone, guest_country, guest_special_requests) 
                            VALUES (?, ?, ?, ?, ?, ?, " . $room_value . ", ?, '14:00', 'Pending Payment', ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssisssssss", $guest_name, $guest_email, $guest_type, $checkin, $checkout, $guests, $db_acc_name, $checkin_token, $cancel_token, $payment_method, $guest_phone, $guest_country, $guest_special_requests);
                    
                    if ($stmt->execute()) {
                        $booking_id = $stmt->insert_id;
                        
                        $pay_status = 'pending';
                        $stmt_pay = $conn->prepare("INSERT INTO payments (booking_id, guest_name, guest_email, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt_pay->bind_param("issdsss", $booking_id, $guest_name, $guest_email, $deposit_amount, $payment_method, $payment_ref, $pay_status);
                        $stmt_pay->execute();
                        
                        $booking_ref = 'REF-' . str_pad($booking_id, 3, '0', STR_PAD_LEFT);
                        
                        // QR Code
                        $base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
                        $checkin_url = rtrim($base_url, '/') . '/checkin.php?ref=' . $booking_ref . '&token=' . $checkin_token;
                        $cancel_url = rtrim($base_url, '/') . '/cancel_booking.php?token=' . $cancel_token;
                        
                        $qr_dir = __DIR__ . '/assets/qrcodes/';
                        if (!is_dir($qr_dir)) {
                            mkdir($qr_dir, 0777, true);
                        }
                        $qr_file = $qr_dir . 'qr_booking_' . $booking_id . '.png';
                        QRcode::png($checkin_url, $qr_file, QR_ECLEVEL_H, 6, 4);
                        
                        // Notify the dashboard of the new booking / payment
                        $notif_title = 'New Guest Reservation';
                        $notif_type = 'info';
                        $notif_message = htmlspecialchars($guest_name) . ' booked ' . htmlspecialchars($db_acc_name) . ' (' . $booking_ref . ').';
                        if ($payment_method === 'GCash') {
                            $notif_title = 'GCash Payment Pending Verification';
                            $notif_type = 'warning';
                            $notif_message = htmlspecialchars($guest_name) . ' submitted a GCash 50% deposit payment of ₱' . number_format($deposit_amount, 2) . ' for ' . htmlspecialchars($db_acc_name) . ' (' . $booking_ref . '). Ref #: ' . htmlspecialchars($gcash_reference) . '. Please verify and confirm in Payments.';
                        }
                        $stmt_notif = $conn->prepare("INSERT INTO notifications (title, message, type, booking_id) VALUES (?, ?, ?, ?)");
                        $stmt_notif->bind_param("sssi", $notif_title, $notif_message, $notif_type, $booking_id);
                        $stmt_notif->execute();
                        
                        // PRG: store success data in session then redirect so page refresh
                        // doesn't re-submit the form and create duplicate bookings/notifications.
                        $_SESSION['booking_success'] = [
                            'booking_id'     => $booking_id,
                            'booking_ref'    => $booking_ref,
                            'guest_name'     => $guest_name,
                            'guest_email'    => $guest_email,
                            'checkin'        => $checkin,
                            'checkout'       => $checkout,
                            'accommodation'  => $db_acc_name,
                            'nights'         => $nights,
                            'deposit_amount' => $deposit_amount,
                            'payment_method' => $payment_method,
                            'checkin_token'  => $checkin_token,
                            'cancel_token'   => $cancel_token,
                            'guests'         => $guests,
                        ];
                        header('Location: book.php?success=1&ref=' . urlencode($booking_ref) . '&room_type=' . urlencode($room_type) . '&checkin=' . urlencode($checkin) . '&checkout=' . urlencode($checkout));
                        exit;
                    } else {
                        $error = 'Error saving your booking. Please try again.';
                    }
                } catch (Exception $e) {
                    $error = 'Error saving your booking: ' . $e->getMessage();
                }
            }
        }
    }
}

// Formatting helpers
$checkin_fmt = date('D, d M Y', strtotime($checkin));
$checkout_fmt = date('D, d M Y', strtotime($checkout));
$full_name = trim(($_SESSION['guest_first_name'] ?? '') . ' ' . ($_SESSION['guest_last_name'] ?? ''));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Reservation - Santa Fe Beach Club</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="book.css">
</head>
<body>

    <!-- Header -->
    <header class="main-header">
        <div class="brand-logo" style="display:flex; align-items:center;">
            <a href="index.php" class="logo-link" style="font-size:24px;">SANTA FE</a>
        </div>
        <nav class="nav-menu">
            <ul>
                <li><a href="index.php#about" style="color:#111; font-weight:600;">About Us</a></li>
            </ul>
        </nav>
    </header>

    <?php if ($step === 4 && $success): ?>
    <!-- SUCCESS SCREEN -->
    <div class="booking-page-container">
        <div class="booking-card success-box">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h2>Booking Confirmed!</h2>
            <p style="color: #666; margin-bottom: 30px;">Thank you, <?php echo htmlspecialchars($guest_name); ?>. Your reservation has been received!<br><br>
            <strong style="color:#d32f2f;">Important:</strong> Your booking is currently <strong>Pending Payment Verification</strong>. Once our staff verifies your payment, your booking will be Confirmed and your room will be officially reserved.</p>
            
            <div class="ticket-pass">
                <div class="ticket-header">
                    <div class="ticket-brand">Santa Fe Beach Club</div>
                    <div class="ticket-header-actions">
                        <button type="button" class="btn-pdf btn-pdf--header pdf-hide" onclick="downloadBookingPdf()">Download PDF</button>
                        <div class="ticket-ref">REF-<?php echo str_pad($booking_id, 3, '0', STR_PAD_LEFT); ?></div>
                    </div>
                </div>
                <div class="ticket-grid">
                    <div>
                        <div style="margin-bottom: 15px;">
                            <span style="font-size: 11px; color: #888; text-transform: uppercase;">Room</span><br>
                            <strong><?php echo htmlspecialchars($accommodation_name); ?></strong>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <span style="font-size: 11px; color: #888; text-transform: uppercase;">Check-in / Check-out</span><br>
                            <strong><?php echo $checkin_fmt; ?> - <?php echo $checkout_fmt; ?></strong>
                        </div>
                        <div>
                            <span style="font-size: 11px; color: #888; text-transform: uppercase;">Guests</span><br>
                            <strong><?php echo $guests; ?> Adults</strong>
                        </div>
                    </div>
                    <div style="display:flex; justify-content:center; align-items:center;">
                        <img src="assets/qrcodes/qr_booking_<?php echo $booking_id; ?>.png" alt="Check-in QR" style="width: 120px; height: 120px; border: 1px solid #eee; padding: 5px; border-radius: 6px; background: #fff;">
                    </div>
                </div>
                <div style="margin-top:20px; font-size:12px; color:#666;">
                    Present this QR code at the front desk for a quick and seamless check-in.
                </div>
                <div style="margin-top:15px; padding:12px 14px; border:1px solid #f0e3d8; border-radius:8px; background:#fffaf6; font-size:12px; color:#6b4a35;">
                    Need to cancel later? Save this secure link:
                    <div style="margin-top:6px; word-break: break-all;">
                        <a href="<?php echo htmlspecialchars($cancel_url); ?>" style="color:#8B5E3C; font-weight:600;"><?php echo htmlspecialchars($cancel_url); ?></a>
                    </div>
                </div>
            </div>
            
            <div class="booking-success-actions">
                <a href="index.php" class="btn-home">Return to Home</a>
            </div>
        </div>
    </div>
    <?php else: ?>

    <div class="bk-layout">
        
        <!-- Top Progress Bar -->
        <div class="bk-progress">
            <div class="bk-step <?php echo $step >= 1 ? 'bk-step--active' : ''; ?> <?php echo $step > 1 ? 'bk-step--done' : ''; ?>">
                <div class="bk-step-num">
                    <?php if($step > 1): ?><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><?php else: ?>1<?php endif; ?>
                </div>
                <div class="bk-step-label">CONFIRMATION & EXTRAS</div>
            </div>
            <div class="bk-line"></div>
            <div class="bk-step <?php echo $step >= 2 ? 'bk-step--active' : ''; ?> <?php echo $step > 2 ? 'bk-step--done' : ''; ?>">
                <div class="bk-step-num">
                    <?php if($step > 2): ?><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><?php else: ?>2<?php endif; ?>
                </div>
                <div class="bk-step-label">GUEST DETAILS</div>
            </div>
            <div class="bk-line"></div>
            <div class="bk-step <?php echo $step >= 3 ? 'bk-step--active' : ''; ?>">
                <div class="bk-step-num">3</div>
                <div class="bk-step-label">PAYMENT</div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div style="color: #dc2626; background: #fef2f2; padding: 10px 15px; border-radius: 6px; margin-bottom: 20px; text-align:center;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="bk-columns">
            
            <!-- LEFT MAIN CONTENT -->
            <div class="bk-main">
                <form method="POST" action="book.php">

                    <?php if ($step === 1): ?>
                    <!-- STEP 1: CONFIRMATION & EXTRAS -->
                    <div class="bk-card">
                        <h2 class="bk-card-title">Room</h2>
                        <div class="bk-room-box">
                            <div class="bk-room-left">
                                <h3 class="bk-room-name">1 × <?php echo htmlspecialchars($accommodation_name); ?></h3>
                                <div style="margin-bottom:12px; display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:600; background:<?php echo ($available_rooms === 0) ? '#FEE2E2' : (($available_rooms !== null && $available_rooms <= 3) ? '#FFF7ED' : '#ECFDF5'); ?>; color:<?php echo ($available_rooms === 0) ? '#B91C1C' : (($available_rooms !== null && $available_rooms <= 3) ? '#B45309' : '#15803D'); ?>;">
                                    <?php if ($available_rooms === null): ?>
                                        Availability checking...
                                    <?php elseif ($available_rooms === 0): ?>
                                        Sold out for selected dates
                                    <?php elseif ($available_rooms <= 3): ?>
                                        Only <?php echo $available_rooms; ?> room<?php echo $available_rooms > 1 ? 's' : ''; ?> left
                                    <?php else: ?>
                                        <?php echo $available_rooms; ?> rooms available
                                    <?php endif; ?>
                                </div>
                                <ul class="bk-room-perks">
                                    <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Flexible cancellation <span class="info-icon">i</span></li>
                                    <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo $has_breakfast ? 'Breakfast included' : 'No breakfast included'; ?></li>
                                </ul>
                            </div>
                            <div class="bk-room-right">
                                <div class="bk-edit-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
                                <div class="bk-room-price">₱ <?php echo number_format($total_amount, 2); ?></div>
                                <div class="bk-room-subtext">1 room, <?php echo $nights; ?> night<?php echo $nights>1?'s':''; ?>, <?php echo $guests; ?> adult<?php echo $guests>1?'s':''; ?> included in price</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bk-actions">
                        <a href="rooms.php" class="btn-bk-back">← Back</a>
                        <input type="hidden" name="action" value="go_step_2">
                        <button type="submit" class="btn-bk-next" <?php echo ($available_rooms === 0) ? 'disabled' : ''; ?>>Next →</button>
                    </div>


                    <?php elseif ($step === 2): ?>
                    <!-- STEP 2: GUEST DETAILS -->
                    <div class="bk-card">
                        <h2 class="bk-card-title">Guest Details</h2>
                        
                        <div class="bk-form-grid">
                            <div class="bk-form-group">
                                <label>First name <span class="req">*</span></label>
                                <div class="bk-input-combo">
                                    <select name="title" class="bk-select-small">
                                        <option value="Mr.">Mr.</option>
                                        <option value="Ms.">Ms.</option>
                                        <option value="Mrs.">Mrs.</option>
                                    </select>
                                    <input type="text" name="first_name" placeholder="First name" required value="<?php echo htmlspecialchars($_SESSION['guest_first_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="bk-form-group">
                                <label>Last name <span class="req">*</span></label>
                                <input type="text" name="last_name" placeholder="Last name" required value="<?php echo htmlspecialchars($_SESSION['guest_last_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="bk-form-group">
                                <label>Email <span class="req">*</span></label>
                                <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($_SESSION['guest_email'] ?? ''); ?>">
                            </div>
                            <div class="bk-form-group">
                                <label>Retype email <span class="req">*</span></label>
                                <input type="email" name="email_confirm" placeholder="Retype email" required value="<?php echo htmlspecialchars($_SESSION['guest_email'] ?? ''); ?>">
                            </div>
                            
                            <div class="bk-form-group">
                                <label>Contact phone <span class="req">*</span></label>
                                <div class="bk-input-combo">
                                    <select name="phone_code" class="bk-select-small" style="width: 80px;">
                                        <option value="+63">🇵🇭 +63</option>
                                        <option value="+1">🇺🇸 +1</option>
                                    </select>
                                    <input type="text" name="phone" placeholder="" required value="<?php echo htmlspecialchars($_SESSION['guest_phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="bk-form-group">
                                <label>Country <span class="req">*</span></label>
                                <select name="country" required>
                                    <option value="">--Select--</option>
                                    <option value="Philippines" <?php echo (($_SESSION['guest_country']??'')=='Philippines')?'selected':''; ?>>Philippines</option>
                                    <option value="United States" <?php echo (($_SESSION['guest_country']??'')=='United States')?'selected':''; ?>>United States</option>
                                </select>
                            </div>
                            
                            <div class="bk-form-group" style="grid-column: 1 / -1;">
                                <label>Additional comments</label>
                                <textarea name="comments" placeholder="Additional comments" rows="3"><?php echo htmlspecialchars($_SESSION['guest_comments'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bk-actions">
                        <a href="book.php?step=1" class="btn-bk-back">← Back</a>
                        <input type="hidden" name="action" value="go_step_3">
                        <button type="submit" class="btn-bk-next">Next →</button>
                    </div>


                    <?php elseif ($step === 3): ?>
                    <!-- STEP 3: PAYMENT -->
                    <div class="bk-card">
                        <div class="bk-card-header-flex">
                            <h2 class="bk-card-title" style="margin:0;">Pay With</h2>
                            <div class="bk-pay-logos">
                                <span style="color:#1434CB; font-weight:700; font-style:italic;">VISA</span>
                                <span style="color:#EB001B; font-weight:700; font-style:italic;">mastercard</span>
                                <span style="font-size:11px; font-weight:700; display:flex; align-items:center;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                                    BANK TRANSFER
                                </span>
                                <span style="color:#007AFF; font-weight:800;">GCash</span>
                            </div>
                        </div>

                        <div class="bk-pay-option bk-pay-option--active" id="opt-card">
                            <label class="bk-pay-radio">
                                <input type="radio" name="payment_method" value="Card" checked onchange="bkSelectPayment('card')">
                                <span style="color:#1434CB; font-weight:700; font-style:italic; margin-right:4px;">VISA</span>
                                <span style="color:#EB001B; font-weight:700; font-style:italic; margin-right:8px;">MC</span>
                                <strong>Card</strong>
                            </label>

                            <div id="card-form" class="bk-card-form">
                                <div class="bk-card-brands">
                                    <span class="bk-cb bk-cb-visa">VISA</span>
                                    <span class="bk-cb bk-cb-mc"><div class="mc-circles"></div></span>
                                </div>
                                <div class="bk-form-group">
                                    <label>Card Number</label>
                                    <input type="text" placeholder="Card Number" class="bk-card-input">
                                </div>
                                <div class="bk-form-group">
                                    <label>Name On Card</label>
                                    <input type="text" placeholder="Name On Card">
                                </div>
                                <div class="bk-form-grid" style="grid-template-columns: 1fr 1fr;">
                                    <div class="bk-form-group">
                                        <label>Expiry Date</label>
                                        <input type="text" placeholder="MM/YY">
                                    </div>
                                    <div class="bk-form-group">
                                        <label>CVV</label>
                                        <input type="text" placeholder="CVV">
                                    </div>
                                </div>
                                <div class="bk-form-group">
                                    <label>Cardholder Email</label>
                                    <input type="email" placeholder="<?php echo htmlspecialchars($_SESSION['guest_email']??''); ?>" value="<?php echo htmlspecialchars($_SESSION['guest_email']??''); ?>">
                                </div>
                                <div class="bk-secured-by">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="border:1px solid #ccc; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:bold;">GeoTrust SECURED</div>
                                    </div>
                                    <div>Powered by <strong style="color: #2D3A9C; font-size:18px;">Kovena</strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="bk-pay-option" id="opt-bank">
                            <label class="bk-pay-radio">
                                <input type="radio" name="payment_method" value="Bank Deposit" onchange="bkSelectPayment('bank')">
                                <span style="font-size:11px; font-weight:700; display:flex; align-items:center; margin-right:8px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                                    BANK TRANSFER
                                </span>
                                <strong>Bank Deposit</strong>
                            </label>
                            <div id="bank-form" class="bk-card-form" style="display:none; padding-top: 15px;">
                                <p style="font-size:13px; color:#555;">Please transfer the deposit amount to our BDO Account and bring the receipt during check-in.</p>
                            </div>
                        </div>

                        <div class="bk-pay-option" id="opt-gcash">
                            <label class="bk-pay-radio">
                                <input type="radio" name="payment_method" value="GCash" onchange="bkSelectPayment('gcash')">
                                <span style="width:22px; height:22px; border-radius:6px; background:#007AFF; color:#fff; font-weight:800; font-size:13px; display:inline-flex; align-items:center; justify-content:center; margin-right:8px;">G</span>
                                <strong>GCash</strong>
                            </label>
                            <div id="gcash-form" class="bk-card-form" style="display:none; padding-top:15px; text-align:center;">
                                <p style="font-size:13px; color:#555; margin-bottom:14px;">Scan the QR code with your GCash app or send payment to the number below, then keep your reference number for check-in.</p>
                                <div style="display:inline-block; background:#f0f4ff; border:2px dashed #007AFF; border-radius:12px; padding:14px; margin-bottom:14px;">
                                    <img src="assets/gcash_qr.png?v=<?= time(); ?>" alt="GCash QR Code" width="180" height="180" style="display:block;" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=180x180&amp;data=GCash%3A09505223146%20Santa+Fe+Beach+Club'">
                                </div>
                                <div style="background:#f0f4ff; border-radius:10px; padding:12px 20px; margin:0 auto 6px; max-width:260px;">
                                    <div style="font-size:11px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">GCash Number</div>
                                    <div style="font-size:22px; font-weight:800; color:#007AFF; letter-spacing:2px;">09505223146</div>
                                    <div style="font-size:13px; color:#333; font-weight:600; margin-top:2px;">Santa Fe Beach Club</div>
                                </div>
                                <div class="bk-form-group" style="text-align:left; max-width:260px; margin:0 auto 10px;">
                                    <label>GCash Reference Number <span class="req">*</span></label>
                                    <input type="text" name="gcash_reference" id="gcashReferenceInput" placeholder="e.g. 1234 5678 9012" value="<?php echo htmlspecialchars($_POST['gcash_reference'] ?? ''); ?>">
                                    <div id="gcashRefError" style="display:none; align-items:center; gap:6px; margin-top:8px; padding:8px 10px; background:#FFF3E0; color:#E65100; border-radius:6px; font-size:12px; font-weight:600; text-align:left;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        <span>Please enter your GCash reference number to continue.</span>
                                    </div>
                                </div>
                                <p style="font-size:11px; color:#999;">Your booking will be marked pending until front desk confirms your GCash payment.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bk-card">
                        <h2 class="bk-card-title">Payment currency</h2>
                        <div class="bk-form-group">
                            <label>Your card will be charged in the selected currency <span class="info-icon">i</span></label>
                            <select class="bk-select-full">
                                <option value="PHP">Philippine Peso</option>
                            </select>
                            <div class="bk-warning-msg">
                                <span style="color:#eab308; margin-right:5px; font-weight:bold;">!</span> If you change the currency, you will need to re-enter your card details to ensure secure payment.
                            </div>
                        </div>
                    </div>

                    <div class="bk-card">
                        <h2 class="bk-card-title">Payment schedule</h2>
                        <div class="bk-schedule-row">
                            <span>Deposit due now</span>
                            <span class="bk-amt">₱ <?php echo number_format($deposit_amount, 2); ?></span>
                        </div>
                        <div class="bk-schedule-row bk-schedule-row--due">
                            <div>
                                Due on <?php echo date('d M Y', strtotime($checkin)); ?><br>
                                <small style="color:#888;">Amount</small><br>
                                <small style="color:#888;">Payment convenience fee</small>
                            </div>
                            <div style="text-align: right;">
                                <span class="bk-amt">₱ <?php echo number_format($total_amount - $deposit_amount, 2); ?></span><br>
                                <small style="color:#888;">₱ <?php echo number_format($total_amount - $deposit_amount, 2); ?></small><br>
                                <small style="color:#888;">₱ 0.00</small>
                            </div>
                        </div>
                    </div>

                    <div class="bk-card">
                        <h2 class="bk-card-title">Booking Policies</h2>
                        <p class="bk-policy-intro">Our booking includes items with different booking policies.</p>
                        <div class="bk-policy-box">
                            - 1,000+ 150/pax for excess of 10pax<br>
                            - 1,000 50 to 10 pax<br>
                            - 500 1 to 5 pax<br><br>
                            ☐ Check out time is 11:00 AM<br>
                            ☐ Check in time is 1:30 PM<br><br>
                            ☐ Restaurant time:<br>
                            ☐ Breakfast 7AM to 1:30 PM
                        </div>
                        <label class="bk-checkbox-label">
                            <input type="checkbox" required>
                            Please check this box to indicate that you have read and agree to the Booking Policies as well as the <a href="#">Kovena Payer Policy</a>.
                        </label>
                    </div>

                    <div class="bk-actions" style="justify-content: flex-start; gap: 15px;">
                        <a href="book.php?step=2" class="btn-bk-back">← Back</a>
                        <input type="hidden" name="action" value="confirm_booking">
                        <button type="submit" class="btn-bk-next" style="width: auto; padding: 14px 40px; margin-left: 0;">Confirm and book</button>
                    </div>

                    <?php endif; ?>

                </form>
            </div>

            <!-- RIGHT SIDEBAR (SUMMARY) -->
            <aside class="bk-sidebar">
                <div class="bk-sb-inner">
                    <h3 class="bk-sb-title">Booking Summary</h3>
                    
                    <?php if ($full_name): ?>
                    <div class="bk-sb-guest"><?php echo htmlspecialchars($full_name); ?></div>
                    <?php endif; ?>
                    
                    <div class="bk-sb-dates">
                        <div class="bk-sb-dcol">
                            <div class="bk-sb-dmain"><?php echo $checkin_fmt; ?></div>
                            <div class="bk-sb-dsub">from 13:30</div>
                        </div>
                        <div class="bk-sb-darrow">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                        <div class="bk-sb-dcol">
                            <div class="bk-sb-dmain"><?php echo $checkout_fmt; ?></div>
                            <div class="bk-sb-dsub">until 11:00</div>
                        </div>
                    </div>
                    
                    <div class="bk-sb-meta">
                        <span style="color:#0ea5e9;"><?php echo $nights; ?> night<?php echo $nights>1?'s':''; ?> | 1 room | <?php echo $guests; ?> adult<?php echo $guests>1?'s':''; ?></span>
                    </div>
                    
                    <div class="bk-sb-room-row">
                        <span>Room</span>
                        <span style="font-weight:600; color:#111;">₱ <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    
                    <div class="bk-sb-room-detail">
                        <strong>1 × <?php echo htmlspecialchars($accommodation_name); ?></strong><br>
                        <span style="color:#777; font-size:12px;"><?php echo $guests; ?> adult<?php echo $guests>1?'s':''; ?></span>
                    </div>
                    
                    <div class="bk-sb-total-row">
                        <span>Total</span>
                        <span class="bk-sb-total-val">₱ <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="bk-sb-tax-note">Price includes all taxes and fees</div>
                </div>
            </aside>
            
        </div>
    </div>
    
    <?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script>
function bkSelectPayment(method) {
    var forms = { card: 'card-form', bank: 'bank-form', gcash: 'gcash-form' };
    var opts  = { card: 'opt-card',  bank: 'opt-bank',  gcash: 'opt-gcash'  };
    Object.keys(forms).forEach(function (key) {
        var formEl = document.getElementById(forms[key]);
        var optEl  = document.getElementById(opts[key]);
        if (formEl) formEl.style.display = (key === method) ? 'block' : 'none';
        if (optEl)  optEl.classList.toggle('bk-pay-option--active', key === method);
    });
}

// Require the GCash reference number before letting the guest submit, but only when GCash is selected
(function () {
    var bookingForm = document.querySelector('form[action="book.php"]');
    var actionField = bookingForm ? bookingForm.querySelector('input[name="action"][value="confirm_booking"]') : null;
    if (!bookingForm || !actionField) return;

    var refInput = document.getElementById('gcashReferenceInput');
    var refError = document.getElementById('gcashRefError');

    if (refInput) {
        refInput.addEventListener('input', function () {
            if (refInput.value.trim() !== '' && refError) {
                refError.style.display = 'none';
                refInput.style.borderColor = '';
            }
        });
    }

    bookingForm.addEventListener('submit', function (e) {
        var selectedMethod = bookingForm.querySelector('input[name="payment_method"]:checked');
        if (selectedMethod && selectedMethod.value === 'GCash' && refInput && refInput.value.trim() === '') {
            e.preventDefault();
            if (refError) refError.style.display = 'flex';
            refInput.style.borderColor = '#E65100';
            refInput.focus();
            refInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();

async function downloadBookingPdf() {
    var ticket = document.querySelector('.ticket-pass');
    var button = document.querySelector('.btn-pdf--header');

    if (!ticket) {
        alert('Unable to find the booking ticket.');
        return;
    }

    if (!window.html2canvas || !window.jspdf || !window.jspdf.jsPDF) {
        alert('PDF tools are not ready yet. Please try again in a moment.');
        return;
    }

    var hiddenEls = ticket.querySelectorAll('.pdf-hide');
    var hiddenState = [];
    hiddenEls.forEach(function (el) {
        hiddenState.push({ el: el, display: el.style.display });
        el.style.display = 'none';
    });

    if (button) {
        button.disabled = true;
        button.textContent = 'Downloading...';
    }

    try {
        var canvas = await html2canvas(ticket, {
            scale: 2,
            backgroundColor: '#FFFDF9',
            useCORS: true,
            scrollY: -window.scrollY
        });

        var imgData = canvas.toDataURL('image/png');
        var pdf = new window.jspdf.jsPDF('p', 'mm', 'a4');
        var pageWidth = pdf.internal.pageSize.getWidth();
        var pageHeight = pdf.internal.pageSize.getHeight();
        var margin = 10;
        var maxWidth = pageWidth - (margin * 2);
        var maxHeight = pageHeight - (margin * 2);
        var scale = Math.min(maxWidth / canvas.width, maxHeight / canvas.height);
        var finalWidth = canvas.width * scale;
        var finalHeight = canvas.height * scale;
        var x = (pageWidth - finalWidth) / 2;
        var y = margin;

        pdf.addImage(imgData, 'PNG', x, y, finalWidth, finalHeight);
        pdf.save('booking-confirmation-REF-<?php echo str_pad($booking_id, 3, '0', STR_PAD_LEFT); ?>.pdf');
    } catch (error) {
        console.error(error);
        alert('Unable to download the PDF. Please try again.');
    } finally {
        hiddenState.forEach(function (item) {
            item.el.style.display = item.display;
        });
        if (button) {
            button.disabled = false;
            button.textContent = 'Download PDF';
        }
    }
}
</script>
</body>
</html>
