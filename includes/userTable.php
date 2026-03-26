<?php
// Controleer of de 'user' tabel al bestaat
$checkTable = $pdo->query("SHOW TABLES LIKE 'user'");
if ($checkTable->rowCount() == 0) {
    // Maak de 'user' tabel als deze nog niet bestaat
   $pdo->exec("CREATE TABLE `user` (
        `id` int NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `balance` decimal(10,2) NOT NULL,
        `isAdmin` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");

    // Voeg de standaardgebruikers toe met vooraf berekende hashes
    $seedUsersSql = <<<'SQL'
INSERT INTO `user` (`id`, `username`, `password`, `balance`, `isAdmin`) VALUES
    (1, 'KeesDeAdmin', '$2y$10$ENBhO2mgTA4lMfjVrbZp5u7MMYBjG6Ww8FCc8F5B1dTjn5K09HZrC', 1000.00, 1),
    (2, 'FerryKuhlman', '$2y$10$sp75dTcsmbkwMq6o3qlp0O0YSp3o7WwbDx.psWdqxwBa7XUYi0ovS', 1255.36, 0),
    (3, 'Han2002', '$2y$10$arVd25KxPcEvCW8e3UgodO4h6TMnzqErf/sCfzpyKXbYExfTYCG3S', 23424.84, 0),
    (4, 'RoyBos', '$2y$10$xVLL072NOb00ThKbJCspzu15fZlT/x9aV6a7Zw03yblM5CuWiYal.', 9.23, 0),
    (5, 'SophieJansen', '$2y$10$0uN0ANz5HdbGXfsYmeeyKOeL3NzwAeKxaUnrnER14o48rvTd4ROuu', 500.00, 0),
    (6, 'LarsDeVries', '$2y$10$JrMgUqcZhNWxc9b0k2ud7u7rUYgnxzOpecFqxk7ZLU.9FXgnVLybG', 150.75, 0),
    (7, 'EmmaVisser', '$2y$10$E1HVUlC7fChlLJbm0te9GOm8pgyUHXfCVtXS6L1ad0jMNuXQmYIHa', 300.50, 0),
    (8, 'NoahSmith', '$2y$10$mkfb7ncPPMD91FbS2buUcumC4ofz9/5bAOjtQDXbVt6usb9NtoC7y', 75.20, 0),
    (9, 'IsabellaJansen', '$2y$10$4yaWPWQhSToYloA5oXII/u3DEsXAox6G4CswSi9gAykxihn/xuQSq', 600.00, 0)
SQL;
    $pdo->exec($seedUsersSql);
}