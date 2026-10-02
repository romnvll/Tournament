<?php
require('security.php');
require 'class/equipeDao.class.php';
require 'class/categorie.class.php';

$equipe = new EquipeDAO();
$categorie = new CategorieDao();


if (isset ($_POST['changeClub']) && $_POST['changeClub'] == "true") {
    $equipe->changerClubEquipe($_POST['idEquipe'], $_POST['club_id']);
    header("Location: " . $_SERVER['HTTP_REFERER']);
}


if (isset($_POST['changePresence']) && $_POST['changePresence'] === "true") {
    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
        try {
            // La checkbox envoie sa valeur "on" si cochée, rien si décochée
            $etat = isset($_POST['etatPresence']) ? "presente" : "absente";
            $equipe->confirmerEquipe((int)$_POST['idEquipe'], $etat);
        } catch (Exception $e) {
            echo "❌ " . htmlspecialchars($e->getMessage());
        }
        exit;
    }
}


if (isset($_POST['changeCategorie']) && $_POST['changeCategorie'] == "true") {

//    $dejaplanifie = $equipe->equipeAUneRencontrePlanifiee($_POST['idEquipe'], $_POST['tournoi_id']);
    $dejaplanifie = $categorie->existePlanificationPourCategorie($_POST['idCategorie']);
    if ($dejaplanifie) {
        echo "❌ Impossible de modifier la categorie de l'équipe car elle a déjà une rencontre planifiée.";
                header("HX-Refresh: true");
        exit;
    } else {

        $equipe->changerCategorieEquipe($_POST['idEquipe'], $_POST['categorie']);
        header("HX-Refresh: true");
        echo "✅  OK";
    }
}


if (isset($_POST['changeNomEquipe']) && $_POST['changeNomEquipe'] === "true") {
    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
        try {
            $equipe->modifierEquipe($_POST['idEquipe'], $_POST['nomEquipe'], $_POST['categorie']);
            echo "✅ Nom modifié";
        } catch (Exception $e) {
            echo "❌ " . htmlspecialchars($e->getMessage());
        }
        exit;
    }
}

if (isset($_POST['changeNomCoach']) && $_POST['changeNomCoach'] === "true") {
    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
        try {
            $equipe->modifierNomCoach($_POST['idEquipe'], $_POST['nomCoach']);
            echo "✅ Nom du coach modifié";
        } catch (Exception $e) {
            echo "❌ " . htmlspecialchars($e->getMessage());
        }
        exit;
    }
}



//header("location: ajoutEquipe.php?idTournoi=".$_POST['tournoi_id']);
