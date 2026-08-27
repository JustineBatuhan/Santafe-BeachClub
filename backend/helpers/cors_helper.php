<?php
/**
 * cors_helper.php — Secure Cross-Origin Resource Sharing (CORS) Configuration
 * Restricts allowed origins, HTTP methods, and headers.
 */

function handle_cors(array $allowedOrigins = []): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Default allowed origins (same host + standard local hosts for development)
    $defaultOrigins = [
        'http://localhost',
        'http://127.0.0.1',
        'http://localhost:3000',
        'http://localhost:8080'
    ];
    
    $allowed = array_merge($defaultOrigins, $allowedOrigins);
    
    // If request comes from an allowed origin or origin is same host
    if (!empty($origin)) {
        $parsedOrigin = parse_url($origin, PHP_URL_HOST);
        $serverHost = parse_url($_SERVER['HTTP_HOST'] ?? '', PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? '');
        
        if (in_array($origin, $allowed, true) || $parsedOrigin === $serverHost) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
        }
    }

    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, Accept");
    header("Access-Control-Max-Age: 86400"); // Cache preflight for 24 hours

    // Intercept OPTIONS pre-flight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
?>
