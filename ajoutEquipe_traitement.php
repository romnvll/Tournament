<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

<?php
require 'security.php';
require('class/equipeDao.class.php');
require ('class/rencontreDao.class.php');









// Vérifier si des rencontres existent déjà pour cette catégorie et ce tournoi
$rencontreDao = new RencontreDAO();

$rencontrePlanifiee = $rencontreDao->rencontresCategorieDejaPlanifiees($_POST['Categorie'], $_POST['IdTournoi']);

if ($rencontrePlanifiee) {
    // Si des rencontres existent déjà, afficher un message d'erreur
    echo "<div class='alert alert-danger'>Des rencontres existent déjà pour cette catégorie et ce tournoi. Impossible d'ajouter une équipe.</div>";
    exit;
}



$equipeDao = new EquipeDAO();


    $nomEquipe = $_POST['nomEquipe'];
    $nomEquipe = strtoupper($nomEquipe);
    $nomEquipe = trim($nomEquipe); // Supprimer les espaces inutiles
    try {
            $equipeDao->ajouterEquipe($nomEquipe, $_POST['Categorie'], $_POST['IdTournoi'], null, $_POST['idClubs']);            } catch (Exception $e) {
                // Redirige avec message d'erreur
                header("Location: ajoutEquipe.php?error=" . urlencode($e->getMessage())."&idTournoi=".$_POST['IdTournoi']."&query=".$_POST['query']);
                exit;
            }


// Redirection
header("Location: ajoutEquipe.php?idTournoi=".$_POST['IdTournoi']."&query=".$_POST['query']);
exit;
?>