<?php
// General helper functions: auth, validation, logging

// Vereist: includes/session.php (session active) en $pdo beschikbaar

function require_login() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: index.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (empty($_SESSION['user']['isAdmin']) || $_SESSION['user']['isAdmin'] != 1) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="nl"><head><meta charset="utf-8"><title>403 Toegang geweigerd</title></head><body><div style="max-width:600px;margin:40px auto;font-family:Arial,Helvetica,sans-serif;"><h1>403 - Toegang geweigerd</h1><p>Alleen administrators hebben toegang tot deze pagina.</p><p><a href="dashboard.php">Terug naar dashboard</a></p></div></body></html>';
        exit;
    }
}

function valid_username($username) {
    $username = trim($username);
    if ($username === '') return false;
    if (strlen($username) > MAX_USERNAME_LENGTH) return false;
    // alleen letters, cijfers, underscore en punt
    return preg_match('/^[A-Za-z0-9_.-]{1,' . MAX_USERNAME_LENGTH . '}$/', $username);
}

function sanitize_description($desc) {
    $desc = trim($desc);
    if (strlen($desc) > MAX_DESCRIPTION_LENGTH) {
        $desc = mb_substr($desc, 0, MAX_DESCRIPTION_LENGTH);
    }
    return $desc;
}

function log_event($level, $message) {
    // eenvoudig file-logging via php error_log
    $ts = date('Y-m-d H:i:s');
    $entry = "[$ts] [$level] $message\n";
    error_log($entry, 3, __DIR__ . '/../logs/app.log');
}

// Specifieke foutlogging met gebruikercontext naar logs/log.txt
function log_error_with_user(string $message, string $level = 'ERROR') {
    $ts = date('Y-m-d H:i:s');
    $uid = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $req = $_SERVER['REQUEST_URI'] ?? 'cli';

    $post = [];
    if (!empty($_POST) && is_array($_POST)) {
        foreach ($_POST as $k => $v) {
                // bewaar alleen korte weergave om log-ruis te beperken
                $post[$k] = is_string($v) ? mb_substr($v, 0, 200) : '[non-string]';
        }
    }

    $entry = sprintf("[%s] [%s] [uid:%s] [ip:%s] [req:%s] %s | POST:%s\n", $ts, $level, $uid, $ip, $req, $message, json_encode($post, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    // Append with exclusive lock
    @file_put_contents(__DIR__ . '/../logs/log.txt', $entry, FILE_APPEND | LOCK_EX);
}

// Verkrijg gecombineerde transacties voor een gebruiker (incoming + outgoing)
function get_transactions_for_user(PDO $pdo, int $userId, int $limit = 100) : array {
    // Some PDO drivers (native prepares) don't allow binding LIMIT, so interpolate a safe int
    $limit = (int)$limit;
    $sql = "SELECT t.id, t.sender, su.username AS sender_name, t.receiver, ru.username AS receiver_name, t.amount, t.description
        FROM transaction t
        LEFT JOIN user su ON t.sender = su.id
        LEFT JOIN user ru ON t.receiver = ru.id
        WHERE t.sender = :id1 OR t.receiver = :id2
        ORDER BY t.id DESC
        LIMIT $limit";
    $stmt = $pdo->prepare($sql);
    // Bind both placeholders explicitly to avoid driver issues with repeated named params
    $stmt->bindValue(':id1', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':id2', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Verkrijg alle transacties (alle gebruikers) - voor admins
function get_all_transactions(PDO $pdo, int $limit = 500) : array {
    $limit = (int)$limit;
    $sql = "SELECT t.id, t.sender, su.username AS sender_name, t.receiver, ru.username AS receiver_name, t.amount, t.description
            FROM transaction t
            LEFT JOIN user su ON t.sender = su.id
            LEFT JOIN user ru ON t.receiver = ru.id
            ORDER BY t.id DESC
            LIMIT $limit";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Maak een URL-veilige token voor een interne id (bv. user id).
// Token bevat het id en een HMAC zodat het niet triviaal gefabriceerd kan worden.
function id_to_token(int $id): string {
    $idStr = (string)$id;
    $mac = hash_hmac('sha256', $idStr, APP_SECRET);
    $token = $idStr . ':' . $mac;
    // url-safe base64
    return rtrim(strtr(base64_encode($token), '+/', '-_'), '=');
}

// Decodeer een token terug naar de interne id, of null als ongeldig
function token_to_id(string $token): ?int {
    // herstel padding
    $b64 = strtr($token, '-_', '+/');
    $decoded = base64_decode($b64, true);
    if ($decoded === false) return null;
    $parts = explode(':', $decoded, 2);
    if (count($parts) !== 2) return null;
    list($idStr, $mac) = $parts;
    if (!ctype_digit($idStr)) return null;
    $expected = hash_hmac('sha256', $idStr, APP_SECRET);
    if (!hash_equals($expected, $mac)) return null;
    return (int)$idStr;
}

?>
