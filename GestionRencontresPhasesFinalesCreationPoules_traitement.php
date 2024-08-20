<?php
require 'class/pouleManagerDao.class.php';


$poule = new PouleManager();

$poule->createPoule($_GET['pouledeClassmeent'],$_GET['idtournoi'],$_GET['categorie'],1);
header("Location: " . $_SERVER['HTTP_REFERER']);

?>