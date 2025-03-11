<?php
require 'security.php';
require ('class/tournoiDao.class.php');


if ($_GET['action'] == "ajoutUserSurTable") {
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    
    try {
    $personneTable->genererUrlEtCodePin($_GET['idPersonne'],$_GET['idterrain'],$_GET['tournoiId']);
    header("Location:modifierTournoi.php?idTournoi=".$_GET['tournoiId']."#placementPersonneSurTerrain");

    }
    catch (Exception $e) {
        header("Location:modifierTournoi.php?idTournoi=".$_GET['tournoiId']."#placementPersonneSurTerrain");

        echo 'Exception reçue : ',  $e->getMessage(), "\n";
    }
    
}


if ($_GET['action'] == "delPersonneTable") {
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    $personneTable->supprimerPersonneTable($_GET['personneTableId']);
    
    header("Location:modifierTournoi.php?idTournoi=".$_GET['tournoiId']."#placementPersonneSurTerrain");
    exit(0);
}

if ($_GET['action'] == "sendMail") {
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    $status = $personneTable->envoyerMail($_GET['personneTableId']);
    $statusParam = $status ? 'success' : 'error';
    header("Location: " . $_SERVER['HTTP_REFERER'] . "&status=$statusParam&#placementPersonneSurTerrain");
    exit(0);
}


if ($_GET['addPersonne'] == "true") {

   $nom=$_GET['nom'];
   $prenom=$_GET['prenom'];
   $mail=$_GET['mail'];
   $idTournoi = $_GET['tournoiId'];

   require 'class/personneDao.class.php';
   $personne=new PersonneDao;
   $personne->ajouterPersonne($nom,$prenom,$mail,$idTournoi);
  header("Location: " . $_SERVER['HTTP_REFERER']. "#placementPersonneSurTerrain");

}

if ($_GET['action'] == "delPersonne") {

    $idPersonne=$_GET['idPersonne'];
    (int)$idTournoi = $_GET['tournoiId'];
    require 'class/personneDao.class.php';
    $personne=new PersonneDao;
    $personne->supprimerPersonne($idPersonne,$idTournoi,$userData['id']);
    header("Location: " . $_SERVER['HTTP_REFERER']. "#placementPersonneSurTerrain");
    exit();
 }
 

if ($_GET['addArbitre'] == true) {
    require 'class/arbitreDao.class.php';
    $arbitre=new arbitreDao();
    var_dump($_GET);
    $arbitre->ajouterArbitre($_GET['nomArbitre'],$_GET['tournoiId'],$_GET['clubID']);
    header("Location: " . $_SERVER['HTTP_REFERER']."#tableDesArbitres");
   exit();
}

if ($_GET['delArbitre'] == true) {
    require 'class/arbitreDao.class.php';
    $arbitre=new arbitreDao();
    try {
        $arbitre->supprimerArbitre($_GET['arbitre_id']);
        echo "Arbitre supprimé avec succès.";
        echo "<script>setTimeout(function(){ window.location.href = '" . $_SERVER['HTTP_REFERER'] . "#tableDesArbitres'; }, 0);</script>";

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







$tempRefresh = $_POST['refreshClientTime'] * 1000;



$tournoidao = new tournoiDao();
$var = $tournoidao->modifierTournoi($_POST['idTournoi'],$_POST['nom'],$_POST['heure_debut'],$isClassement,$_POST['pasHoraire'],$isVisible,$heureIsVisible,$isArchived,$IsRankingView,$gestionTables,$gestionArbitres,$tempRefresh);

header("Location: " . $_SERVER['HTTP_REFERER'] ."#option");
?>