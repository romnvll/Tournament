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


//on recupere l'id du tournoi
if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    // Utilisez parse_url pour obtenir les composants de l'URL
    $refererUrl = parse_url($_SERVER['HTTP_REFERER']);
  
    // Utilisez parse_str pour extraire les paramètres de la chaîne de requête
    parse_str($refererUrl['query'], $params);
  
    // Vérifiez si idTournoi est défini dans les paramètres
    if (isset($params['idTournoi'])) {
        $idtournoi = $params['idTournoi'];
        
        // Maintenant, $idtournoi contient la valeur passée dans la requête précédente
    }
  }
//


$template = $twig->load('modifierPoules.twig');
echo $template->render([
    'email' => $_COOKIE['email'],
    'pageEnCours' => 'GestionDesPoules',
    'tournoiEnCours' => $idtournoi,
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