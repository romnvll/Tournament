<?php
require 'security.php';
require ('class/tournoiDao.class.php');

//var_dump($_POST);

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









$tournoidao = new tournoiDao();
$var = $tournoidao->modifierTournoi($_POST['idTournoi'],$_POST['nom'],$_POST['nb_terrains'],$_POST['heure_debut'],$isClassement,$idparent,$_POST['heure_fin'],$_POST['pasHoraire'],$isVisible,$heureIsVisible,$isArchived,$IsRankingView);

header("Location: " . $_SERVER['HTTP_REFERER']);
?>