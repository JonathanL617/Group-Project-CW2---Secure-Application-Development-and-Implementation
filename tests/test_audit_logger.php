<?php

require_once __DIR__ . '/../app/config/conn.php';
require_once __DIR__ . '/../app/controller/audit_logger.php';

try {
    // Use the central database connection

    // Create a fictional test activity
    $event = [
        'actor_user_id' => 'TEST001',
        'actor_role' => 'Admin',
        'action' => 'USER_LOGIN',
        'category' => 'Authentication',
        'target_type' => 'User',
        'target_id' => 'TEST001',
        'status' => 'SUCCESS',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Q5 Audit Logger Test'
    ];

    // Record the activity
    $logId = logActivity($conn, $event);

    echo "Audit log inserted successfully!<br>";
    echo "Generated log ID: " . $logId . "<br>";

    // Verify that the record exists
    $stmt = $conn->prepare(
        "SELECT action, actor_role, status
         FROM security_audit_logs
         WHERE id = ?"
    );

    $stmt->bind_param("i", $logId);
    $stmt->execute();

    $result = $stmt->get_result();
    $record = $result->fetch_assoc();

    if ($record) {
        echo "<br>Verification successful!<br>";
        echo "Action: " . htmlspecialchars($record['action']) . "<br>";
        echo "Role: " . htmlspecialchars($record['actor_role']) . "<br>";
        echo "Status: " . htmlspecialchars($record['status']) . "<br>";
    } else {
        echo "Verification failed: Record not found.";
    }

    $stmt->close();
    $conn->close();

} catch (Throwable $e) {
    echo "Audit logger test failed.";
    error_log($e->getMessage());
}
