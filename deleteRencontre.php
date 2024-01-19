<?php
require ('class/rencontreDao.class.php');
$rencontreDao = new RencontreDAO();
$rencontreDao->resetRencontre($_GET['idRencontre']);

$referer = $_SERVER['HTTP_REFERER'];
header("Location: $referer");
?>