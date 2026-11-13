<?php

require_once __DIR__ . '/../includes/q5_db.php';

try {
    $conn = q5_get_db_connection();

    echo "Q5 database connection successful!<br>";

    $result = $conn->query("SELECT DATABASE() AS db_name");

    $row = $result->fetch_assoc();

    echo "Connected database: " . htmlspecialchars($row['db_name']);

    $conn->close();

} catch (Throwable $e) {
    echo "Database connection test failed.";
    error_log($e->getMessage());
}
