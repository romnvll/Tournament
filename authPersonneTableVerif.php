<?php
if (isset ($_POST['codePin'])) {
    require 'class/PersonneTableDao.class.php';
    require 'class/planificationDao.class.php';

    $key = $_POST['key'] ;
    $tournoiId = $_POST['tournoiId'];
    $tablePersonne = new PersonneTableDao();
    $infoUser = $tablePersonne->verifierCodePinEtRecupererRencontres($_POST['key'],$_POST['codePin']);
    
 
    
    $afficherCodePin = false ;
    
    $idterrain = substr($_POST['key'], -1);
    
    session_start();
    $_SESSION['idterrain'] = $idterrain;
    $_SESSION['tournoiId'] = $tournoiId;
    $_SESSION['infoUser'] = $infoUser;

   
    header("Location: vuePersonneTable.php");
   
    

    
    }

?>