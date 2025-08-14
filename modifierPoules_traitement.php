<?php
require ('security.php');
require 'class/equipeDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/tournoiDao.class.php';

$tournoiDao = new tournoiDao();
var_dump($_POST);

// Prioriser $_POST['idTournoi'], sinon utiliser $_GET['tournoiId']
if (isset($_POST['id_tournoi']) && is_numeric($_POST['id_tournoi'])) {
    $tournoiId = (int)$_POST['id_tournoi'];
} elseif (isset($_GET['tournoiId']) && is_numeric($_GET['tournoiId'])) {
    $tournoiId = (int)$_GET['tournoiId'];
} else {
    // Si aucune valeur valide n'est trouvée, terminer le script
    exit;
}

if ($tournoiDao->droitTournoiClub($tournoiId, $userData['id']) == null) {
      
    exit;
  }


$rencontres = new RencontreDAO();
$poulemanager = new PouleManager();

$pouleinfo = $poulemanager->getPouleById($_POST['dstpoule']);

$nouvelleCategorie = $pouleinfo['fk_idcategorie'];


if (isset ($_GET['idPoule'])) {
    $idPoule = (int)$_GET['idPoule'];
   
    $poulemanager->deletePoule($idPoule);
    header("Location: " . $_SERVER['HTTP_REFERER']);
}


if ($poulemanager->pouleHasRencontreProgrammee($_POST['id_poule'],$_POST['id_tournoi'])) {
    echo "Impossible de déplacer l'équipe, des rencontres sont déjà programmées pour cette poule";
}

else {
    
    $rencontres->supprimerRencontresParPoule($_POST['id_poule']);
    $rencontres->supprimerRencontresParPoule($_POST['dstpoule']);

    $equipe = new EquipeDAO();
    $equipe->modifierEquipe($_POST['equipe'],$_POST['equipeNom'],$nouvelleCategorie);
    $equipe->modifierEquipeIdPoule($_POST['dstpoule'],$_POST['equipe']);


   //var_dump($_POST['id_poule']);
    
    $rencontres->createRencontreByPoule($_POST['id_poule'],$_POST['id_tournoi']);
   
    $rencontres->createRencontreByPoule($_POST['dstpoule'],$_POST['id_tournoi']);
    
   
  
    
    
}




header("Location: " . $_SERVER['HTTP_REFERER']);

?>