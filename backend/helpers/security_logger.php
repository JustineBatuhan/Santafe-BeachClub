<?php
/**
 * security_logger.php — Security Audit Logging & Monitoring Helper
 * Records security-sensitive events, failed logins, and access violations.
 */

require_once __DIR__ . '/../config/db.php';

class SecurityLogger {
    const LEVEL_INFO     = 'INFO';
    const LEVEL_WARNING  = 'WARNING';
    const LEVEL_CRITICAL = 'CRITICAL';

    /**
     * Log a security event to the database and log file
     * @param mysqli $conn
     * @param string $eventType (e.g. 'FAILED_LOGIN', 'CSRF_MISMATCH', 'RATE_LIMIT_TRIGGER', 'UNAUTHORIZED_ACCESS', 'FILE_UPLOAD', 'PASSWORD_CHANGE')
     * @param string $description
     * @param string $level ('INFO', 'WARNING', 'CRITICAL')
     * @param string|null $username
     */
    public static function log(
        mysqli $conn,
        string $eventType,
        string $description,
        string $level = self::LEVEL_INFO,
        ?string $username = null
    ): void {
        $user = $username ?? ($_SESSION['admin_username'] ?? 'anonymous');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);
        $requestUri = substr($_SERVER['REQUEST_URI'] ?? '', 0, 255);

        // 1. Insert into database
        $stmt = $conn->prepare("
            INSERT INTO security_logs (event_type, event_level, username, ip_address, user_agent, request_uri, description, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        if ($stmt) {
            $stmt->bind_param("sssssss", $eventType, $level, $user, $ip, $userAgent, $requestUri, $description);
            $stmt->execute();
            $stmt->close();
        }

        // 2. Also append to secure log file for external monitoring tools / fail2ban
        $logFile = __DIR__ . '/../logs/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] [%s] [%s] User: %s | IP: %s | URI: %s | %s\n",
            $timestamp,
            $level,
            $eventType,
            $user,
            $ip,
            $requestUri,
            $description
        );
        @error_log($logEntry, 3, $logFile);
    }
}
?>
