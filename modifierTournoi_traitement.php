<?php
require 'security.php';
require ('class/tournoiDao.class.php');


// Vérification de l'existence et de la validité des paramètres $_GET ou $_POST

$tournoiDao = new tournoiDao();

// Prioriser $_POST['idTournoi'], sinon utiliser $_GET['tournoiId']
if (isset($_POST['idTournoi']) && is_numeric($_POST['idTournoi'])) {
    $tournoiId = (int)$_POST['idTournoi'];
} elseif (isset($_GET['tournoiId']) && is_numeric($_GET['tournoiId'])) {
    $tournoiId = (int)$_GET['tournoiId'];
} else {
    // Si aucune valeur valide n'est trouvée, terminer le script
    exit;
}

if ($tournoiDao->droitTournoiClub($tournoiId, $userData['id']) == null) {
      
    exit;
  }




if ($_GET['action'] == "ajoutUserSurTable") {
    $idTournoi = $_GET['tournoiId'];
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    
    try {
    $personneTable->genererUrlEtCodePin($_GET['idPersonne'],$_GET['idterrain'],$_GET['tournoiId']);
  header("Location:  modifierTournoi.php?idTournoi=".$idTournoi."&tab=gestionTables");

    }
    catch (Exception $e) {
  header("Location:  modifierTournoi.php?idTournoi=".$idTournoi."&tab=gestionTables");

        echo 'Exception reçue : ',  $e->getMessage(), "\n";
    }
    
}


if ($_GET['action'] == "delPersonneTable") {
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    $personneTable->supprimerPersonneTable($_GET['personneTableId']);
    
    header("Location:modifierTournoi.php?idTournoi=".$_GET['tournoiId']."&tab=gestionTables");
    exit(0);
}

if ($_GET['action'] == "sendMail") {
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    $status = $personneTable->envoyerMail($_GET['personneTableId']);
    $statusParam = $status ? 'success' : 'error';
    header("Location: " . $_SERVER['HTTP_REFERER'] . "&status=$statusParam&#placementPersonneSurTerrain&tab=gestionTables");
    exit(0);
}


if (isset($_GET['addPersonne']) && $_GET['addPersonne'] == "true") {

   $nom=$_GET['nom'];
   $prenom=$_GET['prenom'];
   $mail=$_GET['mail'];
   $idTournoi = $_GET['tournoiId'];

   require 'class/personneDao.class.php';
   $personne=new PersonneDao;
   $personne->ajouterPersonne($nom,$prenom,$mail,$idTournoi);
  header("Location:  modifierTournoi.php?idTournoi=".$idTournoi."&tab=gestionTables");

}

if (isset($_GET['action']) && $_GET['action'] == "delPersonne") {

    $idPersonne=$_GET['idPersonne'];
    (int)$idTournoi = $_GET['tournoiId'];
    require 'class/personneDao.class.php';
    $personne=new PersonneDao;
    $personne->supprimerPersonne($idPersonne,$idTournoi,$userData['id']);
  header("Location:  modifierTournoi.php?idTournoi=".$idTournoi."&tab=gestionTables");
    exit();
 }
 

if (isset($_GET['addArbitre']) && $_GET['addArbitre'] == "true") {
    (int)$idTournoi = $_GET['tournoiId'];
    require 'class/arbitreDao.class.php';
    $arbitre=new arbitreDao();
    var_dump($_GET);
    $arbitre->ajouterArbitre($_GET['nomArbitre'],$_GET['tournoiId'],$_GET['clubID']);
    header("Location: modifierTournoi.php?idTournoi=".$idTournoi."&tab=arbitres");
   exit();
}

if ($_GET['delArbitre'] == true) {
    (int)$idTournoi = $_GET['tournoiId'];
    require 'class/arbitreDao.class.php';
    $arbitre=new arbitreDao();
    
    try {
        $arbitre->supprimerArbitre($_GET['arbitre_id']);
        echo "Arbitre supprimé avec succès.";
            header("Location: modifierTournoi.php?idTournoi=".$idTournoi."&tab=arbitres");


    } catch (PDOException $e) {
        // Vérifie si l'exception est une violation de contrainte d'intégrité
        if ($e->getCode() == 23000) {
            echo "Erreur : impossible de supprimer cet arbitre car il est encore associé à une planification.";
            echo "<script>setTimeout(function(){ window.location.href = '" . $_SERVER['HTTP_REFERER'] . "'; }, 5000);</script>";
        } else {
            // Affiche le message d'erreur pour toute autre exception
            echo "Erreur lors de la suppression de l'arbitre : " . $e->getMessage();
        }
    }
    
   exit();
}








if (!isset ($_POST['isArchived'])) {
$isArchived = 0;
}
else {
    $isArchived = 1;
}

if (!isset ($_POST['heureIsVisible'])) {
    $heureIsVisible = 0;
    }
    else {
        $heureIsVisible = 1;
    }

if (!isset ($_POST['isVisible'])) {
       $isVisible = 0;
        }
        else {
            $isVisible = 1;
        }
    
        if (!isset ($_POST['IsRankingView'])) {
            $IsRankingView = 0;
             }
             else {
                 $IsRankingView = 1;
             }
         


if ($_POST['idParent'] == "") {
    $isClassement = 0;
}

else {
    $isClassement = 1;
}


if ($_POST['gestionTables'] == "") {
    $gestionTables = 0;
}

else {
    $gestionTables = 1;
}


if ($_POST['gestionArbitres'] == "") {
    $gestionArbitres = 0;
}

else {
    $gestionArbitres = 1;
    
}

if ($_POST['gestionRepas'] == "") {
    $gestionRepas = 0;
}
else {
    $gestionRepas = 1;
    
}

if ($_POST['gestionVoix'] == "") {
    $gestionVoix = 0;
}
else {
    $gestionVoix = 1;
    
}

if ($_POST['gestionPartenaires'] == "") {
    $gestionPartenaires = 0;
}
else {
    $gestionPartenaires = 1;
    
}



if (isset($_POST['idArbitre'])) {
   require ('class/arbitreDao.class.php');
$arbitreDao = new arbitreDao();
$arbitreDao->modifierArbitre($_POST['idArbitre'],$_POST['nomArbitre']);
  echo "✅ Ok!";

exit;
}

if (isset($_POST['effetsSonores'])) {
    
    $tournoiDao = new tournoiDao();
    $tournoiDao->toggleEffetsSonores($_POST['idTournoi'], 1);
    
} else {
    
    $tournoiDao = new tournoiDao();
    $tournoiDao->toggleEffetsSonores($_POST['idTournoi'], 0);
}




$tempRefresh = $_POST['refreshClientTime'] * 1000;



$tournoidao = new tournoiDao();
$var = $tournoidao->modifierTournoi($_POST['idTournoi'],$_POST['nom'],$_POST['heure_debut'],$isClassement,$_POST['pasHoraire'],$isVisible,$heureIsVisible,$isArchived,$IsRankingView,$gestionTables,$gestionArbitres,$tempRefresh,$gestionRepas,$gestionPartenaires,$gestionVoix);

header("Location: " . $_SERVER['HTTP_REFERER'] ."");
?>