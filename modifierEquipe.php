<?php
require('security.php');
require 'class/equipeDao.class.php';
require 'class/categorie.class.php';

$equipe = new EquipeDAO();


if ($_POST['changeClub'] == "true") {
    $equipe->changerClubEquipe($_POST['idEquipe'], $_POST['club_id']);
    header("Location: " . $_SERVER['HTTP_REFERER']);
}


if ($_POST['changeCategorie'] == "true") {

    $dejaplanifie = $equipe->equipeAUneRencontrePlanifiee($_POST['idEquipe'], $_POST['tournoi_id']);

    if ($dejaplanifie) {
        echo "❌ Impossible de modifier la categorie de l'équipe car elle a déjà une rencontre planifiée.";
        //header("HX-Refresh: true");
        exit;
    } else {

        $equipe->changerCategorieEquipe($_POST['idEquipe'], $_POST['categorie']);
        echo "✅  OK";
    }
}


if ($_POST['changeNomEquipe'] == "true") {
    if (isset($_SERVER['HTTP_HX_REQUEST'])) {

        $equipe->modifierEquipe($_POST['idEquipe'], $_POST['nomEquipe'], $_POST['categorie']);

        echo "✅ Nom modifié";
        exit;
    }
}


//header("location: ajoutEquipe.php?idTournoi=".$_POST['tournoi_id']);
