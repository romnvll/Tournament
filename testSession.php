<?php

session_start();

$_SESSION['romain'] = "romain";

echo "<pre>";
echo "Session ID : " . session_id() . "\n";
echo "Cookie PHPSESSID : " . ($_COOKIE['PHPSESSID'] ?? 'Aucun') . "\n";
echo "Contenu SESSION : ";
print_r($_SESSION);
echo "</pre>";

echo "<a href='testSession2.php'>Test Session</a><br>";
