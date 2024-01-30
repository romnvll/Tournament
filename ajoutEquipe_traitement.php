<?php
require 'security.php';


error_reporting(E_ALL);
ini_set("display_errors", 1);
//require('class/equipe.class.php');
require('class/equipeDao.class.php');
print_r($_POST);
//$Equipe = new Equipe($_POST['nomEquipe'],$_POST['categorie'],0);

$equipeDao = new EquipeDAO();
$equipeDao->ajouterEquipe($_POST['nomEquipe'],$_POST['Categorie'],$_POST['IdTournoi'],null,$_POST['idClubs']);

header("location: ajoutEquipe.php?idTournoi=".$_POST['IdTournoi'])



?>