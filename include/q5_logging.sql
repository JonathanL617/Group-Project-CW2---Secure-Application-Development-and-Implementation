CREATE TABLE IF NOT EXISTS security_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    actor_user_id VARCHAR(64) DEFAULT NULL,
    actor_role VARCHAR(50) NOT NULL,

    action VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,

    target_type VARCHAR(50) DEFAULT NULL,
    target_id VARCHAR(100) DEFAULT NULL,

    status VARCHAR(20) NOT NULL,

    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(512) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_actor (actor_user_id),
    INDEX idx_action (action),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
