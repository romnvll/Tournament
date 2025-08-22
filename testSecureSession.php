<?php
// Détruire toute session existante
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

// Configurer les bons paramètres (identique au cookie auth)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,      // ← Doit être TRUE
    'httponly' => true,    // ← Doit être TRUE
    'samesite' => 'Lax'
]);

session_start();
session_regenerate_id(true); // Important !

$_SESSION['secure_test'] = date('Y-m-d H:i:s');
$_SESSION['user_id'] = 123;

echo "<h3>Session sécurisée créée</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Secure test: " . $_SESSION['secure_test'] . "<br>";

// Vérifier le cookie
echo "<h3>Cookie PHPSESSID :</h3>";
echo "Présent: " . (isset($_COOKIE[session_name()]) ? 'OUI' : 'NON') . "<br>";

if (isset($_COOKIE[session_name()])) {
    echo "Valeur: " . $_COOKIE[session_name()] . "<br>";
}

echo '<meta http-equiv="refresh" content="2;url=testSecureSession.php">';
echo "Redirection dans 2 secondes...";
?>