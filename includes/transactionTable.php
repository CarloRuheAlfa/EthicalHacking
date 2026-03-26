<?php
// Controleer of de 'transaction' tabel al bestaat
$checkTable = $pdo->query("SHOW TABLES LIKE 'transaction'");
if ($checkTable->rowCount() == 0) {
    // Maak de 'transaction' tabel als deze nog niet bestaat
    $pdo->exec("CREATE TABLE `transaction` (
        `id` int NOT NULL AUTO_INCREMENT,
        `sender` int NOT NULL,
        `receiver` int NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `description` varchar(500) NOT NULL,
        PRIMARY KEY (`id`),
        CHECK (amount >= 0.01)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");

    // Voeg de gegevens toe
    $pdo->exec("
            INSERT INTO `transaction` (`id`, `sender`, `receiver`, `amount`, `description`) VALUES
            (1, 3, 2, 65.00, 'Auto'),
            (2, 5, 2, 94.00, 'Betaling winkel'),
            (3, 6, 2, 38.84, 'Avondje stappen'),
            (4, 5, 6, 50.00, 'Boodschappen buurtsuper'),
            (5, 6, 5, 50.00, 'Vakantie'),
            (6, 2, 5, 30.00, 'Zakgeld'),
            (7, 5, 6, 47.68, 'Boodschappen'),
            (8, 2, 6, 100.00, 'Zakgeld'),
            (9, 6, 2, 20.00, 'Terugbetaling'),
            (10, 3, 5, 15.00, 'Boodschappen'),
            (11, 5, 3, 15.00, 'Terugbetaling'),
            (12, 4, 5, 200.00, 'Salaris'),
            (13, 5, 4, 50.00, 'Huur'),
            (14, 4, 5, 50.00, 'Terugbetaling huur'),
            (15, 7, 5, 120.00, 'Freelance werk'),
            (16, 5, 7, 120.00, 'Betaling freelance werk'),
            (17, 8, 5, 80.00, 'Geboorte cadeau'),
            (18, 5, 8, 80.00, 'Bedankt voor het cadeau!'),
            (19, 9, 5, 60.00, 'Uitnodiging verjaardag'),
            (20, 5, 9, 60.00, 'Bedankt voor de uitnodiging!'),
            (21, 10, 5, 25.00, 'Lunch'),
            (22, 5, 10, 25.00, 'Bedankt voor de lunch!'),
            (23, 11, 5, 40.00, 'Concertkaartjes'),
            (24, 5, 11, 40.00, 'Bedankt voor de kaartjes!'),
            (25, 12, 5, 300.00, 'Bonus'),
            (26, 5, 12, 100.00, 'Huur'),
            (27, 12, 5, 100.00, 'Terugbetaling huur'),
            (28, 13, 5, 150.00, 'Salaris'),
            (29, 5, 13, 50.00, 'Huur'),
            (30, 13, 5, 50.00, 'Terugbetaling huur'),
            (31, 14, 5, 75.00, 'Freelance werk'),
            (32, 5, 14, 75.00, 'Betaling freelance werk'),
            (33, 15, 5, 90.00, 'Geboorte cadeau'),
            (34, 5, 15, 90.00, 'Bedankt voor het cadeau!'),
            (35, 16, 5, 55.00, 'Uitnodiging verjaardag'),
            (36, 5, 16, 55.00,'Bedankt voor de uitnodiging!')

    ");
}
?>
