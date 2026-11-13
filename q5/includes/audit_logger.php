<?php

/**
 * Q5 Audit Logger
 *
 * Records important user and system activities.
 * Do not include passwords, tokens, session IDs,
 * or sensitive patient/clinical information.
 */

function logActivity(mysqli $conn, array $event): int
{
    // Validate required fields
    $requiredFields = [
        'actor_role',
        'action',
        'category',
        'status'
    ];

    foreach ($requiredFields as $field) {
        if (
            !isset($event[$field]) ||
            trim((string)$event[$field]) === ''
        ) {
            throw new InvalidArgumentException(
                "Missing required audit field: $field"
            );
        }
    }

    // Prepare event values and enforce database field lengths
    $actorUserId = $event['actor_user_id'] ?? null;
    $actorRole   = substr(trim($event['actor_role']), 0, 50);
    $action      = substr(trim($event['action']), 0, 100);
    $category    = substr(trim($event['category']), 0, 50);
    $targetType  = isset($event['target_type'])
        ? substr(trim($event['target_type']), 0, 50)
        : null;
    $targetId    = isset($event['target_id'])
        ? substr(trim((string)$event['target_id']), 0, 100)
        : null;
    $status      = strtoupper(trim($event['status']));

    $ipAddress = $event['ip_address']
        ?? ($_SERVER['REMOTE_ADDR'] ?? null);

    $userAgent = $event['user_agent']
        ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);

    if ($actorUserId !== null) {
        $actorUserId = substr((string)$actorUserId, 0, 64);
    }

    if ($ipAddress !== null) {
        $ipAddress = substr((string)$ipAddress, 0, 45);
    }

    if ($userAgent !== null) {
        $userAgent = substr((string)$userAgent, 0, 512);
    }

    // Insert audit event using a prepared statement
    $sql = "INSERT INTO security_audit_logs
            (
                actor_user_id,
                actor_role,
                action,
                category,
                target_type,
                target_id,
                status,
                ip_address,
                user_agent
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssssssss",
        $actorUserId,
        $actorRole,
        $action,
        $category,
        $targetType,
        $targetId,
        $status,
        $ipAddress,
        $userAgent
    );

    $stmt->execute();

    $logId = $conn->insert_id;

    $stmt->close();

    return $logId;
}
