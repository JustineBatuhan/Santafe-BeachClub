<?php
/**
 * analytics_api.php — Native PHP Analytics & Executive KPI API
 * Replaces external Flask dependency so the dashboard loads instantly from MySQL directly.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/room_status_helper.php';

$action = $_GET['action'] ?? '';

// Fallback path matching
if (empty($action) && isset($_SERVER['PATH_INFO'])) {
    $action = trim($_SERVER['PATH_INFO'], '/');
}

switch ($action) {

    // ── 1. Executive Stats & KPIs ─────────────────────────────────────────────
    case 'executive-stats':
        // Daily revenue (today's verified payments)
        $daily_res = $conn->query("
            SELECT COALESCE(SUM(amount), 0) AS daily_rev
            FROM payments
            WHERE status = 'verified' AND DATE(paid_at) = CURDATE()
        ");
        $daily_revenue = (float)($daily_res ? $daily_res->fetch_assoc()['daily_rev'] : 0);

        // Weekly revenue (last 7 days verified payments)
        $weekly_res = $conn->query("
            SELECT COALESCE(SUM(amount), 0) AS weekly_rev
            FROM payments
            WHERE status = 'verified' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        ");
        $weekly_revenue = (float)($weekly_res ? $weekly_res->fetch_assoc()['weekly_rev'] : 0);

        // Room occupancy
        $total_rooms_res = $conn->query("SELECT COUNT(*) AS c FROM rooms");
        $total_rooms = (int)($total_rooms_res ? $total_rooms_res->fetch_assoc()['c'] : 0);

        $checked_in_ids = sf_get_checked_in_room_ids($conn);
        $reserved_ids   = sf_get_reserved_room_ids($conn);

        $occupied_rooms = count($checked_in_ids);
        $reserved_rooms = count($reserved_ids);

        $occupancy_rate = ($total_rooms > 0) ? round(($occupied_rooms / $total_rooms) * 100, 1) . '%' : '0%';

        // Bookings counters
        $total_bookings_res = $conn->query("SELECT COUNT(*) AS c FROM bookings");
        $total_bookings = (int)($total_bookings_res ? $total_bookings_res->fetch_assoc()['c'] : 0);

        $pending_bookings_res = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'Pending'");
        $pending_bookings = (int)($pending_bookings_res ? $pending_bookings_res->fetch_assoc()['c'] : 0);

        $pending_payments_res = $conn->query("SELECT COUNT(*) AS c FROM payments WHERE status = 'pending'");
        $pending_payments = (int)($pending_payments_res ? $pending_payments_res->fetch_assoc()['c'] : 0);

        $checkins_today_res = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'Pending' AND DATE(check_in) = CURDATE()");
        $checkins_today = (int)($checkins_today_res ? $checkins_today_res->fetch_assoc()['c'] : 0);

        $checkouts_today_res = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'Checked In' AND DATE(check_out) = CURDATE()");
        $checkouts_today = (int)($checkouts_today_res ? $checkouts_today_res->fetch_assoc()['c'] : 0);

        echo json_encode([
            'daily_revenue'    => $daily_revenue,
            'weekly_revenue'   => $weekly_revenue,
            'occupancy_rate'   => $occupancy_rate,
            'occupied_rooms'   => $occupied_rooms,
            'total_rooms'      => $total_rooms,
            'total_bookings'   => $total_bookings,
            'pending_bookings' => $pending_bookings,
            'reserved_rooms'   => $reserved_rooms,
            'pending_payments' => $pending_payments,
            'checkins_today'   => $checkins_today,
            'checkouts_today'  => $checkouts_today,
        ]);
        break;

    // ── 2. Weekly Revenue Trajectory Chart ────────────────────────────────────
    case 'weekly-revenue-trajectory':
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayLabel = date('D', strtotime($date));
            $labels[] = $dayLabel;

            $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS amt FROM payments WHERE status = 'verified' AND DATE(paid_at) = ?");
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $amt = (float)($stmt->get_result()->fetch_assoc()['amt'] ?? 0);
            $stmt->close();

            $data[] = $amt;
        }

        echo json_encode([
            'labels' => $labels,
            'data'   => $data,
        ]);
        break;

    // ── 3. Status Breakdown Doughnut Chart ────────────────────────────────────
    case 'status-breakdown':
        $res = $conn->query("SELECT status, COUNT(*) AS count FROM bookings GROUP BY status");
        $breakdown = [
            'Checked In'  => 0,
            'Checked Out' => 0,
            'Pending'     => 0,
            'Cancelled'   => 0,
        ];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $status = $row['status'];
                if (isset($breakdown[$status])) {
                    $breakdown[$status] = (int)$row['count'];
                }
            }
        }
        echo json_encode($breakdown);
        break;

    // ── 4. Room Type Occupancy Bar Chart ──────────────────────────────────────
    case 'room-type-occupancy':
        $labels = [];
        $data = [];

        $rt_res = $conn->query("SELECT name, total_rooms FROM room_types ORDER BY id ASC");
        if ($rt_res) {
            while ($rt = $rt_res->fetch_assoc()) {
                $typeName = $rt['name'];
                $cleanName = ucwords(str_replace('_', ' ', $typeName));
                $totalForType = (int)$rt['total_rooms'];

                // Count occupied rooms of this type
                $occ_stmt = $conn->prepare("
                    SELECT COUNT(*) AS occ
                    FROM bookings b
                    JOIN rooms r ON b.room_id = r.id
                    WHERE b.status = 'Checked In' AND r.type = ?
                ");
                $occ_stmt->bind_param("s", $typeName);
                $occ_stmt->execute();
                $occCount = (int)($occ_stmt->get_result()->fetch_assoc()['occ'] ?? 0);
                $occ_stmt->close();

                $pct = ($totalForType > 0) ? round(($occCount / $totalForType) * 100) : 0;

                $labels[] = $cleanName;
                $data[] = $pct;
            }
        }

        echo json_encode([
            'labels' => $labels,
            'data'   => $data,
        ]);
        break;

    // ── 5. Recent Reservations Table ─────────────────────────────────────────
    case 'recent-bookings':
        $b_res = $conn->query("
            SELECT id, guest_name, accommodation_name, check_in, status
            FROM bookings
            ORDER BY id DESC
            LIMIT 6
        ");
        $bookings = [];
        if ($b_res) {
            while ($row = $b_res->fetch_assoc()) {
                $bookings[] = $row;
            }
        }
        echo json_encode($bookings);
        break;

    // ── 6. Recent Logs Feed ──────────────────────────────────────────────────
    case 'recent-logs':
        $l_res = $conn->query("
            SELECT admin_username, action, details, created_at
            FROM activity_logs
            ORDER BY id DESC
            LIMIT 6
        ");
        $logs = [];
        if ($l_res) {
            while ($row = $l_res->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        echo json_encode($logs);
        break;

    // ── 7. Daily Summary Widget ──────────────────────────────────────────────
    case 'daily-summary':
        $today = date('Y-m-d');

        $b_today = (int)($conn->query("SELECT COUNT(*) AS c FROM bookings WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'] ?? 0);
        $pay_res = $conn->query("SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'verified' AND DATE(paid_at) = CURDATE()")->fetch_assoc();
        $p_count = (int)($pay_res['c'] ?? 0);
        $p_amt   = (float)($pay_res['total'] ?? 0);

        $cin_today  = (int)($conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'Checked In' AND DATE(check_in) = CURDATE()")->fetch_assoc()['c'] ?? 0);
        $cout_today = (int)($conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'Checked Out' AND DATE(check_out) = CURDATE()")->fetch_assoc()['c'] ?? 0);
        $canc_today = (int)($conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'Cancelled' AND DATE(cancelled_at) = CURDATE()")->fetch_assoc()['c'] ?? 0);

        echo json_encode([
            'date'                  => $today,
            'bookings_today'        => $b_today,
            'payments_today_count'  => $p_count,
            'payments_today_amount' => $p_amt,
            'checkins_today'        => $cin_today,
            'checkouts_today'       => $cout_today,
            'cancellations_today'   => $canc_today,
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action endpoint']);
        break;
}
