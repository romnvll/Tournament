<?php
require('class/rencontreDao.class.php');
session_start();

if (!isset($_SESSION['infoUser'][0]['url_key'])) {
  exit();
}


//mise a jour du status
if (isset($_GET['action']) && $_GET['action'] == 'updateStatus' && isset($_GET['idRencontre'])) {
  $idRencontre = (int)$_GET['idRencontre'];
  
  // Récupérer la rencontre pour vérifier les scores
  $rencontre = new RencontreDAO();
  $planification = $rencontre->getRencontre($idRencontre);  // Méthode pour obtenir les détails d'une rencontre

  $status = 0; // À venir

  // Vérifier les scores des deux équipes
  if ($planification['score1'] !== null || $planification['score2'] !== null) {
      if ($planification['score1'] === null && $planification['score2'] === null) {
          $status = 2; // En cours
      } else {
          $status = 1; // Terminé
      }
  }

  // Mettre à jour le statut dans la base de données
  $rencontre->updateStatus($idRencontre, $status);

  // Retourner le bouton mis à jour avec le nouveau statut
  if ($status == 1) {
      echo '<a class="btn btn-primary" id="State' . $idRencontre . '" role="button">Terminé</a>';
  } elseif ($status == 2) {
      echo '<a class="btn btn-primary" id="State' . $idRencontre . '" role="button">En cours</a>';
  } else {
      echo '<a class="btn btn-primary" id="State' . $idRencontre . '" role="button">À venir</a>';
  }
}

// fin de MAJ status


if (isset($_GET['action']) && isset($_GET['idRencontre']) && isset($_GET['team'])) {
  $rencontre = new RencontreDAO();
  $idRencontre = $_GET['idRencontre'];
  $team = $_GET['team'];

  // Récupérer les scores actuels
  $currentScores = $rencontre->getScore($idRencontre); // Suppose que cette méthode existe et renvoie un tableau avec score1 et score2

  $scoreEquipe1 = $currentScores['score1'];
  $scoreEquipe2 = $currentScores['score2'];

  // Ajouter ou retirer un but en fonction de l'équipe
  if ($_GET['action'] === 'addGoal') {
    if ($team == 1) {
      $rencontre->updateStatus($idRencontre, 2);
      // Retourner le code HTML du bouton mis à jour

      $scoreEquipe1 += 1;
    } elseif ($team == 2) {
      $rencontre->updateStatus($idRencontre, 2);
      $scoreEquipe2 += 1;
    }
  } elseif ($_GET['action'] === 'removeGoal') {
    if ($team == 1 && $scoreEquipe1 > 0) {
      $scoreEquipe1 -= 1;
    } elseif ($team == 2 && $scoreEquipe2 > 0) {
      $scoreEquipe2 -= 1;
    }
  }


  // Mettre à jour les scores dans la base de données
  $rencontre->modifierRencontre($idRencontre, $scoreEquipe1, $scoreEquipe2);

  // Renvoyer le nouveau score pour l'équipe concernée
  if ($team == 1) {
    echo '<span id="scoreEquipe1' . $idRencontre . '">' . $scoreEquipe1 . '</span>';
  } else {
    echo '<span id="scoreEquipe2' . $idRencontre . '">' . $scoreEquipe2 . '</span>';
  }

  exit();
}


