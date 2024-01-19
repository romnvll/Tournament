<?php
require ('security.php');

require 'class/rencontreDao.class.php';
require 'class/equipeDao.class.php';
//require 'class/equipe.class.php';
//require 'class/rencontre.class.php';



$rencontreDao = new RencontreDAO();

if ((($_POST['equipeScore2'] === "") && ($_POST['equipeScore1'] === ""))) {
    $rencontreDao->modifierRencontre($_POST['IdRencontre'], null, null, (int)$_POST['terrain'], $_POST['heure'], $_POST['arbitre']);
    echo "3";
   
} elseif ($_POST['equipeScore2'] === "") {
    $rencontreDao->modifierRencontre($_POST['IdRencontre'], (int)$_POST['equipeScore1'], null, (int)$_POST['terrain'], $_POST['heure'], $_POST['arbitre']);
    echo "0";
} elseif ($_POST['equipeScore1'] === "")  {
    $rencontreDao->modifierRencontre($_POST['IdRencontre'], null, (int)$_POST['equipeScore2'], (int)$_POST['terrain'], $_POST['heure'], $_POST['arbitre']);
    echo "1";
    
} else {
    $rencontreDao->modifierRencontre($_POST['IdRencontre'], (int)$_POST['equipeScore1'], (int)$_POST['equipeScore2'], (int)$_POST['terrain'], $_POST['heure'], $_POST['arbitre']);
    echo "2";
}
//header("location: gestionRencontres.php?idTournoi=". $_POST['idTournoi'] ."&idPoule=".$_POST['idPoule']. "");
//header("Location: " . $_SERVER['HTTP_REFERER']);




?>



