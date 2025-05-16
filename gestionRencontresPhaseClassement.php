<?php
require ('security.php');
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/rencontreDao.class.php';
require  'class/pouleManagerDao.class.php';
require 'class/equipeDao.class.php';
require 'class/categorie.class.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,
]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('GestionRencontresPhasesClassement.twig');
$poulemanager = new PouleManager();
$rencontreDao = new RencontreDAO();
$tournoiDao = new tournoiDao();
$equipeDao = new EquipeDAO();
$categorie = new CategorieDao();


if (!isset ($_GET['idTournoiBase']) || $_GET['idTournoiBase'] == 0) {
  echo "Aucun tournoi actif en cours.";
  header("Refresh:3; url=ajoutTournoi.php");
  exit();
}

if ($tournoiDao->droitTournoiClub($_GET['idTournoiBase'], $userData['id']) == null) {
    
  exit;
}

//afficher les phase de classement uniquement:


if (isset ($_GET['idTournoiBase'])){
$tournoiId = $_GET['idTournoiBase']; // Remplacez cela par l'ID du tournoi pour lequel vous souhaitez créer les rencontres
//$tournoiEnCours = $tournoiDao->getIdTournoiParIdParent($tournoiId);

}



$poules = $poulemanager->getAllPoulesByTournoi($tournoiId);




if (isset($_GET['categorie'])) {

foreach ( $tournoiDao->genererRencontresPhaseclassement($tournoiId,$_GET['categorie']) as $rencontre) {
    $rencontreDao->insertRencontre($rencontre['equipe1']['id'],$rencontre['equipe2']['id'],$tournoiId,1);
}

}

if (isset ($_GET['idPoule'])) {

  $GetResultatDesPoules= $rencontreDao->GetResultatDesPoules($_GET['idPoule']);
 
 }


 foreach ($GetResultatDesPoules  as &$equipe) {
 
  try {
      $equipe['poules'] = $poulemanager->getPoulesByEquipeId($equipe['id']);
  } catch (Exception $e) {
      // Gérer l'exception ou attribuer une valeur par défaut
      $equipe['poules'] = []; // ou toute autre valeur appropriée
  }
  
}

$poulesFinales = $poulemanager->getAllPoulesFinalesByTournoi($tournoiId);
usort($poulesFinales, function($a, $b) {
  return $a['id'] <=> $b['id'];
});
foreach ($poulesFinales as &$poule) {
  $poule['contenu'] = $poulemanager->getEquipesInPoule($poule['id']);
  $poule['hasRencontres'] = $poulemanager->checkRencontresInPoule($poule['id'],1);
}


//Savoir si une poule contient des rencontre
$pouleHasRencontres = $poulemanager->checkRencontresInPoule($_GET['idPoule'],1);


echo $template->render([
 'email' => $userData['email'],
  'logo' => $userData['logo'],
  
  'pageEnCours' => 'GestionDesRencontres',
  //'categorieEnCours' => $_GET['categorie'],
  'categories' => $tournoiDao->getCategoriesPourTournoi($tournoiId),
  'afficherLesPoules' => $poulemanager->getAllPoulesByTournoi($tournoiId),
  'afficherLespoulesFinales' => $poulesFinales,
  //'afficherlecontenudespoules' => $poulemanager->getEquipesInPoule($idpoule),
  'PouleDuTournoi' => $poulemanager->getAllPoulesByTournoi($tournoiId),
  'pouleEnCours' => $_GET['idPoule'],
 
  
  'ResultatDesPoules' =>$GetResultatDesPoules,
  //'resultatPoules' => $GetResultatDesPoules,
  'idTournoi' => $tournoiId,
  //'RencontresByPoulephase1' => $rencontreDao->GetEquipesClasseesParPoule($tournoiId),
  'idCategorieEnCours' => $_GET['idCategorie'],
  //'TournoiDeClassement' => $tournoiDao ->afficherLesTournoisDeClassement(),
  'TournoisDeBase' => $tournoiDao->afficherLesTournoisQuiNeSontPasClassement($tournoiId),
  'pouleHasRencontres' => $pouleHasRencontres,
  
]);
?>
