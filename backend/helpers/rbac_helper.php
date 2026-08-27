<?php
/**
 * rbac_helper.php — Role-Based Access Control & Authorization Helper
 * Handles user role validation and route authorization guards.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class RBAC {
    /**
     * Role hierarchy and capabilities
     */
    private static array $rolePermissions = [
        'admin' => [
            'view_dashboard',
            'view_reservations',
            'create_reservation',
            'edit_reservation',
            'cancel_reservation',
            'checkin_guest',
            'checkout_guest',
            'view_reports',
            'manage_rooms',
            'manage_gallery',
            'manage_staff',
            'manage_settings',
            'view_logs',
            'clear_logs',
            'view_security_logs',
        ],
        'receptionist' => [
            'view_dashboard',
            'view_reservations',
            'create_reservation',
            'edit_reservation',
            'cancel_reservation',
            'checkin_guest',
            'checkout_guest',
            'view_reports',
            'manage_rooms',
        ],
    ];

    /**
     * Get current user role
     */
    public static function getCurrentRole(): string {
        return $_SESSION['admin_role'] ?? 'guest';
    }

    /**
     * Check if current user has a specific role or one of allowed roles
     */
    public static function hasRole(string|array $roles): bool {
        $currentRole = self::getCurrentRole();
        if (is_array($roles)) {
            return in_array($currentRole, $roles, true);
        }
        return ($currentRole === $roles);
    }

    /**
     * Check if current user has permission
     */
    public static function can(string $permission): bool {
        $role = self::getCurrentRole();
        $permissions = self::$rolePermissions[$role] ?? [];
        return in_array($permission, $permissions, true);
    }

    /**
     * Require a specific role or exit with 403 Forbidden
     */
    public static function requireRole(string|array $roles): void {
        if (!self::hasRole($roles)) {
            http_response_code(403);
            
            $isJson = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                      (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

            if ($isJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Access Denied: You do not have permission to perform this action.'
                ]);
            } else {
                echo '<!DOCTYPE html><html><head><title>403 Forbidden</title><style>body{font-family:sans-serif;text-align:center;padding:60px;background:#f8f9fa;color:#333;}.box{max-width:500px;margin:0 auto;background:#fff;padding:32px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.08);}h1{color:#e53e3e;font-size:22px;}a{color:#007bff;text-decoration:none;font-weight:bold;}</style></head><body><div class="box"><h1>403 Forbidden</h1><p>You do not have permission to access this resource or action.</p><p><a href="javascript:history.back()">Go Back</a></p></div></body></html>';
            }
            exit;
        }
    }

    /**
     * Require permission or exit with 403
     */
    public static function requirePermission(string $permission): void {
        if (!self::can($permission)) {
            self::requireRole('admin'); // Trigger standard 403 response
        }
    }
}
?>
