<?php
require ('security.php');
require 'class/equipeDao.class.php';
require 'class/pouleManagerDao.class.php';




$poulemanager = new PouleManager();

$pouleinfo = $poulemanager->getPouleById($_POST['dstpoule']);

$nouvelleCategorie = $pouleinfo['fk_idcategorie'];


if (isset ($_GET['idPoule'])) {
    $idPoule = (int)$_GET['idPoule'];
   
    $poulemanager->deletePoule($idPoule);
    header("Location: " . $_SERVER['HTTP_REFERER']);
}

$equipe = new EquipeDAO();
$equipe->modifierEquipe($_POST['equipe'],$_POST['equipeNom'],$nouvelleCategorie);


$equipe->modifierEquipeIdPoule($_POST['dstpoule'],$_POST['equipe']);

header("Location: " . $_SERVER['HTTP_REFERER']);

?>