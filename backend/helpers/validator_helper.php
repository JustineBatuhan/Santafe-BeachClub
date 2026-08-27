<?php
/**
 * validator_helper.php — Server-Side Validation Helper
 * Provides strict, robust validation rules and sanitizers.
 */

class Validator {
    /**
     * Sanitize string input: trim, strip null bytes and unwanted control characters
     */
    public static function sanitize(mixed $data): string {
        if ($data === null) return '';
        if (is_array($data)) return '';
        $str = trim((string)$data);
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
    }

    /**
     * Validate email format
     */
    public static function validateEmail(string $email): bool {
        $email = trim($email);
        if (strlen($email) > 150) return false;
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate Philippine/International phone format
     * Supports: 09xxxxxxxxx, +639xxxxxxxxx, or international 7-15 digits
     */
    public static function validatePhone(string $phone): bool {
        $clean = preg_replace('/[\s\-\(\)]/', '', trim($phone));
        if (empty($clean)) return false;
        // PH mobile: 09171234567 or +639171234567, or generic +[country_code][number]
        return (bool) preg_match('/^(\+639\d{9}|09\d{9}|\+[1-9]\d{6,14}|\d{7,15})$/', $clean);
    }

    /**
     * Validate date format (Y-m-d)
     */
    public static function validateDate(string $date, string $format = 'Y-m-d'): bool {
        $d = DateTime::createFromFormat($format, trim($date));
        return $d && $d->format($format) === trim($date);
    }

    /**
     * Validate date range (checkin must be before checkout, and not in the past)
     */
    public static function validateDateRange(string $checkin, string $checkout, bool $allowPast = false): array {
        if (!self::validateDate($checkin) || !self::validateDate($checkout)) {
            return ['valid' => false, 'error' => 'Invalid date format (must be YYYY-MM-DD).'];
        }

        $checkinDt = new DateTime($checkin);
        $checkoutDt = new DateTime($checkout);
        $today = new DateTime(date('Y-m-d'));

        if (!$allowPast && $checkinDt < $today) {
            return ['valid' => false, 'error' => 'Check-in date cannot be in the past.'];
        }

        if ($checkoutDt <= $checkinDt) {
            return ['valid' => false, 'error' => 'Check-out date must be after check-in date.'];
        }

        return ['valid' => true, 'nights' => (int) $checkinDt->diff($checkoutDt)->days];
    }

    /**
     * Validate positive integer range (e.g. guest count, pagination)
     */
    public static function validateInt(mixed $val, int $min = 1, int $max = PHP_INT_MAX): bool {
        if (!is_numeric($val)) return false;
        $intVal = (int) $val;
        return ($intVal >= $min && $intVal <= $max);
    }

    /**
     * Validate price or monetary amount
     */
    public static function validatePrice(mixed $val, float $min = 0.0, float $max = 1000000.0): bool {
        if (!is_numeric($val)) return false;
        $fVal = (float) $val;
        return ($fVal >= $min && $fVal <= $max);
    }

    /**
     * Validate username (alphanumeric, underscores, hyphens, 3-30 chars)
     */
    public static function validateUsername(string $username): bool {
        return (bool) preg_match('/^[a-zA-Z0-9_\-]{3,30}$/', trim($username));
    }

    /**
     * Validate room type against whitelist
     */
    public static function validateRoomType(string $type): bool {
        $allowed = ['beachview_duplex', 'seaview_duplex', 'beach_villa', 'standard_room', 'standard_king'];
        return in_array(trim($type), $allowed, true);
    }
}
?>
