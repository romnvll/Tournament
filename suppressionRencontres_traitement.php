<?php
require 'security.php';
require ('class/rencontreDao.class.php');

$rencontreDao = new RencontreDAO();
$rencontreDao->supprimerRencontresParPoule($_GET['idPoule']);
header("Location: " . $_SERVER['HTTP_REFERER']);
?>