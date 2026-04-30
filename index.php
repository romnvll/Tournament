<?php

require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/equipeDao.class.php';
require 'class/clubDao.class.php';
require 'class/planificationDao.class.php';
require 'class/labelsDao.class.php';
require 'class/terrainDao.class.php';
require 'class/licenceDao.class.php';
require 'class/categorie.class.php';
require 'class/gymnaseDao.class.php';
require 'Lang/lang.php';



$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);

$twig->addFilter(new \Twig\TwigFilter('shuffle', function ($array) {
    shuffle($array);
    return $array;
}));

$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('index.twig');





$rencontre = new RencontreDAO();
$tournoiDao = new tournoiDao();
$poulemanager = new PouleManager();
$clubdao = new ClubDAO();
$equipeDao = new EquipeDAO();
$licenceDao = new LicenceDAO();
$categorieDao = new CategorieDao();
$gymnaseDao = new GymnaseDAO();



$listeDesTournois = $tournoiDao->afficherTousLesTournois();
$RencontreByPoule=null;
$Labels= new LabelDao();
$listeDesRencontresByTerrain = null;
$terrain = new TerrainDao();
$planTournoi = null;
$classementFinal = null;


if (isset ($_GET['id_tournoi'])) {
  $nbrterrain = $terrain->compterTerrains($_GET['id_tournoi']);
  if (file_exists(('img/planTournoi/'.$_GET['id_tournoi'].'-plan.png'))) {
  $planTournoi = 'img/planTournoi/'.$_GET['id_tournoi'].'-plan.png';
  
  }

$gymnaseDao = new GymnaseDAO();
$gymnaseInfo = $gymnaseDao->getGymnaseByTournoiId($_GET['id_tournoi']);

}
else {
  $planTournoi = null;
  $gymnaseInfo = null;
}


if (isset ($_GET['affichageByPoule'])) {
  $affichageByPoule = true;
}
else {
  $affichageByPoule = null;
}


if (isset ($_GET['affichageByClubs'])) {
  $affichageByClubs = true;
}
else {
  $affichageByClubs = null;
}

if (isset ($_GET['affichageByTeam'])) {
  
  $affichageByTeam = true;
  $listeDesRencontresByTeam = $rencontre->afficherRencontreByTournoiByEquipe($_GET['id_tournoi'],$_GET['id_equipe']);
}
else {
  $affichageByTeam=null;
  $listeDesRencontresByTeam = null;
}

if (isset ($_GET['affichageByTerrain'])) {
  $affichageByTerrain = true;
  $listeDesRencontresByTerrain = $rencontre->afficherRencontresParTerrainEtTournoi($_GET['terrain'],$_GET['id_tournoi']);
}

if (isset ($_GET['id_equipe'])) {
$listePoulesParEquipe = $poulemanager->getPoulesByEquipeId($_GET['id_equipe']);


}

else {

  $listePoulesParEquipe = null;
 
}

if (isset ($_GET['affichageByCategorie'])) {
  
  $affichageByCategorie = true;
   if (isset ($_GET['idCategorie'])) {
    
    
    $RencontreByCategorie = $rencontre->getRencontreByCategorie($_GET['idCategorie'], $_GET['id_tournoi'], 1);
    $getCategorieCourante = $categorieDao->obtenirCategorie($_GET['idCategorie']);
    
  
   }

   else {
    $RencontreByCategorie = null;
    $RencontreByCategoriePhaseFinale = null;
   }
}
else {
  $affichageByCategorie = null;
    $RencontreByCategorie = null;
    $RencontreByCategoriePhaseFinale = null;
}







if (isset ($_GET['id_club'])) {
  

  $idclub = $_GET['id_club'];

  $listeDesRencontreByClubs = $rencontre->afficherRencontreByTournoiByClub($_GET['id_tournoi'],$_GET['id_club']);
  $listeDesEquipesByClubs = $equipeDao->getAllEquipeByIdTournoiAndClub($_GET['id_tournoi'],$_GET['id_club']);
  $nomClub = $clubdao->getClubById($_GET['id_club'])['nom'];
  $logoClub = $clubdao->getClubById($_GET['id_club'])['logo'];
//derniere poules des équipes :

$equipesAvecPoule = [];

foreach ($listeDesEquipesByClubs as $equipe) {
    $equipesAvecPoule[] = [
        'id' => $equipe['id'],
        'nom' => $equipe['nom'],
        'Nom_categorie' => $equipe['Nom_categorie'],
        'idCategorie' => $equipe['idCategorie'],
        'idPoule' => $poulemanager->getPremierePouleIdParEquipe($equipe['id']) ?? null,
        //'idPoule' => $poulemanager->getDernierePouleIdParEquipe($equipe['id']),
        'couleurCategorie' => $equipe['Couleur'],
        'nomCoach' => $equipe['nomCoach'] ?? null,
    ];
}


//



$classementFinal = [];
if (isset($_GET['idCategorie'])) {
    $classementFinal = $poulemanager->getClassementFinal((int)$_GET['id_tournoi'], (int)$_GET['idCategorie']);
}



}

else {
  $logoClub = null;
  $nomClub = null;
  $equipesAvecPoule = null;
  $listeDesRencontreByClubs = null;
  $listeDesEquipesByClubs = null;
  $idclub = null;
}

