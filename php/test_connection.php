<?php

require __DIR__ . '/config.php';

try {
    $pdo = db();

    echo "SUCCESS: PHP connected to PostgreSQL!";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}