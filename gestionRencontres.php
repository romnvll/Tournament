<?php
$url1=$_SERVER['REQUEST_URI'];

require ('security.php');
require 'class/equipeDao.class.php';

require 'class/rencontreDao.class.php';
require 'class/tournoiDao.class.php';

require 'class/pouleManagerDao.class.php';
require 'vendor/autoload.php';
session_start();
$_SESSION['idTournoi'] = $_GET['idTournoi'];


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$tournoi = new tournoiDao();
$poulemanager = new PouleManager();
$rencontre = new RencontreDAO();
$equipeDao = new EquipeDAO();



if (isset ($_GET['rencontreRetour']) && ($_GET['rencontreRetour'] == true) ) {
  $rencontre->createRencontreByPoule($_GET['idPoule'],$_GET['idTournoi'],0,true);
}

else {
  $rencontre->createRencontreByPoule($_GET['idPoule'],$_GET['idTournoi']);
}



//si on ne test pas le parametre idPoule --> erreur 500
if (isset ($_GET['idPoule'])) {
 $GetResultatDesPoules= $rencontre->GetResultatDesPoules($_GET['idPoule']);
}


$template = $twig->load('GestionRencontres.twig');
echo $template->render([
  'email' => $_COOKIE['email'],
  'isRencontreCreated' => $poulemanager->checkRencontresInPoule($_GET['idPoule']),
  'pageEnCours' => 'GestionDesRencontres',
  'afficherLesTournois' => $tournoi->afficherLesTournois(),
  'afficherLesPoules' => $poulemanager->getAllPoulesByTournoi($_SESSION['idTournoi']),
  'idTournoi'=> $_SESSION['idTournoi'],
  'listeDesEquipesParPoules' => $poulemanager->getEquipesInPoule($_GET['idPoule'],),
  'RencontreByPoule' => $rencontre->getRencontreByPoule($_GET['idPoule'],$_SESSION['idTournoi']),
  'idPoule' => $_GET['idPoule'],
  'pouleEnCours' => $_GET['idPoule'],
  'tournoiEnCours' => $_SESSION['idTournoi'],
  'resultatRencontres' => $GetResultatDesPoules,
  'NombreEquipeParPoules' => $equipeDao->countEquipesPresentesInPoule($_GET['idPoule']),
  'NombreTerrain' => $tournoi->getNbTerrainsById($_GET['idTournoi']),
  'InfoPoule' => $poulemanager->getInfoPoule($_GET['idPoule']),

]);


?>