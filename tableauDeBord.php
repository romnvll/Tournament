<?php
require 'security.php';

require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/clubDao.class.php';

$tournoiDao = new tournoiDao();
$equipeDao = new equipeDao();
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('tableauDeBord.twig');


$tousLesTournois = $tournoiDao->afficherLesTournois($userData['id']);

$dernierId = 0;

foreach ($tousLesTournois as $tournoi) {
    if (isset($tournoi['isArchived']) && $tournoi['isArchived'] == 0) {
        $dernierId = $tournoi['id'];
       
    }
}
 //var_dump($dernierId);
        
$nbrequipe = $equipeDao->getAllEquipeByIdTournoi($dernierId);
$nbrequipe = count($nbrequipe);


$rencontreDao = new rencontreDao();
$statsStatusRencontres = $rencontreDao->getAllRencontresByTournoiId($dernierId);

$clubDao = new clubDao();
$countClub = $clubDao->clubsParticipatingInTournoi($dernierId);
$countClub = count($countClub);
//stats 
$avenir = 0;
$terminees = 0;
$encours = 0;
$avecScore = 0;

$total = count($statsStatusRencontres);

foreach ($statsStatusRencontres as $rencontre) {
    switch ($rencontre['isTerminated']) {
        case 0:
            $avenir++;
            break;
        case 1:
            $terminees++;
            break;
        case 2:
            $encours++;
            break;
    }

    if (!is_null($rencontre['score1']) || !is_null($rencontre['score2'])) {
        $avecScore++;
    }
}

$sansScore = $total - $avecScore;
$pourcentageTerminees = $total > 0 ? round(($terminees / $total) * 100, 2) : 0;


//fin stats


echo $template->render([
 'email' => $userData['email'],
  'logo' => $userData['logo'],
'pageEnCours' => 'Stats',
'idTournoi' => $dernierId,
'tournois' => $tousLesTournois,
'nbrequipe' => $nbrequipe,
'nbRencontreAvenir' => $avenir,
'nbRencontreTerminees' => $terminees,
'nbRencontreEncours' => $encours,
'nbClub' => $countClub,



]);


?>

