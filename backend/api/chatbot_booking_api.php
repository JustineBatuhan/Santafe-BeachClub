<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/cors_helper.php';
require_once __DIR__ . '/../helpers/rate_limiter.php';

handle_cors();
RateLimiter::enforce($conn, 'chatbot_api', 120, 60);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

const CHATBOT_STEPS = ['ASK_SERVICE', 'ASK_DATE', 'ASK_TIME', 'ASK_NAME', 'CONFIRM', 'SAVE'];

function chatbot_respond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function chatbot_room_type_label(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}

function chatbot_services(mysqli $conn): array
{
    $services = [];
    $result = $conn->query("SELECT name FROM room_types ORDER BY name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $value = (string)$row['name'];
            $services[] = [
                'value' => $value,
                'label' => chatbot_room_type_label($value),
            ];
        }
    }

    if (empty($services)) {
        $fallback = ['beachview_duplex', 'seaview_duplex', 'beach_villa', 'standard_king', 'standard_room'];
        foreach ($fallback as $value) {
            $services[] = [
                'value' => $value,
                'label' => chatbot_room_type_label($value),
            ];
        }
    }

    return $services;
}

function chatbot_time_slots(): array
{
    return ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
}

function chatbot_state_prompt(string $state): string
{
    switch ($state) {
        case 'ASK_SERVICE':
            return 'Hi! Please choose a room type to book.';
        case 'ASK_DATE':
            return 'Great choice. What date would you like to book?';
        case 'ASK_TIME':
            return 'What time would you like to arrive?';
        case 'ASK_NAME':
            return 'Please enter your full name and contact number.';
        case 'CONFIRM':
            return 'Please review your booking details and confirm.';
        case 'SAVE':
            return 'Your booking is saved. Thank you!';
        default:
            return 'Let us continue your booking.';
    }
}

function chatbot_summary(array $data): array
{
    return [
        'service' => $data['service_label'] ?? null,
        'date'    => $data['date'] ?? null,
        'time'    => $data['time'] ?? null,
        'name'    => $data['name'] ?? null,
        'contact' => $data['contact'] ?? null,
    ];
}

