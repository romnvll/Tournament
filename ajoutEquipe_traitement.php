<?php
require 'security.php';
require('class/equipeDao.class.php');
require ('class/rencontreDao.class.php');



//Etat des equipe ( absente ou présente)
if (isset($_POST['etat'])) {
 $id = $_POST['id'];
$etat = $_POST['etat'];
$equipeDao = new EquipeDAO();
$equipeDao->confirmerEquipe((int)$id, $etat);
// renvoyer le HTML mis à jour
if ($etat === 'presente') {
    echo '<a 
        href="javascript:void(0);"
        hx-post="ajoutEquipe_traitement.php"
        hx-vals=\'{"id": ' . $id . ', "etat": "absente"}\'
        hx-trigger="dblclick"
        hx-swap="outerHTML"
        hx-target="this"
        title="Double-cliquer pour marquer comme absente"
        style="text-decoration: none; cursor: pointer;"
    >✅</a>';
    exit;
} else {
    echo '<a 
        href="javascript:void(0);"
        hx-post="ajoutEquipe_traitement.php"
        hx-vals=\'{"id": ' . $id . ', "etat": "presente"}\'
        hx-trigger="dblclick"
        hx-swap="outerHTML"
        hx-target="this"
        title="Double-cliquer pour marquer comme présente"
        style="text-decoration: none; cursor: pointer;"
    >❌</a>';
    exit;


}

}





// Vérifier si des rencontres existent déjà pour cette catégorie et ce tournoi
$rencontreDao = new RencontreDAO();
$RencontreExist = $rencontreDao->rencontresExistByCategorieAndTournoi($_POST['Categorie'], $_POST['IdTournoi']);

if ($RencontreExist) {
    echo "Des rencontres existent déjà, impossible d'ajouter une équipe.<br>Il faut d'abord supprimer les rencontres.";
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