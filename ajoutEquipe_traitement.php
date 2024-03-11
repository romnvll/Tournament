<?php
require 'security.php';


error_reporting(E_ALL);
ini_set("display_errors", 1);
//require('class/equipe.class.php');
require('class/equipeDao.class.php');
require ('class/rencontreDao.class.php');
//print_r($_POST);
//$Equipe = new Equipe($_POST['nomEquipe'],$_POST['categorie'],0);


$rencontreDao = new RencontreDAO();
$RencontreExist = $rencontreDao->rencontresExistByCategorieAndTournoi($_POST['Categorie'],$_POST['IdTournoi']);

if ($RencontreExist) {
    echo "des rencontres existe deja, impossible d'ajouter une equipe<br>Il faut d'abord supprimer les rencontres ";
    exit;
}


$equipeDao = new EquipeDAO();
$equipeDao->ajouterEquipe($_POST['nomEquipe'],$_POST['Categorie'],$_POST['IdTournoi'],null,$_POST['idClubs']);

header("location: ajoutEquipe.php?idTournoi=".$_POST['IdTournoi'])



?>