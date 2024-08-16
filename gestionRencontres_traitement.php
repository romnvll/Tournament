<?php
require ('security.php');

require 'class/rencontreDao.class.php';
require 'class/equipeDao.class.php';







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



