<?php 
include 'includes/bootstrap.php';

// Tabellen aanmaken
include 'includes/userTable.php';
include 'includes/transactionTable.php';

// Controleer of POST is verzonden
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basis CSRF-controle
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Ongeldige aanvraag";
        if (function_exists('log_error_with_user')) log_error_with_user('CSRF token mismatch on login attempt', 'WARN');
    } else {
    // Lees gebruikersnaam en wachtwoord uit POST
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

    // Prepared statement om SQL-injectie te voorkomen
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Gebruiker is ingelogd
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['user'] = $user;

            header("location: dashboard.php");
            exit;
        } else {
            $error = "Gebruikersnaam of wachtwoord is onjuist";
            if (function_exists('log_error_with_user')) log_error_with_user('Failed login for username: ' . substr($username,0,200), 'WARN');
        }
    }

}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omanido</title>
    <!-- Voeg Tailwind CSS toe via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto mt-20 p-6 bg-white max-w-sm shadow-md rounded-md">
        <div class="flex justify-center">
            <img src="img/Omanido1.png" alt="Omanido Logo" class="mb-6 w-1/2"> <!-- Aanpassen van de breedte naar 1/2 van de container -->
        </div>
        <h2 class="text-lg text-center font-bold mb-6">Inloggen bij Omanido</h2>
    <form action="<?php echo esc($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700">Gebruikersnaam:</label>
                <input type="text" id="username" name="username" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Wachtwoord:</label>
                <input type="password" id="password" name="password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <input type="submit" value="Inloggen" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 focus:outline-none focus:shadow-outline">
            <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token']) ?>">
        </form>
        <a href="register.php" class="block text-center text-sm text-blue-600 hover:underline mt-4">Nog geen account? Registreer hier</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="mt-4 p-2 border border-red-300 rounded text-red-600"><?= esc($error) ?></div>
    <?php endif; ?>

    
</body>
</html>
