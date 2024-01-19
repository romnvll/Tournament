<?php



if (!isset($_COOKIE['user'])) {
  header("Location: index.php");
  //var_dump($_COOKIE);
  exit;
}



?>