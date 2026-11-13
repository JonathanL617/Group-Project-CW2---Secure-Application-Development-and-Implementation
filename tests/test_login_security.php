<?php

require_once __DIR__ . '/../app/config/conn.php';
require_once __DIR__ . '/../app/controller/login_security.php';

try {

    // Fictional test account and documentation-only IP address.
    $identifier = 'q5-test-account@example.invalid';
    $ipAddress = '198.51.100.25';
    $otherIp = '198.51.100.26';

    $accountKey = q5AccountKey($identifier);

    // Clean up any previous test data.
    $stmt = $conn->prepare(
        "DELETE FROM login_security_attempts
         WHERE account_key = ? AND ip_address = ?"
    );
    $stmt->bind_param('ss', $accountKey, $ipAddress);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        "DELETE FROM login_security_blocks
         WHERE account_key = ? AND ip_address = ?"
    );
    $stmt->bind_param('ss', $accountKey, $ipAddress);
    $stmt->execute();
    $stmt->close();

    echo "Starting login security tests...<br><br>";

    // Simulate five failed login attempts.
    for ($i = 1; $i <= Q5_MAX_FAILED_ATTEMPTS; $i++) {
        $result = recordFailedLogin(
            $conn,
            $identifier,
            $ipAddress,
            'Q5 Security Test'
        );

        echo "Attempt $i: "
            . htmlspecialchars($result['message'])
            . "<br>";
    }

    echo "<br>";

    // Verify the account/IP combination is blocked.
    $blocked = isLoginBlocked(
        $conn,
        $identifier,
        $ipAddress
    );

    echo "Same account and IP blocked: "
        . ($blocked ? 'YES' : 'NO')
        . "<br>";

    // Verify a different IP is not automatically blocked.
    $otherIpBlocked = isLoginBlocked(
        $conn,
        $identifier,
        $otherIp
    );

    echo "Same account, different IP blocked: "
        . ($otherIpBlocked ? 'YES' : 'NO')
        . "<br>";

    if ($blocked && !$otherIpBlocked) {
        echo "<br>LOGIN SECURITY TEST PASSED!";
    } else {
        echo "<br>LOGIN SECURITY TEST FAILED.";
    }

    // Remove test tracking data after verification.
    $stmt = $conn->prepare(
        "DELETE FROM login_security_attempts
         WHERE account_key = ? AND ip_address = ?"
    );
    $stmt->bind_param('ss', $accountKey, $ipAddress);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        "DELETE FROM login_security_blocks
         WHERE account_key = ? AND ip_address = ?"
    );
    $stmt->bind_param('ss', $accountKey, $ipAddress);
    $stmt->execute();
    $stmt->close();

} catch (Throwable $e) {
    echo "<br>Test failed. Check the PHP error log.";
    error_log('Q5 Login Security Test: ' . $e->getMessage());

} finally {
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
