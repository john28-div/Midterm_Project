<?php
/**
 * Database connection for Lakat (XAMPP defaults).
 * Change $user / $pass if your MySQL credentials differ.
 */
$host = 'localhost';
$db   = 'lakat_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
