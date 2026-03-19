<?php
require ('security.php');
require 'class/rencontreDao.class.php';

$rencontre = new RencontreDAO();
$idTournoi = $_GET['id_tournoi'];
$categorieId = $_GET['categorie_id'] ?? null;

// Afficher l'arbre du tournoi
$rencontre->afficherArbreTournoi($idTournoi, $categorieId);
?>