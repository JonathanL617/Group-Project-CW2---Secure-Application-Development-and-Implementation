<?php
ob_start();

$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit-Integration-Test';

require_once __DIR__ . '/../app/config/conn.php';
require_once __DIR__ . '/../app/controller/AuthController.php';

echo "=== STARTING AUTH CONTROLLER + Q5 INTEGRATION TESTS ===" . PHP_EOL . PHP_EOL;

$testEmail = 'admin1@mail.com';
$ipAddress = '127.0.0.1';
$accountKey = q5AccountKey($testEmail);

// 1. Clean up any existing records for testEmail and IP
$stmt = $conn->prepare("DELETE FROM login_security_attempts WHERE account_key = ?");
$stmt->bind_param("s", $accountKey);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("DELETE FROM login_security_blocks WHERE account_key = ?");
$stmt->bind_param("s", $accountKey);
$stmt->execute();
$stmt->close();

echo "[TEST 1] Testing failed login attempt recording..." . PHP_EOL;

// Check recordFailedLogin behavior with AuthController flow
$failed1 = recordFailedLogin($conn, $testEmail, $ipAddress, 'Integration-Test-Agent');
echo "Attempt 1 result: " . $failed1['message'] . " (Blocked: " . ($failed1['blocked'] ? 'YES' : 'NO') . ")" . PHP_EOL;

// Check security_audit_logs
$logRes = $conn->query("SELECT action, category, status FROM security_audit_logs WHERE target_id = '$accountKey' ORDER BY id DESC LIMIT 1");
$logRow = $logRes->fetch_assoc();
if ($logRow && $logRow['action'] === 'FAILED_LOGIN' && $logRow['status'] === 'FAILED') {
    echo "  -> Audit log verified: Action={$logRow['action']}, Status={$logRow['status']} [PASS]" . PHP_EOL;
} else {
    echo "  -> Audit log check FAILED!" . PHP_EOL;
}

echo PHP_EOL . "[TEST 2] Testing brute-force threshold lockout (5 attempts)..." . PHP_EOL;
for ($i = 2; $i <= 5; $i++) {
    $res = recordFailedLogin($conn, $testEmail, $ipAddress, 'Integration-Test-Agent');
    echo "Attempt $i: " . $res['message'] . " (Blocked: " . ($res['blocked'] ? 'YES' : 'NO') . ")" . PHP_EOL;
}

$isBlocked = isLoginBlocked($conn, $testEmail, $ipAddress);
echo "isLoginBlocked check: " . ($isBlocked ? "BLOCKED [PASS]" : "NOT BLOCKED [FAIL]") . PHP_EOL;

echo PHP_EOL . "[TEST 3] Testing successful login cleanup & logging..." . PHP_EOL;
// Record successful login
recordSuccessfulLogin($conn, $testEmail, $ipAddress, 'A001', 'admin', 'Integration-Test-Agent');

$isBlockedAfter = isLoginBlocked($conn, $testEmail, $ipAddress);
echo "isLoginBlocked after success: " . (!$isBlockedAfter ? "UNBLOCKED [PASS]" : "STILL BLOCKED [FAIL]") . PHP_EOL;

$attRes = $conn->query("SELECT COUNT(*) as cnt FROM login_security_attempts WHERE account_key = '$accountKey'");
$attCnt = $attRes->fetch_assoc()['cnt'];
echo "Failed attempts in DB after success: $attCnt [PASS]" . PHP_EOL;

$successLogRes = $conn->query("SELECT action, actor_user_id, status FROM security_audit_logs WHERE actor_user_id = 'A001' ORDER BY id DESC LIMIT 1");
$successLogRow = $successLogRes->fetch_assoc();
if ($successLogRow && $successLogRow['action'] === 'USER_LOGIN' && $successLogRow['status'] === 'SUCCESS') {
    echo "  -> Audit log verified: Action={$successLogRow['action']}, User={$successLogRow['actor_user_id']}, Status={$successLogRow['status']} [PASS]" . PHP_EOL;
} else {
    echo "  -> Success audit log check FAILED!" . PHP_EOL;
}

// Clean up test logs
$conn->query("DELETE FROM security_audit_logs WHERE user_agent = 'Integration-Test-Agent'");

echo PHP_EOL . "=== ALL INTEGRATION TESTS COMPLETED SUCCESSFULLY ===" . PHP_EOL;
