<?php
include 'includes/bootstrap.php';

require_login();

// Bepaal wat we laten zien:
// - Als admin en ?user=ID is meegegeven: laat transacties van die gebruiker zien
// - Als admin en geen user param: laat alle transacties zien
// - Als normale gebruiker: laat alleen eigen transacties zien

$rawUserParam = isset($_GET['user']) ? $_GET['user'] : null;
$userParam = null;
if ($rawUserParam) {
    // Probeer token terug te zetten naar intern id
    $resolved = token_to_id($rawUserParam);
    if ($resolved === null) {
        http_response_code(404);
        echo '<p class="text-center text-red-500 font-bold">Ongeldige gebruiker.</p>';
        exit;
    }
    $userParam = $resolved;
}
$isAdmin = !empty($_SESSION['user']['isAdmin']) && $_SESSION['user']['isAdmin'] == 1;

if ($isAdmin) {
    if ($userParam) {
        // admin bekijkt specifieke gebruiker
        $viewUserId = $userParam;
        $transactions = get_transactions_for_user($pdo, $viewUserId, 500);
        $viewMode = 'user';
    } else {
        // admin bekijkt alle transacties
        $transactions = get_all_transactions($pdo, 1000);
        $viewMode = 'all';
    }
} else {
    // normale gebruiker ziet alleen eigen transacties
    $viewUserId = (int)$_SESSION['user']['id'];
    $transactions = get_transactions_for_user($pdo, $viewUserId, 500);
    $viewMode = 'self';
}

// Als we niet in 'alle' view zitten, laad dan de gebruiker die weergegeven moet worden
if ($viewMode !== 'all') {
    $displayUserId = $viewUserId ?? (int)$_SESSION['user']['id'];
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$displayUserId]);
    $user = $stmt->fetch();
} else {
    $user = null;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php if ($user) { echo esc($user['username']) . ' | Omanido'; } else { echo 'Transacties | Omanido'; } ?></title>
    <!-- Voeg Tailwind CSS toe via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.15/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<?php include 'includes/header.php'; ?>

<div class="container mx-auto mt-20 p-6 bg-white shadow-md rounded-md">
    <div class="grid grid-cols-3 gap-4">
        <div class="col-span-1">
            <div class="flex justify-center">
                <img src="img/Omanido1.png" alt="Omanido Logo" class="mb-6 w-1/2">
            </div>
            <?php if ($user): ?>
                <h2 class="text-lg text-center font-bold mb-6"><?= esc($user['username']) ?></h2>
                <p class="text-center mb-6">Saldo: €<?= number_format($user['balance'], 2, ',', '.') ?></p>
                <div class="flex justify-center">
                    <a href="dashboard.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Geld overmaken</a>
                </div>
                <div class="flex justify-center mt-6">
                    <a href="logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Uitloggen</a>
                </div>
            <?php else: ?>
                <h2 class="text-lg text-center font-bold mb-6">Transacties</h2>
                <p class="text-center mb-6">Administrator overzicht van transacties.</p>
                <div class="flex justify-center">
                    <a href="users.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Gebruikers beheren</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-span-1">
            <div class="col-span-3">
                <?php if (!empty($transactions)): ?>
                    <h2 class="text-lg text-center font-bold mb-6"><?php
                        if ($viewMode === 'all') echo 'Alle transacties';
                        elseif ($viewMode === 'user') echo 'Transacties van gebruiker ID ' . ($viewUserId ?? '');
                        else echo 'Mijn transacties';
                    ?></h2>
                    <div class="bg-white p-2 rounded">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left">
                                    <th class="p-2">ID</th>
                                    <th class="p-2">Richting</th>
                                    <th class="p-2">Omschrijving</th>
                                    <th class="p-2">Van</th>
                                    <th class="p-2">Aan</th>
                                    <th class="p-2">Bedrag</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $tx): ?>
                                    <?php
                                        // Bepaal de richting ten opzichte van de getoonde gebruiker (alleen relevant in user/self view)
                                        $direction = '-';
                                        if ($viewMode !== 'all') {
                                            $currentId = (int)($displayUserId ?? (int)$_SESSION['user']['id']);
                                            if ((int)$tx['sender'] === $currentId) {
                                                $direction = 'Uitgaand';
                                            } else {
                                                $direction = 'Inkomend';
                                            }
                                        }

                                        // Nette weergave van afzender/ontvanger: toon gebruikersnaam als aanwezig,
                                        // anders duidelijk 'Onbekend (ID #)'.
                                        $senderDisplay = !empty($tx['sender_name']) ? esc($tx['sender_name']) : 'Onbekend (ID ' . (int)$tx['sender'] . ')';
                                        $receiverDisplay = !empty($tx['receiver_name']) ? esc($tx['receiver_name']) : 'Onbekend (ID ' . (int)$tx['receiver'] . ')';
                                    ?>
                                    <tr class="border-t">
                                        <td class="p-2"><?= (int)$tx['id'] ?></td>
                                        <td class="p-2"><?= $direction ?></td>
                                        <td class="p-2"><?= esc($tx['description']) ?></td>
                                        <td class="p-2"><?= $senderDisplay ?></td>
                                        <td class="p-2"><?= $receiverDisplay ?></td>
                                        <td class="p-2">€<?= number_format($tx['amount'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-red-500 font-bold">Er zijn geen transacties om te tonen.</p>
                <?php endif; ?>
            </div>
    </div>
</div>


</body>
</html>
