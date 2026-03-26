<?php
include 'includes/bootstrap.php';

// Zorg dat gebruiker ingelogd is
require_login();

// Als formulier is verzonden
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Ongeldige aanvraag";
        if (function_exists('log_error_with_user')) log_error_with_user('CSRF token mismatch on transfer attempt', 'WARN');
    } else {
        $ontvangerInput = trim($_POST['ontvanger'] ?? '');
        $bedrag = (float)($_POST['bedrag'] ?? 0);
        $omschrijving = sanitize_description($_POST['omschrijving'] ?? '');

        // Eenvoudige validatie
        if (!valid_username($ontvangerInput)) {
            $error = "Ongeldige ontvanger";
            if (function_exists('log_error_with_user')) log_error_with_user('Invalid recipient format: ' . $ontvangerInput, 'WARN');
        } else {
            // Ververs het saldo van de afzender vroegtijdig om de toegestane maximum te berekenen
            $stmt = $pdo->prepare("SELECT balance FROM user WHERE id = ?");
            $stmt->execute([$_SESSION['user']['id']]);
            $currentBalance = (float)$stmt->fetchColumn();
            $maxAllowed = min(MAX_TRANSFER, $currentBalance);

            // Controleer bedrag grenzen
            if ($bedrag < MIN_TRANSFER) {
                $error = sprintf("Minimaal over te maken bedrag is €%0.2f.", MIN_TRANSFER);
                if (function_exists('log_error_with_user')) log_error_with_user('Transfer amount below minimum: ' . $bedrag . ' by uid:' . $_SESSION['user']['id'], 'WARN');
            } elseif ($bedrag > $maxAllowed) {
                $error = sprintf("Maximaal over te maken bedrag is €%0.2f.", $maxAllowed);
                if (function_exists('log_error_with_user')) log_error_with_user('Transfer amount above maxAllowed: ' . $bedrag . ' by uid:' . $_SESSION['user']['id'], 'WARN');
            } else {
                // Zoek ontvanger (zonder lock)
                $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
                $stmt->execute([$ontvangerInput]);
                $ontv = $stmt->fetch();

                if (!$ontv) {
                    $error = "Deze gebruiker bestaat niet";
                    if (function_exists('log_error_with_user')) log_error_with_user('Transfer failed: receiver not found (' . $ontvangerInput . ') by uid:' . $_SESSION['user']['id'], 'WARN');
                } else {
                    $senderId = (int)$_SESSION['user']['id'];
                    $receiverId = (int)$ontv['id'];

                    // Begin transaction en lock betrokken rijen in consistente volgorde
                    try {
                        $pdo->beginTransaction();

                        // Lock beide gebruikers (ascending ids) om deadlocks te vermijden
                        if ($senderId < $receiverId) {
                            $lockStmt = $pdo->prepare("SELECT id, balance FROM user WHERE id IN (?, ?) FOR UPDATE");
                            $lockStmt->execute([$senderId, $receiverId]);
                        } else {
                            $lockStmt = $pdo->prepare("SELECT id, balance FROM user WHERE id IN (?, ?) FOR UPDATE");
                            $lockStmt->execute([$receiverId, $senderId]);
                        }

                        $rows = $lockStmt->fetchAll();
                        $balances = [];
                        foreach ($rows as $r) {
                            $balances[(int)$r['id']] = (float)$r['balance'];
                        }

                        $senderBalance = $balances[$senderId] ?? null;
                        if ($senderBalance === null) {
                            throw new Exception('Afzender niet gevonden');
                        }
                        if ($senderBalance < $bedrag) {
                            throw new Exception('Onvoldoende saldo');
                        }

                        // Insert transactie en update saldi atomair
                        $ins = $pdo->prepare("INSERT INTO transaction (sender, receiver, amount, description) VALUES (?, ?, ?, ?)");
                        $ins->execute([$senderId, $receiverId, $bedrag, $omschrijving]);

                        $updSender = $pdo->prepare("UPDATE user SET balance = balance - ? WHERE id = ?");
                        $updSender->execute([$bedrag, $senderId]);

                        $updReceiver = $pdo->prepare("UPDATE user SET balance = balance + ? WHERE id = ?");
                        $updReceiver->execute([$bedrag, $receiverId]);

                        $pdo->commit();

                        // Refresh session user data
                        $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
                        $stmt->execute([$senderId]);
                        $_SESSION['user'] = $stmt->fetch();

                        $success = "Het bedrag is succesvol overgemaakt";
                        log_event('INFO', "Overdracht: $senderId -> $receiverId €$bedrag (omschrijving: " . substr($omschrijving,0,100) . ")");
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        if ($e->getMessage() === 'Onvoldoende saldo') {
                            $error = "Je hebt niet genoeg saldo om dit bedrag over te maken";
                        } else {
                            $error = "Er is een fout opgetreden bij het verwerken van de transactie";
                        }
                        // Log both to legacy app.log and the user-aware log
                        log_event('ERROR', 'Overdracht mislukt: ' . $e->getMessage());
                        if (function_exists('log_error_with_user')) log_error_with_user('Transfer failed exception: ' . $e->getMessage() . ' sender:' . $senderId . ' receiver:' . $receiverId, 'ERROR');
                    }
                }
            }
        }
    }

}

// Haal het saldo van de ingelogde gebruiker op
$stmt = $pdo->prepare("SELECT balance FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$saldo = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Omanido</title>
    <!-- Voeg Tailwind CSS toe via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto p-4">
        <div class="flex flex-wrap -mx-2">
            <!-- Saldo Kaart -->
            <div class="w-full md:w-1/3 px-2 mb-4">
                <div class="bg-white p-6 rounded-lg shadow-md h-full flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-xl mb-2">Mijn Saldo</h3>
                        <p class="text-sm text-gray-600 mb-4">Actueel Beschikbaar Saldo</p>
                    </div>
                    <p class="text-4xl font-bold mb-4 <?php echo $saldo >= 0 ? 'text-green-500' : 'text-red-500'; ?> self-center">
                        €<?php echo number_format($saldo, 2, ',', '.'); ?>
                    </p>
                    <div class="text-center">
                        <a href="transacties.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Transactieoverzicht
                            </a>
                    </div>
                </div>
            </div>


            <!-- Overdrachtsformulier Kaart -->
            <div class="w-full md:w-2/3 px-2 mb-4">
                <div class="bg-white p-6 rounded-lg shadow-md h-full"> <!-- Verhoogde padding van p-4 naar p-6 -->
                    <h3 class="font-bold text-xl mb-4">Geld Overmaken</h3>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token']) ?>">
                        <div class="mb-4">
                            <label for="ontvanger" class="block text-sm font-medium text-gray-700">Ontvanger:</label>
                            <input type="text" id="ontvanger" name="ontvanger" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div class="mb-4">
                            <label for="bedrag" class="block text-sm font-medium text-gray-700">Bedrag(€):</label>
                            <input type="number" id="bedrag" name="bedrag" step="0.01" min="<?php echo MIN_TRANSFER ?>" max="<?php echo min(MAX_TRANSFER, $saldo) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div class="mb-4">
                            <label for="omschrijving" class="block text-sm font-medium text-gray-700">Omschrijving:</label>
                            <input type="text" id="omschrijving" name="omschrijving" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <input type="submit" value="Overmaken" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 focus:outline-none focus:shadow-outline">
                        <?php
                            if(isset($error)) {
                                echo '<p class="text-red-500 text-sm mt-2">' . $error . '</p>';
                            }
                            if(isset($success)) {
                                echo '<p class="text-green-500 text-sm mt-2">' . $success . '</p>';
                            }
                        ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
