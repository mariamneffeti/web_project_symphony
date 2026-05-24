<?php
try {
    $pdo = new PDO(
        "mysql:host=mysql-2cbe4241-neffetimeriem-7468.h.aivencloud.com;port=17708;dbname=web_project;charset=utf8mb4", 
        "avnadmin", 
        "AVNS_4bdB1072NUSpok6AKLT"
    );
    echo "🟢 SUCCESS: Connection established successfully!";
} catch (PDOException $e) {
    echo "🔴 ERROR: " . $e->getMessage();
}