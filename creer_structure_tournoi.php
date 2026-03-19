<?php
require ('security.php');
require 'class/rencontreDao.class.php';

$rencontre = new RencontreDAO();
$idTournoi = $_GET['id_tournoi'];
$nombreEquipes = $_POST['nombre_equipes'] ?? 16;

$resultat = $rencontre->creerRencontresVidesPhaseFinale($idTournoi, $nombreEquipes);

if ($resultat['success']) {
    echo "<div class='alert alert-success'>{$resultat['message']}</div>";
    header("Location: arbre_tournoi.php?id_tournoi=$idTournoi");
} else {
    echo "<div class='alert alert-danger'>Erreur</div>";
}
?>