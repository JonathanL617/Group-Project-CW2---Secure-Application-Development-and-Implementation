
<?php

/**
 * Determine the dashboard access level for a user role.
 *
 * Returns:
 * full    = Full dashboard access
 * limited = Limited dashboard access
 * null    = No dashboard access
 */
function q5_get_dashboard_access_level(string $role): ?string
{
    $role = strtolower(trim($role));

    switch ($role) {
        case 'superadmin':
            return 'full';

        case 'admin':
            return 'limited';

        case 'doctor':
        case 'pharmacist':
        default:
            return null;
    }
}

/**
 * Require an authenticated user with dashboard privileges.
 */
function q5_require_dashboard_access(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check whether the user is logged in
    if (
        empty($_SESSION['user_id']) ||
        empty($_SESSION['user_role'])
    ) {
        header('Location: ../../app/view/login_page.php');
        exit;
    }

    $accessLevel = q5_get_dashboard_access_level(
        $_SESSION['user_role']
    );

    // Reject unauthorised roles
    if ($accessLevel === null) {
        http_response_code(403);
        exit('403 Forbidden: You do not have permission to access this dashboard.');
    }

    return $accessLevel;
}
