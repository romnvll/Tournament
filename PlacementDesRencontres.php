<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/terrainDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/creneauxDao.class.php';
require 'class/planificationDao.class.php';
require 'class/arbitreDao.class.php';
require 'class/labelsDao.class.php';
require 'class/categorie.class.php';
require 'class/licenceDao.class.php';
require 'class/equipeDao.class.php';
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
$template = $twig->load('PlacementDesRencontres.twig');


$tournois = new tournoiDao();
$creneaux = new creneauxDao();
$terrain = new TerrainDao();
$planification = new planificationDao();
$arbitre = new arbitreDao();
$labels = new labelDao();
$categories = new CategorieDao();
$equipes = new EquipeDAO();

if (!isset ($_GET['id_tournoi']) || $_GET['id_tournoi'] == 0) {
    echo "Aucun tournoi actif en cours.";
    header("Refresh:3; url=ajoutTournoi.php");
    exit();
}


$idTournoi = isset($_GET['id_tournoi']) ? (int) $_GET['id_tournoi'] : 0;

if (
    $userData['role'] !== 'admin' &&
    $tournois->droitTournoiClub($idTournoi, $userData['id']) === null
) {
    exit;
}








if (!isset($_GET['id_tournoi'])) {
    $listedestournois = $tournois->afficherLesTournois($userData['id']);
    $nbrterrain = null;
    $table = null;
    $listdecreneau = null;
    $planification = null;
    $planificationSansCreneauNiTerrain = null;
    $libelleParTournoi = null;
    $listeDesArbitres = null;
    $tournoiInfo = null;
    $lastCreneau=null;
    $nombreDeRencontresPlanifiee=null;
    $nombreRencontreAPlanifier=null;
    $nombreDeLabel=null;
    $listeDesEquipes=null;
    
    
} else {
   $listedestournois = $tournois->afficherLesTournois($userData['id']);
   $nbrterrain = $terrain->AfficherTerrains($_GET['id_tournoi']);
   $listdecreneaux = $creneaux->afficherCreneaux($_GET['id_tournoi']);
   $ToutesPlanification = $planification->afficherPlanifications($_GET['id_tournoi']);
    $planificationSansCreneauNiTerrain = $planification->afficherRencontresSansPlanification($_GET['id_tournoi']);
    $libelleParTournoi = $planification->listerLabelsParTournoi($_GET['id_tournoi']);
    $listeDesArbitres = $arbitre->afficherArbitres($_GET['id_tournoi']);
    $tournoiInfo = $tournois->getTournoiById($_GET['id_tournoi']);
    $nombreDeRencontresPlanifiee = $tournois->rencontresPlanifieeDuTournoi($_GET['id_tournoi']);
    $nombreRencontreAPlanifier = $tournois->nombreRencontreAPlanifier($_GET['id_tournoi']);
    $nombreDeLabel = $labels->getLabelsByTournoiId($_GET['id_tournoi']);
    $listeDesEquipes = $equipes->rechercherEquipesDansTournoi($_GET['id_tournoi'], $_GET['query']??null);
    

//création du premier creneau :
//si aucun creneau n'existe on va creer la premiere à la l'heure de commencement

    if ($creneaux->existeCreneauPourTournoi($_GET['id_tournoi'])) {
        //echo "Il existe au moins une entrée dans la table Planification pour le tournoi ID $tournoi_id.";
    } else {
        
        $creneaux= new creneauxDao();
        $creneaux->ajouterCreneau($tournoiInfo['heure_debut'],$_GET['id_tournoi']);
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }
    $lastCreneau = $creneaux->getLastCreneau($_GET['id_tournoi']);

if (!$lastCreneau || !isset($lastCreneau['nom'])) {
    // Gérer l'erreur si la dernière valeur du créneau est manquante
    throw new Exception("Créneau non trouvé pour le tournoi");
}

$timeDebut = DateTime::createFromFormat('H:i:s', $lastCreneau['nom']);

if (!$timeDebut) {
    // Gérer l'erreur si la création de l'objet DateTime échoue
    throw new Exception("Le format de l'heure est invalide : " . $lastCreneau['nom']);
}

$pasHoraire = $tournoiInfo['pasHoraire']; // Valeur des minutes à ajouter

// Ajouter le pas horaire
$timeNextCreneau = $timeDebut->add(new DateInterval('PT' . $pasHoraire . 'M'));

// Vérifie si $timeNextCreneau est un objet DateTime valide
if (!$timeNextCreneau instanceof DateTime) {
    throw new Exception("Erreur lors de l'ajout de l'intervalle au créneau.");
}

// Formater le prochain créneau
$timeNextCreneauFormatted = $timeNextCreneau->format('H:i');




}


