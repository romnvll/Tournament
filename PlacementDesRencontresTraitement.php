<?php
require 'security.php';

if (isset($_GET['action'])) {

        if (($_GET['action']=="addCreneau")) {
            require 'class/evenementsDao.class.php';
            $evenement = new EvenementDAO();
            $evenement->ajoutCreneau($_GET['idTournoi']);
        }

        if (($_GET['action'] == "addEvent")) {
            require 'class/evenementsDao.class.php';
            $evenement = new EvenementDAO();

            $events = $_GET['events'];
            // Diviser la chaîne en utilisant le délimiteur '-'
            $parts = explode('-', $events);
            $idtournoi=$parts[0];
            $type = $parts[1]; 
            $creneau = $parts[2]; 
            $terrain = $parts[3]; 
            
            //il faut recuperer l'id du creneau
            $idCreneau = $evenement->recupererIdCreneau($idtournoi,$terrain);

            //il faut recuper l'id du terrain
            require 'class/terrainDao.class.php';
            $terrainDao = new TerrainDao();

           
            $idterrain = $terrainDao->AfficherIdTerrain($idtournoi,$terrain);

            if ($type == "pause") {
            $evenement->ajouterEvenement($idCreneau,null,$idterrain,$idtournoi);
            }
            else {
                $evenement->ajouterEvenement($idCreneau,$type,$idterrain,$idtournoi);
                

            }

        }

}


?>