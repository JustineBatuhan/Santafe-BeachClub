<?php
require_once 'auth_check.php';
require_once 'db.php';

// Handle payment processing action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'process_payment' || $_POST['action'] === 'verify_payment') {
        $pay_id = intval($_POST['payment_id']);
        $method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Front Desk Cash';
        
        $stmt = $conn->prepare("UPDATE payments SET status = 'verified', payment_method = ? WHERE id = ?");
        $stmt->bind_param("si", $method, $pay_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Confirmed' WHERE id = (SELECT booking_id FROM payments WHERE id = ?)");
        $stmt->bind_param("i", $pay_id);
        $stmt->execute();
        
        header("Location: payments.php?success=1");
        exit;
    } elseif ($_POST['action'] === 'reject_payment') {
        $pay_id = intval($_POST['payment_id']);
        
        $stmt = $conn->prepare("UPDATE payments SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $pay_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = (SELECT booking_id FROM payments WHERE id = ?)");
        $stmt->bind_param("i", $pay_id);
        $stmt->execute();
        
        header("Location: payments.php?rejected=1");
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
        p.payment_method,
        p.transaction_id,
        p.status as payment_status,
        p.paid_at,
        b.accommodation_name,
        b.check_in,
        b.check_out,
        DATEDIFF(b.check_out, b.check_in) as nights
    FROM payments p
    LEFT JOIN bookings b ON p.booking_id = b.id
    ORDER BY p.id DESC
");


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Santa Fe Beach Club</title>
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
        .alert-success {
            background-color: #E8F5E9;
            color: #2E7D32;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* GCash Modal */
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
            max-width: 380px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
            animation: popIn 0.22s ease;
        }
        @keyframes popIn {
            from { transform: scale(0.88); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }
        .gcash-modal .gcash-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 18px;
        }
        .gcash-modal .gcash-logo-icon {
            width: 48px; height: 48px;
            background: #007AFF;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .gcash-modal .gcash-logo-icon svg { fill: white; }
        .gcash-modal h2 {
            font-size: 22px; font-weight: 800; color: #007AFF; margin: 0;
        }
        .gcash-modal p.subtitle {
            color: #666; font-size: 13px; margin: 0 0 20px;
        }
        .gcash-qr-box {
            background: #f0f4ff;
            border: 2px dashed #007AFF;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 18px;
            display: inline-block;
        }
        .gcash-qr-box img {
            width: 180px; height: 180px;
            display: block;
        }
        .gcash-number-box {
            background: #f0f4ff;
            border-radius: 10px;
            padding: 12px 20px;
            margin-bottom: 8px;
        }
        .gcash-number-box .label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .gcash-number-box .number { font-size: 24px; font-weight: 800; color: #007AFF; letter-spacing: 2px; }
        .gcash-number-box .name   { font-size: 13px; color: #333; font-weight: 600; margin-top: 2px; }
        .gcash-note { font-size: 11px; color: #999; margin-bottom: 22px; }
        .gcash-modal .btn-gcash-confirm {
            background: #007AFF;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            margin-bottom: 8px;
        }
        .gcash-modal .btn-gcash-confirm:hover { background: #0056CC; }
        .gcash-modal .btn-gcash-cancel {
            background: transparent;
            color: #888;
            border: none;
            font-size: 13px;
            cursor: pointer;
            padding: 6px;
        }
        .gcash-modal .btn-gcash-cancel:hover { color: #333; }
    </style>
</head>
<body>

    <?php $active_page = 'payments'; include '_sidebar.php'; ?>

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
        ';
        include '_page_header.php';
        ?>

        <section class="dashboard-grid" style="grid-template-columns: 1fr;">
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success">
                    ✅ Payment verified successfully! Booking has been confirmed.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['rejected'])): ?>
                <div class="alert-success" style="background-color:#FFEBEE; color:#C62828;">
                    ❌ Payment rejected. Booking has been cancelled.
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
                            <th>Status</th>
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
                                $pay_status_display = htmlspecialchars($row['payment_status']);
                                $pay_class = ($pay_status_display === 'verified' || $pay_status_display === 'Paid') ? 'status-paid' : 'status-pending';
                                if ($pay_status_display === 'rejected') $pay_class = 'status-rejected';

                                echo "<tr>";
                                echo "<td><strong>INV-100{$pay_id}</strong></td>";
                                echo "<td>{$name}</td>";
                                echo "<td>{$room}</td>";
                                echo "<td>
                                        <div style='font-weight:600; font-size:13px;'>{$method}</div>
                                        <div style='font-size:11px; color:#888;'>{$txn}</div>
                                      </td>";
                                echo "<td><strong>PHP {$amount}</strong></td>";
                                echo "<td><span class='status-badge {$pay_class}'>".ucfirst($pay_status_display)."</span></td>";
                                echo "<td>";
                                
                                if (strtolower($pay_status_display) === 'pending') {
                                    echo "
                                    <div style='display:flex; gap:6px; align-items:center; flex-wrap:wrap;'>
                                        <form method='POST' action='payments.php' style='margin:0; display:flex; gap:6px; align-items:center;'>
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
                                        <form method='POST' action='payments.php' style='margin:0;'>
                                            <input type='hidden' name='action' value='reject_payment'>
                                            <input type='hidden' name='payment_id' value='{$pay_id}'>
                                            <button type='submit' class='btn-receipt' style='padding:6px 12px; font-size:12px; color:#d32f2f; border-color:#d32f2f;' onclick='return confirm(\"Are you sure you want to reject this payment and cancel the booking?\")'>Reject</button>
                                        </form>
                                    </div>";
                                } elseif ($pay_status_display === 'verified' || $pay_status_display === 'Paid') {
                                    $rcpt_num = 'RCPT-' . str_pad($pay_id, 6, '0', STR_PAD_LEFT);
                                    echo "<button class='btn-receipt' style='padding:6px 12px; font-size:12px;' onclick='openReceiptModal(\"RCPT-".str_pad($pay_id, 6, '0', STR_PAD_LEFT)."\", \"INV-100{$pay_id}\", \"{$name}\", \"{$room}\", {$raw_amount}, \"{$method}\", \"{$txn}\")'>Print Receipt</button>";
                                } else {
                                    echo "<span style='font-size:12px; color:#888;'>Rejected</span>";
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align: center; color: #888; padding: 20px;'>No payment records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- GCash Payment Modal -->
    <div class="gcash-overlay" id="gcashOverlay">
        <div class="gcash-modal">
            <div class="gcash-logo">
                <div class="gcash-logo-icon">
                    <svg width="28" height="28" viewBox="0 0 28 28" xmlns="http://www.w3.org/2000/svg">
                        <text x="4" y="22" font-size="22" font-weight="900" font-family="Arial, sans-serif">G</text>
                    </svg>
                </div>
                <h2>GCash</h2>
            </div>
            <p class="subtitle">Scan the QR code or send to the number below</p>
            <div class="gcash-qr-box">
                <img src="assets/gcash_qr.png?v=<?= time(); ?>" alt="GCash QR Code" id="gcashQrImg" style="display:block; width:180px; height:180px;" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=180x180&amp;data=GCash%3A09505223146%20Santa+Fe+Beach+Club'">
            </div>
            <div class="gcash-number-box">
                <div class="label">GCash Number</div>
                <div class="number">0950 522 3146</div>
                <div class="name">Santa Fe Beach Club</div>
            </div>
            <p class="gcash-note">After sending, click "I've Paid" to record the payment.</p>
            <button class="btn-gcash-confirm" id="gcashConfirmBtn" onclick="submitGcashForm()">I've Paid</button>
            <br>
            <button class="btn-gcash-cancel" onclick="closeGcashModal()">Cancel</button>
        </div>
    </div>

    <!-- Receipt Preview Modal -->
    <div class="gcash-overlay" id="receiptOverlay">
        <div class="gcash-modal" style="max-width:420px; padding:0; overflow:hidden; border-radius:16px; display:flex; flex-direction:column; max-height:90vh;">
            <div id="receiptPreview" style="background:#fff; color:#000; font-family:'Courier New',monospace; padding:28px 24px; font-size:13px; line-height:1.7; text-align:center; overflow-y:auto;">
                <!-- Filled by JS -->
            </div>
            <div style="padding:16px 24px; display:flex; gap:10px; justify-content:center; background:#fff; border-top:1px solid #eee;">
                <button onclick="doPrintReceipt()" class="btn-pay" style="padding:10px 28px; font-size:14px; border-radius:8px;">🖨️ Print</button>
                <button onclick="closeReceiptModal()" class="btn-receipt" style="padding:10px 28px; font-size:14px; border-radius:8px;">Close</button>
            </div>
        </div>
    </div>

    <style>
        .status-rejected { background: #FFEBEE; color: #C62828; }
    </style>

    <script>
        // The form that triggered the GCash modal
        let _gcashPendingForm = null;

        document.querySelectorAll('select[name="payment_method"]').forEach(function(sel) {
            sel.addEventListener('change', function() {});
        });

        // Intercept every settle-bill form submit
        document.querySelectorAll('form[action="payments.php"]').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                var sel = form.querySelector('select[name="payment_method"]');
                if (sel && sel.value === 'GCash QR') {
                    e.preventDefault();
                    _gcashPendingForm = form;
                    document.getElementById('gcashOverlay').classList.add('active');
                }
            });
        });

        function closeGcashModal() {
            document.getElementById('gcashOverlay').classList.remove('active');
            _gcashPendingForm = null;
        }

        function submitGcashForm() {
            if (_gcashPendingForm) {
                var clone = _gcashPendingForm.cloneNode(true);
                _gcashPendingForm.parentNode.replaceChild(clone, _gcashPendingForm);
                clone.submit();
            }
        }

        document.getElementById('gcashOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeGcashModal();
        });

        // ── Receipt Modal ──
        const RECEPTIONIST_NAME = <?php echo json_encode($_SESSION['admin_username'] ?? 'Administrator'); ?>;
        let _receiptData = {};

        function openReceiptModal(rcpt, inv, guest, room, amountDue, method, txn) {
            _receiptData = { rcpt, inv, guest, room, amountDue, method, txn };
            renderReceipt(amountDue, 0);
            document.getElementById('receiptOverlay').classList.add('active');
        }

        function closeReceiptModal() {
            document.getElementById('receiptOverlay').classList.remove('active');
        }

        document.getElementById('receiptOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeReceiptModal();
        });

        function renderReceipt(amountTendered, change) {
            const d = _receiptData;
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            const sep = '<div style="color:#000; letter-spacing:2px;">--------------------------------------</div>';
            const fmt = (v) => '₱ ' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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
                <div class="text-align:left; margin:8px 0;">
                    <div style="display:flex; justify-content:space-between;"><span>Payment Type:</span><strong>${d.method}</strong></div>
                    <div style="display:flex; justify-content:space-between;"><span>Ref/Txn #:</span><strong>${d.txn}</strong></div>
                </div>
                ${sep}
                <div style="text-align:left; margin:8px 0;">
                    <div style="display:flex; justify-content:space-between; color:#666;"><span>Total Room Price:</span><strong>${fmt(d.amountDue * 2)}</strong></div>
                    <div style="display:flex; justify-content:space-between; margin-top:4px;"><span>Downpayment (50%):</span><strong>${fmt(d.amountDue)}</strong></div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin: 4px 0;">
                        <span>Amount Tendered:</span>
                        <input type="number" id="rcptAmtTendered" value="${amountTendered || d.amountDue}" min="0" step="0.01"
                               style="width:110px; background:#f9f9f9; border:1px solid #ccc; color:#000; font-family:inherit; font-size:13px; font-weight:700; text-align:right; padding:4px 8px; border-radius:4px;"
                               oninput="updateChange()">
                    </div>
                    <div style="display:flex; justify-content:space-between;"><span>Change:</span><strong id="rcptChange">${fmt(change)}</strong></div>
                </div>
                ${sep}
                <div style="margin:12px 0 4px; font-size:15px; font-weight:800; letter-spacing:1px;">TOTAL PAID</div>
                <div style="font-size:20px; font-weight:900; letter-spacing:1px; margin-bottom:8px;">${fmt(d.amountDue)}</div>
                ${sep}
                <div style="text-align:left; margin:8px 0; color:#d32f2f; font-weight:700;">
                    <div style="display:flex; justify-content:space-between;"><span>Balance Due at Desk:</span><strong>${fmt(d.amountDue)}</strong></div>
                </div>
                ${sep}
                <div style="text-align:left; margin:8px 0;">
                    <div style="display:flex; justify-content:space-between;"><span>Receptionist:</span><strong>${RECEPTIONIST_NAME}</strong></div>
                </div>
                ${sep}
                <div style="color:#666; font-size:12px; margin-top:10px;">Thank you for staying with us!<br>Have a safe trip!</div>
                <div style="color:#888; font-size:11px; margin-top:8px;">This is an official receipt.</div>
            `;
        }

        function updateChange() {
            const tendered = parseFloat(document.getElementById('rcptAmtTendered').value) || 0;
            const change = Math.max(0, tendered - _receiptData.amountDue);
            const fmt = (v) => '₱ ' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('rcptChange').textContent = fmt(change);
        }

        function doPrintReceipt() {
            const tendered = parseFloat(document.getElementById('rcptAmtTendered').value) || _receiptData.amountDue;
            const change = Math.max(0, tendered - _receiptData.amountDue);
            const d = _receiptData;
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            const fmt = (v) => '₱ ' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const sep = '--------------------------------------';

            const printWin = window.open('', '', 'width=420,height=700');
            printWin.document.write(`<html>
            <head>
                <title>Receipt - ${d.rcpt}</title>
                <style>
                    @page { 
                        size: 80mm auto; /* Standard thermal receipt paper size */
                        margin: 0; 
                    }
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body {
                        font-family: 'Courier New', Courier, monospace;
                        background: #fff;
                        color: #000;
                        padding: 16px 8px;
                        font-size: 13px;
                        line-height: 1.6;
                        width: 80mm;
                        margin: 0;
                    }
                    @media print {
                        body { 
                            width: 80mm; 
                            max-width: 80mm; 
                            padding: 8px 0; 
                            margin: 0;
                        }
                    }
                    .center { text-align: center; }
                    .sep { color: #000; letter-spacing: 2px; margin: 6px 0; text-align: center; white-space: nowrap; overflow: hidden; }
                    .row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2px; }
                    .row span { flex-shrink: 0; margin-right: 10px; }
                    .row strong { text-align: right; word-break: break-word; }
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
                <div class="row"><span>Receipt #:</span><strong>${d.rcpt}</strong></div>
                <div class="row"><span>Invoice #:</span><strong>${d.inv}</strong></div>
                <div class="row"><span>Date & Time:</span><strong>${dateStr}, ${timeStr}</strong></div>
                <div class="sep">${sep}</div>
                <div class="row"><span>Guest Name:</span><strong>${d.guest}</strong></div>
                <div class="row"><span>Accommodation:</span><strong>${d.room}</strong></div>
                <div class="sep">${sep}</div>
                <div class="row"><span>Payment Type:</span><strong>${d.method}</strong></div>
                <div class="row"><span>Ref/Txn #:</span><strong>${d.txn}</strong></div>
                <div class="sep">${sep}</div>
                <div class="row" style="color:#666;"><span>Total Room Price:</span><strong>${fmt(d.amountDue * 2)}</strong></div>
                <div class="row" style="margin-top:4px;"><span>Downpayment (50%):</span><strong>${fmt(d.amountDue)}</strong></div>
                <div class="row"><span>Amount Tendered:</span><strong>${fmt(tendered)}</strong></div>
                <div class="row"><span>Change:</span><strong>${fmt(change)}</strong></div>
                <div class="sep">${sep}</div>
                <div class="center">
                    <div class="total-label">TOTAL PAID</div>
                    <div class="total-amount">${fmt(d.amountDue)}</div>
                </div>
                <div class="sep">${sep}</div>
                <div class="row" style="color:#d32f2f; font-weight:700;"><span>Balance Due at Desk:</span><strong>${fmt(d.amountDue)}</strong></div>
                <div class="sep">${sep}</div>
                <div class="row"><span>Receptionist:</span><strong>${RECEPTIONIST_NAME}</strong></div>
                <div class="sep">${sep}</div>
                <div class="center footer">Thank you for staying with us!<br>Have a safe trip!</div>
                <div class="center footer-note">This is an official receipt.</div>
                <script>window.onload = function() { window.print(); }<\/script>
</body>
            </html>`);
            printWin.document.close();
        }
    </script>
<script src="sidebar-toggle.js"></script>
</body>
</html>
