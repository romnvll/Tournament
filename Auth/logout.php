<?php


if (isset($_GET['logout'])) {
    
    unset($_COOKIE['auth']);
    setcookie('auth', '', -1, '/'); 
    //var_dump($_COOKIE['user']);
    header("Location: login.php");
    //exit;
}
