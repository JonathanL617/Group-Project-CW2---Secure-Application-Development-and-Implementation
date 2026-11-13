<?php

require_once __DIR__ . '/../config/security_config.php';
require_once __DIR__ . '/audit_logger.php';

/**
 * Generate a private, consistent hash for an account identifier.
 */
function q5AccountKey(string $identifier): string
{
    if (trim($identifier) === '') {
        throw new InvalidArgumentException('Account identifier is required.');
    }

    return hash_hmac(
        'sha256',
        strtolower(trim($identifier)),
        Q5_SECURITY_HMAC_KEY
    );
}

/**
 * Validate the supplied IP address.
 */
function q5ValidateIp(string $ipAddress): string
{
    if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
        throw new InvalidArgumentException('Invalid IP address.');
    }

    return $ipAddress;
}

/**
 * Check whether an account/IP combination is currently blocked.
 */
function isLoginBlocked(
    mysqli $conn,
    string $identifier,
    string $ipAddress
): bool {
    $accountKey = q5AccountKey($identifier);
    $ipAddress = q5ValidateIp($ipAddress);

    $stmt = $conn->prepare(
        "SELECT blocked_until
         FROM login_security_blocks
         WHERE account_key = ?
           AND ip_address = ?
           AND blocked_until > NOW()
         LIMIT 1"
    );

    $stmt->bind_param('ss', $accountKey, $ipAddress);
    $stmt->execute();
    $stmt->store_result();

    $blocked = $stmt->num_rows > 0;

    $stmt->close();

    return $blocked;
}

/**
 * Record a failed login and apply a temporary block if necessary.
 */
function recordFailedLogin(
    mysqli $conn,
    string $identifier,
    string $ipAddress,
    ?string $userAgent = null
): array {
    $accountKey = q5AccountKey($identifier);
    $ipAddress = q5ValidateIp($ipAddress);

    if (isLoginBlocked($conn, $identifier, $ipAddress)) {
        return [
            'blocked' => true,
            'attempts' => Q5_MAX_FAILED_ATTEMPTS,
            'message' => 'Login temporarily restricted.'
        ];
    }

    // Store the failed attempt.
    $stmt = $conn->prepare(
        "INSERT INTO login_security_attempts
         (account_key, ip_address)
         VALUES (?, ?)"
    );

    $stmt->bind_param('ss', $accountKey, $ipAddress);
    $stmt->execute();
    $stmt->close();

    // Count failures within the configured time window.
    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM login_security_attempts
         WHERE account_key = ?
           AND ip_address = ?
           AND attempted_at >= DATE_SUB(
               NOW(),
               INTERVAL ? MINUTE
           )"
    );

    $window = (int) Q5_LOGIN_WINDOW_MINUTES;

    $stmt->bind_param('ssi', $accountKey, $ipAddress, $window);
    $stmt->execute();
    $stmt->bind_result($attempts);
    $stmt->fetch();
    $stmt->close();

    // Record the failed login in the general audit log.
    logActivity($conn, [
        'actor_user_id' => null,
        'actor_role' => 'Unknown',
        'action' => 'FAILED_LOGIN',
        'category' => 'Authentication',
        'target_type' => 'Account',
        'target_id' => $accountKey,
        'status' => 'FAILED',
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent
    ]);

    // Apply a temporary block once the threshold is reached.
    if ($attempts >= Q5_MAX_FAILED_ATTEMPTS) {
        $lockout = (int) Q5_LOCKOUT_MINUTES;

        $stmt = $conn->prepare(
            "INSERT INTO login_security_blocks
             (account_key, ip_address, blocked_until, reason)
             VALUES (
                 ?,
                 ?,
                 DATE_ADD(NOW(), INTERVAL {$lockout} MINUTE),
                 'Repeated failed login attempts'
             )
             ON DUPLICATE KEY UPDATE
                 blocked_until = IF(
                     blocked_until <= NOW(),
                     VALUES(blocked_until),
                     blocked_until
                 )"
        );

        $stmt->bind_param('ss', $accountKey, $ipAddress);
        $stmt->execute();
        $stmt->close();

        return [
            'blocked' => true,
            'attempts' => (int) $attempts,
            'message' => 'Too many failed attempts. Login temporarily restricted.'
        ];
    }

    return [
        'blocked' => false,
        'attempts' => (int) $attempts,
        'remaining' => Q5_MAX_FAILED_ATTEMPTS - (int) $attempts,
        'message' => 'Failed login recorded.'
    ];
}

/**
 * Record a successful login and clear failures for that account/IP pair.
 */
function recordSuccessfulLogin(
    mysqli $conn,
    string $identifier,
    string $ipAddress,
    string $userId,
    string $role,
    ?string $userAgent = null
): void {
    $accountKey = q5AccountKey($identifier);
    $ipAddress = q5ValidateIp($ipAddress);

    // Clear failed attempts for this account/IP combination.
    $stmt = $conn->prepare(
        "DELETE FROM login_security_attempts
         WHERE account_key = ? AND ip_address = ?"
    );

    $stmt->bind_param('ss', $accountKey, $ipAddress);
    $stmt->execute();
    $stmt->close();

    // Remove the corresponding temporary block.
    $stmt = $conn->prepare(
        "DELETE FROM login_security_blocks
         WHERE account_key = ? AND ip_address = ?"
    );

    $stmt->bind_param('ss', $accountKey, $ipAddress);
    $stmt->execute();
    $stmt->close();

    // Record successful authentication.
    logActivity($conn, [
        'actor_user_id' => $userId,
        'actor_role' => $role,
        'action' => 'USER_LOGIN',
        'category' => 'Authentication',
        'target_type' => 'User',
        'target_id' => $userId,
        'status' => 'SUCCESS',
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent
    ]);
}
