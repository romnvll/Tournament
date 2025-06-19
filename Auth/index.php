<?php
if (isset($_COOKIE['auth'])) {
    
    header("Location: ../creerClub.php");
    exit;
}
header("Location: login.php");

?>