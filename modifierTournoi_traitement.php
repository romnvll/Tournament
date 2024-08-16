<?php
require 'security.php';
require ('class/tournoiDao.class.php');


if ($_GET['action'] == "ajoutUserSurTable") {
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    
    try {
    $personneTable->genererUrlEtCodePin($_GET['idPersonne'],$_GET['idterrain'],$_GET['tournoiId']);
    header("Location: " . $_SERVER['HTTP_REFERER']);
    }
    catch (Exception $e) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        echo 'Exception reçue : ',  $e->getMessage(), "\n";
    }
    
}


if ($_GET['action'] == "delPersonneTable") {
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    $personneTable->supprimerPersonneTable($_GET['personneTableId']);
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit(0);
}

if ($_GET['action'] == "sendMail"){
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    $personneTable->envoyerMail($_GET['personneTableId']);
    header("Location: " . $_SERVER['HTTP_REFERER']);
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
   header("Location: " . $_SERVER['HTTP_REFERER']);

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











$tournoidao = new tournoiDao();
$var = $tournoidao->modifierTournoi($_POST['idTournoi'],$_POST['nom'],$_POST['heure_debut'],$isClassement,$_POST['pasHoraire'],$isVisible,$heureIsVisible,$isArchived,$IsRankingView,$gestionTables,$gestionArbitres);

header("Location: " . $_SERVER['HTTP_REFERER']);
?>