<?php
if (isset($_COOKIE['auth'])) {
    
    header("Location: ../tableauDeBord.php");
    exit;
}
header("Location: login.php");

?>