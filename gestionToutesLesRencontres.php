<?php
require ('security.php');
require 'class/equipeDao.class.php';
//require 'class/equipe.class.php';
require 'class/rencontreDao.class.php';
require 'class/tournoiDao.class.php';
//require 'class/tournoi.class.php';
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

$rencontre->createRencontreByPoule($_GET['idPoule'],$_GET['id_tournoi']);
//var_dump($rencontre->repartirRencontresSurTerrains($rencontre->getRencontreByTournoi($_GET['id_tournoi']),6));

$listeDesTournois = $tournoi->afficherLesTournois();
$nombreTerrain = $tournoi->getNbTerrainsById($_GET['id_tournoi']);

//si on ne test pas le parametre idPoule --> erreur 500
if (isset ($_GET['idPoule'])) {
 $GetResultatDesPoules= $rencontre->GetResultatDesPoules($_GET['idPoule']);
}


$rencontres = $rencontre->getRencontreByTournoi($_GET['id_tournoi']);

//
function ajouterRencontresVides($rencontres, $nombreTerrains, $heureDebut = '08:00:00', $heureFin = '18:00:00',$pashoraire) {
  
  $dureeCreneau = $pashoraire; // Durée d'un créneau, ici 15 minutes 00:15:00
  $rencontresCompletes = [];

  // Initialiser les créneaux pour chaque terrain
  for ($terrain = 1; $terrain <= $nombreTerrains; $terrain++) {
      $heureActuelle = $heureDebut;
      while ($heureActuelle < $heureFin) {
          $rencontresCompletes[$terrain][$heureActuelle] = [
              'rencontre_id' => null, // Rencontre vide
              'num_terrain' => $terrain,
              'heure_rencontre' => $heureActuelle,
              'equipe1_categorie' => "Aucune Rencontre",
              // autres champs vides ou par défaut
          ];
          $heureActuelle = date('H:i:s', strtotime($heureActuelle) + strtotime($dureeCreneau) - strtotime('TODAY'));
      }
  }

  // Placer les rencontres existantes dans les créneaux correspondants
  foreach ($rencontres as $rencontre) {
      $terrain = $rencontre['num_terrain'];
      $heure = $rencontre['heure_rencontre'];

      // S'assurer que le format de l'heure est le même
      if (isset($rencontresCompletes[$terrain][$heure])) {
          $rencontresCompletes[$terrain][$heure] = $rencontre;
      }
  }

  // Aplatir le tableau pour obtenir la structure souhaitée
  $resultat = [];
  foreach ($rencontresCompletes as $terrain => $creneaux) {
      foreach ($creneaux as $heure => $rencontre) {
          $resultat[] = $rencontre;
      }
  }

  return $resultat;
}


//var_dump($tournoi->getTournoiById($_GET['id_tournoi']));


$starttime = $tournoi->getTournoiById($_GET['id_tournoi']);

$starttime = $starttime['heure_debut'];
$starttime = date('H:i:s', strtotime($starttime));


$endtime = $tournoi->afficherLesTournois();

foreach ($endtime as $end) {
  if ($end['id'] == $_GET['id_tournoi']) {
    $endtime = $end['heure_fin'];
    $endtime = date('H:i:s', strtotime($endtime));
  }
}





$lestournois = $tournoi->afficherLesTournois();


foreach ($lestournois as $tournoi) {
  if ($tournoi['id'] == $_GET['id_tournoi']) {
      $pashoraire = $tournoi['pasHoraire'];
      
  }
}




// Convertir les minutes en secondes
$pashoraire = $pashoraire * 60;

// Formater en HH:MM:SS
$pashoraire = gmdate('H:i:s', $pashoraire);



$rencontre1 = ajouterRencontresVides($rencontres,$nombreTerrain,$starttime,$endtime,$pashoraire);
//echo "<pre>";
//var_dump($rencontre1);
//echo "<pre>";


//



$template = $twig->load('gestionToutesLesRencontres.twig');
echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' => 'GestionDesRencontres',

  //'afficherRencontreByIdTournoi' =>  $recontreDao->afficherRencontreByIdTournoi($_GET['idTournoi']),
  
  'idTournoi'=> $_GET['id_tournoi'],
 
  
  //'idPoule' => $_GET['idPoule'],
  
  'tournoiEnCours' => $_SESSION['idTournoi'],
  

  'ToutesLesRencontres' => $rencontre1 ,

  //'ToutesLesRencontres' => $rencontre->getRencontreByTournoi($_GET['id_tournoi']),

  'nombreTerrain' => $nombreTerrain,
  'ListeDesTournois' => $listeDesTournois,
 


]);


?>