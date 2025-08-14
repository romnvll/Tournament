<?php
require 'security.php';

require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/licenceDao.class.php';
require 'class/typeSportDao.class.php';


$tournoiDao = new tournoiDao();
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('ajoutTournoi.twig');

$afficherTypeDeSport = new TypeSportDAO();
$typeDeSport = $afficherTypeDeSport->getTousLesTypesDeSport();


$tousLesTournois = $tournoiDao->afficherLesTournois($userData['id']);

$dernierId = null;

foreach ($tousLesTournois as $tournoi) {
    if (isset($tournoi['isArchived']) && $tournoi['isArchived'] == 0) {
        $dernierId = $tournoi['id'];
    }
}

$licenceDao = new LicenceDao();
$licence = $licenceDao->getLicencesParUtilisateur($userData['id']);


echo $template->render([
 'email' => $userData['email'],
 
'pageEnCours' => 'GestionTournois',
'idTournoi' => $dernierId,
'tousLesTournois' => $tousLesTournois,
'licence' => $licence,
'typeDeSport' => $typeDeSport,

//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),

]);


?>

