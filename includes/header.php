<?php
// Header: ga ervan uit dat de sessie al is gestart via includes/session.php
// Controleer of de gebruiker is ingelogd
if (isset($_SESSION['loggedin'])) {
    $username = esc($_SESSION['user']['username']);
    $userId = (int)$_SESSION['user']['id'];
}
?>

<div class="bg-white py-4 shadow-md">
    <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
        <a href="<?= isset($_SESSION['loggedin']) ? 'dashboard.php' : 'index.php' ?>">
            <img src="img/Omanido2.png" alt="Bank Logo" class="h-12">
        </a>
            <?php if (isset($_SESSION['loggedin'])): ?>
                <div class="text-right">
                    <p class="text-gray-500 text-sm">Welkom,  <a href="transacties.php" class="text-blue-600 hover:underline"><?= $username ?></a></p>
                    <form action="/logout.php" method="post" style="display:inline;margin-left:8px;">
                        <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token']) ?>">
                        <button type="submit" class="ml-3 bg-red-500 hover:bg-red-700 text-white font-semibold py-1 px-3 rounded text-sm">Uitloggen</button>
                    </form>
                </div>
            <?php endif; ?>
    </div>
</div>

    <?php
    // Breadcrumbs: automatische, eenvoudige breadcrumbs gebaseerd op het huidige script.
    // Pagina's kunnen extra context (zoals $user) definiëren vóór het includen van header.php.
    $currentScript = basename($_SERVER['PHP_SELF']);
    $homeLink = isset($_SESSION['loggedin']) ? 'dashboard.php' : 'index.php';
    $labels = [
        'index.php' => 'Home',
        'dashboard.php' => 'Dashboard',
        'transacties.php' => 'Transacties',
        'users.php' => 'Gebruikers',
        'register.php' => 'Registreren',
        'logout.php' => 'Uitloggen',
        'register.php' => 'Registreren',
        'login.php' => 'Inloggen'
    ];
    $currentLabel = $labels[$currentScript] ?? ucfirst(str_replace('.php','',$currentScript));

    // Don't show breadcrumbs on the home page
    if ($currentScript !== 'index.php') {
        $crumbs = [];
        $crumbs[] = ['label' => 'Home', 'href' => $homeLink];

        // Specifieke extra context: als we op transacties kijken voor een gebruiker, voeg die gebruiker toe
        if ($currentScript === 'transacties.php' && isset($user) && is_array($user)) {
            $crumbs[] = ['label' => $currentLabel, 'href' => 'transacties.php'];
            $crumbs[] = ['label' => esc($user['username']), 'href' => null];
        } else {
            $crumbs[] = ['label' => $currentLabel, 'href' => null];
        }

        // Render breadcrumbs
        echo '<nav class="bg-white border-t shadow-sm"><div class="max-w-7xl mx-auto px-4 py-2 text-sm text-gray-600">';
        foreach ($crumbs as $i => $c) {
            if ($i > 0) echo ' <span class="mx-1">/</span> ';
            if (!empty($c['href'])) {
                echo '<a class="text-blue-600 hover:underline" href="' . $c['href'] . '">' . $c['label'] . '</a>';
            } else {
                echo '<span>' . $c['label'] . '</span>';
            }
        }
        echo '</div></nav>';
    }
    ?>
