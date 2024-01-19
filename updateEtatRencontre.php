<?php
// Inclure le fichier contenant la classe RencontreDao
require_once('class/rencontreDao.class.php');

// Vérifier si les paramètres nécessaires sont présents dans l'URL (GET)
if (isset($_GET['etat']) && isset($_GET['heure'])) {
    // Récupérer les valeurs de l'URL
    $rencontreId = $_GET['idRencontre'];
    $nouvelEtat = $_GET['etat'];
    $heure = $_GET['heure'];
    $tournoi = $_GET['tournoiId'];
    
    // Créer une instance de RencontreDao (supposons que RencontreDao contient la méthode)
    $rencontreDao = new RencontreDao();

    // Appeler la méthode updateEtatRencontre avec les valeurs récupérées
    $updateSuccess = $rencontreDao->updateEtatRencontre($nouvelEtat,$tournoi,$heure);

    if ($updateSuccess) {
      //echo "up";
       // header("Location: " . $_SERVER['HTTP_REFERER']);
        
    } else {
        echo "Erreur lors de la mise à jour de l'état de la rencontre.";
        //echo $nouvelEtat;
    }
} else {
    echo "Veuillez fournir l'ID de la rencontre et l'état dans l'URL.";
}
?>
