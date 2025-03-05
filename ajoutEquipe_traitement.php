<?php
require 'security.php';
require('class/equipeDao.class.php');
require ('class/rencontreDao.class.php');





// Vérifier si des rencontres existent déjà pour cette catégorie et ce tournoi
$rencontreDao = new RencontreDAO();
$RencontreExist = $rencontreDao->rencontresExistByCategorieAndTournoi($_POST['Categorie'], $_POST['IdTournoi']);

if ($RencontreExist) {
    echo "Des rencontres existent déjà, impossible d'ajouter une équipe.<br>Il faut d'abord supprimer les rencontres.";
    exit;
}

$equipeDao = new EquipeDAO();
$listeEquipes = preg_split('/[\n,]+/', $_POST['nomEquipes']); // Séparation par virgule et retour à la ligne

foreach ($listeEquipes as $nomEquipe) {
    $nomEquipe = trim($nomEquipe); // Supprimer les espaces inutiles
    if (!empty($nomEquipe)) {
        $equipeDao->ajouterEquipe($nomEquipe, $_POST['Categorie'], $_POST['IdTournoi'], null, $_POST['idClubs']);
    }
}

// Redirection
header("Location: ajoutEquipe.php?idTournoi=".$_POST['IdTournoi']);
exit;
?>