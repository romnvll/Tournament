<?php
require ('security.php');
require 'class/pouleManagerDao.class.php';
require 'class/equipeDao.class.php';

$poulemanager = new PouleManager();
echo $poulemanager->getIdFromNom($_GET['dstPoule']);
$idequipe=$_GET['IdEquipe'];
$equipe = new EquipeDAO();


$equipe->modifierEquipeIdPoule($poulemanager->getIdFromNom($_GET['dstPoule']),$idequipe);

?>


