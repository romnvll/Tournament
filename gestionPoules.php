<?php
require('security.php');
require 'vendor/autoload.php';
require 'class/pouleManagerDao.class.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/planificationDao.class.php';
require 'class/licenceDao.class.php';
require 'Lang/lang.php';

$tournois = new tournoiDao();

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

$licenceDao = new LicenceDao();
$licence=$licenceDao->getLicencesParUtilisateur($userData['id'])[0];


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));

$idCategorie = null;



$equipesByCategorie = new EquipeDAO();
$afficheCategorie = new EquipeDAO();
$poules = new PouleManager();
$rencontres = new RencontreDAO();
$planification = new planificationDao();
$equipeDao = new EquipeDAO();


    $listeDesEquipes = $equipeDao->rechercherEquipesDansTournoi($_GET['id_tournoi'], $_GET['query']??null);



if (isset($_GET['categorie'])) {
  $nbrEquipeEnCours = $_GET['NbrEquipeParPoule'];
  $idCategorie = $_GET['categorie'];
  $categorieEnCours = $_GET['categorie'];

  if (isset($_GET['NbrEquipeParPoule'])) {

    $rencontre = new RencontreDAO();
    $poule =  $poules->afficherPoulesPourCategorie($_GET['id_tournoi'], $nbrEquipeEnCours, $idCategorie);
   
    $pouleHasRencontre = new PouleManager();
   
    
      if (isset($_GET['creation'])) {
       
        


        if ($_GET['creation'] == "ok") {
             // $rencontreDejaPlanifiee = $rencontre->rencontresCategorieDejaPlanifiees($idCategorie,$_GET['id_tournoi']);

            
              
             //on efface les rencontres existantes
               foreach ($poule as $key => $value) {
                      $rencontreDejaPlanifiee = $rencontre->rencontresCategorieDejaPlanifiees($idCategorie,$_GET['id_tournoi']);
                      
                      if ( $rencontreDejaPlanifiee) {
                        echo "<div class=\"alert alert-danger alert-dismissible fade show d-flex\" role=\"alert\" style=\"z-index: 1050;\">
                          <button type=\"button\" class=\"btn-close\" data-dismiss=\"alert\" aria-label=\"Close\"></button>
                          <div>
                            Impossible de manipuler cette poule, des rencontres planifiées existent déjà. Veuillez les déprogrammer avant de continuer 
                            <a href=\"PlacementDesRencontres.php?id_tournoi=" . htmlspecialchars($_GET['id_tournoi']) . "\">ici</a>.
                          </div>
                        </div>";
                        

                      exit(1);
                      }

                      else {
                        
                        $poule = $poules->creerPoulesPourCategorie($_GET['id_tournoi'],$idCategorie,$nbrEquipeEnCours);
                       
                       foreach ($poule as $idpoule) {
                        $rencontres->supprimerRencontresParPoule($idpoule);
                        
                        $rencontres->createRencontreByPoule($idpoule,$_GET['id_tournoi'],1,false);
                        
                        }
                                       

                        
                        
                      }
                      


                
               }    
               echo "
               <div class=\"alert alert-success alert-dismissible fade show d-flex\" role=\"alert\" style=\"z-index: 1050;\">
                   <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
                   <div>
                       <strong>La poule et les rencontres ont été créées avec succès ! 😊</strong><br>
                       Il reste à les planifier. Vous pouvez commencer à planifier <a href=\"PlacementDesRencontres.php?id_tournoi=" . htmlspecialchars($_GET['id_tournoi']) . "\" class=\"btn btn-link\">
                           <i class=\"fa-solid fa-calendar-check me-2\"></i>Planifier les rencontres</a>, 
                       ou retoucher cette poule <a href=\"modifierPoules.php?id_tournoi=" . htmlspecialchars($_GET['id_tournoi']) . "\" class=\"btn btn-link\">
                           <i class=\"fa-solid fa-pencil-alt me-2\"></i>Modifier la poule</a>.
                   </div>
               </div>";
               

               

        exit();
             
        }
        
      }


  }
}



$template = $twig->load('GestionPoules.twig');
echo $template->render([
  'email' => $userData['email'],

  'pageEnCours' => 'GestionDesPoules',

  'nbrEquipeEnCours' => $nbrEquipeEnCours??null,

  

  'ListeDesTournois' => $tournois->afficherLesTournois($userData['id']),
  'ListeDesCategorie' => $afficheCategorie->getAllCategorieByIdTournoi($_GET['id_tournoi']),
  'idTournoi' => $_GET['id_tournoi'],
  
  'listeDesPoules' => $poule??null,
  'categorieEnCours' => $categorieEnCours??null,
  'nbrEquipe' => $_GET['NbrEquipeParPoule']??null,
  'modal' => isset($_GET['modal']) ? $_GET['modal'] : null, // Passez la variable de modal à Twig
  'AfficherLesEquipes' => $listeDesEquipes,
  'licence' => $licence,


]);
