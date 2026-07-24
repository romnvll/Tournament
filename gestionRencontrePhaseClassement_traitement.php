<?php
require ('security.php');
//ajout des equipes dans les poules
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/tournoiDao.class.php';
require 'class/labelsDao.class.php';
require 'class/categorie.class.php';
require 'class/planificationDao.class.php';

$pouledao = new PouleManager();
$rencontreDao = new RencontreDAO();
$tournoiDao = new tournoiDao();
$labelsDao = new LabelDAO();
$categorieDao = new CategorieDao();

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
    $nomCategorie = $categorieDao->obtenirCategorie($categorieId);

    if ($categorieId && $idTournoi) {

        if (isset($_GET['createPouleOnly']) && $_GET['createPouleOnly'] == 1) {
            $poules = $pouledao->genererPoulesClassementAutomatique($idTournoi, $categorieId, 1);
        } else {
            $poules = $pouledao->genererPoulesClassementAutomatique($idTournoi, $categorieId, 0);
        }

        // 🔥 Récupérer le nombre d'équipes des poules initiales
        $totalEquipes = $pouledao->compterTotalEquipesByCategorie($idTournoi, $categorieId);

        if ($totalEquipes === 0) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        foreach ($poules as $poule) {
            $pouleId = $poule['pouleId'];
            
            // Nombre d'équipes par poule de classement
            // (1er de chaque poule, 2eme de chaque poule, etc...)
            $nombrePouleInitiales = $pouledao->compterPoulesInitiales($idTournoi, $categorieId);
            $equipesParPouleClassement = $pouledao->compterEquipesParPoule($pouleId);

            
    $equipesParPouleClassement = $categorieDao->getNombreMaxEquipesParPouleInitiale($idTournoi, $categorieId);
   
           
            if ($equipesParPouleClassement === 0) {
                continue;
            }
            
            // Déterminer le type de rencontre (Aller simple ou Aller-retour)
            $estAller_retour = false; // À MODIFIER selon ta config
            
            // Nombre de tours
            $nombreToursAller = (int)(($nombrePouleInitiales)/2);
            $nombreTours = $estAller_retour ? $nombreToursAller * 2 : $nombreToursAller;
            
            // Nombre de rencontres par tour
            $rencontresParTour = floor($equipesParPouleClassement / 2);

            // ──────────────────────────────────────────────────────
            // Créer les labels pour chaque tour
            // ──────────────────────────────────────────────────────
            for ($tour = 1; $tour <= $nombreTours; $tour++) {
                $nomTour = ($tour <= $nombreToursAller) ? $tour : $tour - $nombreToursAller;
                $suffixe = ($tour > $nombreToursAller) ? ' (Retour)' : '';
                
                // Créer une rencontre par match du tour
                for ($rencontre = 1; $rencontre <= $rencontresParTour; $rencontre++) {
                    $libelleLabel = 'Rencontre ' . $rencontre . ' tour ' . $nomTour . ' ' . $nomCategorie['Nom_categorie'] . ' ' . $poule['nom'] . $suffixe;
                    
                    if (!$labelsDao->labelDescriptionExiste($libelleLabel, $idTournoi)) {
                       $labelsDao->ajouterLabel($libelleLabel, '#000000', $idTournoi, $categorieId);
                    }
                }
            }
        }
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}


if (isset($_GET['autoHauteBasse']) && $_GET['autoHauteBasse'] == 1) {
    $categorieId = filter_input(INPUT_GET, 'categorieId', FILTER_VALIDATE_INT);
    $nomCategorie = $categorieDao->obtenirCategorie($categorieId);

    if ($categorieId && $idTournoi) {

        if (isset($_GET['createPouleOnly']) && $_GET['createPouleOnly'] == 1) {
            $poules = $pouledao->genererPoulesHauteBasseAutomatique($idTournoi, $categorieId, 1);
        } else {
            $poules = $pouledao->genererPoulesHauteBasseAutomatique($idTournoi, $categorieId, 0);
        }

        // 🔥 Récupérer le nombre d'équipes par poule INITIALE
        $totalEquipes = $pouledao->compterTotalEquipesByCategorie($idTournoi, $categorieId);

        if ($totalEquipes === 0) {
            return;
        }

        // Calculer le nombre de tours (chaque poule Haute/Basse aura totalEquipes/2 équipes)
        $equipesParPouleHauteBasse = ceil($totalEquipes / 2);
        $estAller_retour = FALSE; // À MODIFIER selon ta config
        
        $nombreToursAller = $equipesParPouleHauteBasse - 1;
        $nombreTours = $estAller_retour ? $nombreToursAller * 2 : $nombreToursAller;
        
        // Nombre de rencontres par tour
        $rencontresParTour = floor($equipesParPouleHauteBasse / 2);

        foreach ($poules as $poule) {
            // ──────────────────────────────────────────────────────
            // Créer les labels pour chaque tour
            // ──────────────────────────────────────────────────────
            for ($tour = 1; $tour <= $nombreTours; $tour++) {
                $nomTour = ($tour <= $nombreToursAller) ? $tour : $tour - $nombreToursAller;
                $suffixe = ($tour > $nombreToursAller) ? ' (Retour)' : '';
                
                // 🔥 Créer une rencontre par match du tour
                for ($rencontre = 1; $rencontre <= $rencontresParTour; $rencontre++) {
                    $libelleLabel = 'Rencontre ' . $rencontre . ' tour ' . $nomTour . ' ' . $nomCategorie['Nom_categorie'] . ' ' . $poule['nom'] . $suffixe;
                    
                    if (!$labelsDao->labelDescriptionExiste($libelleLabel, $idTournoi)) {
                        $labelsDao->ajouterLabel($libelleLabel, '#000000', $idTournoi, $categorieId);
                    }
                }
            }
        }
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
    //verifier si les rencontres sont terminées avant de creer les rencontres de classement
$rencontresNonTerminees = $rencontreDao->countRencontresNonTermineesByCategorie($idTournoi,$_GET['idCategorie']);
if ($rencontresNonTerminees > 0) {
    // Rediriger vers la page précédente avec un message d'erreur
    echo "Il y a encore des rencontres non terminées pour cette catégorie. Veuillez terminer toutes les rencontres avant de créer les rencontres de classement.";
   // header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=rencontres_non_terminees");
    exit();
}




        $rencontreDao->createRencontreByPoule($_GET['pouleId'],$_GET['tournoiId'],3,false);

        if (isset($_GET['creerRencontresRetour']) && $_GET['creerRencontresRetour'] == 'true') {
            $rencontreDao->createRencontreByPoule($_GET['pouleId'],$_GET['tournoiId'],3,true);
        }
    
            //il faudrait placer les rencontres de classement a la place des labels.
          
            $planificationDao = new planificationDao();
            $placementAuto = $planificationDao->placerAutomatiquementRencontresType3($_GET['tournoiId'], $_GET['idCategorie']);    
         




       header("Location: " . $_SERVER['HTTP_REFERER']);


        }

}


if (isset ($_GET['suppressionPoule'])) {
        if ($_GET['suppressionPoule'] == 1) {
            $pouledao->deletePoule($_GET['pouleId']);
            
            $labelsDao->supprimerLabelParNomEtTournoiId($_GET['pouleNom'], $_GET['tournoiId']);
            
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }


}

?>