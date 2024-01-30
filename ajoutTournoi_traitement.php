<?php
require 'security.php';

error_reporting(E_ALL);
ini_set("display_errors", 1);
//require('class/tournoi.class.php');
require('class/tournoiDao.class.php');




//$PasHoraire = (int)$pasHoraire;

$tournoiDao = new tournoiDao();




$tournoiDao->ajouterTournoi($_POST['nomTournoi'],$_POST['nbr_terrain'],$_POST['heuredebut'],0,null,$_POST['heurefin'],$_POST['pasHoraire']);
header("location: ajoutEquipe.php?idTournoi=0");








?>