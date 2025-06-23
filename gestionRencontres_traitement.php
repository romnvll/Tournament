<?php
require ('security.php');

require 'class/rencontreDao.class.php';
require 'class/equipeDao.class.php';


if (isset ($_POST['etatTotal'])) {
   if ($_POST['etatTotal'] == "Avenir") {
     $rencontre = new RencontreDAO();
     $rencontre->updateStatusByCreneau($_POST['creneau_id'],0);
     header("Location: " . $_SERVER['HTTP_REFERER']);
   }
   if ($_POST['etatTotal'] == "enCours") {
     $rencontre = new RencontreDAO();
     $rencontre->updateStatusByCreneau($_POST['creneau_id'],2);
     header("Location: " . $_SERVER['HTTP_REFERER']);
   }
   if ($_POST['etatTotal'] == "terminee") {
     $rencontre = new RencontreDAO();
     $rencontre->updateStatusByCreneau($_POST['creneau_id'],1);
     header("Location: " . $_SERVER['HTTP_REFERER']);
     
   }

}


if (isset ($_POST['etat'])) {
  if ($_POST['etat'] == "Avenir") {
    
    $rencontre = new RencontreDAO();
    $rencontre->updateStatus($_POST['idRencontre'],0);
    
    echo "<p class='fw-bold text-center text-danger bg-dark' >Rencontre à venir</p>";
      }

  if ($_POST['etat'] == "enCours") {
    $rencontre = new RencontreDAO();
    $rencontre->updateStatus($_POST['idRencontre'],2);
   echo " <p class='fw-bold text-center text-warning bg-dark' >Rencontre en cours</p>";

  }

  if ($_POST['etat'] == "terminee") {
    $rencontre = new RencontreDAO();
    $rencontre->updateStatus($_POST['idRencontre'],1);
    echo "<p class='fw-bold text-center text-success bg-dark'>Rencontre terminée  </p>";
  }


}



if (isset($_POST['scoreEquipe1']) ) {
    $rencontre = new RencontreDAO();
    
    // A faire : si le champ est vide, passer le score à null
    if ($_POST['scoreEquipe1'] == "") {
      $rencontre->modifierRencontre($_POST['idRencontre'],null,9999);
  
    }
    else {
    // Mettre à jour la rencontre avec les scores
    $rencontre->modifierRencontre($_POST['idRencontre'],$_POST['scoreEquipe1'],9999);
    }
  } 
  if (isset($_POST['scoreEquipe2']) ) {
    $rencontre = new RencontreDAO();
    if ($_POST['scoreEquipe2'] == "") {
      // si le score est vide, on passe l'argument 9999 pour ne pas toucher au score
      $rencontre->modifierRencontre($_POST['idRencontre'],9999,null);
  
    }
    else {
      $rencontre->modifierRencontre($_POST['idRencontre'],9999,$_POST['scoreEquipe2']);
  
    } 
  }
//header("location: gestionRencontres.php?idTournoi=". $_POST['idTournoi'] ."&idPoule=".$_POST['idPoule']. "");
//header("Location: " . $_SERVER['HTTP_REFERER']);




?>



