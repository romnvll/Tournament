<?php
// Inclure les fichiers nécessaires et initialiser la connexion à la base de données si nécessaire

// Vérifier si l'ID de la rencontre a été fourni en tant que paramètre GET
if (isset($_GET['id'])) {
    $rencontreId = $_GET['id'];

    // Utiliser la classe RencontreDao ou la connexion à la base de données pour récupérer l'état de la rencontre
    // Inclure ou initialiser RencontreDao si nécessaire
    require_once('class/rencontreDao.class.php'); // Assurez-vous que le chemin est correct

    // Créer une instance de RencontreDao
    $rencontreDao = new RencontreDao(); // Modifiez cela en fonction de la façon dont vous avez défini votre classe

    // Appeler la méthode getEtatRencontre pour récupérer l'état de la rencontre
    $etatRencontre = $rencontreDao->getEtatRencontre($rencontreId);
    
    if ($etatRencontre !== null) {
        // Retourner l'état de la rencontre
        echo $etatRencontre;
    } else {
        // Si la rencontre n'est pas trouvée ou si l'état est indéfini, renvoyer un message d'erreur
      //  echo 'Erreur : Impossible de récupérer l\'état de la rencontre.';
    }
} else {
    // Si l'ID de la rencontre n'est pas fourni en tant que paramètre GET, renvoyer un message d'erreur
    echo 'Erreur : ID de la rencontre non fourni.';
}
?>
