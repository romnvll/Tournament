<?php
require 'security.php';



if (isset ($_POST['Addevent'])) {
    
    
    $dataArray = json_decode($_POST['Addevent'], true);
    var_dump($dataArray);
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
        var_dump($dataArray);
        $planification = new planificationDao();

        if (!empty($idrencontre)) {
           
            $planification->ajouterOuModifierPlanification($idterrain, $idcreneau, $idrencontre, $idtournoi, null, null);
            header("Location: " . $_SERVER['HTTP_REFERER']);
            
        } elseif (!empty($idarbitre)) {
            $planification->ajouterOuModifierPlanification($idterrain, $idcreneau, null, $idtournoi, $idarbitre, null);
            
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } elseif (!empty($idlabel)) {
            $planification->ajouterOuModifierPlanification($idterrain, $idcreneau, null, $idtournoi, null, $idlabel);
            echo "<script>history.back</script>";
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            echo "Aucune donnée valide à ajouter ou modifier.";
        }
        
        
    }
   
   
}

if (isset ($_POST['modifMinutes'])) {
            
    require 'class/creneauxDao.class.php';
    $creneau=new creneauxDao();
    $creneau->mettreAJourCreneauxAvecMinutesAjoutees($_POST['idTournoi'],$_POST['modifMinutes']);


    //entest :
    //$creneau->mettreAJourHoraireDebut($_POST['idTournoi'],"10:15","15");
    header("Location: " . $_SERVER['HTTP_REFERER']);

}

//permet de modifier dans la bdd les info du tournoi et de mettre à jour les horaires
if (isset ($_POST['modifHeureDebut'])) {
    require 'class/creneauxDao.class.php';
    require 'class/tournoiDao.class.php';
    $creneau=new creneauxDao();
    $tournoi = new tournoiDao();
    
    //var_dump($_POST);
    $creneau->mettreAJourHoraireDebut($_POST['idTournoi'],$_POST['modifHeureDebut'],$_POST['modifPasHoraire']);
    $tournoi->modifierTournoi($_POST['idTournoi'],null,null,$_POST['modifHeureDebut'],null,null,null,$_POST['modifPasHoraire'],null,null,null,null,null,null);

    header("Location: " . $_SERVER['HTTP_REFERER']);
}



if (isset($_GET['action'])) {

        if (($_GET['action']=="addCreneau")) {
            require 'class/creneauxDao.class.php';
            $creneau = new creneauxDao();
            $creneau->ajouterCreneau($_GET['newTime'],$_GET['idTournoi']);
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }

        if  (($_GET['action']=="delCreneau")) {
            
            require 'class/creneauxDao.class.php';
            try {
            $creneau = new creneauxDao();
            $creneau->supprimerCreneau($_GET['creneauId']);
            header("Location: " . $_SERVER['HTTP_REFERER']);
            }catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    echo "Erreur: Impossible de supprimer ce créneau car il est deja utilisé, il faut deplanifier les evenements .";
                } else {
                    
                    echo "Erreur: " . $e->getMessage();
                }

          
        }

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
            
        }

        if (($_GET['action'] == "delTerrain")) {
            require 'class/terrainDao.class.php';
            $terrain = new TerrainDao();
            
            $terrain->suppressionTerrain($_GET['idTournoi'],$_GET['terrain_id']);
            
        }


     
      


}


?>