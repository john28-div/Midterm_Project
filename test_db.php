<?php
require_once __DIR__ . '/config/db.php';
$result = $conn->query('SELECT COUNT(*) AS total FROM users');
$row = $result->fetch_assoc();
echo 'Connected! Users in database: ' . (int) $row['total'];
