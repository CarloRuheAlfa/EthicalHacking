<?php
require_once '../includes/config.php';
require_once '../includes/db.php';


// haal gebruikers op op basis van zoekterm in post-parameter 'searchterm'
$searchTerm = $_POST['searchterm'] ?? '';
$stmt = $pdo->query("SELECT id, username FROM user WHERE username LIKE '%$searchTerm%'");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// toon resultaten
if (count($results) > 0) {
    echo "<h2>Zoekresultaten voor '" . htmlspecialchars($searchTerm) . "':</h2><ul>";
    foreach ($results as $row) {
        echo "<li>" . htmlspecialchars($row['username']) . " (ID: " . (int)$row['id'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Geen gebruikers gevonden voor '" . htmlspecialchars($searchTerm) . "'.</p>";
}

?>

<form method="POST" action="search.php">
    <input type="text" name="searchterm" placeholder="Zoek gebruikers..." value="<?= htmlspecialchars($searchTerm) ?>">
    <button type="submit">Zoeken</button>
</form>