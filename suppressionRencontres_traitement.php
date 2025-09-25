<?php
require 'security.php';
require 'class/tournoiDao.class.php';
require ('class/rencontreDao.class.php');

$tournois = new tournoiDao();

if ($tournois->droitTournoiClub($_GET['id_tournoi'], $userData['id']) == null) {
    
  exit;
}

$rencontreDao = new RencontreDAO();
$rencontreDao->supprimerRencontresParPoule($_GET['idPoule']);
header("Location: " . $_SERVER['HTTP_REFERER']);
?>