-- Q5: Login monitoring and brute-force detection

CREATE TABLE IF NOT EXISTS login_security_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_key CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_account_ip_time (
        account_key,
        ip_address,
        attempted_at
    ),
    INDEX idx_ip_time (
        ip_address,
        attempted_at
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS login_security_blocks (
    account_key CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    blocked_until DATETIME NOT NULL,
    reason VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (account_key, ip_address),
    INDEX idx_blocked_until (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
