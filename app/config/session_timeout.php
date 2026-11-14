
<?php

require_once __DIR__ . '/session_config.php';

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conn.php';

$timeoutDuration = 30 *60; // 30 minutes

// Only check authenticated users
if (isset($_SESSION['user_id'])) {

    $userId = $_SESSION['user_id'];
    $sessionId = (int) ($_SESSION['session_id'] ?? 0);
    $currentTime = time();

    // Check inactivity
    $lastActivity = (int) ($_SESSION['last_activity'] ?? $currentTime);

    if (($currentTime - $lastActivity) >= $timeoutDuration) {

        // Mark database session as expired
        if ($sessionId > 0) {
            $stmt = $conn->prepare("
                UPDATE sessions
                SET logout_time = NOW(),
                    last_activity = NOW(),
                    status = 'Expired'
                WHERE session_id = ?
                  AND user_id = ?
                  AND status = 'Active'
            ");

            $stmt->bind_param("is", $sessionId, $userId);
            $stmt->execute();
            $stmt->close();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => !empty($params['secure']),
                    'httponly' => true,
                    'samesite' => $params['samesite'] ?? 'Lax'
                ]
            );
        }

        session_destroy();

        // API requests receive JSON; normal pages receive a redirect
        if (!empty($isApiRequest)) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => false,
                'message' => 'Session expired. Please login again.'
            ]);
        } else {
            header(
                'Location: ' . BASE_URL .
                '/app/view/login_page.php?timeout=1'
            );
        }

        exit();
    }

    // Confirm the database session is still active
    $stmt = $conn->prepare("
        SELECT status
        FROM sessions
        WHERE session_id = ?
          AND user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("is", $sessionId, $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $sessionRecord = $result->fetch_assoc();

    $stmt->close();

    // Reject invalid or revoked sessions
    if (
        $sessionId <= 0 ||
        !$sessionRecord ||
        $sessionRecord['status'] !== 'Active'
    ) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => !empty($params['secure']),
                    'httponly' => true,
                    'samesite' => $params['samesite'] ?? 'Lax'
                ]
            );
        }

        session_destroy();

        if (!empty($isApiRequest)) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => false,
                'message' => 'Invalid session. Please login again.'
            ]);
        } else {
            header('Location: ' . BASE_URL . '/app/view/login_page.php');
        }

        exit();
    }

    // Update last activity
    $_SESSION['last_activity'] = $currentTime;

    $stmt = $conn->prepare("
        UPDATE sessions
        SET last_activity = NOW()
        WHERE session_id = ?
          AND user_id = ?
          AND status = 'Active'
    ");

    $stmt->bind_param("is", $sessionId, $userId);
    $stmt->execute();
    $stmt->close();
}
?>
