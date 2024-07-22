<?php
require ('security.php');
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/equipeDao.class.php';
require 'class/rencontreDao.class.php';

$tournoi = new tournoiDao();
$tournoi = $tournoi->getTournoiById($_POST['TournoiId']);
$equipe = new EquipeDAO();
$rencontre = new RencontreDAO();

//creation des poules:

echo "ici->" . var_dump($categorie) . "<-";


$categorie = $_POST["categorie"];
    $tournoiId = $_POST["TournoiId"];
    $equipeIds = $_POST["equipeId"];
    $numPoules = $_POST["NumPoule"];
    
    $NomTournoi = $tournoi['nom'];
    
    
    $poulesUniques = [];
    foreach ($equipeIds as $key => $equipeIdList) {
        $numPoule = $numPoules[$key];
        foreach ($equipeIdList as $equipeId) {
            $poulesUniques[$key][] = [
                'equipeId' => $equipeId,
                'numPoule' => $numPoule
            ];
        }
    }
    $poules = new PouleManager();
    foreach ($poulesUniques as $key => $equipes) {
        // Concaténer la catégorie et le numéro de poule pour obtenir le nom souhaité
        $nomPoule = $key;
        $nomPoule = $NomTournoi."-".$nomPoule;
        
        


        if (!$poules->pouleExists($nomPoule, $_POST['TournoiId'])) {
            
            // Si la poule n'existe pas, la créer
            
            $poules->createPoule($nomPoule, $_POST['TournoiId'], $_POST['categorie']);
            
           // $rencontre->createRencontreByPoule($poules->getIdFromNom($nomPoule),$_POST['TournoiId']);

        }


     // ce n'est peut etre pas une bonne idée de creer les rencontres en meme temps que la création d'une poule en cas de retouche?
      // $rencontre->createRencontreByPoule($poules->getIdFromNom($nomPoule),$_POST['TournoiId']);

       
    }




//il faut recuperer l'id des poules avec le nom du tournoi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
$NomDePoule = $tournoi['nom']."-".$_POST['categorie'];


// Créez une instance de la classe PouleManager (assurez-vous que cela est fait correctement)
$pouleManager = new PouleManager();


// Utilisez la méthode getIdFromNom pour obtenir l'ID en tant qu'entier



$equipeIds = $_POST["equipeId"];
//inscription des équipes dans les poules
foreach ($equipeIds as $equipeid => $key) {
    
  foreach ($key as $idequipe) {
  $nomEquipe = $tournoi['nom']."-".$equipeid;
echo $idequipe ."->" .$equipeid ."<br>";
$idPoule = $pouleManager->getIdFromNom($nomEquipe);
$equipe->modifierEquipeIdPoule($idPoule,$idequipe);

//on creer les rencontres
//$rencontre->createRencontreByPoule($_GET['idPoule'],$_GET['idTournoi']);
        }
    
    
   
}

}

//header("location: gestionPoules.php?id_tournoi=".$tournoiId."&NbrEquipeParPoule=".$_POST['nbrEquipePoule']);
