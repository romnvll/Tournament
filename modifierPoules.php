<?php
require ('security.php');
require 'vendor/autoload.php';
require 'class/pouleManagerDao.class.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';

$tournois = new tournoiDao();
$poules = new PouleManager();
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$equipe = new EquipeDAO();
if (isset ($_GET['id_tournoi'])) {
    //on recup les poules
    $poulesEtNombreEquipe=[];
    foreach($poules->getAllPoulesByTournoi($_GET['id_tournoi']) as $poule) {
            
            $poulesEtNombreEquipe[]=[
               'nomPoule' => $poule['nom'],
               'nbrEquipeParPoule' => $poules->compterEquipesParPoule($poule['id']),
               'idPoule' => $poule['id']
            ];
          
        
    }
   

}



if (isset($_GET['id_poule'])) {
    $equipes = $equipe->getAllEquipesByPouleId($_GET['id_poule']);

    
}

if (isset ($_SESSION['message'])) {
    $message = $_SESSION['message'];
}



$template = $twig->load('modifierPoules.twig');
echo $template->render([
    'email' => $_COOKIE['email'],
    'pageEnCours' => 'GestionDesPoules',
    
    'poules' => $poulesEtNombreEquipe,
   
    'ListeDesTournois' => $tournois->afficherLesTournois(),
    //'ListeDesCategorie' => $afficheCategorie->getAllCategorieByIdTournoi($_GET['id_tournoi']),
    'idTournoi' => $_GET['id_tournoi'],
    'idPoule' => $_GET['id_poule'],
    'nombreEquipeParPoules' => $poules->compterEquipesParPoule($_GET['id_poule']),
    'message' => $message,
    'afficherEquipeParPoule' => $equipes,

    //'nombreEquipeParPoule' => $PouleAuto,



]);


?>