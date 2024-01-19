<?php


if (isset($_GET['logout'])) {
    
    unset($_COOKIE['user']);
    setcookie('user', '', -1, '/'); 
    //var_dump($_COOKIE['user']);
    header("Location: login.php");
    //exit;
}
