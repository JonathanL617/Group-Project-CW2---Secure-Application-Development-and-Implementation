<?php

/**
 * Local Q5 testing database configuration.
 * Separate from the group's InfinityFree database.
 */

function q5_get_db_connection(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli(
            '127.0.0.1',
            'root',
            '',
            'pharmacy_q5_test',
            3306
        );

        $conn->set_charset('utf8mb4');

        return $conn;

    } catch (mysqli_sql_exception $e) {
        error_log('Q5 Database Error: ' . $e->getMessage());

        throw new RuntimeException(
            'Unable to connect to the local Q5 database.'
        );
    }
}
