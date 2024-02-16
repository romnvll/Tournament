<?php
require ('security.php');
require 'class/equipeDao.class.php';

$equipe = new EquipeDAO();
//var_dump($_POST);
$equipe->modifierEquipe($_POST['idEquipe'],$_POST['nomEquipe'],$_POST['categorie']);

header("location: ajoutEquipe.php?idTournoi=".$_POST['tournoi_id']);





?>