$statsParCategorie = [];
$equipeMatchs = [];

foreach ($ToutesPlanification as $p) {
    if ($p['rencontre_id'] !== null) {
        [$h, $m] = explode(':', $p['creneau_nom']);
        $heureMin = ((int)$h * 60) + (int)$m;

        $key1 = $p['equipe1_id'].'|'.$p['equipe1_nom'].'|'.$p['equipe1_categorie_nom'];
        $key2 = $p['equipe2_id'].'|'.$p['equipe2_nom'].'|'.$p['equipe2_categorie_nom'];

        $equipeMatchs[$key1][] = $heureMin;
        $equipeMatchs[$key2][] = $heureMin;
    }
}

$format = function(int $min): string {
    return sprintf('%dh%02d', floor($min / 60), $min % 60);
};

foreach ($equipeMatchs as $key => $creneaux) {

    if (count($creneaux) < 2) continue;

    [$id, $equipeNom, $categorieNom] = explode('|', $key);

    sort($creneaux);

    $maxEcart      = 0;
    $maxDe         = 0;
    $maxA          = 0;
    $total         = 0;
    $count         = 0;
    $enchaînements = [];

    for ($i = 0; $i < count($creneaux) - 1; $i++) {
        $ecart = $creneaux[$i + 1] - ($creneaux[$i] + $tournoiInfo['pasHoraire']);
        $ecart = max(0, $ecart);

        // Détecter tous les enchaînements sans pause
        if ($ecart === 0) {
            $enchaînements[] = $format($creneaux[$i]);
        }

        // Ne compter que les vraies pauses
        if ($ecart > 0) {
            $total += $ecart;
            $count++;
        }

        if ($ecart > $maxEcart) {
            $maxEcart = $ecart;
            $maxDe    = $creneaux[$i];
            $maxA     = $creneaux[$i + 1];
        }
    }

    $moyenne = $count > 0 ? round($total / $count) : 0;

    if (!isset($statsParCategorie[$categorieNom])) {
        $statsParCategorie[$categorieNom] = [
            'totalEcart'     => 0,
            'nbEquipes'      => 0,
            'maxAttente'     => 0,
            'maxEquipe'      => '',
            'maxEquipeDe'    => '',
            'maxEquipeA'     => '',
            'minAttente'     => PHP_INT_MAX,
            'minEquipe'      => '',
            'enchaînements'  => [],  // toutes les équipes qui enchaînent
        ];
    }

    $cat = &$statsParCategorie[$categorieNom];

    if ($maxEcart > $cat['maxAttente']) {
        $cat['maxAttente']  = $maxEcart;
        $cat['maxEquipe']   = $equipeNom;
        $cat['maxEquipeDe'] = $format($maxDe);
        $cat['maxEquipeA']  = $format($maxA);
    }

    if ($cat['minEquipe'] === '' || $moyenne < $cat['minAttente']) {
        $cat['minAttente'] = $moyenne;
        $cat['minEquipe']  = $equipeNom;
    }

    // Stocker les enchaînements de cette équipe
    if (!empty($enchaînements)) {
        $cat['enchaînements'][] = [
            'equipe'  => $equipeNom,
            'horaires' => $enchaînements,
        ];
    }

    $cat['totalEcart'] += $moyenne;
    $cat['nbEquipes']++;
}





echo $template->render([
    'email' => $userData['email'],
  
    'pageEnCours' => 'GestionDesRencontres',
    'idTournoi' => $_GET['id_tournoi'],
    'afficherPlanification' =>  $ToutesPlanification,
    'planificationSansCreneauNiTerrain' => $planificationSansCreneauNiTerrain,
    'nombreDeRencontresPlanifiee' => $nombreDeRencontresPlanifiee,

    'ListeDesTournois' => $listedestournois,
    'terrains' => $nbrterrain,
    'listCreneaux' => $listdecreneaux,
    'libelleParTournoi' => $libelleParTournoi,
    'listeDesArbitres' => $listeDesArbitres,
    'tournoiInfo'   => $tournoiInfo,
    'lastCreneau' => $lastCreneau,
    'timeNextCreneau' => $timeNextCreneauFormatted,
    'nombreRencontreAPlanifier' => $nombreRencontreAPlanifier,
    'nombreDeLabel' => $nombreDeLabel,
    'listeCategorie' => $categories->obtenirCategoriesDuTournoi($_GET['id_tournoi']),
    'licence' => $licence,
    'AfficherLesEquipes' => $listeDesEquipes,
    'statsParCategorie' => $statsParCategorie,

    


]);
