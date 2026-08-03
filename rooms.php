<?php
require_once 'db.php';

/* ─────────────────────────────────────────
   Filter inputs
───────────────────────────────────────── */
$filter_type     = isset($_GET['room_type']) ? $_GET['room_type'] : 'any';
$filter_guests   = isset($_GET['guests'])    ? (int)$_GET['guests']    : 2;
$filter_checkin  = isset($_GET['checkin'])   ? $_GET['checkin']        : date('Y-m-d');
$filter_checkout = isset($_GET['checkout'])  ? $_GET['checkout']       : date('Y-m-d', strtotime('+1 day'));

/* ─────────────────────────────────────────
   Fetch rooms (with unavailable flag for
   rooms already booked in the date range)
───────────────────────────────────────── */
$where_clauses = [];
$where_clauses[] = "r.type IN ('beachview_duplex','seaview_duplex','beach_villa','standard_room','standard_king')";
if ($filter_type !== 'any') {
    $safe_type = $conn->real_escape_string($filter_type);
    $where_clauses[] = "r.type = '$safe_type'";
}
if ($filter_guests > 0) {
    $where_clauses[] = "r.capacity >= " . intval($filter_guests);
}

$where_sql = count($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$safe_checkin  = $conn->real_escape_string($filter_checkin);
$safe_checkout = $conn->real_escape_string($filter_checkout);

$sql = "
    SELECT r.*,
           (SELECT COUNT(*) FROM bookings b
            WHERE b.room_id = r.id
              AND b.status NOT IN ('Cancelled', 'Checked Out')
              AND b.check_in  < '$safe_checkout'
              AND b.check_out > '$safe_checkin'
           ) AS active_bookings
    FROM rooms r
    $where_sql
    ORDER BY r.price_per_night ASC
";

$result = $conn->query($sql);
$rooms  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

/* ─────────────────────────────────────────
   Display helpers
───────────────────────────────────────── */
$type_labels = [
    'beachview_duplex' => 'Beachview Duplex',
    'seaview_duplex'   => 'Seaview Duplex',
    'beach_villa'      => 'Beach Villa',
    'standard_room'    => 'Standard Room',
    'standard_king'    => 'Standard Family Room',
];

$type_gradients = [
    'beachview_duplex' => 'linear-gradient(135deg, #1a6b8a 0%, #0d9488 50%, #065f46 100%)',
    'seaview_duplex'   => 'linear-gradient(135deg, #1e3a5f 0%, #1a6b8a 50%, #0891b2 100%)',
    'beach_villa'      => 'linear-gradient(135deg, #92400e 0%, #b45309 50%, #d97706 100%)',
    'standard_room'    => 'linear-gradient(135deg, #374151 0%, #4b5563 50%, #6b7280 100%)',
    'standard_king'    => 'linear-gradient(135deg, #4c1d95 0%, #6d28d9 50%, #7c3aed 100%)',
];

$type_icons = [
    'beachview_duplex' => '🌊',
    'seaview_duplex'   => '🌅',
    'beach_villa'      => '🏖️',
    'standard_room'    => '🛏️',
    'standard_king'    => '👑',
];

// Fetch custom photos from room_types table
$db_photos = [];
$db_modal_photos = [];
$photo_q = $conn->query("SELECT name, image_url, gallery_images FROM room_types");
if ($photo_q) {
    while ($row = $photo_q->fetch_assoc()) {
        $t_name = $row['name'];
        if (!empty($row['image_url'])) {
            $db_photos[$t_name] = $row['image_url'];
        }
        if (!empty($row['gallery_images'])) {
            $gallery_arr = array_filter(array_map('trim', explode(',', $row['gallery_images'])));
            if (!empty($gallery_arr)) {
                $db_modal_photos[$t_name] = $gallery_arr;
            }
        }
    }
}

$type_photos = [
    'beachview_duplex' => $db_photos['beachview_duplex'] ?? '',
    'seaview_duplex'   => $db_photos['seaview_duplex']   ?? '',
    'beach_villa'      => $db_photos['beach_villa']      ?? '',
    'standard_room'    => $db_photos['standard_room']    ?? '',
    'standard_king'    => $db_photos['standard_king']    ?? '',
];

$type_descriptions = [
    'beachview_duplex' => 'Front-row. Serene. Intimate. Sensual.',
    'seaview_duplex'   => 'Front-row. Inspiring. Captivating. Sensual.',
    'beach_villa'      => 'Beachfront. Natural light-filled. Calm.',
    'standard_room'    => 'Standard Room',
    'standard_king'    => 'Standard Family Room',
];

$type_long_descriptions = [
    'beachview_duplex' => "Front-row. Serene. Intimate. Sensual.\nCozy Modern Tropical bedroom is the other half of the duplex structure facing the beach. Perfect people watching spot by your terrace or enjoy the property's entire beach view inside your room.\nYou can even transform to a blackout window cave-like dwelling with the drop-down projector screen.",
    'seaview_duplex'   => "Front-row. Inspiring. Captivating. Sensual.\nDesigned to make you never want to leave your room. A reading nook and desk crafted to connect you to nature while your cozy bedroom feels like a warm embrace even introverts would love.\nA secret back door offers direct access to the allure of the sea calling just beyond.",
    'beach_villa'      => "Beachfront. Natural light-filled. Calm.\nAn intimate 59sqm tropical modern coastal accommodation featuring atmospheric high ceilings to maximize a biophilic natural light-filled layout.\n*26 sqm bedroom 2x Queen beds\n*12 sqm glass-walled shower room with dual skylit vanity lavatories\n*4 sqm pocket garden for air-drying\n*10 sqm Covered beachfront lounge\n\nMaximum of 2 kids for free, 10 years and below.",
    'standard_room'    => "Standard Room\nMaximum of 2 kids for free, 10 years and below.\nMaximum occupancy of 5 adults.",
    'standard_king'    => "Standard Family Room\nMaximum of 2 kids for free, 10 years and below.",
];

$type_modal_photos = [
    'beachview_duplex' => $db_modal_photos['beachview_duplex'] ?? ($type_photos['beachview_duplex'] ? [$type_photos['beachview_duplex']] : []),
    'seaview_duplex'   => $db_modal_photos['seaview_duplex']   ?? ($type_photos['seaview_duplex']   ? [$type_photos['seaview_duplex']]   : []),
    'beach_villa'      => $db_modal_photos['beach_villa']      ?? ($type_photos['beach_villa']      ? [$type_photos['beach_villa']]      : []),
    'standard_room'    => $db_modal_photos['standard_room']    ?? ($type_photos['standard_room']    ? [$type_photos['standard_room']]    : []),
    'standard_king'    => $db_modal_photos['standard_king']    ?? ($type_photos['standard_king']    ? [$type_photos['standard_king']]    : []),
];

$type_beds = [
    'beachview_duplex' => '1 queen bed',
    'seaview_duplex'   => '1 king bed',
    'beach_villa'      => '2 queen beds',
    'standard_room'    => '1 queen bed or 2 single beds',
    'standard_king'    => '4 single beds',
];

$extra_person_rates = [
    'beachview_duplex' => 0,
    'seaview_duplex'   => 0,
    'beach_villa'      => 0,
    'standard_room'    => 700,
    'standard_king'    => 0,
];

$breakfast_included_types = [
    'beachview_duplex' => true,
    'seaview_duplex'   => true,
    'beach_villa'      => true,
];

$type_amenities = [
    'beachview_duplex' => [
        'Air conditioning',
        'Toiletries',
        'Mini Refrigerator',
        'Towels provided',
        'Shower',
        'Wardrobe',
        'Terrace/patio',
    ],
    'seaview_duplex' => [
        'Air conditioning',
        'Hair dryer',
        'Sofa/lounge chairs',
        'Wardrobe',
        'Ceiling fan',
        'Internet WiFi access in room',
        'Terrace/patio',
        'Work/writing desk',
        'Coffee/tea making facilities',
        'Mini Refrigerator',
        'Toiletries',
        'Daily Mineral Water',
        'Shower',
        'Towels provided',
    ],
    'beach_villa' => [
        'Air conditioning',
        'Bathroom with shower',
        'Internet WiFi access in room',
        'Toiletries',
    ],
    'standard_king' => [
        'Air conditioning',
        'Bathroom with shower',
        'Internet WiFi access in room',
        'Toiletries',
    ],
    'standard_room' => [
        'Air conditioning',
        'Internet WiFi access in room',
        'Shower',
        'Bathroom with shower',
        'Internet cable access in room',
        'Toiletries',
        'Daily Mineral Water',
        'Linen provided',
        'Towels provided',
        'Hot Shower',
        'Non-smoking rooms available',
    ],
];

$nights = max(1, (strtotime($filter_checkout) - strtotime($filter_checkin)) / 86400);

/* Keep the Standard Room inventory at six room units. */
$limited_rooms = [];
$standard_room_count = 0;
foreach ($rooms as $room) {
    if ($room['type'] === 'standard_room') {
        if ($standard_room_count >= 6) {
            continue;
        }
        $standard_room_count++;
    }
    $limited_rooms[] = $room;
}
$rooms = $limited_rooms;

/* Group rooms by type — available vs unavailable */
$avail_by_type   = [];
$unavail_by_type = [];
foreach ($rooms as $room) {
    $is_booked      = $room['active_bookings'] > 0;
    $is_maintenance = $room['status'] === 'maintenance';
    if ($is_booked || $is_maintenance) {
        $unavail_by_type[$room['type']][] = $room;
    } else {
        $avail_by_type[$room['type']][] = $room;
    }
}

$checkin_fmt  = date('D, d M Y', strtotime($filter_checkin));
$checkout_fmt = date('D, d M Y', strtotime($filter_checkout));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms & Accommodations – Santa Fe Beach Club</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo (int) filemtime(__DIR__ . '/styles.css'); ?>">
    <link rel="stylesheet" href="rooms.css?v=<?php echo (int) filemtime(__DIR__ . '/rooms.css'); ?>">
</head>
<body class="rooms-page">

    <!-- ── Header ── -->
    <header class="main-header">
        <div class="brand-logo">
            <a href="index.php" class="logo-link">
                <img src="assets/logo.jpg" alt="Santa Fe Beach Club logo" class="logo-mark" width="56" height="56">
            </a>
        </div>
        <nav class="nav-menu">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li class="active"><a href="rooms.php">Rooms</a></li>
                <li><a href="gallery.php">Gallery</a></li>

                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
        <div class="header-action">
    <a href="rooms.php" class="btn-book-header">Book Now</a>
</div>
    </header>

    <section class="rooms-hero">
        <div class="rooms-hero-inner">
            <div class="rooms-hero-copy">
                <p class="section-kicker">Coastal collection</p>
                <h1>Luxury stays curated for every shoreline mood</h1>
                <p>Compare sea-facing suites, family rooms, and tropical villas with live availability and transparent pricing.</p>
                <div class="rooms-hero-meta" aria-label="Highlights">
                    <span>Live availability</span>
                    <span>Flexible dates</span>
                    <span>Transparent pricing</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Booking Search Bar ── -->
    <div class="be-bar-wrapper">
        <form method="GET" action="rooms.php" class="be-bar-form">
            <div class="be-bar-inner">
                <!-- Date range -->
                <div class="be-field be-field--dates">
                    <div class="be-date-segment">
                        <span class="be-date-label">Check-in</span>
                        <input type="date" id="be-checkin" name="checkin" value="<?php echo htmlspecialchars($filter_checkin); ?>" min="<?php echo date('Y-m-d'); ?>" class="be-date-input">
                    </div>
                    <div class="be-date-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </div>
                    <div class="be-date-segment">
                        <span class="be-date-label">Check-out</span>
                        <input type="date" id="be-checkout" name="checkout" value="<?php echo htmlspecialchars($filter_checkout); ?>" min="<?php echo date('Y-m-d', strtotime($filter_checkin . ' +1 day')); ?>" class="be-date-input">
                    </div>
                    <script>
                    (function() {
                        var checkin  = document.getElementById('be-checkin');
                        var checkout = document.getElementById('be-checkout');
                        if (!checkin || !checkout) return;
                        checkin.addEventListener('change', function() {
                            var next = new Date(checkin.value);
                            next.setDate(next.getDate() + 1);
                            var minStr = next.toISOString().split('T')[0];
                            checkout.min = minStr;
                            if (checkout.value < minStr) {
                                checkout.value = minStr;
                            }
                        });
                    })();
                    </script>
                </div>
                <div class="be-divider"></div>
                <!-- Guests -->
                <div class="be-field be-field--guests">
                    <span class="be-field-label">Guests</span>
                    <select name="guests" class="be-select">
                        <option value="1" <?php if($filter_guests==1) echo 'selected'; ?>>1 adult · 1 room</option>
                        <option value="2" <?php if($filter_guests==2) echo 'selected'; ?>>2 adults · 1 room</option>
                        <option value="3" <?php if($filter_guests==3) echo 'selected'; ?>>3 adults · 1 room</option>
                        <option value="4" <?php if($filter_guests==4) echo 'selected'; ?>>4 adults · 1 room</option>
                        <option value="5" <?php if($filter_guests>=5) echo 'selected'; ?>>5+ adults · 1 room</option>
                    </select>
                </div>
                <div class="be-divider"></div>
                <!-- Room type (as promo-code-style field) -->
                <div class="be-field be-field--type">
                    <span class="be-field-label">Room Type</span>
                    <select name="room_type" class="be-select be-select--type">
                        <option value="any"              <?php if($filter_type=='any')             echo 'selected'; ?>>All Types</option>
                        <option value="beachview_duplex" <?php if($filter_type=='beachview_duplex') echo 'selected'; ?>>Beachview Duplex</option>
                        <option value="seaview_duplex"   <?php if($filter_type=='seaview_duplex')   echo 'selected'; ?>>Seaview Duplex</option>
                        <option value="beach_villa"      <?php if($filter_type=='beach_villa')      echo 'selected'; ?>>Beach Villa</option>
                        <option value="standard_room"    <?php if($filter_type=='standard_room')    echo 'selected'; ?>>Standard Room</option>
                        <option value="standard_king"    <?php if($filter_type=='standard_king')    echo 'selected'; ?>>Standard Family Room</option>
                    </select>
                </div>
                <!-- Search button -->
                <button type="submit" class="be-search-btn" aria-label="Search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </div>
        </form>
        <div class="be-currency">
            <span>Display currency</span>
            <strong>PHP <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></strong>
        </div>
    </div>

    <!-- ── Tab Navigation ── -->
    <div class="room-tabs-bar">
        <div class="room-tabs-inner">
            <button class="room-tab room-tab--active" data-tab="recommended" onclick="switchTab('recommended', this)">Rooms Recommended For You</button>
            <button class="room-tab" data-tab="other" onclick="switchTab('other', this)">Other Available Rooms</button>
        </div>
    </div>

    <!-- ── Main Content + Sidebar ── -->
    <div class="rooms-layout">

        <!-- ══ Rooms Main Column ══ -->
        <main class="rooms-main">

            <!-- RECOMMENDED TAB -->
            <div id="tab-recommended" class="tab-panel">
                <div class="rooms-section-intro">
                    <h2>Rooms Recommended For You</h2>
                    <p>Based on your requirements, we recommend that you book the following rooms</p>
                </div>

                <?php if (empty($avail_by_type)): ?>
                <div class="rooms-empty">
                    <div class="empty-icon">🏖️</div>
                    <h3>No rooms available for your selection</h3>
                    <p>Try adjusting your dates, guest count, or room type to find available accommodations.</p>
                    <a href="rooms.php" class="btn-reset">View All Rooms</a>
                </div>
                <?php else: ?>
                <?php foreach ($avail_by_type as $type_key => $type_rooms):
                    $label      = $type_labels[$type_key]      ?? ucfirst(str_replace('_', ' ', $type_key));
                    $gradient   = $type_gradients[$type_key]   ?? 'linear-gradient(135deg,#374151,#6b7280)';
                    $desc       = $type_descriptions[$type_key] ?? '';
                    $long_desc  = $type_long_descriptions[$type_key] ?? $desc;
                    $bed        = $type_beds[$type_key]        ?? '1 bed';
                    $photo      = $type_photos[$type_key]      ?? 'assets/hero_beach.png';
                    $modal_photos = $type_modal_photos[$type_key] ?? [$photo];
                    $amenities  = $type_amenities[$type_key] ?? [];
                    $has_breakfast = !empty($breakfast_included_types[$type_key]);
                    $extra_rate = $extra_person_rates[$type_key] ?? 0;
                    $avail_count = count($type_rooms);
                    $sample_room = $type_rooms[0];
                    $price      = $sample_room['price_per_night'];
                    $capacity   = $sample_room['capacity'];
                    $type_id    = 'room-' . str_replace('_', '-', $type_key);
                    $book_url   = 'book.php?' . http_build_query([
                        'checkin'   => $filter_checkin,
                        'checkout'  => $filter_checkout,
                        'guests'    => max(1, $filter_guests),
                        'room_type' => $type_key,
                    ]);
                ?>
                <div class="room-type-section" id="<?php echo $type_id; ?>">
                    <!-- Type Header -->
                    <div class="rts-header">
                        <h3 class="rts-title"><?php echo strtoupper($label); ?></h3>
                        <div class="rts-alerts">
                            <?php if ($avail_count === 1): ?>
                            <span class="alert-urgency">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                Only 1 room left
                            </span>
                            <span class="alert-divider">|</span>
                            <?php endif; ?>
                            <span class="alert-tip">You need to book at least 1 room of this rate plan for your group of <?php echo max(1,$filter_guests); ?> adult<?php echo $filter_guests > 1 ? 's' : ''; ?> and 0 children.</span>
                        </div>
                    </div>

                    <!-- Rate Card -->
                    <div class="rate-card">
                        <!-- LEFT: photo + info -->
                        <div class="rate-card-left">
                            <div class="rate-photo" style="background: <?php echo $gradient; ?>; background-image: url('<?php echo htmlspecialchars($photo); ?>');">
                                <div class="rate-photo-overlay">
                                    <span class="rate-photo-badge">Signature Stay</span>
                                    <div class="rate-photo-copy">
                                        <span class="rate-photo-kicker"><?php echo htmlspecialchars($label); ?></span>
                                        <strong class="rate-photo-title"><?php echo $capacity; ?> guests · <?php echo htmlspecialchars($bed); ?></strong>
                                    </div>
                                </div>
                                <span class="rate-photo-count">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    <?php echo $avail_count; ?>
                                </span>
                            </div>
                            <div class="rate-info">
                                <div class="rate-bed">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/><line x1="12" y1="10" x2="12" y2="20"/></svg>
                                    <?php echo htmlspecialchars($bed); ?>
                                </div>
                                <p class="rate-desc"><?php echo htmlspecialchars($desc); ?></p>
                                <button
                                    class="btn-view-room-info"
                                    type="button"
                                    data-room-name="<?php echo htmlspecialchars($label, ENT_QUOTES); ?>"
                                    data-room-bed="<?php echo htmlspecialchars($bed, ENT_QUOTES); ?>"
                                    data-room-desc="<?php echo htmlspecialchars($long_desc, ENT_QUOTES); ?>"
                                    data-room-photos="<?php echo htmlspecialchars(json_encode($modal_photos), ENT_QUOTES); ?>"
                                    data-room-amenities="<?php echo htmlspecialchars(json_encode($amenities), ENT_QUOTES); ?>"
                                    onclick="openRoomModal(this)">
                                    View more Room information
                                </button>
                            </div>
                        </div>

                        <!-- RIGHT: rate panel -->
                        <div class="rate-card-right">
                            <div class="rate-name-wrap">
                                <span class="rate-plan-tag">Best Flexible Rate</span>
                                <h4 class="rate-name"><?php echo htmlspecialchars($label); ?></h4>
                            </div>
                            <div class="rate-features">
                                <div class="rate-feature">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Flexible cancellation
                                </div>
                                <div class="rate-feature">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2h18l-2 7H5L3 2z"/><path d="M5 9l-1 11h16l-1-11"/><line x1="12" y1="13" x2="12" y2="18"/><line x1="9.5" y1="13" x2="9.5" y2="18"/><line x1="14.5" y1="13" x2="14.5" y2="18"/></svg>
                                    <?php echo $has_breakfast ? 'Breakfast included' : 'No breakfast included'; ?>
                                </div>
                            </div>
                            <div class="rate-pricing">
                                <div class="rate-from-label">From</div>
                                <div class="rate-price-main">₱ <?php echo number_format($price, 2); ?></div>
                                <div class="rate-price-sub">per night, <?php echo max(1,$filter_guests); ?> adult<?php echo $filter_guests > 1 ? 's' : ''; ?></div>
                                <div class="rate-price-note">Price includes all taxes and fees</div>
                            </div>
                            <div class="rate-actions-box">
                                <div class="rate-selector-row">
                                    <?php if ($avail_count === 1): ?>
                                    <label class="rate-room-checkbox-label" style="display:flex;align-items:center;gap:10px;cursor:pointer;border:1px solid #d1d5db;padding:8px 12px;border-radius:8px;font-weight:600;color:#1f2937;flex:1;">
                                        <input type="checkbox" class="rate-room-select" style="width:18px;height:18px;cursor:pointer;"
                                            data-type="<?php echo htmlspecialchars($type_key); ?>"
                                            data-label="<?php echo htmlspecialchars($label); ?>"
                                            data-price="<?php echo $price; ?>"
                                            data-extra-price="<?php echo $extra_rate; ?>"
                                            data-nights="<?php echo $nights; ?>"
                                            data-book-url="<?php echo htmlspecialchars($book_url); ?>"
                                            onchange="updateSummary()" value="1">
                                        Book this room
                                    </label>
                                    <?php else: ?>
                                    <select class="rate-room-select"
                                            data-type="<?php echo htmlspecialchars($type_key); ?>"
                                            data-label="<?php echo htmlspecialchars($label); ?>"
                                            data-price="<?php echo $price; ?>"
                                            data-extra-price="<?php echo $extra_rate; ?>"
                                            data-nights="<?php echo $nights; ?>"
                                            data-book-url="<?php echo htmlspecialchars($book_url); ?>"
                                            onchange="updateSummary()">
                                        <option value="0">0 rooms</option>
                                        <?php for ($i = 1; $i <= min($avail_count, 5); $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> room<?php echo $i > 1 ? 's' : ''; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                    <?php endif; ?>
                                </div>
                                <button class="rate-extra-persons" type="button" data-type="<?php echo htmlspecialchars($type_key); ?>" onclick="toggleExtraPersons(this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="23" y1="11" x2="17" y2="11"/><line x1="20" y1="8" x2="20" y2="14"/></svg>
                                    <span class="rate-extra-persons-label">add extra persons (0)</span>
                                    <svg class="extra-chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                                </button>
                                <div class="rate-extra-panel" id="extra-panel-<?php echo htmlspecialchars($type_key); ?>">
                                    <p class="rate-extra-note">Please note that infants 0 - 3 years old stay free when using existing bedding</p>
                                    <div class="rate-extra-card">
                                        <div class="rate-extra-rows" id="extra-rows-<?php echo htmlspecialchars($type_key); ?>"></div>
                                        <div class="rate-extra-total">
                                            <span>Total</span>
                                            <span id="extra-total-<?php echo htmlspecialchars($type_key); ?>">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- OTHER AVAILABLE ROOMS TAB -->
            <div id="tab-other" class="tab-panel" style="display:none;">
                <div class="rooms-section-intro">
                    <h2>Other Available Rooms</h2>
                    <p>These rooms are currently unavailable for your selected dates</p>
                </div>

                <?php if (empty($unavail_by_type)): ?>
                <div class="rooms-empty">
                    <div class="empty-icon">✅</div>
                    <h3>All rooms are available!</h3>
                    <p>Great news — no unavailable rooms for your selected dates.</p>
                </div>
                <?php else: ?>
                <?php foreach ($unavail_by_type as $type_key => $type_rooms):
                    $label     = $type_labels[$type_key]      ?? ucfirst(str_replace('_', ' ', $type_key));
                    $gradient  = $type_gradients[$type_key]   ?? 'linear-gradient(135deg,#374151,#6b7280)';
                    $desc      = $type_descriptions[$type_key] ?? '';
                    $long_desc = $type_long_descriptions[$type_key] ?? $desc;
                    $bed       = $type_beds[$type_key]        ?? '1 bed';
                    $photo     = $type_photos[$type_key]      ?? 'assets/hero_beach.png';
                    $modal_photos = $type_modal_photos[$type_key] ?? [$photo];
                    $sample_room = $type_rooms[0];
                    $price     = $sample_room['price_per_night'];
                    $capacity  = $sample_room['capacity'];
                    $is_maintenance = $sample_room['status'] === 'maintenance';
                    $unavail_reason = $is_maintenance ? 'Under Maintenance' : 'Fully Booked';
                ?>
                <div class="room-type-section room-type-section--unavailable">
                    <div class="rts-header">
                        <h3 class="rts-title"><?php echo strtoupper($label); ?></h3>
                        <span class="unavail-badge"><?php echo $unavail_reason; ?></span>
                    </div>
                    <div class="rate-card rate-card--unavailable">
                        <div class="rate-card-left">
                            <div class="rate-photo" style="background: <?php echo $gradient; ?>; background-image: url('<?php echo htmlspecialchars($photo); ?>'); opacity: 0.7;">
                            <div class="rate-photo-overlay">
                                <span class="rate-photo-badge">Limited Availability</span>
                                <div class="rate-photo-copy">
                                    <span class="rate-photo-kicker"><?php echo htmlspecialchars($label); ?></span>
                                    <strong class="rate-photo-title"><?php echo $capacity; ?> guests · <?php echo htmlspecialchars($bed); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="rate-info">
                            <div class="rate-bed">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/><line x1="12" y1="10" x2="12" y2="20"/></svg>
                                    <?php echo htmlspecialchars($bed); ?>
                                </div>
                                <p class="rate-desc"><?php echo htmlspecialchars($desc); ?></p>
                            </div>
                        </div>
                        <div class="rate-card-right rate-card-right--unavailable">
                            <h4 class="rate-name"><?php echo htmlspecialchars($label); ?></h4>
                            <div class="rate-pricing">
                                <div class="rate-price-main" style="color:#999;">₱ <?php echo number_format($price, 2); ?></div>
                                <div class="rate-price-sub">per night</div>
                            </div>
                            <div class="unavail-notice">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Not available for selected dates
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </main>

        <!-- ══ Booking Summary Sidebar ══ -->
        <aside class="booking-sidebar" id="booking-sidebar">
            <h3 class="bs-title">Booking Summary</h3>

            <div class="bs-dates">
                <div class="bs-date-col">
                    <div class="bs-date-main"><?php echo $checkin_fmt; ?></div>
                    <div class="bs-date-time">from 14:00</div>
                </div>
                <div class="bs-date-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
                <div class="bs-date-col bs-date-col--right">
                    <div class="bs-date-main"><?php echo $checkout_fmt; ?></div>
                    <div class="bs-date-time">until 12:00</div>
                </div>
            </div>
            <div class="bs-nights"><?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></div>

            <div class="bs-room-info">
                <div class="bs-room-info-header">
                    <span>Room information:</span>
                    <a href="#" class="bs-clear-link" id="bs-clear-link" onclick="clearSelection(event)">Clear selection</a>
                </div>
                <div class="bs-room-placeholder" id="bs-placeholder">Choose a room and add to your booking</div>
                <div class="bs-selected-rooms" id="bs-selected-rooms" style="display:none;"></div>
            </div>

            <div class="bs-total-row">
                <span class="bs-total-label">Total</span>
                <span class="bs-total-amount" id="bs-total">₱ 0.00</span>
            </div>
            <div class="bs-tax-note">Price includes all taxes and fees</div>

            <button class="btn-book-now" id="btn-book-now" disabled onclick="proceedToBook()">BOOK NOW</button>
        </aside>

    </div><!-- /.rooms-layout -->

    <div class="room-modal" id="room-modal" aria-hidden="true">
        <div class="room-modal-backdrop" onclick="closeRoomModal()"></div>
        <div class="room-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="room-modal-title">
            <div class="room-modal-header">
                <h3>Room details</h3>
                <button type="button" class="room-modal-close" aria-label="Close room details" onclick="closeRoomModal()">&times;</button>
            </div>
            <div class="room-modal-body">
                <div class="room-modal-gallery">
                    <div class="room-modal-main-wrap">
                        <img src="" alt="" class="room-modal-main-image" id="room-modal-main-image">
                        <span class="room-modal-counter" id="room-modal-counter">1/1</span>
                    </div>
                    <div class="room-modal-thumbs" id="room-modal-thumbs"></div>
                </div>
                <div class="room-modal-content">
                    <h4 class="room-modal-title" id="room-modal-title"></h4>
                    <div class="room-modal-bed" id="room-modal-bed"></div>
                    <p class="room-modal-description" id="room-modal-description"></p>
                    <div class="room-modal-amenities-wrap" id="room-modal-amenities-wrap" style="display:none;">
                        <h5>Amenities</h5>
                        <div class="room-modal-amenities" id="room-modal-amenities"></div>
                    </div>
                </div>
            </div>
            <div class="room-modal-footer">
                <button type="button" class="room-modal-close-btn" onclick="closeRoomModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- ── Why Choose Us Strip ── -->
    <section class="perks-strip">
        <div class="perks-inner">
            <div class="perk-item">
                <div class="perk-icon">🏖️</div>
                <div>
                    <h4>Direct Beach Access</h4>
                    <p>Steps away from pristine white-sand shores</p>
                </div>
            </div>
            <div class="perk-item">
                <div class="perk-icon">🍽️</div>
                <div>
                    <h4>In-Room Dining</h4>
                    <p>Curated meals delivered to your suite</p>
                </div>
            </div>
            <div class="perk-item">
                <div class="perk-icon">🛡️</div>
                <div>
                    <h4>Free Cancellation</h4>
                    <p>Up to 48 hours before check-in</p>
                </div>
            </div>
            <div class="perk-item">
                <div class="perk-icon">🌐</div>
                <div>
                    <h4>High-Speed WiFi</h4>
                    <p>Complimentary in all accommodations</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Footer ── -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-brand-col">
                <h3>Santa Fe Beach Club</h3>
                <p>Experience the ultimate coastal sophistication. A serene blend of boutique hospitality and tropical elegance.</p>
            </div>
            <div class="footer-links-col">
                <h4>LEGAL</h4>
                <ul>
                    <li><a href="#privacy">Privacy Policy</a></li>
                    <li><a href="#terms">Terms of Service</a></li>
                </ul>
            </div>
            <div class="footer-links-col">
                <h4>COMPANY</h4>
                <ul>
                    <li><a href="#careers">Careers</a></li>
                    <li><a href="#sustainability">Sustainability</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Santa Fe Beach Club. All rights reserved.</p>
        </div>
    </footer>

<script>
/* ── Tab switching ── */
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.room-tab').forEach(b => b.classList.remove('room-tab--active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('room-tab--active');
}

/* ── Booking summary state ── */
var selectedRooms = {};
var extraPersonsByType = {};

function updateSummary() {
    selectedRooms = {};
    document.querySelectorAll('.rate-room-select').forEach(function(sel) {
        var qty = (sel.tagName === 'INPUT' && sel.type === 'checkbox') ? (sel.checked ? 1 : 0) : parseInt(sel.value);
        if (qty > 0) {
            var key = sel.dataset.type;
            selectedRooms[key] = {
                label:    sel.dataset.label,
                price:    parseFloat(sel.dataset.price),
                extraPrice: parseFloat(sel.dataset.extraPrice || '0'),
                nights:   parseInt(sel.dataset.nights),
                qty:      qty,
                bookUrl:  sel.dataset.bookUrl
            };
        }
    });
    syncExtraPersonsPanels();
    renderSummary();
}

function renderSummary() {
    var placeholder   = document.getElementById('bs-placeholder');
    var selectedDiv   = document.getElementById('bs-selected-rooms');
    var totalEl       = document.getElementById('bs-total');
    var bookBtn       = document.getElementById('btn-book-now');
    var clearLink     = document.getElementById('bs-clear-link');

    var keys = Object.keys(selectedRooms);
    var grandTotal = 0;

    if (keys.length === 0) {
        placeholder.style.display  = 'block';
        selectedDiv.style.display  = 'none';
        totalEl.textContent        = '₱ 0.00';
        bookBtn.disabled           = true;
        clearLink.style.visibility = 'hidden';
        return;
    }

    clearLink.style.visibility = 'visible';
    placeholder.style.display  = 'none';
    selectedDiv.style.display  = 'block';
    selectedDiv.innerHTML = '';

    keys.forEach(function(key) {
        var r = selectedRooms[key];
        var extraCount = (extraPersonsByType[key] || []).reduce(function(sum, value) {
            return sum + (parseInt(value, 10) || 0);
        }, 0);
        var roomSubtotal = r.price * r.nights * r.qty;
        var extraSubtotal = extraCount * r.extraPrice;
        var subtotal = roomSubtotal + extraSubtotal;
        grandTotal += subtotal;

        var el = document.createElement('div');
        el.className = 'bs-room-line';
        el.innerHTML =
            '<span class="bs-room-name">' + r.qty + 'x ' + r.label + '</span>' +
            '<span class="bs-room-subtotal">₱ ' + roomSubtotal.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span>';
        selectedDiv.appendChild(el);

        if (extraSubtotal > 0) {
            var extraEl = document.createElement('div');
            extraEl.className = 'bs-room-line bs-room-line--extra';
            extraEl.innerHTML =
                '<span class="bs-room-name">Extra adults (' + extraCount + ')</span>' +
                '<span class="bs-room-subtotal">₱ ' + extraSubtotal.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span>';
            selectedDiv.appendChild(extraEl);
        }
    });

    totalEl.textContent = '₱ ' + grandTotal.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    bookBtn.disabled = false;
}

function clearSelection(e) {
    e.preventDefault();
    document.querySelectorAll('.rate-room-select').forEach(function(sel) { sel.value = '0'; });
    selectedRooms = {};
    extraPersonsByType = {};
    document.querySelectorAll('.rate-extra-persons-label').forEach(function(label) {
        label.textContent = 'add extra persons (0)';
    });
    document.querySelectorAll('.rate-extra-panel').forEach(function(panel) {
        panel.classList.remove('rate-extra-panel--open');
    });
    document.querySelectorAll('.rate-extra-persons').forEach(function(btn) {
        btn.classList.remove('rate-extra-persons--open');
    });
    renderSummary();
}

function proceedToBook() {
    var keys = Object.keys(selectedRooms);
    if (keys.length === 0) return;
    // Navigate to the first selected room's booking URL
    window.location.href = selectedRooms[keys[0]].bookUrl;
}

function toggleExtraPersons(btn) {
    var type = btn.getAttribute('data-type');
    var panel = document.getElementById('extra-panel-' + type);
    var isOpen = panel.classList.contains('rate-extra-panel--open');

    document.querySelectorAll('.rate-extra-panel').forEach(function(item) {
        item.classList.remove('rate-extra-panel--open');
    });
    document.querySelectorAll('.rate-extra-persons').forEach(function(item) {
        item.classList.remove('rate-extra-persons--open');
    });

    if (!isOpen) {
        renderExtraPersonsPanel(type);
        panel.classList.add('rate-extra-panel--open');
        btn.classList.add('rate-extra-persons--open');
    }
}

function getSelectedRoomQty(type) {
    var sel = document.querySelector('.rate-room-select[data-type="' + type + '"]');
    if (!sel) return 1;
    var qty = parseInt(sel.value, 10);
    return Math.max(1, qty || 0);
}

function renderExtraPersonsPanel(type) {
    var qty = getSelectedRoomQty(type);
    var rowsWrap = document.getElementById('extra-rows-' + type);
    var values = extraPersonsByType[type] || [];
    values = values.slice(0, qty);
    while (values.length < qty) {
        values.push(0);
    }
    extraPersonsByType[type] = values;

    rowsWrap.innerHTML = '';
    values.forEach(function(value, index) {
        var row = document.createElement('div');
        row.className = 'rate-extra-row';

        var label = document.createElement('div');
        label.className = 'rate-extra-room-label';
        label.textContent = 'Room ' + (index + 1);

        var selectWrap = document.createElement('div');
        selectWrap.className = 'rate-extra-select-wrap';

        var select = document.createElement('select');
        select.className = 'rate-extra-select';
        [0, 1, 2, 3, 4].forEach(function(count) {
            var option = document.createElement('option');
            option.value = count;
            option.textContent = count + ' adult' + (count !== 1 ? 's' : '');
            if (count === value) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        select.onchange = function() {
            updateExtraPersons(type, index, this.value);
        };

        var chevron = document.createElement('span');
        chevron.className = 'rate-extra-select-chevron';
        chevron.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';

        selectWrap.appendChild(select);
        selectWrap.appendChild(chevron);
        row.appendChild(label);
        row.appendChild(selectWrap);
        rowsWrap.appendChild(row);
    });

    updateExtraPersonsSummary(type);
}

function updateExtraPersons(type, index, value) {
    var values = extraPersonsByType[type] || [];
    values[index] = parseInt(value, 10) || 0;
    extraPersonsByType[type] = values;
    updateExtraPersonsSummary(type);
}

function updateExtraPersonsSummary(type) {
    var values = extraPersonsByType[type] || [];
    var total = values.reduce(function(sum, value) { return sum + value; }, 0);
    var totalEl = document.getElementById('extra-total-' + type);
    var btn = document.querySelector('.rate-extra-persons[data-type="' + type + '"]');
    var roomSelect = document.querySelector('.rate-room-select[data-type="' + type + '"]');
    var extraRate = roomSelect ? (parseFloat(roomSelect.getAttribute('data-extra-price') || '0')) : 0;
    var extraCharge = total * extraRate;

    if (totalEl) {
        totalEl.textContent = extraCharge > 0
            ? ('₱ ' + extraCharge.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}))
            : '-';
    }
    if (btn) {
        var label = btn.querySelector('.rate-extra-persons-label');
        if (label) {
            label.textContent = 'add extra persons (' + total + ')';
        }
    }
    renderSummary();
}

function syncExtraPersonsPanels() {
    Object.keys(extraPersonsByType).forEach(function(type) {
        var panel = document.getElementById('extra-panel-' + type);
        if (panel && panel.classList.contains('rate-extra-panel--open')) {
            renderExtraPersonsPanel(type);
        } else {
            updateExtraPersonsSummary(type);
        }
    });
}

function setRoomModalImage(src, alt, index, total, thumbEl) {
    var mainImage = document.getElementById('room-modal-main-image');
    var counter = document.getElementById('room-modal-counter');
    mainImage.src = src;
    mainImage.alt = alt;
    counter.textContent = (index + 1) + '/' + total;

    document.querySelectorAll('.room-modal-thumb').forEach(function(thumb) {
        thumb.classList.remove('room-modal-thumb--active');
    });
    if (thumbEl) {
        thumbEl.classList.add('room-modal-thumb--active');
    }
}

function openRoomModal(btn) {
    var modal = document.getElementById('room-modal');
    var name = btn.getAttribute('data-room-name') || '';
    var bed = btn.getAttribute('data-room-bed') || '';
    var desc = btn.getAttribute('data-room-desc') || '';
    var photos = [];
    var amenities = [];

    try {
        photos = JSON.parse(btn.getAttribute('data-room-photos') || '[]');
    } catch (e) {
        photos = [];
    }
    try {
        amenities = JSON.parse(btn.getAttribute('data-room-amenities') || '[]');
    } catch (e) {
        amenities = [];
    }

    document.getElementById('room-modal-title').textContent = name.toUpperCase();
    document.getElementById('room-modal-bed').textContent = bed;
    document.getElementById('room-modal-description').textContent = desc;
    var amenitiesWrap = document.getElementById('room-modal-amenities-wrap');
    var amenitiesEl = document.getElementById('room-modal-amenities');
    amenitiesEl.innerHTML = '';
    if (amenities.length) {
        amenities.forEach(function(item) {
            var amenity = document.createElement('div');
            amenity.className = 'room-modal-amenity';
            amenity.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span></span>';
            amenity.querySelector('span').textContent = item;
            amenitiesEl.appendChild(amenity);
        });
        amenitiesWrap.style.display = '';
    } else {
        amenitiesWrap.style.display = 'none';
    }

    var thumbsWrap = document.getElementById('room-modal-thumbs');
    thumbsWrap.innerHTML = '';
    if (!photos.length) {
        photos = ['assets/hero_beach.png'];
    }

    photos.forEach(function(src, index) {
        var thumb = document.createElement('button');
        thumb.type = 'button';
        thumb.className = 'room-modal-thumb' + (index === 0 ? ' room-modal-thumb--active' : '');
        thumb.innerHTML = '<img src="' + src + '" alt="' + name + ' thumbnail ' + (index + 1) + '">';
        thumb.onclick = function() {
            setRoomModalImage(src, name, index, photos.length, thumb);
        };
        thumbsWrap.appendChild(thumb);
    });

    setRoomModalImage(photos[0], name, 0, photos.length, thumbsWrap.querySelector('.room-modal-thumb'));
    modal.classList.add('room-modal--open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('body-modal-open');
}

function closeRoomModal() {
    var modal = document.getElementById('room-modal');
    modal.classList.remove('room-modal--open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('body-modal-open');
}

/* Init clear-link hidden */
document.addEventListener('DOMContentLoaded', function() {
    var cl = document.getElementById('bs-clear-link');
    if (cl) cl.style.visibility = 'hidden';

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRoomModal();
        }
    });
});
</script>
</body>
</html>