if (isset ($_GET['id_equipe'])) {

  $listeDesRenbcontreByEquipe = $rencontre->afficherRencontreByTournoiByEquipe($_GET['id_tournoi'],$_GET['id_equipe']);
$idequipe = $_GET['id_equipe'];
$equipeNom = $equipeDao->getEquipeById($_GET['id_equipe'])['nom'];



}



else {
  $equipeNom = null;
  $idequipe = null;
  $listeDesRenbcontreByEquipe = null;
}

if (isset ($_GET['id_tournoi'])) {
  
  $idTournoi= $_GET['id_tournoi'];
  $listeClubsParticipants = $clubdao->clubsParticipatingInTournoi($_GET['id_tournoi']);
  $Labels = $Labels->getLabelsWithCreneauxByTournoiId($_GET['id_tournoi']);
  

}

else {
  $listeClubsParticipants=null;
  $Labels = [];
  $idTournoi=0;
}

if (isset ($_GET['idPoule'])) {
$idPoule = $_GET['idPoule'];
$pouleInfo = $poulemanager->getPouleById($idPoule);

}
else {
  $idPoule = null;
  $pouleInfo = null;
}



if (isset ($_GET['idPoule'])) {
  //$GetResultatDesPoules= $rencontre->GetResultatDesPoules($_GET['idPoule']);
 

 
  if ($poulemanager->getPouleById($_GET['idPoule'])['is_classement'] == 3 ) {
    
   $RencontreByPoule = $rencontre->getRencontreByPoule($idPoule,3);
   $GetResultatDesPoules= $rencontre->GetResultatDesPoules($_GET['idPoule'],3);

  }
   else {
   $RencontreByPoule = $rencontre->getRencontreByPoule($idPoule,1);
   $GetResultatDesPoules= $rencontre->GetResultatDesPoules($_GET['idPoule'],1);

   }

   



   }

 else {
  $GetResultatDesPoules = null;
  $PouleHasPhasefinal = null;
 }





if (isset ($_GET['id_equipe'])) {
  $equipeAsRencontreAmicale = $rencontre->equipeAsRencontreAmicale($_GET['id_equipe']);
}
else {
  $equipeAsRencontreAmicale = null;
}
 
//gestion des sponsor
if (isset($_GET['id_tournoi']) && $_GET['id_tournoi'] != 0) {
    $idTournoi = (int) $_GET['id_tournoi'];

    if ($tournoiDao->getTournoiById($idTournoi)['gestionPartenaires'] == 1) {
        // Récupération des partenaires du club qui a organisé ce tournoi
        require_once 'class/SponsorDAO.class.php';
        $sponsorDao = new SponsorDAO();
        $listeDesPartenaires = $sponsorDao->getSponsorsActifParClub(
            $tournoiDao->getTournoiById($idTournoi)['utilisateur_id']
        );
    } else {
        $listeDesPartenaires = null;
    }
}


if (isset ($_GET['idCategorie'])) {
  $RencontreByCategoriePhaseFinale = $rencontre->getRencontreByCategorie($_GET['idCategorie'], $_GET['id_tournoi'], 3);
   $getCategorieCourante = $categorieDao->obtenirCategorie($_GET['idCategorie']);

}


echo $template->render([
    'infoTournoiEnCours'=> $tournoiDao->getTournoiById($idTournoi),
    'ListeDesTournois' => $listeDesTournois,
    'afficherLesPoules' => $listePoulesParEquipe ,
    'RencontreByPoule' => $RencontreByPoule,
    'IdPoules' => $idPoule,
    'IdClub' => $idclub,
    'affichageByClubs'=> $affichageByClubs,
    'affichageByTeam' =>$affichageByTeam,
    'affichageByPoule' => $affichageByPoule,
    'affichageByCategorie' => $affichageByCategorie,
    'listeDesCLubs' => $listeClubsParticipants,
    'listeDesRencontreByClubs' => $listeDesRencontreByClubs,
    'listeDesEquipesByClubs' =>$listeDesEquipesByClubs,
    'listeDesRencontresByTerrain' => $listeDesRencontresByTerrain,
    'listeDesEquipesByEquipeId' => $listeDesRenbcontreByEquipe,
    'IdEquipe' => $idequipe,
    'listeDesRencontreByTeam' =>$listeDesRencontresByTeam,
    'affichageByTerrain' => $listeDesRencontresByTerrain,
    'resultatRencontres'=> $GetResultatDesPoules,
    'getNomClubCourant' => $nomClub,
    'getCategorieCourante' => $getCategorieCourante ?? null,
    'getNomEquipeCourant' => $equipeNom,
    'logoClub' => $logoClub,
    'labels' => $Labels,
    'equipesAvecPoule' => $equipesAvecPoule,
    'nbrTerrains' => $nbrterrain ?? null,
    'partenaires' => $listeDesPartenaires ??null,
    'idTournoi' => $idTournoi,
    'licence' =>$licenceDao->getTousLesTypesDeLicence(),
    'planTournoi' => $planTournoi,
    'equipeAsRencontreAmicale' => $equipeAsRencontreAmicale,
     'classementFinal' => $classementFinal,
     'RencontreByCategorie' => $RencontreByCategorie,
     'pouleInfo' => $pouleInfo,
     'RencontreByCategoriePhaseFinale' => $RencontreByCategoriePhaseFinale,
      'gymnaseInfo' => $gymnaseInfo,
    
//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poulesDao->getAllPoulesByTournoi($_GET['idTournoi']),

]);
?>
