<?php
require ('security.php');
require 'class/equipeDao.class.php';
require 'class/pouleManagerDao.class.php';

var_dump ($_SESSION);
$equipeDao = new EquipeDAO();
$poulemanager = new PouleManager();
$poulemanager->getEquipesInPoule($_GET['pouleId']);

// Récupérer la liste des équipes cochées
$equipesPresentes = isset($_GET['isPresent']) ? $_GET['isPresent'] : array();

// Récupérer la liste de toutes les équipes de la poule
$equipesPoule = $poulemanager->getEquipesInPoule($_GET['pouleId']);

foreach ($equipesPoule as $equipe) {
    $equipeId = $equipe['id'];

    if (in_array($equipeId, $equipesPresentes)) {
        // L'équipe est cochée (présente)
        $equipeDao->confirmerEquipe($equipeId, "presente");
    } else {
        // L'équipe n'est pas cochée (absente)
        $equipeDao->confirmerEquipe($equipeId, "absente");
    }
}

$PouleId = $_GET['pouleId'];
$tournoi = $_SESSION['idTournoi'];
header('Location: PointerLesEquipes.php?idTournoi='.$tournoi.'&idPoule='.$PouleId.'');

?>