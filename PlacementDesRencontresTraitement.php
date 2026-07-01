<?php
require 'security.php';
require ('class/tournoiDao.class.php');



if (isset ($_POST['nomTerrain'])) {
    require ('class/terrainDao.class.php');
    $terrain = new TerrainDao();
    $terrain->modifierNomTerrain($_POST['terrain_id'],$_POST['nomTerrain']);
    
}
 if (isset($_POST['action']) && $_POST['action'] === 'deplacerPlanification') {
    require 'class/planificationDao.class.php';
    $planification = new planificationDao();

    $planification->nettoyerPlanificationsVides($_POST['idTournoi']);

    $planifId      = (int) $_POST['planifId'];
    $newTerrain    = (int) $_POST['newTerrain'];
    $newCreneau    = (int) $_POST['newCreneau'];
    $targetPlanifId = isset($_POST['targetPlanifId']) && $_POST['targetPlanifId'] !== '' 
                        ? (int) $_POST['targetPlanifId'] 
                        : null;
    $originTerrain = (int) $_POST['originTerrain'];
    $originCreneau = (int) $_POST['originCreneau'];

    if ($targetPlanifId !== null) {
        // SWAP : déplacer d'abord la cible vers l'origine, puis la source vers la destination
        $planification->deplacerPlanification($targetPlanifId, $originTerrain, $originCreneau);
        $planification->deplacerPlanification($planifId, $newTerrain, $newCreneau);
    } else {
        // Déplacement simple
        $planification->deplacerPlanification($planifId, $newTerrain, $newCreneau);
    }

    http_response_code(200);
    exit;
}



if (isset($_POST['action']) && $_POST['action'] === 'deplanifier') {
  require ('class/planificationDao.class.php');
  $planification = new planificationDao();
    $idTournoi = $_POST['idTournoi'];
 foreach ($_POST['rencontreCheck'] as $idRencontre) {
        // Sécurise chaque identifiant avant appel
        $idRencontre = (int) $idRencontre;

        // Appel DAO : un retrait par rencontre
        $planification->retireRencontre($idTournoi, $idRencontre);
                    header("Location: " . $_SERVER['HTTP_REFERER']);

    }

   

 

    
}


if (isset ($_POST['Addevent'])) {
    
    
    $dataArray = json_decode($_POST['Addevent'], true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        // Récupération de chaque champ
        $rencontre = $dataArray['rencontre'];
        $idterrain = $dataArray['idterrain'];
        $idcreneau = $dataArray['idcreneau'];

        $idrencontre = $dataArray['idrencontre'];
        $idlabel = $dataArray['idlabel'];
        $idarbitre = $dataArray['idarbitre'];

        $idtournoi = $dataArray['idtournoi'];
        
        require 'class/planificationDao.class.php';
        
        $planification = new planificationDao();

        if (!empty($idrencontre)) {
           
            $planification->ajouterOuModifierPlanification($idterrain, $idcreneau, $idrencontre, $idtournoi, null, null);
            header("Location: " . $_SERVER['HTTP_REFERER']);
            
        } elseif (!empty($idarbitre)) {
            $planification->ajouterOuModifierPlanification($idterrain, $idcreneau, null, $idtournoi, $idarbitre, null);
           
           // header("Location: " . $_SERVER['HTTP_REFERER']);
        } elseif (!empty($idlabel)) {
            $planification->ajouterOuModifierPlanification($idterrain, $idcreneau, null, $idtournoi, null, $idlabel);
            echo "<script>history.back</script>";
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            echo "Aucune donnée valide à ajouter ou modifier.";
        }
        
        
    }
   
   
}



if (isset($_POST['modifMinutes']) && $_POST['modifMinutes'] != "" ) {
    
    require 'class/creneauxDao.class.php';
    $creneau=new creneauxDao();
    $creneau->mettreAJourCreneauxAvecMinutesAjoutees($_POST['idTournoi'],$_POST['modifMinutes']);
   
    header("Location: " . $_SERVER['HTTP_REFERER']);

}

//permet de modifier dans la bdd les info du tournoi et de mettre à jour les horaires
if (isset ($_POST['modifHeureDebut'])) {
    require 'class/creneauxDao.class.php';
    require_once 'class/tournoiDao.class.php';
    $creneau = new creneauxDao();
    $tournoi = new tournoiDao();

    $idTournoi = (int) $_POST['idTournoi'];
    $nouveauPasHoraire = (int) $_POST['modifPasHoraire'];

    $tournoi->modifierTournoi($idTournoi, null, $_POST['modifHeureDebut'], null, $nouveauPasHoraire, null, null, null, null, null, null);

    // Recalcule tous les créneaux en respectant le tempsChangementMinutes propre à chacun
    $creneau->recalculerCreneauxAvecNouveauPas($idTournoi, $nouveauPasHoraire);

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}



