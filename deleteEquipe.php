<?php
require ('security.php');
require 'class/equipeDao.class.php';

$equipe= new EquipeDAO();
$equipe->supprimerEquipeParId($_GET['idEquipe']);

header("location: ajoutEquipe.php?idTournoi=".$_GET['idTournoi']);
