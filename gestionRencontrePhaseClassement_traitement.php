<?php
require ('security.php');
//ajout des equipes dans les poules
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/tournoiDao.class.php';

$pouledao = new PouleManager();
$rencontreDao = new RencontreDAO();
$tournoiDao = new tournoiDao();

$idTournoi = isset($_GET['tournoiId']) ? (int) $_GET['tournoiId'] : 0;

if (
    $userData['role'] !== 'admin' &&
    $tournoiDao->droitTournoiClub($idTournoi, $userData['id']) === null &&
    $idTournoi === 0
) {
    echo "ici";
   // header("Location: ajoutTournoi.php");
    exit;
}



if (isset($_GET['autoClassement']) && $_GET['autoClassement'] == 1) {
    $categorieId = filter_input(INPUT_GET, 'categorieId', FILTER_VALIDATE_INT);

    if ($categorieId && $idTournoi) {
        $pouledao->genererPoulesClassementAutomatique($idTournoi, $categorieId);
        
       
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}


if (isset($_GET['addpoule'])) {
    $pouledao->addEquipeToPoule($_GET['idequipe'], $_GET['pouleId'], $_GET['tournoiId']);

    // Récupérer l'URL de référence et ajouter l'ancre
    $referer = $_SERVER['HTTP_REFERER'];
    $anchor = "#section-" . $_GET['idequipe'];
    header("Location: " . $referer . $anchor);
    exit();
}



if (isset($_GET['delete']) && $_GET['delete'] == 1) {
    // Valider et nettoyer les entrées utilisateur
    $pouleId = filter_input(INPUT_GET, 'pouleId', FILTER_VALIDATE_INT);
    $equipeId = filter_input(INPUT_GET, 'idequipe', FILTER_VALIDATE_INT);

    if ($pouleId !== false && $equipeId !== false) {
        try {
            // Supprimer les rencontres associées à la poule
            $rencontreDao->supprimerRencontresParPoule($pouleId);

            // Supprimer le lien entre l'équipe et la poule
            $pouledao->supprimerLienEquipePoule($equipeId, $pouleId);

            // Rediriger vers la page précédente avec l'ancre
            
            //$anchor = "#section-" . $equipeId;
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        } catch (Exception $e) {
            // Gérer les exceptions (par exemple, loguer l'erreur)
            error_log("Erreur lors de la suppression : " . $e->getMessage());
            // Rediriger vers une page d'erreur ou afficher un message d'erreur
            header("Location: erreur.php");
            exit();
        }
    } else {
        // Gérer les entrées invalides
        header("Location: erreur.php");
        exit();
    }
}




if (isset($_GET['CreerRencontre'])) {
        if ($_GET['CreerRencontre'] == 1) {
        $rencontreDao->createRencontreByPoule($_GET['pouleId'],$_GET['tournoiId'],3);
        header("Location: " . $_SERVER['HTTP_REFERER']);

      //header("Location:  PlacementDesRencontres.php?id_tournoi=".$_GET['tournoiId']."&redirect=" . $_SERVER['HTTP_REFERER']);

        }

}


if (isset ($_GET['suppressionPoule'])) {
        if ($_GET['suppressionPoule'] == 1) {
            $pouledao->deletePoule($_GET['pouleId']);
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }


}

?>