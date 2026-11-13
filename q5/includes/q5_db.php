<?php

/**
 * Local Q5 testing database configuration.
 */
if (file_exists(__DIR__ . '/../../app/config/config.php')) {
    require_once __DIR__ . '/../../app/config/config.php';
}

function q5_get_db_connection(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $dbHost = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
    $dbUser = defined('DB_USER') ? DB_USER : 'root';
    $dbPass = defined('DB_PASS') ? DB_PASS : '';
    $dbName = defined('DB_NAME') ? DB_NAME : 'security';

    try {
        $conn = new mysqli(
            $dbHost,
            $dbUser,
            $dbPass,
            $dbName,
            3306
        );

        $conn->set_charset('utf8mb4');

        return $conn;

    } catch (mysqli_sql_exception $e) {
        error_log('Q5 Database Error: ' . $e->getMessage());

        throw new RuntimeException(
            'Unable to connect to the local Q5 database: ' . $e->getMessage()
        );
    }
}