function chatbot_columns(mysqli $conn): array
{
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM bookings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

function chatbot_bind(mysqli_stmt $stmt, string $types, array &$values): void
{
    $params = [$types];
    foreach ($values as $index => &$value) {
        $params[] = &$value;
    }
    call_user_func_array([$stmt, 'bind_param'], $params);
}

function chatbot_save_booking(mysqli $conn, array $data): int
{
    $columns = chatbot_columns($conn);
    $hasLegacyColumns = in_array('service', $columns, true)
        && in_array('date', $columns, true)
        && in_array('time', $columns, true)
        && in_array('name', $columns, true)
        && in_array('contact', $columns, true);

    if ($hasLegacyColumns) {
        $stmt = $conn->prepare("INSERT INTO bookings (`service`, `date`, `time`, `name`, `contact`) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $data['service_label'], $data['date'], $data['time'], $data['name'], $data['contact']);
        $stmt->execute();
        $bookingId = (int)$stmt->insert_id;
        $stmt->close();
        return $bookingId;
    }

    $roomTypeId = null;
    if (in_array('room_type_id', $columns, true)) {
        $typeStmt = $conn->prepare("SELECT id FROM room_types WHERE name = ? LIMIT 1");
        $typeStmt->bind_param("s", $data['service']);
        $typeStmt->execute();
        $typeResult = $typeStmt->get_result();
        if ($typeResult && $typeResult->num_rows > 0) {
            $roomTypeId = (int)$typeResult->fetch_assoc()['id'];
        }
        $typeStmt->close();
    }

    // Auto-detect guest tier based on previous confirmed bookings
    $tierName  = $data['name'] ?? '';
    $tierEmail = $data['email'] ?? '';
    if (!empty($tierEmail)) {
        $tierStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE guest_email = ? AND status NOT IN ('Cancelled','Pending Payment')");
        $tierStmt->bind_param("s", $tierEmail);
    } else {
        $tierStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE guest_name = ? AND status NOT IN ('Cancelled','Pending Payment')");
        $tierStmt->bind_param("s", $tierName);
    }
    $tierStmt->execute();
    $tierCount = (int)$tierStmt->get_result()->fetch_assoc()['cnt'];
    $tierStmt->close();
    if ($tierCount === 0)     $autoGuestType = 'First Visit';
    elseif ($tierCount === 1) $autoGuestType = 'Returning Guest';
    else                      $autoGuestType = 'VIP Member';

    $defaultValues = [
        'guest_name'        => $data['name'],
        'guest_type'        => $autoGuestType,
        'check_in'          => $data['date'],
        'check_out'         => date('Y-m-d', strtotime($data['date'] . ' +1 day')),
        'guests_count'      => 1,
        'room_type_id'      => $roomTypeId,
        'accommodation_name'=> $data['service_label'],
        'eta'               => $data['time'],
        'status'            => 'Pending',
        'payment_method'    => 'Pay at Check-in',
        'guest_phone'       => $data['contact'],
    ];

    $insertColumns = [];
    $insertValues = [];
    $types = '';

    foreach ($defaultValues as $column => $value) {
        if (!in_array($column, $columns, true)) {
            continue;
        }
        $insertColumns[] = "`$column`";
        $insertValues[] = $value;
        $types .= is_int($value) ? 'i' : 's';
    }

    if (empty($insertColumns)) {
        throw new RuntimeException('Could not map booking columns for chatbot save.');
    }

    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
    $sql = "INSERT INTO bookings (" . implode(', ', $insertColumns) . ") VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    chatbot_bind($stmt, $types, $insertValues);
    $stmt->execute();
    $bookingId = (int)$stmt->insert_id;
    $stmt->close();

    return $bookingId;
}

function chatbot_reset(): array
{
    return [
        'state' => 'ASK_SERVICE',
        'data' => [],
    ];
}

if (!isset($_SESSION['chatbot_booking']) || !is_array($_SESSION['chatbot_booking'])) {
    $_SESSION['chatbot_booking'] = chatbot_reset();
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '{}', true);
if (!is_array($payload)) {
    $payload = [];
}

$action = isset($payload['action']) ? (string)$payload['action'] : 'get_state';
$sessionData = &$_SESSION['chatbot_booking'];
$services = chatbot_services($conn);
$timeSlots = chatbot_time_slots();
$serviceMap = [];
foreach ($services as $service) {
    $serviceMap[$service['value']] = $service['label'];
}

try {
    switch ($action) {
        case 'start':
            $sessionData = chatbot_reset();
            break;

        case 'select_service':
            $serviceValue = isset($payload['service']) ? trim((string)$payload['service']) : '';
            if (!isset($serviceMap[$serviceValue])) {
                chatbot_respond(['ok' => false, 'error' => 'Please choose a valid room type.'], 422);
            }
            $sessionData['data']['service'] = $serviceValue;
            $sessionData['data']['service_label'] = $serviceMap[$serviceValue];
            $sessionData['state'] = 'ASK_DATE';
            break;

        case 'set_date':
            $date = isset($payload['date']) ? trim((string)$payload['date']) : '';
            $dateValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
            $today = date('Y-m-d');
            if (!$dateValid || $date < $today) {
                chatbot_respond(['ok' => false, 'error' => 'Please select a valid date (today or later).'], 422);
            }
            $sessionData['data']['date'] = $date;
            $sessionData['state'] = 'ASK_TIME';
            break;

        case 'set_time':
            $time = isset($payload['time']) ? trim((string)$payload['time']) : '';
            if (!in_array($time, $timeSlots, true)) {
                chatbot_respond(['ok' => false, 'error' => 'Please choose a valid time slot.'], 422);
            }
            $sessionData['data']['time'] = $time;
            $sessionData['state'] = 'ASK_NAME';
            break;

        case 'set_name':
            $name = isset($payload['name']) ? trim((string)$payload['name']) : '';
            $contact = isset($payload['contact']) ? trim((string)$payload['contact']) : '';
            if ($name === '' || strlen($name) < 2) {
                chatbot_respond(['ok' => false, 'error' => 'Please enter your full name.'], 422);
            }
            if ($contact === '') {
                chatbot_respond(['ok' => false, 'error' => 'Please enter your contact number.'], 422);
            }
            $sessionData['data']['name'] = $name;
            $sessionData['data']['contact'] = $contact;
            $sessionData['state'] = 'CONFIRM';
            break;

        case 'confirm':
            $decision = isset($payload['decision']) ? strtolower(trim((string)$payload['decision'])) : '';
            if ($decision === 'no') {
                $sessionData = chatbot_reset();
                chatbot_respond([
                    'ok' => true,
                    'state' => $sessionData['state'],
                    'prompt' => 'No problem — I reset your booking. Let us start again.',
                    'options' => [
                        'services' => $services,
                        'times' => $timeSlots,
                        'min_date' => date('Y-m-d'),
                    ],
                    'data' => $sessionData['data'],
                    'summary' => chatbot_summary($sessionData['data']),
                ]);
            }

            if ($decision !== 'yes') {
                chatbot_respond(['ok' => false, 'error' => 'Please choose Confirm or Start Over.'], 422);
            }

            $requiredFields = ['service', 'service_label', 'date', 'time', 'name', 'contact'];
            foreach ($requiredFields as $field) {
                if (empty($sessionData['data'][$field])) {
                    chatbot_respond(['ok' => false, 'error' => 'Missing booking details. Please restart the booking flow.'], 422);
                }
            }

            $bookingId = chatbot_save_booking($conn, $sessionData['data']);
            $sessionData['state'] = 'SAVE';
            $sessionData['data']['booking_id'] = $bookingId;
            break;

        case 'new_booking':
            $sessionData = chatbot_reset();
            break;

        case 'get_state':
            break;

        default:
            chatbot_respond(['ok' => false, 'error' => 'Unsupported chatbot action.'], 400);
    }
} catch (Throwable $e) {
    chatbot_respond(['ok' => false, 'error' => 'Chatbot booking failed: ' . $e->getMessage()], 500);
}

$state = $sessionData['state'];
$data = $sessionData['data'];
$prompt = $state === 'SAVE' && isset($data['booking_id'])
    ? 'Your booking is saved! Reference ID: ' . (int)$data['booking_id']
    : chatbot_state_prompt($state);

chatbot_respond([
    'ok' => true,
    'state' => $state,
    'prompt' => $prompt,
    'options' => [
        'services' => $services,
        'times' => $timeSlots,
        'min_date' => date('Y-m-d'),
    ],
    'data' => $data,
    'summary' => chatbot_summary($data),
]);
