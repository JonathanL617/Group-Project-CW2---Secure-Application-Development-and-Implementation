
<?php
require_once __DIR__ . '/../config/conn.php';

// Hash password before storing it
function hashPassword(string $password): string {

    if (strlen($password) < 8) {
        throw new InvalidArgumentException(
            "Password must be at least 8 characters long."
        );
    }

    return password_hash($password, PASSWORD_ARGON2ID);
}

// Verify password during login
function isPasswordCorrect(
    string $password,
    string $hashedPassword
): bool {

    return password_verify($password, $hashedPassword);
}
?>
