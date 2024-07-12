<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/terrainDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/creneauxDao.class.php';
require 'class/planificationDao.class.php';
require 'class/arbitreDao.class.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,

]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('PlacementDesRencontres.twig');

$tournois = new tournoiDao();
$creneaux = new creneauxDao();
$terrain = new TerrainDao();
$planification = new planificationDao();
$arbitre = new arbitreDao();

if (!isset($_GET['id_tournoi'])) {
    $listedestournois = $tournois->afficherLesTournois();
    $nbrterrain = null;
    $table = null;
    $listdecreneau = null;
    $planification = null;
    $planificationSansCreneauNiTerrain = null;
    $libelleParTournoi = null;
    $listeDesArbitres = null;
    $tournoiInfo = null;
    $lastCreneau=null;
    
} else {
   $listedestournois = $tournois->afficherLesTournois();
   $nbrterrain = $terrain->AfficherTerrains($_GET['id_tournoi']);
   $listdecreneaux = $creneaux->afficherCreneaux($_GET['id_tournoi']);
   $ToutesPlanification = $planification->afficherPlanifications($_GET['id_tournoi']);
    $planificationSansCreneauNiTerrain = $planification->afficherRencontresSansPlanification($_GET['id_tournoi']);
    $libelleParTournoi = $planification->listerLabelsParTournoi($_GET['id_tournoi']);
    $listeDesArbitres = $arbitre->afficherArbitres($_GET['id_tournoi']);
    $tournoiInfo = $tournois->getTournoiById($_GET['id_tournoi']);
   
    

//création du premier creneau :
//si aucun creneau n'existe on va creer la premiere à la l'heure de commencement

    if ($creneaux->existeCreneauPourTournoi($_GET['id_tournoi'])) {
        //echo "Il existe au moins une entrée dans la table Planification pour le tournoi ID $tournoi_id.";
    } else {
        //var_dump($tournoiInfo['heure_debut']);
        $creneaux= new creneauxDao();
        $creneaux->ajouterCreneau($tournoiInfo['heure_debut'],$_GET['id_tournoi']);
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }
    $lastCreneau = $creneaux->getLastCreneau($_GET['id_tournoi']);
    
    $timeDebut = DateTime::createFromFormat('H:i', $lastCreneau['nom']);
    $pasHoraire = $tournoiInfo['pasHoraire']; // Valeur des minutes à ajouter
    $timeNextCreneau = $timeDebut->add(new DateInterval('PT' . $pasHoraire . 'M'));
    $timeNextCreneau = $timeNextCreneau->format('H:i');

}








echo $template->render([
    'email' => $_COOKIE['email'],
    'pageEnCours' => 'GestionDesRencontres',
    'idTournoi' => $_GET['id_tournoi'],
    'afficherPlanification' =>  $ToutesPlanification,
    'planificationSansCreneauNiTerrain' => $planificationSansCreneauNiTerrain,

    'ListeDesTournois' => $listedestournois,
    'terrains' => $nbrterrain,
    'listCreneaux' => $listdecreneaux,
    'libelleParTournoi' => $libelleParTournoi,
    'listeDesArbitres' => $listeDesArbitres,
    'tournoiInfo'   => $tournoiInfo,
    'lastCreneau' => $lastCreneau,
    'timeNextCreneau' => $timeNextCreneau,
    


]);