if (isset($_POST['action']) && $_POST['action'] === 'modifierTempsChangement') {
    require_once 'class/creneauxDao.class.php';
    require_once 'class/tournoiDao.class.php';
    $creneau  = new creneauxDao();
    $tournoi  = new tournoiDao();

    $idTournoi    = (int) $_POST['idTournoi'];
    $creneauId    = (int) $_POST['creneau_id'];
    $nouveauTemps = (int) $_POST['tempsChangementMinutes'];

    $infosCreneau = $creneau->getOrdreParId($creneauId);
    if (!$infosCreneau) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Sauvegarder la nouvelle valeur SUR CE créneau précis
    $creneau->modifierTempsChangementCreneau($creneauId, $nouveauTemps);

    // Recalculer tous les créneaux suivants en partant de l'heure réelle du créneau modifié
    $tournoiInfo = $tournoi->getTournoiById($idTournoi);
    $creneau->decalerCreneauxApres($idTournoi, $infosCreneau['ordre'], (int)$tournoiInfo['pasHoraire']);

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require 'class/creneauxDao.class.php';
    $action = $_POST['action'];
    
    if ($action === 'updateCreneau') {
        $creneauId = $_POST['creneau_id'] ?? null;
        $nom = $_POST['creneau'] ?? null;
        
        if ($creneauId && $nom !== null) {
            $creneau = new creneauxDao();
            $creneau->modifierCreneau((int)$creneauId, $nom);
        }
    }
}




if (isset($_GET['action'])) {

        if (($_GET['action']=="addCreneau")) {
            require 'class/creneauxDao.class.php';
            $creneau = new creneauxDao();
            $creneau->ajouterCreneau($_GET['newTime'],$_GET['idTournoi']);
            header("Location: " . $_SERVER['HTTP_REFERER'] ."#bottom");
        }

        if  (($_GET['action']=="delCreneau")) {
            
            require 'class/creneauxDao.class.php';
            try {
            $creneau = new creneauxDao();
            $creneau->supprimerCreneauEtRecaler($_GET['creneauId'],$_GET['tournoiId'], $_GET['pas']);
            header("Location: " . $_SERVER['HTTP_REFERER']."#bottom");
            }catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    echo "Erreur: Impossible de supprimer ce créneau car il est deja utilisé, il faut deplanifier les evenements .";
                } else {
                    
                    echo "Erreur: " . $e->getMessage();
                }

          
        }

    }

     if  (($_GET['action']=="ajouterCreneauEntre")) {
        require 'class/creneauxDao.class.php';
         $creneau = new creneauxDao();
            $creneau->ajouterCreneauEntre($_GET['idTournoi'],$_GET['CreneauOrdre'],$_GET['pas']);
             header("Location: " . $_SERVER['HTTP_REFERER']);


     }

      

        if (($_GET['action'] == "noPlanifEvent")) {

            if (isset($_GET['arbitre_id'])) {
                
                require 'class/planificationDao.class.php';
                $planification = new planificationDao();
                $planification->retireArbitre($_GET['tournoi_id'],$_GET['event']);
                header("Location: " . $_SERVER['HTTP_REFERER']);
            }

            if (isset ($_GET['rencontreId'])) {
                require 'class/planificationDao.class.php';
                $planification = new planificationDao();
                $planification->retireRencontre($_GET['tournoi_id'],$_GET['event']);
                header("Location: " . $_SERVER['HTTP_REFERER']);

            }

            require 'class/planificationDao.class.php';
            $planification = new planificationDao();
            $planification->supprimerPlanification($_GET['event']);
           header("Location: " . $_SERVER['HTTP_REFERER']);
          // $planification->resetTerrainAndCreneau($_GET['tournoi_id'],$_GET['event']);
        }


        if (($_GET['action'] == "addTerrain")) {
            require 'class/terrainDao.class.php';
            $terrain = new TerrainDao();
           
            $terrain->ajoutTerrain($_GET['idTournoi'],$terrain->compterTerrains($_GET['idTournoi']) + 1);
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }

        if (($_GET['action'] == "delTerrain")) {
            require 'class/terrainDao.class.php';
            require 'class/PersonneTableDao.class.php';
            try {
                $terrain = new TerrainDao();
                $personneTableDao = new PersonneTableDao();
                $verif = $personneTableDao->verifierSiPersonneEstSurUnTerrain((int)$_GET['terrain_id']);
                if ($verif) {
                    echo "Erreur: Impossible de supprimer ce terrain car il y a des personnes assignées à cette table .";
                    header("Refresh:3; url=" . $_SERVER['HTTP_REFERER']);
                    exit();

                }
                else {
                 $terrain->suppressionTerrain($_GET['idTournoi'], $_GET['terrain_id']);
                header("Location: " . $_SERVER['HTTP_REFERER']);
                }
               
            } catch (PDOException $e) {
                
                    echo "Erreur: Impossible de supprimer ce terrain car il est déjà utilisé, il faut deplanifier les événements.";
               
                
            }
        }
        


     
      


}


?>