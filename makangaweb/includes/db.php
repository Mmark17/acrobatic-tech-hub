<?php
// Environment variables override the local XAMPP defaults.
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS');
$DB_PASS = $DB_PASS === false ? '' : $DB_PASS;
$DB_NAME = getenv('DB_NAME') ?: 'makangaweb_db';
$DB_PORT = (int)(getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    http_response_code(500);
    error_log('Database connection failed: ' . $ex->getMessage());
    die('Database connection failed. Check the configured DB_HOST, DB_PORT, DB_NAME, DB_USER, and DB_PASS values.');
}
