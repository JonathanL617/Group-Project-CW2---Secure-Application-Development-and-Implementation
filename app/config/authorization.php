
<?php

// Check whether the user is logged in and has permission
function requireRole(array $allowedRoles) {

    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);

        exit;
    }

    // Check user role
    $userRole = $_SESSION['user_role'] ?? '';

    if (!in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Access denied'
        ]);

        exit;
    }
}
