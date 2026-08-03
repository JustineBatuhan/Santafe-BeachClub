<?php
require_once 'db.php';
require_once 'mailer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = trim($_REQUEST['token'] ?? '');
$error = '';
$success_message = '';
$email_notice = '';
$booking = null;

if ($token === '') {
    $error = 'Missing cancellation link.';
} else {
    $stmt = $conn->prepare("SELECT id, guest_name, guest_email, accommodation_name, check_in, check_out, status, cancellation_token FROM bookings WHERE cancellation_token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        $booking_ref = 'REF-' . str_pad((int)$booking['id'], 3, '0', STR_PAD_LEFT);
        if ($booking['status'] === 'Checked In' || $booking['status'] === 'Checked Out') {
            $error = 'This booking can no longer be cancelled because it has already been checked in or checked out.';
        } elseif ($booking['status'] === 'Cancelled') {
            $error = 'This booking has already been cancelled.';
        }
    } else {
        $error = 'We could not find a booking for that cancellation link.';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cancel']) && $booking && $error === '') {
    $booking_ref = 'REF-' . str_pad((int)$booking['id'], 3, '0', STR_PAD_LEFT);
    $booking_id = (int)$booking['id'];

    $cancel_stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled', cancelled_at = NOW() WHERE id = ? AND status = 'Pending'");
    $cancel_stmt->bind_param("i", $booking_id);
    $cancel_stmt->execute();

    if ($cancel_stmt->affected_rows === 1) {
        $notif_title = 'Booking Cancelled';
        $notif_message = $booking['guest_name'] . ' cancelled ' . $booking_ref . ' for ' . $booking['accommodation_name'] . '.';
        $notif_type = 'alert';
        $notif_stmt = $conn->prepare("INSERT INTO notifications (title, message, type, booking_id) VALUES (?, ?, ?, ?)");
        $notif_stmt->bind_param("sssi", $notif_title, $notif_message, $notif_type, $booking_id);
        $notif_stmt->execute();
        $notif_stmt->close();

        if (!empty($booking['guest_email'])) {
            $email_result = sendBookingCancellationEmail(
                $booking['guest_email'],
                $booking['guest_name'],
                $booking_ref,
                $booking['accommodation_name'],
                $booking['check_in'],
                $booking['check_out']
            );

            $email_notice = $email_result['success']
                ? 'A cancellation email has been sent to your inbox.'
                : 'The booking was cancelled, but the email could not be sent.';
        }

        $success_message = 'Your booking has been cancelled successfully.';
        $booking['status'] = 'Cancelled';
    } else {
        $error = 'This booking could not be cancelled.';
    }

    $cancel_stmt->close();
}

$booking_ref = $booking ? 'REF-' . str_pad((int)$booking['id'], 3, '0', STR_PAD_LEFT) : '';
$checkin_fmt = $booking ? date('D, d M Y', strtotime($booking['check_in'])) : '';
$checkout_fmt = $booking ? date('D, d M Y', strtotime($booking['check_out'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Booking - Santa Fe Beach Club</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7f4f0;
            color: #2f2a26;
        }
        .cancel-wrap {
            max-width: 760px;
            margin: 60px auto;
            padding: 0 20px;
        }
        .cancel-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .cancel-head {
            padding: 28px 32px;
            background: linear-gradient(135deg, #8B5E3C, #b07a53);
            color: white;
        }
        .cancel-body {
            padding: 32px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }
        .detail {
            background: #faf7f4;
            border: 1px solid #efe4db;
            border-radius: 12px;
            padding: 14px;
        }
        .detail .label {
            font-size: 12px;
            text-transform: uppercase;
            color: #8b6f5a;
            margin-bottom: 6px;
            font-weight: 700;
        }
        .detail .value {
            font-size: 15px;
            font-weight: 700;
        }
        .notice {
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .notice.error {
            background: #fff1f2;
            color: #b42318;
            border: 1px solid #fecdd3;
        }
        .notice.success {
            background: #ecfdf3;
            color: #027a48;
            border: 1px solid #abefc6;
        }
        .btn-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 22px;
        }
        .btn {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            border: 0;
            cursor: pointer;
        }
        .btn-primary {
            background: #8B5E3C;
            color: white;
        }
        .btn-secondary {
            background: #f3ede8;
            color: #5a4637;
        }
        .muted {
            color: #7a6a5d;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="cancel-wrap">
        <div class="cancel-card">
            <div class="cancel-head">
                <h1 style="margin:0; font-size:28px;">Cancel Booking</h1>
                <p style="margin:8px 0 0; opacity:0.9;">Santa Fe Beach Club self-service cancellation</p>
            </div>
            <div class="cancel-body">
                <?php if ($error): ?>
                    <div class="notice error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="notice success">
                        <?php echo htmlspecialchars($success_message); ?>
                        <?php if ($email_notice): ?>
                            <div style="margin-top:6px;"><?php echo htmlspecialchars($email_notice); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($booking): ?>
                    <p class="muted">Review the booking below. If this is the right reservation, you can cancel it now.</p>
                    <div class="details-grid">
                        <div class="detail"><div class="label">Booking Ref</div><div class="value"><?php echo htmlspecialchars($booking_ref); ?></div></div>
                        <div class="detail"><div class="label">Guest Name</div><div class="value"><?php echo htmlspecialchars($booking['guest_name']); ?></div></div>
                        <div class="detail"><div class="label">Room</div><div class="value"><?php echo htmlspecialchars($booking['accommodation_name']); ?></div></div>
                        <div class="detail"><div class="label">Stay</div><div class="value"><?php echo htmlspecialchars($checkin_fmt); ?> to <?php echo htmlspecialchars($checkout_fmt); ?></div></div>
                    </div>

                    <?php if ($booking['status'] === 'Pending' && !$success_message): ?>
                        <form method="POST" action="cancel_booking.php">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <input type="hidden" name="confirm_cancel" value="1">
                            <p class="muted">Once cancelled, the booking cannot be used for check-in unless the front desk rebooks it.</p>
                            <div class="btn-row">
                                <button type="submit" class="btn btn-primary">Confirm Cancellation</button>
                                <a href="index.php" class="btn btn-secondary">Back to Home</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="btn-row">
                            <a href="index.php" class="btn btn-secondary">Back to Home</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="btn-row">
                        <a href="index.php" class="btn btn-secondary">Back to Home</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
