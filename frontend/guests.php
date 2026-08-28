<?php
require_once __DIR__ . '/../backend/helpers/auth_check.php';
require_once __DIR__ . '/../backend/config/db.php';

// ── Guest summary query ───────────────────────────────────────────────────────
$guests_result = $conn->query("
    SELECT 
        guest_name,
        guest_email,
        guest_type,
        COUNT(id)              AS total_bookings,
        MIN(check_in)          AS first_visit,
        MAX(check_in)          AS last_visit,
        SUM(CASE WHEN status = 'Cancelled' THEN 0 ELSE 1 END) AS confirmed_bookings
    FROM bookings
    GROUP BY guest_email, guest_name
    ORDER BY guest_name ASC
");

$guests = [];
if ($guests_result) {
    while ($r = $guests_result->fetch_assoc()) {
        $guests[] = $r;
    }
}

// ── Per-guest booking history (for profile modal) ────────────────────────────
$bookings_result = $conn->query("
    SELECT 
        b.id, b.guest_email, b.guest_name,
        b.accommodation_name, b.check_in, b.check_out,
        b.guests_count, b.status, b.payment_method,
        b.guest_phone, b.guest_country, b.guest_special_requests,
        r.price_per_night, r.type AS room_type,
        DATEDIFF(b.check_out, b.check_in) AS nights
    FROM bookings b
    LEFT JOIN rooms r ON b.room_id = r.id
    ORDER BY b.check_in DESC
");

$bookings_by_email = [];
if ($bookings_result) {
    while ($b = $bookings_result->fetch_assoc()) {
        $key = strtolower(trim($b['guest_email'] ?? $b['guest_name']));
        $bookings_by_email[$key][] = $b;
    }
}

// ── Summary counts ────────────────────────────────────────────────────────────
$total_guests    = count($guests);
$vip_count       = 0;
$corporate_count = 0;
$returning_count = 0;
$first_visit_count = 0;
foreach ($guests as $g) {
    $t = $g['guest_type'];
    if ($t === 'VIP Member')        $vip_count++;
    elseif ($t === 'Corporate')     $corporate_count++;
    elseif ($t === 'Returning Guest') $returning_count++;
    else                            $first_visit_count++;
}

// ── Helper: initials & avatar colours ────────────────────────────────────────
function get_initials(string $name): string {
    $initials = '';
    foreach (explode(' ', $name) as $p) {
        if (!empty($p)) $initials .= strtoupper($p[0]);
    }
    return substr($initials, 0, 2);
}
function avatar_colors(string $type): array {
    return match($type) {
        'VIP Member'      => ['#FDF4EC', '#7C533C'],
        'Corporate'       => ['#E3F2FD', '#1565C0'],
        'Returning Guest' => ['#F3E8FF', '#7C3AED'],
        default           => ['#F3F4F6', '#4B5563'],
    };
}
function type_badge(string $type): string {
    $styles = match($type) {
        'VIP Member'      => 'background:#FDF4EC;color:#7C533C;border:1px solid #e8d5c0;',
        'Corporate'       => 'background:#E3F2FD;color:#1565C0;border:1px solid #bbdefb;',
        'Returning Guest' => 'background:#F3E8FF;color:#7C3AED;border:1px solid #ddd6fe;',
        default           => 'background:#F3F4F6;color:#4B5563;border:1px solid #e5e7eb;',
    };
    return "<span style='$styles padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;'>" . htmlspecialchars($type) . "</span>";
}

function split_guest_name(string $name): array {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first_name = $parts[0] ?? '';
    $last_name = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
    return [$first_name, $last_name];
}

function booking_room_type(array $booking): string {
    $room_type = trim((string)($booking['room_type'] ?? ''));
    if ($room_type !== '') {
        return $room_type;
    }

    $accommodation_name = strtolower((string)($booking['accommodation_name'] ?? ''));
    if (strpos($accommodation_name, 'beachview duplex') !== false) {
        return 'beachview_duplex';
    }
    if (strpos($accommodation_name, 'seaview duplex') !== false) {
        return 'seaview_duplex';
    }
    if (strpos($accommodation_name, 'beach villa') !== false) {
        return 'beach_villa';
    }
    if (strpos($accommodation_name, 'standard family') !== false) {
        return 'standard_king';
    }

    return 'standard_room';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=4">
    <style>
        /* ── Stat cards ──────────────────────────────────────── */
        .guest-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        .guest-stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px 22px;
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .guest-stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .guest-stat-label { font-size: 12px; color: #888; font-weight: 600; text-transform: uppercase; }
        .guest-stat-value { font-size: 26px; font-weight: 700; color: #222; line-height: 1.1; }

        /* ── Filter tabs ─────────────────────────────────────── */
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 4px;
        }
        .filter-tab {
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #555;
            transition: all .15s;
        }
        .filter-tab:hover { border-color: var(--color-primary); color: var(--color-primary); }
        .filter-tab.active { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }

        /* ── Table ───────────────────────────────────────────── */
        .reservations-table { width: 100%; border-collapse: collapse; }
        .reservations-table th, .reservations-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .reservations-table tbody tr:last-child td { border-bottom: none; }
        .reservations-table th {
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: .5px;
        }
        .reservations-table tbody tr:hover { background: #fafafa; }
        .guest-profile-small { display: flex; align-items: center; gap: 10px; }
        .avatar-small {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 13px; flex-shrink: 0;
        }
        .guest-name-main { font-weight: 600; font-size: 14px; color: #222; }
        .btn-view {
            background: transparent;
            color: var(--color-primary);
            border: 1px solid var(--color-primary);
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all .15s;
        }
        .btn-view:hover { background: #FDF4EC; }
        .booking-count-badge {
            background: #f0f0f0;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            color: #333;
        }
        .no-results { text-align: center; color: #999; padding: 40px 0; font-size: 15px; }

        /* ── Modal ───────────────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 740px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,.2);
            animation: modalIn .2s ease;
        }
        @keyframes modalIn {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .modal-header {
            padding: 28px 28px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
            gap: 18px;
            position: sticky;
            top: 0;
            background: #fff;
            border-radius: 16px 16px 0 0;
            z-index: 1;
        }
        .modal-avatar {
            width: 64px; height: 64px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 700; flex-shrink: 0;
        }
        .modal-guest-name { font-size: 20px; font-weight: 700; color: #222; margin-bottom: 4px; }
        .modal-guest-email { font-size: 14px; color: #888; margin-bottom: 8px; }
        .modal-header-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-book-again {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            background: var(--color-primary);
            color: #fff;
            border: 1px solid var(--color-primary);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            transition: all .15s;
            white-space: nowrap;
        }
        .btn-book-again:hover {
            background: #6d4935;
            border-color: #6d4935;
        }
        .modal-close {
            background: none; border: none;
            cursor: pointer; color: #aaa; padding: 4px;
            border-radius: 6px; flex-shrink: 0;
        }
        .modal-close:hover { color: #444; background: #f3f4f6; }
        .modal-body { padding: 24px 28px; }
        .modal-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }
        .modal-stat {
            background: #f9fafb;
            border-radius: 10px;
            padding: 14px 16px;
            text-align: center;
        }
        .modal-stat-val { font-size: 22px; font-weight: 700; color: #222; }
        .modal-stat-lbl { font-size: 11px; color: #888; font-weight: 600; text-transform: uppercase; margin-top: 2px; }
        .modal-section-title {
            font-size: 14px;
            font-weight: 700;
            color: #444;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 12px;
        }
        .booking-history-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .booking-history-table th {
            padding: 9px 12px; text-align: left;
            background: #f9fafb; color: #888; font-weight: 600;
            text-transform: uppercase; font-size: 11px; letter-spacing: .4px;
        }
        .booking-history-table th:first-child { border-radius: 8px 0 0 8px; }
        .booking-history-table th:last-child  { border-radius: 0 8px 8px 0; }
        .booking-history-table td { padding: 11px 12px; border-bottom: 1px solid #f0f0f0; }
        .booking-history-table tbody tr:last-child td { border-bottom: none; }
        .status-pill {
            padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .status-pill.pending    { background:#FFF8E1; color:#F59E0B; }
        .status-pill.checked-in { background:#E8F5E9; color:#2E7D32; }
        .status-pill.checked-out{ background:#E3F2FD; color:#1565C0; }
        .status-pill.cancelled  { background:#FFEBEE; color:#C62828; }
        .empty-history { text-align: center; color: #aaa; padding: 30px 0; font-size: 14px; }

        /* ── Card header row with filter + actions ────────────── */
        .card-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 18px 20px 0;
        }
    </style>
</head>
<body>

<?php $active_page = 'guests'; include __DIR__ . '/partials/_sidebar.php'; ?>

<main class="main-content">
<?php
$page_title    = 'Guest Management';
$page_subtitle = 'Directory and history';
$header_extra_html = '
    <div class="search-wrapper">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Search guests..." class="search-input" id="guestSearch">
    </div>
';
include __DIR__ . '/partials/_page_header.php';
?>

<section class="dashboard-grid" style="grid-template-columns:1fr;">

    <!-- ── Summary stat cards ─────────────────────────────────────────── -->
    <div class="guest-stats-grid">
        <div class="guest-stat-card">
            <div class="guest-stat-icon" style="background:#EFF6FF;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <div class="guest-stat-label">Total Guests</div>
                <div class="guest-stat-value"><?= $total_guests ?></div>
            </div>
        </div>
        <div class="guest-stat-card">
            <div class="guest-stat-icon" style="background:#FDF4EC;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7C533C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
            <div>
                <div class="guest-stat-label">VIP Members</div>
                <div class="guest-stat-value"><?= $vip_count ?></div>
            </div>
        </div>
        <div class="guest-stat-card">
            <div class="guest-stat-icon" style="background:#E3F2FD;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1565C0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            </div>
            <div>
                <div class="guest-stat-label">Corporate</div>
                <div class="guest-stat-value"><?= $corporate_count ?></div>
            </div>
        </div>
        <div class="guest-stat-card">
            <div class="guest-stat-icon" style="background:#F0FDF4;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div>
                <div class="guest-stat-label">First Visit</div>
                <div class="guest-stat-value"><?= $first_visit_count ?></div>
            </div>
        </div>
        <div class="guest-stat-card">
            <div class="guest-stat-icon" style="background:#F3E8FF;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <div class="guest-stat-label">Returning</div>
                <div class="guest-stat-value"><?= $returning_count ?></div>
            </div>
        </div>
    </div>

    <!-- ── Guest directory card ───────────────────────────────────────── -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="card-toolbar">
            <div>
                <h2 style="font-size:17px;font-weight:700;color:#222;">Guest Directory</h2>
                <p style="font-size:13px;color:#888;margin-top:2px;">
                    <span id="visibleCount"><?= $total_guests ?></span> guest<?= $total_guests !== 1 ? 's' : '' ?> total
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <!-- Filter tabs -->
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all">All</button>
                    <button class="filter-tab" data-filter="VIP Member">VIP</button>
                    <button class="filter-tab" data-filter="Returning Guest">Returning</button>
                    <button class="filter-tab" data-filter="Corporate">Corporate</button>
                    <button class="filter-tab" data-filter="First Visit">First Visit</button>
                </div>
                <button id="exportCsvBtn" class="btn-new-res-top" style="padding:8px 16px;font-size:13px;margin:0;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px;vertical-align:middle;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export CSV
                </button>
            </div>
        </div>

        <div style="padding:16px 20px 0;">
            <table class="reservations-table" id="guestTable">
                <thead>
                    <tr>
                        <th>Guest Profile</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Total Bookings</th>
                        <th>Last Visit</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="guestTableBody">
                    <?php if (empty($guests)): ?>
                    <tr class="guest-row">
                        <td colspan="6" class="no-results">No guests found in the directory.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($guests as $row):
                        $name       = $row['guest_name'];
                        $email      = $row['guest_email'] ?? '';
                        $type       = $row['guest_type']  ?? 'First Visit';
                        $total      = (int)$row['total_bookings'];
                        $last_visit = $row['last_visit']  ?? '';
                        $first_visit= $row['first_visit'] ?? '';
                        $initials   = get_initials($name);
                        [$avatarBg, $avatarText] = avatar_colors($type);

                        // Build booking history for this guest
                        $key      = strtolower(trim($email !== '' ? $email : $name));
                        $bookings = $bookings_by_email[$key] ?? [];

                        // Compute total spend
                        $total_spend = 0;
                        foreach ($bookings as $bk) {
                            if ($bk['status'] !== 'Cancelled' && $bk['price_per_night']) {
                                $nights = max(1, (int)$bk['nights']);
                                $total_spend += $nights * (float)$bk['price_per_night'];
                            }
                        }

                        // Extract guest contact info from the first (most recent) booking
                        $guest_phone = '';
                        $guest_country = '';
                        $guest_special_requests = '';
                        if (!empty($bookings)) {
                            $guest_phone = $bookings[0]['guest_phone'] ?? '';
                            $guest_country = $bookings[0]['guest_country'] ?? '';
                            $guest_special_requests = $bookings[0]['guest_special_requests'] ?? '';
                        }

                        [$guest_first_name, $guest_last_name] = split_guest_name($name);
                        $recent_booking = $bookings[0] ?? [];
                        $recent_room_type = booking_room_type($recent_booking);
                        $recent_guests = max(1, (int)($recent_booking['guests_count'] ?? 2));
                        $recent_nights = max(1, (int)($recent_booking['nights'] ?? 1));
                        $rebook_checkin = date('Y-m-d');
                        $rebook_checkout = date('Y-m-d', strtotime('+' . $recent_nights . ' day'));
                        $book_again_url = 'book?' . http_build_query([
                            'rebook' => 1,
                            'step' => 2,
                            'checkin' => $rebook_checkin,
                            'checkout' => $rebook_checkout,
                            'guests' => $recent_guests,
                            'room_type' => $recent_room_type,
                            'first_name' => $guest_first_name,
                            'last_name' => $guest_last_name,
                            'email' => $email,
                            'phone' => $guest_phone,
                            'country' => $guest_country,
                            'comments' => $guest_special_requests,
                        ]);

                        // Encode booking rows as JSON for the modal
                        $bookings_json = json_encode(array_map(function($bk) {
                            return [
                                'id'        => (int)$bk['id'],
                                'room'      => $bk['accommodation_name'],
                                'check_in'  => $bk['check_in'],
                                'check_out' => $bk['check_out'],
                                'guests'    => (int)$bk['guests_count'],
                                'nights'    => max(1, (int)$bk['nights']),
                                'status'    => $bk['status'],
                                'payment'   => $bk['payment_method'] ?? 'Pay at Check-in',
                                'rate'      => (float)($bk['price_per_night'] ?? 0),
                            ];
                        }, $bookings));

                        $profile_data = json_encode([
                            'name'        => $name,
                            'email'       => $email,
                            'phone'       => $guest_phone,
                            'country'     => $guest_country,
                            'special_requests' => $guest_special_requests,
                            'type'        => $type,
                            'total'       => $total,
                            'first_visit' => $first_visit,
                            'last_visit'  => $last_visit,
                            'spend'       => $total_spend,
                            'initials'    => $initials,
                            'avatarBg'    => $avatarBg,
                            'avatarText'  => $avatarText,
                            'book_again_url' => $book_again_url,
                            'bookings'    => json_decode($bookings_json),
                        ]);
                    ?>
                    <tr class="guest-row" data-type="<?= htmlspecialchars($type) ?>"
                        data-name="<?= htmlspecialchars(strtolower($name)) ?>"
                        data-email="<?= htmlspecialchars(strtolower($email)) ?>">
                        <td>
                            <div class="guest-profile-small">
                                <div class="avatar-small" style="background:<?= $avatarBg ?>;color:<?= $avatarText ?>;"><?= htmlspecialchars($initials) ?></div>
                                <span class="guest-name-main"><?= htmlspecialchars($name) ?></span>
                            </div>
                        </td>
                        <td style="color:#555;font-size:14px;"><?= htmlspecialchars($email) ?></td>
                        <td><?= type_badge($type) ?></td>
                        <td><span class="booking-count-badge"><?= $total ?></span></td>
                        <td style="color:#555;font-size:14px;"><?= htmlspecialchars($last_visit) ?></td>
                        <td>
                            <button type="button" class="btn-view"
                                onclick='openGuestProfile(<?= htmlspecialchars($profile_data, ENT_QUOTES) ?>)'>
                                View Profile
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div id="noResultsMsg" style="display:none;" class="no-results">No guests match your search.</div>
        </div>
        <div style="height:8px;"></div>
    </div>

</section>
</main>

<!-- ═══════════════════ Guest Profile Modal ═══════════════════ -->
<div class="modal-overlay" id="guestModal" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-avatar" id="modalAvatar"></div>
            <div style="flex:1;min-width:0;">
                <div class="modal-guest-name" id="modalName"></div>
                <div class="modal-guest-email" id="modalEmail"></div>
                <div id="modalTypeBadge"></div>
            </div>
            <div class="modal-header-actions">
                <a id="modalBookAgainBtn" class="btn-book-again" href="book?step=2">Book Again</a>
                <button class="modal-close" onclick="closeModal()" title="Close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>
        <div class="modal-body">
            <div class="modal-stats-row">
                <div class="modal-stat">
                    <div class="modal-stat-val" id="modalTotalBookings">—</div>
                    <div class="modal-stat-lbl">Total Bookings</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-val" id="modalFirstVisit">—</div>
                    <div class="modal-stat-lbl">First Visit</div>
                </div>
                <div class="modal-stat">
                    <div class="modal-stat-val" id="modalTotalSpend">—</div>
                    <div class="modal-stat-lbl">Est. Total Spend</div>
                </div>
            </div>

           <!-- Contact Information Section -->
           <div class="modal-section-title">Contact Information</div>
           <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;padding:12px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;">
               <div>
                   <div style="font-size:12px;color:#6b7280;font-weight:500;margin-bottom:4px;">Phone</div>
                   <div style="font-size:14px;color:#222;" id="modalPhone">—</div>
               </div>
               <div>
                   <div style="font-size:12px;color:#6b7280;font-weight:500;margin-bottom:4px;">Country</div>
                   <div style="font-size:14px;color:#222;" id="modalCountry">—</div>
               </div>
           </div>

           <!-- Special Requests Section -->
           <div id="modalSpecialRequestsSection" style="display:none;margin-bottom:20px;">
               <div class="modal-section-title">Special Requests / Notes</div>
               <div style="padding:12px;background:#fef3c7;border-radius:8px;border:1px solid #fcd34d;font-size:13px;color:#92400e;line-height:1.5;" id="modalSpecialRequests"></div>
           </div>

           <div class="modal-section-title">Booking History</div>
           <div id="modalBookingHistory"></div>
        </div>
    </div>
</div>

<script>
// ── Embed all guest data ──────────────────────────────────────────────────────
const ALL_ROWS = Array.from(document.querySelectorAll('#guestTableBody .guest-row[data-name]'));
let activeFilter = 'all';

// ── Search ────────────────────────────────────────────────────────────────────
document.getElementById('guestSearch').addEventListener('input', function () {
    applyFilters(this.value.toLowerCase().trim());
});

// ── Filter tabs ───────────────────────────────────────────────────────────────
document.querySelectorAll('.filter-tab').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        activeFilter = this.dataset.filter;
        applyFilters(document.getElementById('guestSearch').value.toLowerCase().trim());
    });
});

function applyFilters(searchTerm) {
    let visible = 0;
    ALL_ROWS.forEach(row => {
        const typeMatch  = activeFilter === 'all' || row.dataset.type === activeFilter;
        const searchMatch = searchTerm === '' ||
            row.dataset.name.includes(searchTerm) ||
            row.dataset.email.includes(searchTerm);
        const show = typeMatch && searchMatch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('visibleCount').textContent = visible;
    document.getElementById('noResultsMsg').style.display = (visible === 0 && ALL_ROWS.length > 0) ? 'block' : 'none';
}

// ── Export CSV ────────────────────────────────────────────────────────────────
document.getElementById('exportCsvBtn').addEventListener('click', function () {
    const visibleRows = ALL_ROWS.filter(r => r.style.display !== 'none');
    if (!visibleRows.length) { alert('No guests to export.'); return; }

    const headers = ['Guest Name', 'Email', 'Type', 'Total Bookings', 'Last Visit'];
    const lines   = [headers.join(',')];

    visibleRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const name      = cells[0].querySelector('.guest-name-main')?.textContent.trim() ?? '';
        const email     = cells[1].textContent.trim();
        const type      = cells[2].textContent.trim();
        const bookings  = cells[3].textContent.trim();
        const lastVisit = cells[4].textContent.trim();
        lines.push([name, email, type, bookings, lastVisit].map(v => `"${v.replace(/"/g,'""')}"`).join(','));
    });

    const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'guests_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
});

// ── Profile Modal ─────────────────────────────────────────────────────────────
function openGuestProfile(data) {
    document.getElementById('modalAvatar').textContent        = data.initials;
    document.getElementById('modalAvatar').style.background  = data.avatarBg;
    document.getElementById('modalAvatar').style.color       = data.avatarText;
    document.getElementById('modalName').textContent          = data.name;
    document.getElementById('modalEmail').textContent         = data.email || '—';
    document.getElementById('modalBookAgainBtn').href         = data.book_again_url || 'book?step=2';
    document.getElementById('modalTotalBookings').textContent = data.total;
    document.getElementById('modalFirstVisit').textContent    = data.first_visit || '—';
    document.getElementById('modalTotalSpend').textContent    = data.spend > 0
        ? '₱' + Number(data.spend).toLocaleString('en-PH', {minimumFractionDigits:0})
        : '—';

    // Contact information
    document.getElementById('modalPhone').textContent         = data.phone || '—';
    document.getElementById('modalCountry').textContent       = data.country || '—';

    // Special requests section
    const specialRequestsSection = document.getElementById('modalSpecialRequestsSection');
    const specialRequestsDiv = document.getElementById('modalSpecialRequests');
    if (data.special_requests && data.special_requests.trim()) {
        specialRequestsDiv.textContent = data.special_requests;
        specialRequestsSection.style.display = 'block';
    } else {
        specialRequestsSection.style.display = 'none';
    }

    // Type badge
    const badgeStyles = {
        'VIP Member':      'background:#FDF4EC;color:#7C533C;border:1px solid #e8d5c0;',
        'Corporate':       'background:#E3F2FD;color:#1565C0;border:1px solid #bbdefb;',
        'Returning Guest': 'background:#F3E8FF;color:#7C3AED;border:1px solid #ddd6fe;',
    };
    const bs = badgeStyles[data.type] || 'background:#F3F4F6;color:#4B5563;border:1px solid #e5e7eb;';
    document.getElementById('modalTypeBadge').innerHTML =
        `<span style="${bs}padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;">${data.type}</span>`;

    // Booking history table
    const hist = document.getElementById('modalBookingHistory');
    if (!data.bookings || data.bookings.length === 0) {
        hist.innerHTML = '<div class="empty-history">No booking records found.</div>';
    } else {
        const statusClass = s => {
            if (!s) return '';
            const map = { 'Pending':'pending', 'Checked In':'checked-in', 'Checked Out':'checked-out', 'Cancelled':'cancelled' };
            return map[s] || 'pending';
        };
        let rows = '';
        data.bookings.forEach(b => {
            const amount = b.rate > 0 ? '₱' + (b.rate * b.nights).toLocaleString('en-PH') : '—';
            rows += `<tr>
                <td><strong>#${b.id}</strong></td>
                <td>${b.room}</td>
                <td>${b.check_in}</td>
                <td>${b.check_out}</td>
                <td>${b.nights} night${b.nights !== 1 ? 's' : ''}</td>
                <td><span class="status-pill ${statusClass(b.status)}">${b.status}</span></td>
                <td>${amount}</td>
            </tr>`;
        });
        hist.innerHTML = `
        <div style="overflow-x:auto;">
            <table class="booking-history-table">
                <thead><tr>
                    <th>#ID</th><th>Room</th><th>Check-in</th><th>Check-out</th>
                    <th>Nights</th><th>Status</th><th>Amount</th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
    }

    document.getElementById('guestModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('guestModal').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>

<script src="assets/js/sidebar-toggle.js"></script>
</body>
</html>
