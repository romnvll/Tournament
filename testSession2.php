<?php

session_start();
echo "<pre>";
echo "Session ID : " . session_id() . "\n";
echo "Cookie PHPSESSID : " . ($_COOKIE['PHPSESSID'] ?? 'Aucun') . "\n";
echo "Contenu SESSION : ";
print_r($_SESSION);
echo "</pre>";



// test_config.php
echo "session.use_cookies: " . ini_get('session.use_cookies') . "<br>";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "<br>";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "<br>";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "<br>";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "<br>";

// Vérifier si les cookies sont bloqués
if (ini_get('session.use_cookies') == '0') {
    echo "⚠️ ATTENTION: session.use_cookies est désactivé!<br>";
}
?>