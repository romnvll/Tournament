<?php
require ('security.php');
require 'vendor/autoload.php';
require 'class/pouleManagerDao.class.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

if (isset($_GET['NbrEquipeParPoule'])) {
  $PouleAuto = $_GET['NbrEquipeParPoule'];
}
else 
{
  $PouleAuto=6;
}


$tournois = new tournoiDao();
$equipesByCategorie = new EquipeDAO();
$afficheCategorie = new EquipeDAO();
$poules = new PouleManager();


//to DO
// si la poule existe l'indiquer sur cette page
$nomTournoi=$tournois->getTournoiById($_GET['id_tournoi'])['nom'];

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

$template = $twig->load('GestionPoules.twig');
echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' => 'GestionDesPoules',
  'tournoiEnCours' => $idtournoi,
    'poules' => $poules->getAllPoulesByTournoi($_GET['id_tournoi']),
    'poulesverif' => $poules->getAllPoulesByTournoi($_GET['id_tournoi']),
    'nomDuTournoi' => $nomTournoi,
    'PoulesAuto' => $poules->creerPoules($_GET['id_tournoi'],$PouleAuto),
    'ListeDesTournois' => $tournois->afficherLesTournois(),
    'ListeDesCategorie' => $afficheCategorie->getAllCategorieByIdTournoi($_GET['id_tournoi']),
    'idTournoi' => $_GET['id_tournoi'],
    'nombreEquipeParPoule' => $PouleAuto,



]);


?>