<?php
require('class/rencontreDao.class.php');
session_start();

if (!isset($_SESSION['infoUser'][0]['url_key'])) {
  exit();
}

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
      $scoreEquipe1 += 1;
    } elseif ($team == 2) {
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


