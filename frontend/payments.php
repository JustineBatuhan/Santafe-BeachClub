<?php
require_once __DIR__ . '/../backend/helpers/auth_check.php';
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/../backend/services/mailer.php';

function recordPaymentAction(mysqli $conn, int $paymentId, string $action, string $details = ''): void
{
    $performedBy = $_SESSION['admin_username'] ?? 'System';
    $stmt = $conn->prepare("INSERT INTO payment_action_history (payment_id, action, performed_by, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $paymentId, $action, $performedBy, $details);
    $stmt->execute();
    $stmt->close();
}

// Handle payment processing action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_csrf_token();

    if ($_POST['action'] === 'process_payment' || $_POST['action'] === 'verify_payment') {
        $pay_id = intval($_POST['payment_id']);
        $method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Front Desk Cash';
        
        $stmt = $conn->prepare("UPDATE payments SET status = 'verified', payment_method = ? WHERE id = ?");
        $stmt->bind_param("si", $method, $pay_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Confirmed' WHERE id = (SELECT booking_id FROM payments WHERE id = ?)");
        $stmt->bind_param("i", $pay_id);
        $stmt->execute();

        recordPaymentAction($conn, $pay_id, 'verified', 'Payment verified using ' . $method . '.');

        $booking_id_stmt = $conn->prepare("SELECT booking_id FROM payments WHERE id = ?");
        $booking_id_stmt->bind_param("i", $pay_id);
        $booking_id_stmt->execute();
        $booking_id_result = $booking_id_stmt->get_result()->fetch_assoc();
        $booking_id_stmt->close();
        $verified_booking_id = (int)($booking_id_result['booking_id'] ?? 0);

        // Automatically dispatch confirmation email with check-in QR code pass
        if ($verified_booking_id > 0) {
            $stmt = $conn->prepare("SELECT guest_name, guest_email, accommodation_name, check_in, check_out, cancellation_token, checkin_token FROM bookings WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $verified_booking_id);
            $stmt->execute();
            $b_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($b_data && !empty($b_data['guest_email'])) {
                $b_ref = 'REF-' . str_pad($verified_booking_id, 3, '0', STR_PAD_LEFT);
                $b_base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
                $b_cancellation_token = $b_data['cancellation_token'];
                if (empty($b_cancellation_token)) {
                    $b_cancellation_token = bin2hex(random_bytes(16));
                    $t_stmt = $conn->prepare("UPDATE bookings SET cancellation_token = ? WHERE id = ?");
                    $t_stmt->bind_param("si", $b_cancellation_token, $verified_booking_id);
                    $t_stmt->execute();
                    $t_stmt->close();
                }

                $b_cancellation_url = rtrim($b_base_url, '/') . '/cancel_booking?token=' . urlencode($b_cancellation_token);
                $b_checkin_url = rtrim($b_base_url, '/') . '/checkin?ref=' . urlencode($b_ref) . '&token=' . urlencode($b_data['checkin_token'] ?? '');
                
                $amt_stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS amount FROM payments WHERE booking_id = ? AND status = 'verified'");
                $amt_stmt->bind_param("i", $verified_booking_id);
                $amt_stmt->execute();
                $b_amount = (float)($amt_stmt->get_result()->fetch_assoc()['amount'] ?? 0);
                $amt_stmt->close();

                @sendBookingConfirmationEmail(
                    $b_data['guest_email'],
                    $b_data['guest_name'],
                    $b_ref,
                    $b_data['accommodation_name'],
                    $b_data['check_in'],
                    $b_data['check_out'],
                    $b_amount,
                    $b_cancellation_url,
                    $b_checkin_url
                );
            }
        }
        
        header("Location: payments?success=1&send_booking_id=" . $verified_booking_id);
        exit;
    } elseif ($_POST['action'] === 'send_confirmation_email') {
        $booking_id = intval($_POST['booking_id']);
        $stmt = $conn->prepare("SELECT guest_name, guest_email, accommodation_name, check_in, check_out, cancellation_token, checkin_token FROM bookings WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            header("Location: payments?email_error=Booking%20not%20found");
            exit;
        }

        $booking_ref = 'REF-' . str_pad($booking_id, 3, '0', STR_PAD_LEFT);
        $base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
        $cancellation_token = $booking['cancellation_token'];
        if (empty($cancellation_token)) {
            $cancellation_token = bin2hex(random_bytes(16));
            $token_stmt = $conn->prepare("UPDATE bookings SET cancellation_token = ? WHERE id = ?");
            $token_stmt->bind_param("si", $cancellation_token, $booking_id);
            $token_stmt->execute();
            $token_stmt->close();
        }

        $cancellation_url = rtrim($base_url, '/') . '/cancel_booking?token=' . urlencode($cancellation_token);
        $checkin_url = rtrim($base_url, '/') . '/checkin?ref=' . urlencode($booking_ref) . '&token=' . urlencode($booking['checkin_token'] ?? '');
        $amount_stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS amount FROM payments WHERE booking_id = ? AND status = 'verified'");
        $amount_stmt->bind_param("i", $booking_id);
        $amount_stmt->execute();
        $amount = (float)($amount_stmt->get_result()->fetch_assoc()['amount'] ?? 0);
        $amount_stmt->close();

        if (empty($booking['guest_email'])) {
            header("Location: payments?email_error=Guest%20has%20no%20email%20address");
            exit;
        }

        $send_result = sendBookingConfirmationEmail(
            $booking['guest_email'],
            $booking['guest_name'],
            $booking_ref,
            $booking['accommodation_name'],
            $booking['check_in'],
            $booking['check_out'],
            $amount,
            $cancellation_url,
            $checkin_url
        );
        $query_key = $send_result['success'] ? 'email_sent=1' : 'email_error=' . urlencode($send_result['error'] ?? 'Email sending failed');
        header("Location: payments?{$query_key}");
        exit;
    } elseif ($_POST['action'] === 'reject_payment') {
        $pay_id = intval($_POST['payment_id']);

        $stmt = $conn->prepare("UPDATE payments SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $pay_id);
        $stmt->execute();

        recordPaymentAction($conn, $pay_id, 'rejected', 'Payment rejected.');

        // Fetch booking details before cancelling, so we have guest info for the email
        $rej_stmt = $conn->prepare(
            "SELECT b.id, b.guest_name, b.guest_email, b.accommodation_name, b.check_in, b.check_out
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             WHERE p.id = ? LIMIT 1"
        );
        $rej_stmt->bind_param("i", $pay_id);
        $rej_stmt->execute();
        $rej_booking = $rej_stmt->get_result()->fetch_assoc();
        $rej_stmt->close();

        $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = (SELECT booking_id FROM payments WHERE id = ?)");
        $stmt->bind_param("i", $pay_id);
        $stmt->execute();

        // Send emails to guest (non-fatal — failure doesn't block redirect)
        if ($rej_booking && !empty($rej_booking['guest_email'])) {
            $rej_ref = 'REF-' . str_pad((int)$rej_booking['id'], 3, '0', STR_PAD_LEFT);
            sendPaymentRejectedEmail(
                $rej_booking['guest_email'],
                $rej_booking['guest_name'],
                $rej_ref,
                $rej_booking['accommodation_name'],
                $rej_booking['check_in'],
                $rej_booking['check_out']
            );
            sendBookingCancellationEmail(
                $rej_booking['guest_email'],
                $rej_booking['guest_name'],
                $rej_ref,
                $rej_booking['accommodation_name'],
                $rej_booking['check_in'],
                $rej_booking['check_out']
            );
        }

        header("Location: payments?rejected=1");
        exit;
    } elseif ($_POST['action'] === 'refund_payment') {
        $pay_id = intval($_POST['payment_id']);
        $refund_reason = trim($_POST['refund_reason'] ?? 'No reason provided');

        $stmt = $conn->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?");
        $stmt->bind_param("i", $pay_id);
        $stmt->execute();

        recordPaymentAction($conn, $pay_id, 'refunded', 'Refund reason: ' . $refund_reason);

        // Get booking ID to update booking status and log
        $bstmt = $conn->prepare("SELECT booking_id, guest_name, amount FROM payments WHERE id = ?");
        $bstmt->bind_param("i", $pay_id);
        $bstmt->execute();
        $prow = $bstmt->get_result()->fetch_assoc();
        $bstmt->close();

        if ($prow) {
            $stmt2 = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
            $stmt2->bind_param("i", $prow['booking_id']);
            $stmt2->execute();
            $stmt2->close();

            // Log in activity_logs
            $admin_user = $_SESSION['admin_username'] ?? 'system';
            $detail = "Refunded PHP " . number_format($prow['amount'], 2) . " for payment ID {$pay_id} (Guest: {$prow['guest_name']}). Reason: {$refund_reason}";
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $logstmt = $conn->prepare("INSERT INTO activity_logs (admin_username, action, details, ip_address) VALUES (?, 'Payment Refunded', ?, ?)");
            $logstmt->bind_param("sss", $admin_user, $detail, $ip);
            $logstmt->execute();
            $logstmt->close();

            // Add notification
            $notif_title = 'Payment Refunded';
            $notif_msg = "Payment INV-100{$pay_id} for guest {$prow['guest_name']} has been marked as refunded. Reason: {$refund_reason}";
            $notif_type = 'warning';
            $nstmt = $conn->prepare("INSERT INTO notifications (title, message, type, booking_id) VALUES (?, ?, ?, ?)");
            $nstmt->bind_param("sssi", $notif_title, $notif_msg, $notif_type, $prow['booking_id']);
            $nstmt->execute();
            $nstmt->close();
        }

        header("Location: payments?refunded=1");
        exit;
    }
}

// Fetch all payment records joined with bookings and rooms
$payments_query = $conn->query("
    SELECT 
        p.id as pay_id,
        p.booking_id,
        COALESCE(NULLIF(p.guest_name, ''), b.guest_name, 'Unknown Guest') as guest_name,
        p.guest_email,
        p.amount,
        p.amount_tendered,
        p.change_amount,
        p.payment_method,
        p.transaction_id,
        p.status as payment_status,
        p.receipt_url,
        p.paid_at,
        b.accommodation_name,
        b.check_in,
        b.check_out,
        DATEDIFF(b.check_out, b.check_in) as nights,
        r.price_per_night
    FROM payments p
    LEFT JOIN bookings b ON p.booking_id = b.id
    LEFT JOIN rooms r ON b.room_id = r.id
    ORDER BY p.id DESC
");

$payment_history = [];
$history_query = $conn->query("SELECT payment_id, action, performed_by, details, performed_at FROM payment_action_history ORDER BY performed_at DESC, id DESC");
if ($history_query) {
    while ($history_row = $history_query->fetch_assoc()) {
        $payment_history[(int)$history_row['payment_id']][] = $history_row;
    }
}

// Pre-fetch all verified payments to group them by booking_id for the receipt breakdown
$breakdown_map = [];
$bk_res = $conn->query("SELECT id as pay_id, booking_id, amount, amount_tendered, change_amount, payment_method, status FROM payments WHERE status = 'verified' ORDER BY id ASC");
if ($bk_res) {
    while($row = $bk_res->fetch_assoc()) {
        $bid = $row['booking_id'];
        if (!isset($breakdown_map[$bid])) $breakdown_map[$bid] = [];
        $breakdown_map[$bid][] = [
            'pay_id' => $row['pay_id'],
            'amount' => floatval($row['amount']),
            'tendered' => floatval($row['amount_tendered'] ?? $row['amount']),
            'change' => floatval($row['change_amount'] ?? 0),
            'method' => $row['payment_method']
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=3">
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
        .btn-pay {
            background-color: #2E7D32;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-pay:hover {
            background-color: #1B5E20;
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
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }
        .status-paid { background: #E8F5E9; color: #2E7D32; }
        .status-pending { background: #FFF3E0; color: #E65100; }
        .status-rejected { background: #FFEBEE; color: #C62828; }
        .status-refunded { background: #F3E5F5; color: #6A1B9A; }
        .alert-success {
            background-color: #E8F5E9;
            color: #2E7D32;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Modals & Overlays */
        .gcash-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }
        .gcash-overlay.active { display: flex !important; }
        .gcash-modal {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.25);
            animation: popIn 0.2s ease;
        }
        @keyframes popIn {
            from { transform: scale(0.92); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }
    </style>
</head>
<body>

    <?php $active_page = 'payments'; include __DIR__ . '/partials/_sidebar.php'; ?>

    <!-- Main Dashboard Panel -->
    <main class="main-content">
        <!-- Top Bar (shared component, same as Dashboard) -->
        <?php
        $page_title = 'Payment Processing';
        $page_subtitle = 'Manage bills and transactions';
        $header_extra_html = '
            <div class="search-wrapper">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" placeholder="Search invoice..." class="search-input" id="paymentSearch">
            </div>
            <div class="filter-wrapper" style="margin-left: 12px; display: flex; align-items: center;">
                <select id="paymentStatusFilter" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #D1D5DB; outline: none; font-size: 14px; font-family: inherit; color: #374151; background-color: #fff;">
                    <option value="">All Statuses</option>
                    <option value="verified">Verified</option>
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
        ';
        include __DIR__ . '/partials/_page_header.php';
        ?>

        <section class="dashboard-grid" style="grid-template-columns: 1fr;">
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success">
                    ✅ Payment verified successfully! Booking has been confirmed.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['email_sent'])): ?>
                <div class="alert-success">
                    ✅ Confirmation email sent to the guest successfully.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['email_error'])): ?>
                <div class="alert-success" style="background-color:#FFEBEE; color:#C62828;">
                    ❌ Confirmation email was not sent: <?php echo htmlspecialchars($_GET['email_error']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['rejected'])): ?>
                <div class="alert-success" style="background-color:#FFEBEE; color:#C62828;">
                    ❌ Payment rejected. Booking has been cancelled.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['refunded'])): ?>
                <div class="alert-success" style="background-color:#F3E5F5; color:#6A1B9A;">
                    💜 Payment marked as refunded. Booking has been cancelled and activity logged.
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Outstanding &amp; Settled Bills</h2>
                </div>

                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Guest Name</th>
                            <th>Accommodation</th>
                            <th>Payment Channel</th>
                            <th>Total Amount</th>
                            <th>Receipt</th>
                            <th>Status</th>
                            <th>Payment History</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($payments_query && $payments_query->num_rows > 0) {
                            while ($row = $payments_query->fetch_assoc()) {
                                $pay_id = htmlspecialchars($row['pay_id']);
                                $name = htmlspecialchars($row['guest_name']);
                                $room = htmlspecialchars($row['accommodation_name'] ?: 'Standard Room');
                                $method = htmlspecialchars($row['payment_method'] ?: 'Pay at Check-in');
                                $txn = htmlspecialchars($row['transaction_id'] ?: ('TXN-' . $pay_id));
                                $amount = number_format($row['amount'], 2);
                                $raw_amount = $row['amount'];
                                $pay_status_display = strtolower($row['payment_status']);
                                $receipt_url = $row['receipt_url'];

                                $pay_class = 'status-pending';
                                if ($pay_status_display === 'verified' || $pay_status_display === 'paid') $pay_class = 'status-paid';
                                if ($pay_status_display === 'rejected') $pay_class = 'status-rejected';
                                if ($pay_status_display === 'refunded') $pay_class = 'status-refunded';

                                echo "<tr>";
                                echo "<td><strong>INV-100{$pay_id}</strong></td>";
                                echo "<td>{$name}</td>";
                                echo "<td>{$room}</td>";
                                echo "<td>
                                        <div style='font-weight:600; font-size:13px;'>{$method}</div>
                                        <div style='font-size:11px; color:#888;'>{$txn}</div>
                                      </td>";
                                echo "<td><strong>PHP {$amount}</strong></td>";
                                
                                if (!empty($receipt_url)) {
                                    $safe_url = htmlspecialchars($receipt_url);
                                    echo "<td><a href='javascript:void(0)' onclick='openProofImageModal(\"{$safe_url}\")' style='color:#007AFF; font-size:12px; font-weight:600; text-decoration:underline;'>View Receipt</a></td>";
                                } else {
                                    echo "<td><span style='color:#999; font-size:12px;'>No Receipt</span></td>";
                                }
                                
                                echo "<td><span class='status-badge {$pay_class}'>".ucfirst($pay_status_display)."</span></td>";
                                echo "<td style='font-size:12px; color:#64748B; min-width:170px;'>";
                                if (!empty($payment_history[(int)$row['pay_id']])) {
                                    foreach ($payment_history[(int)$row['pay_id']] as $history) {
                                        $history_action = ucfirst(htmlspecialchars($history['action']));
                                        $history_by = htmlspecialchars($history['performed_by']);
                                        $history_time = date('M j, Y g:i A', strtotime($history['performed_at']));
                                        echo "<div style='margin-bottom:5px;'><strong style='color:#334155;'>{$history_action}</strong><br><span>by {$history_by} · {$history_time}</span></div>";
                                    }
                                } else {
                                    echo "<span style='color:#94A3B8;'>No actions recorded</span>";
                                }
                                echo "</td>";
                                echo "<td>";
                                
                                if ($pay_status_display === 'pending') {
                                    $csrf = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
                                    echo "
                                    <div style='display:flex; gap:6px; align-items:center; flex-wrap:wrap;'>
                                        <form method='POST' action='payments' style='margin:0; display:flex; gap:6px; align-items:center;'>
                                            <input type='hidden' name='csrf_token' value='{$csrf}'>
                                            <input type='hidden' name='action' value='verify_payment'>
                                            <input type='hidden' name='payment_id' value='{$pay_id}'>
                                            <select name='payment_method' style='padding:6px; font-size:12px; border:1px solid #ccc; border-radius:4px; background:white;'>
                                                <option value='Front Desk Cash' ".($method=='Front Desk Cash'?'selected':'').">Cash</option>
                                                <option value='Front Desk Card' ".($method=='Front Desk Card'?'selected':'').">POS Card</option>
                                                <option value='GCash QR' ".($method=='GCash' || $method=='GCash QR'?'selected':'').">GCash</option>
                                                <option value='Bank Deposit' ".($method=='Bank Deposit'?'selected':'').">Bank Deposit</option>
                                            </select>
                                            <button type='submit' class='btn-pay' style='padding:6px 12px; font-size:12px;'>Verify</button>
                                        </form>
                                        <form method='POST' action='payments' style='margin:0;'>
                                            <input type='hidden' name='csrf_token' value='{$csrf}'>
                                            <input type='hidden' name='action' value='reject_payment'>
                                            <input type='hidden' name='payment_id' value='{$pay_id}'>
                                            <button type='button' class='btn-receipt' style='padding:6px 12px; font-size:12px; color:#d32f2f; border-color:#d32f2f;' onclick='showConfirm({ title: \"Reject Payment\", message: \"Reject this payment and cancel the booking? This cannot be undone.\", icon: \"❌\", iconBg: \"#FEE2E2\", confirmText: \"Reject\", onConfirm: () => this.closest(\"form\").submit() })'>Reject</button>
                                        </form>
                                    </div>";

                                } elseif ($pay_status_display === 'verified' || $pay_status_display === 'paid') {
                                    $rcpt_num = 'RCPT-' . str_pad($pay_id, 6, '0', STR_PAD_LEFT);
                                    $nights = max(1, intval($row['nights'] ?? 1));
                                    $price_per_night = floatval($row['price_per_night'] ?? 0);
                                    $b_total = ($price_per_night > 0) ? ($price_per_night * $nights) : $raw_amount;
                                    if ($b_total < $raw_amount) $b_total = $raw_amount;
                                    $b_paid = $raw_amount;
                                    $bid = $row['booking_id'];
                                    echo "<div style='display:flex; gap:6px; align-items:center; flex-wrap:wrap;'>";
                                    echo "<button class='btn-receipt' style='padding:6px 12px; font-size:12px;' onclick='openReceiptModal({$bid}, \"{$rcpt_num}\", \"INV-100{$pay_id}\", \"" . addslashes($name) . "\", \"" . addslashes($room) . "\", {$b_total})'>Print Receipt</button>";
                                    echo "<a href='payments?send_booking_id={$bid}' class='btn-pay' style='padding:6px 12px; font-size:12px; text-decoration:none;'>Send Confirmation</a>";
                                    echo "<button class='btn-receipt' style='padding:6px 12px; font-size:12px; color:#6A1B9A; border-color:#6A1B9A;' onclick='openRefundModal({$pay_id}, \"{$name}\", \"PHP {$amount}\")'>Refund</button>";
                                    echo "</div>";
                                } elseif ($pay_status_display === 'refunded') {
                                    echo "<span style='font-size:12px; color:#6A1B9A; font-weight:600;'>💜 Refunded</span>";
                                } else {
                                    echo "<span style='font-size:12px; color:#888;'>Rejected</span>";
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' style='text-align: center; color: #888; padding: 20px;'>No payment records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php if (isset($_GET['send_booking_id']) && (int)$_GET['send_booking_id'] > 0): ?>
        <?php
            $send_booking_id = (int)$_GET['send_booking_id'];
            $send_stmt = $conn->prepare("SELECT guest_name, guest_email FROM bookings WHERE id = ? LIMIT 1");
            $send_stmt->bind_param("i", $send_booking_id);
            $send_stmt->execute();
            $send_booking = $send_stmt->get_result()->fetch_assoc();
            $send_stmt->close();
        ?>
        <?php if ($send_booking): ?>
            <div class="gcash-overlay active" id="sendConfirmationOverlay">
                <div class="gcash-modal" style="max-width:440px;">
                    <div style="width:54px;height:54px;border-radius:50%;background:#EFF6FF;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-0.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <h3 style="margin:0 0 8px;font-size:18px;font-weight:700;color:#0f172a;">Send Confirmation Email?</h3>
                    <p style="margin:0 0 8px;font-size:14px;color:#475569;line-height:1.6;">Payment verified. Send the booking code and check-in QR code to:</p>
                    <p style="margin:0 0 24px;font-size:14px;font-weight:700;color:#0f172a;word-break:break-word;"><?php echo htmlspecialchars($send_booking['guest_email'] ?: 'No email address'); ?></p>
                    <div style="display:flex;gap:10px;justify-content:center;">
                        <a href="payments" class="btn-receipt" style="flex:1;padding:11px 0;text-decoration:none;">Not Now</a>
                        <?php if (!empty($send_booking['guest_email'])): ?>
                            <form method="POST" action="payments" style="flex:1;margin:0;">
                                <input type="hidden" name="action" value="send_confirmation_email">
                                <input type="hidden" name="booking_id" value="<?php echo $send_booking_id; ?>">
                                <button type="submit" class="btn-pay" style="width:100%;padding:11px 0;">Yes, Send Email</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Proof of Payment Image Lightbox Modal -->
    <div class="gcash-overlay" id="proofOverlay">
        <div class="gcash-modal" style="max-width:500px; padding:20px; text-align:center;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h3 style="font-size:16px; font-weight:700; margin:0;">Guest Payment Proof</h3>
                <button type="button" onclick="closeProofModal()" style="background:none; border:none; font-size:22px; color:#888; cursor:pointer;">&times;</button>
            </div>
            <div style="max-height:70vh; overflow-y:auto; border-radius:8px; background:#f5f5f5; padding:8px;">
                <img id="proofModalImg" src="" alt="Proof of Payment" style="max-width:100%; border-radius:6px; display:block; margin:0 auto;">
            </div>
            <button type="button" class="btn-receipt" onclick="closeProofModal()" style="margin-top:14px; width:100%; padding:9px;">Close</button>
        </div>
    </div>

    <!-- Refund Confirmation Modal -->
    <div class="gcash-overlay" id="refundOverlay">
        <div class="gcash-modal" style="max-width:440px; text-align:left;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-size:17px; font-weight:700; margin:0; color:#6A1B9A;">💜 Issue Refund</h3>
                <button type="button" onclick="closeRefundModal()" style="background:none; border:none; font-size:22px; color:#888; cursor:pointer;">&times;</button>
            </div>
            <div id="refundModalInfo" style="background:#F3E5F5; border-radius:8px; padding:12px 14px; margin-bottom:16px; font-size:14px; color:#4A148C;"></div>
            <form method="POST" action="payments" id="refundForm">
                <input type="hidden" name="action" value="refund_payment">
                <input type="hidden" name="payment_id" id="refundPaymentId">
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Reason for Refund <span style="color:#d32f2f;">*</span></label>
                    <select name="refund_reason" id="refundReasonSelect" style="width:100%; padding:10px 12px; border:1.5px solid #D1D5DB; border-radius:8px; font-size:14px; font-family:inherit; color:#374151; background:#fff;" onchange="toggleCustomReason(this.value)">
                        <option value="">— Select a reason —</option>
                        <option value="Guest requested cancellation">Guest requested cancellation</option>
                        <option value="Booking error / duplicate">Booking error / duplicate</option>
                        <option value="No show — policy refund">No show — policy refund</option>
                        <option value="Overcharge correction">Overcharge correction</option>
                        <option value="Other">Other (specify below)</option>
                    </select>
                </div>
                <div id="customReasonWrap" style="display:none; margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Custom Reason</label>
                    <input type="text" id="customReasonInput" placeholder="Describe the reason..." style="width:100%; padding:10px 12px; border:1.5px solid #D1D5DB; border-radius:8px; font-size:14px; font-family:inherit; box-sizing:border-box;">
                </div>
                <div style="background:#FFF3E0; border-radius:8px; padding:10px 14px; margin-bottom:18px; font-size:13px; color:#E65100;">
                    ⚠️ This will mark the payment as <strong>Refunded</strong> and cancel the associated booking. This action cannot be undone.
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="closeRefundModal()" class="btn-receipt" style="flex:1; padding:10px;">Cancel</button>
                    <button type="button" onclick="submitRefund()" style="flex:1; padding:10px; background:#6A1B9A; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer;">Confirm Refund</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Receipt Preview Modal -->
    <div class="gcash-overlay" id="receiptOverlay">
        <div style="background:#ffffff; border-radius:16px; width:92%; max-width:440px; box-shadow:0 12px 48px rgba(0,0,0,0.25); display:flex; flex-direction:column; max-height:90vh; overflow:hidden; animation:popIn 0.2s ease;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid #f0f0f0; background:#FAFAFA;">
                <span style="font-weight:700; font-size:15px; color:#1F2937;">Official Receipt Preview</span>
                <button type="button" onclick="closeReceiptModal()" style="background:none; border:none; font-size:24px; color:#9CA3AF; cursor:pointer; line-height:1;">&times;</button>
            </div>
            <div id="receiptPreview" style="background:#fff; color:#000; font-family:'Courier New',Courier,monospace; padding:20px 24px; font-size:13px; line-height:1.6; text-align:center; overflow-y:auto; flex:1;">
                <!-- Filled by JS -->
            </div>
            <div style="padding:14px 20px; display:flex; gap:12px; justify-content:flex-end; background:#FAFAFA; border-top:1px solid #f0f0f0;">
                <button type="button" onclick="closeReceiptModal()" style="background:#fff; border:1px solid #D1D5DB; color:#374151; padding:9px 18px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;">Close</button>
                <button type="button" onclick="doPrintReceipt()" style="background:#7C533C; border:none; color:#fff; padding:9px 24px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(124,83,60,0.3);">🖨️ Print Receipt</button>
            </div>
        </div>
    </div>

    <script>
        const PAYMENTS_BREAKDOWN = <?php echo json_encode($breakdown_map); ?>;
        // ── Proof Image Modal ──
        function openProofImageModal(url) {
            const img = document.getElementById('proofModalImg');
            
            // Format URL safely whether accessed via /frontend/ or clean root path
            let finalUrl = url;
            if (!url.startsWith('http') && !url.startsWith('/')) {
                if (window.location.pathname.includes('/frontend/')) {
                    finalUrl = url;
                } else {
                    finalUrl = 'frontend/' + url;
                }
            }
            
            img.src = finalUrl;
            img.onerror = function() {
                // If clean URL failed, try fallback path
                if (!this.src.includes('frontend/')) {
                    this.src = 'frontend/' + url;
                }
            };

            const ov = document.getElementById('proofOverlay');
            ov.classList.add('active');
            ov.style.display = 'flex';
        }
        function closeProofModal() {
            const ov = document.getElementById('proofOverlay');
            ov.classList.remove('active');
            ov.style.display = 'none';
        }
        document.getElementById('proofOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeProofModal();
        });

        // ── Official Receipt Modal ──
        const RECEPTIONIST_NAME = <?php echo json_encode($_SESSION['admin_username'] ?? 'Administrator'); ?>;
        let _receiptData = {};

        function openReceiptModal(bid, rcpt, inv, guest, room, totalCost) {
            const payments = PAYMENTS_BREAKDOWN[bid] || [];
            const totalPaid = payments.reduce((sum, p) => sum + p.amount, 0);
            _receiptData = { bid, rcpt, inv, guest, room, totalCost: parseFloat(totalCost) || 0, payments, totalPaid };
            renderReceipt();
            const ov = document.getElementById('receiptOverlay');
            ov.classList.add('active');
            ov.style.display = 'flex';
        }

        function closeReceiptModal() {
            const ov = document.getElementById('receiptOverlay');
            ov.classList.remove('active');
            ov.style.display = 'none';
        }

        document.getElementById('receiptOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeReceiptModal();
        });

        function renderReceipt() {
            const d = _receiptData;
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            const sep = '<div style="color:#000; letter-spacing:2px;">--------------------------------------</div>';
            const fmt = (v) => '₱ ' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const balance = Math.max(0, d.totalCost - d.totalPaid);

            let breakdownHtml = `<div style="margin:12px 0 4px; font-size:13px; font-weight:800; letter-spacing:1px; text-align:center;">PAYMENT BREAKDOWN</div>`;
            if (d.payments.length === 0) {
                breakdownHtml += `<div style="text-align:center; font-style:italic;">No payments recorded</div>`;
            } else {
                d.payments.forEach((p, i) => {
                    breakdownHtml += `<div style="text-align:left; margin-bottom:8px;">
                        <div style="display:flex; justify-content:space-between;"><span>${i+1}. ${p.method}</span><strong>${fmt(p.amount)}</strong></div>`;
                    if (p.method === 'Front Desk Cash' && p.tendered > p.amount) {
                        breakdownHtml += `<div style="display:flex; justify-content:space-between; color:#666; font-size:11px; padding-left:12px;"><span>Amount Tendered:</span><span>${fmt(p.tendered)}</span></div>`;
                        breakdownHtml += `<div style="display:flex; justify-content:space-between; color:#666; font-size:11px; padding-left:12px;"><span>Change:</span><span>${fmt(p.change)}</span></div>`;
                    }
                    breakdownHtml += `</div>`;
                });
            }

            document.getElementById('receiptPreview').innerHTML = `
                <div style="font-size:16px; font-weight:800; letter-spacing:1px; margin-top:8px;">SANTA FE BEACH CLUB</div>
                <div style="font-size:12px; color:#666;">Bantayan Island, Cebu</div>
                <div style="font-size:12px; color:#666; margin-bottom:8px;">Official Payment Receipt</div>
                ${sep}
                <div style="text-align:left; margin:8px 0;">
                    <div style="display:flex; justify-content:space-between;"><span>Receipt #:</span><strong>${d.rcpt}</strong></div>
                    <div style="display:flex; justify-content:space-between;"><span>Invoice #:</span><strong>${d.inv}</strong></div>
                    <div style="display:flex; justify-content:space-between;"><span>Date & Time:</span><strong>${dateStr}, ${timeStr}</strong></div>
                </div>
                ${sep}
                <div style="text-align:left; margin:8px 0;">
                    <div style="display:flex; justify-content:space-between;"><span>Guest Name:</span><strong>${d.guest}</strong></div>
                    <div style="display:flex; justify-content:space-between;"><span>Accommodation:</span><strong>${d.room}</strong></div>
                </div>
                ${sep}
                <div style="text-align:left; margin:8px 0;">
                    <div style="display:flex; justify-content:space-between; color:#666;"><span>Total Booking Cost:</span><strong>${fmt(d.totalCost)}</strong></div>
                    ${balance > 0.01 ? `<div style="display:flex; justify-content:space-between; color:#d32f2f; margin-top:4px;"><span>Remaining Balance:</span><strong>${fmt(balance)}</strong></div>` : ''}
                </div>
                ${sep}
                ${breakdownHtml}
                ${sep}
                <div style="margin:12px 0 4px; font-size:15px; font-weight:800; letter-spacing:1px;">TOTAL PAID</div>
                <div style="font-size:22px; font-weight:900; letter-spacing:1px; margin-bottom:8px; color:#7C533C;">${fmt(d.totalPaid)}</div>
                ${sep}
                <div style="text-align:left; margin:8px 0; font-weight:700;">
                    ${balance > 0.01 ? `<div style="display:flex; justify-content:space-between; color:#d32f2f;"><span>Status:</span><strong>PARTIAL / BALANCE DUE</strong></div>` : `<div style="display:flex; justify-content:space-between; color:#2E7D32;"><span>Status:</span><strong>PAID IN FULL</strong></div>`}
                </div>
                ${sep}
                <div style="text-align:left; margin:8px 0;">
                    <div style="display:flex; justify-content:space-between;"><span>Staff:</span><strong>${RECEPTIONIST_NAME}</strong></div>
                </div>
                ${sep}
                <div style="color:#666; font-size:12px; margin-top:10px;">Thank you for staying with us!<br>Have a safe trip!</div>
                <div style="color:#888; font-size:11px; margin-top:8px;">This is an official receipt.</div>
            `;
        }

        function doPrintReceipt() {
            const d = _receiptData;
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            const fmt = (v) => '₱ ' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const balance = Math.max(0, d.totalCost - d.totalPaid);
            const sep = '--------------------------------------';

            let breakdownHtml = `<div class="center total-label">PAYMENT BREAKDOWN</div>`;
            if (d.payments.length === 0) {
                breakdownHtml += `<div class="center" style="font-style:italic; margin-bottom:6px;">No payments recorded</div>`;
            } else {
                d.payments.forEach((p, i) => {
                    breakdownHtml += `<div class="row"><span>${i+1}. ${p.method}</span><strong>${fmt(p.amount)}</strong></div>`;
                    if (p.method === 'Front Desk Cash' && p.tendered > p.amount) {
                        breakdownHtml += `<div class="row" style="color:#666; font-size:11px; padding-left:12px;"><span>Amount Tendered:</span><strong>${fmt(p.tendered)}</strong></div>`;
                        breakdownHtml += `<div class="row" style="color:#666; font-size:11px; padding-left:12px; margin-bottom:4px;"><span>Change:</span><strong>${fmt(p.change)}</strong></div>`;
                    }
                });
            }

            const printWin = window.open('', '', 'width=420,height=700');
            printWin.document.write(`<html>
            <head>
                <title>Receipt - ${d.rcpt}</title>
                <style>
                    @page { size: 80mm auto; margin: 0; }
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body { font-family: 'Courier New', Courier, monospace; background: #fff; color: #000; padding: 16px 8px; font-size: 13px; line-height: 1.6; width: 80mm; }
                    @media print { body { width: 80mm; padding: 8px 0; } }
                    .center { text-align: center; }
                    .sep { color: #000; letter-spacing: 2px; margin: 6px 0; text-align: center; }
                    .row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2px; }
                    .brand { font-size: 16px; font-weight: 800; letter-spacing: 1px; }
                    .total-label { font-size: 15px; font-weight: 800; letter-spacing: 1px; margin-top: 10px; margin-bottom: 6px;}
                    .total-amount { font-size: 20px; font-weight: 900; letter-spacing: 1px; margin-bottom: 6px; }
                </style>
            </head>
            <body>
                <div class="center">
                    <div class="brand">SANTA FE BEACH CLUB</div>
                    <div class="subtitle">Official Payment Receipt</div>
                </div>
                <div class="sep">${sep}</div>
                <div class="row"><span>Receipt #:</span><strong>${d.rcpt}</strong></div>
                <div class="row"><span>Date & Time:</span><strong>${dateStr}, ${timeStr}</strong></div>
                <div class="sep">${sep}</div>
                <div class="row"><span>Guest Name:</span><strong>${d.guest}</strong></div>
                <div class="row"><span>Accommodation:</span><strong>${d.room}</strong></div>
                <div class="sep">${sep}</div>
                <div class="row"><span>Total Cost:</span><strong>${fmt(d.totalCost)}</strong></div>
                ${balance > 0.01 ? `<div class="row" style="color:#d32f2f;"><span>Remaining Balance:</span><strong>${fmt(balance)}</strong></div>` : ''}
                <div class="sep">${sep}</div>
                ${breakdownHtml}
                <div class="sep">${sep}</div>
                <div class="center">
                    <div class="total-label">TOTAL PAID</div>
                    <div class="total-amount">${fmt(d.totalPaid)}</div>
                </div>
                <div class="sep">${sep}</div>
                <div class="row"><span>Status:</span><strong>${balance > 0.01 ? 'PARTIAL / BALANCE DUE' : 'PAID IN FULL'}</strong></div>
                <div class="sep">${sep}</div>
                <div class="row"><span>Staff:</span><strong>${RECEPTIONIST_NAME}</strong></div>
                <script>window.onload = function() { window.print(); }<\/script>
            </body>
            </html>`);
            printWin.document.close();
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("paymentSearch");
            const statusFilter = document.getElementById("paymentStatusFilter");
            const tableRows = document.querySelectorAll(".reservations-table tbody tr");

            function filterTable() {
                const searchVal = searchInput.value.toLowerCase();
                const statusVal = statusFilter.value.toLowerCase();

                tableRows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    const statusBadge = row.querySelector(".status-badge");
                    const rowStatus = statusBadge ? statusBadge.innerText.toLowerCase() : "";

                    const matchesSearch = text.includes(searchVal);
                    const matchesStatus = statusVal === "" || rowStatus.includes(statusVal);

                    if (matchesSearch && matchesStatus) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            }

            if(searchInput) searchInput.addEventListener("input", filterTable);
            if(statusFilter) statusFilter.addEventListener("change", filterTable);
        });

        function openRefundModal(payId, guestName, amount) {
            document.getElementById("refundPaymentId").value = payId;
            document.getElementById("refundModalInfo").innerHTML =
                "<strong>Guest:</strong> " + guestName + "<br>" +
                "<strong>Amount:</strong> " + amount + "<br>" +
                "<strong>Invoice:</strong> INV-100" + payId;
            document.getElementById("refundReasonSelect").value = "";
            document.getElementById("customReasonWrap").style.display = "none";
            document.getElementById("customReasonInput").value = "";
            document.getElementById("refundOverlay").classList.add("active");
        }

        function closeRefundModal() {
            document.getElementById("refundOverlay").classList.remove("active");
        }

        function toggleCustomReason(val) {
            document.getElementById("customReasonWrap").style.display = (val === "Other") ? "block" : "none";
        }

        function submitRefund() {
            const reasonSelect = document.getElementById("refundReasonSelect");
            const customInput = document.getElementById("customReasonInput");
            let reason = reasonSelect.value;

            if (!reason) {
                alert("Please select a reason for the refund.");
                return;
            }
            if (reason === "Other") {
                reason = customInput.value.trim();
                if (!reason) {
                    alert("Please describe the reason for the refund.");
                    return;
                }
                // Set the select value to the custom reason so it gets submitted
                const hiddenReason = document.createElement("input");
                hiddenReason.type = "hidden";
                hiddenReason.name = "refund_reason";
                hiddenReason.value = reason;
                document.getElementById("refundForm").appendChild(hiddenReason);
                reasonSelect.name = ""; // disable original select
            }

            document.getElementById("refundForm").submit();
        }

        // Close modals on overlay click
        document.getElementById("refundOverlay").addEventListener("click", function(e) {
            if (e.target === this) closeRefundModal();
        });
    </script>
<script src="assets/js/sidebar-toggle.js"></script>
</body>
</html>
