<?php
require 'class/pouleManagerDao.class.php';
require 'class/labelsDao.class.php';
require 'class/categorie.class.php';
$poule = new PouleManager();
$labelsDao = new LabelDAO();
$categorie = new CategorieDao();

$categorie->obtenirCategorie($_GET['categorie']);
$categorie= $categorie->obtenirCategorie($_GET['categorie']);

$poule->createPoule($_GET['pouledeClassement'],$_GET['idtournoi'],$_GET['categorie'],1);
$labelsDao->ajouterLabel('🏆 ' . $categorie['Nom_categorie'] . ' - ' . $_GET['pouledeClassement'],$categorie['Couleur'],
    $_GET['idtournoi'],
    $_GET['categorie']
);
header("Location: " . $_SERVER['HTTP_REFERER']);

?>