<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/licenceDao.class.php';
require 'Lang/lang.php';

$licenceDao = new LicenceDao();
$licence=$licenceDao->getLicencesParUtilisateur($userData['id'])[0];

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));



require_once 'class/clubDao.class.php';
require_once 'class/databaseInformations.php';
require_once 'class/tournoiDao.class.php';
require_once 'class/pouleManagerDao.class.php';
require_once 'class/equipeDao.class.php';
require_once 'class/categorie.class.php';
$tournoiDao = new tournoiDao();

$tousLesTournois = $tournoiDao->afficherLesTournois($userData['id']);


$idTournoi = isset($_GET['idTournoi']) ? (int) $_GET['idTournoi'] : 0;

if (
    $userData['role'] !== 'admin' &&
    $tournoiDao->droitTournoiClub($idTournoi, $userData['id']) === null &&
    $idTournoi === 0
) {
    header("Location: ajoutTournoi.php");
    exit;
}


//check licence

//

$poules = new PouleManager();

$listeClub = new ClubDAO();
$listeDesEquipes= new EquipeDAO();

$listeDesCategorie = new CategorieDao();

$template = $twig->load('ajoutEquipe.twig');



$dernierId = $_GET['idTournoi'];

foreach ($tousLesTournois as $tournoi) {

    if (isset($tournoi['isArchived']) && $tournoi['isArchived'] == 0) {
       $dernierId = $tournoi['id'];

        
}

}

if (isset($_GET['query'])) {
    $query = $_GET['query'];
}
else {
    $query = '';
}

$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;


if ($_GET['idTournoi'] != 0) {

  $afficherClub = $listeClub->afficherClubsParTypeDeSport($tournoiDao->getTournoiById($_GET['idTournoi'])['type_sport_id']);

}

else {
   $afficherClub = null;
}




echo $template->render([
  'email' => $userData['email'],
  
  'pageEncours' => 'ajoutEquipe',
  'tournoiEnCours' => $_GET['idTournoi'],
  'idTournoi' => $_GET['idTournoi'],
  'dernierTournoi' => $dernierId,
'ListeDesTournois' => $tournoiDao->afficherLesTournois($userData['id']),
'AfficherClub' => $afficherClub,
'AfficherLesEquipes' => $listeDesEquipes->rechercherEquipesDansTournoi($_GET['idTournoi'], $_GET['query']??null),
'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),
'AfficheLesCategories' => $listeDesCategorie->obtenirToutesLesCategories($userData['id']),
'infoTournoi' => $tournoiDao->getTournoiById($_GET['idTournoi']),
'query' => $query,
'error' => $error,
'licence' => $licence,


]);







?>
