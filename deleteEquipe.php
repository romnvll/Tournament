<?php
require ('security.php');
require 'class/equipeDao.class.php';
require 'class/tournoiDao.class.php';

$tournoiDao = new tournoiDao();
$tournoiId = $_GET['idTournoi'];
if ($tournoiDao->droitTournoiClub($tournoiId, $userData['id']) == null) {
      
    exit;
  }

$equipe= new EquipeDAO();
$equipe->supprimerEquipeParId($_GET['idEquipe']);

header("location: ajoutEquipe.php?idTournoi=".$_GET['idTournoi']);
