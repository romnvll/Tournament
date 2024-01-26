<?php
require ('security.php');
require ('class/equipeDao.class.php');
require ('class/rencontreDao.class.php');
require ('class/tournoiDao.class.php');
$IdTournoi = $_GET['id_tournoi'];
$equipeDao = new EquipeDAO();

$rencontreDao = new RencontreDAO();
$rencontres = $rencontreDao->getRencontreByTournoi($IdTournoi);

$tournoi = new tournoiDao();
var_dump($tournoi->getTournoiById($IdTournoi));

//var_dump($tournoi->getTournoiById(1)['heure_debut']); 
echo "<pre>";
//print_r($rencontres);
echo "</pre>";
$pasHoraire = $tournoi->getTournoiById($IdTournoi)['pasHoraire'];
$heure_debut = $tournoi->getTournoiById($IdTournoi)['heure_debut'];
$heure_debut = DateTime::createFromFormat('H:i', $heure_debut);
$heure_debut = $heure_debut->format('H:i');
echo $heure_debut;
foreach ($rencontres as $rencontre) {
 
   $rencontreDao->modifierRencontre($rencontre['rencontre_id'],null,null,1,$heure_debut,null,$IdTournoi);

   $heure_debut = $heure_debut->add(new DateInterval('PT'.$pasHoraire.'M'));

    echo "<pre>";
print_r($rencontre);
echo "</pre>";

}










// Afficher le résultat
echo "<pre>";
print_r($rencontresPlanifiees);
echo "</pre>";


foreach ($rencontres as $rencontre) {
  //  $rencontreDao->modifierRencontre($rencontre['rencontre_id'],null,null,$rencontre['num_terrain'],$rencontre['heure_rencontre'],null,$IdTournoi);
}
