<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,

]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('gestionDesTours.twig');

$tournois = new tournoiDao();


if (!isset($_GET['id_tournoi'])) {
    $listedestournois = $tournois->afficherLesTournois();
    $table = null;
} else {


    require('class/rencontreDao.class.php');
    $rencontreDao = new RencontreDAO();
    $IdTournoi = $_GET['id_tournoi'];
    $rencontres = $rencontreDao->getRencontreByTournoi($IdTournoi);


    usort($rencontres, function ($a, $b) {
        if ($a['equipe1_poule_nom'] == $b['equipe1_poule_nom']) {
            return $a['rencontre_id'] - $b['rencontre_id'];
        }
        return strcmp($a['equipe1_poule_nom'], $b['equipe1_poule_nom']);
    });





    // Déterminer le nombre d'équipes dans chaque poule
    $equipesParPoule = [];
    foreach ($rencontres as $rencontre) {
        $poule = $rencontre['equipe1_poule_nom'];
        if (!isset($equipesParPoule[$poule])) {
            $equipesParPoule[$poule] = [];
        }
        $equipesParPoule[$poule][$rencontre['equipe1_id']] = true;
        $equipesParPoule[$poule][$rencontre['equipe2_id']] = true;
    }


    $tourCompteur = 1;
    $poulePrecedente = null;
    $rencontreCompteur = 0;





    foreach ($rencontres as $rencontre) {
        if ($poulePrecedente !== $rencontre['equipe1_poule_nom']) {
            $tourCompteur = 1;
            $rencontreCompteur = 0;
            $poulePrecedente = $rencontre['equipe1_poule_nom'];
        }

        $tourNom = "tour" . $tourCompteur . "-" . $rencontre['equipe1_poule_nom'];

        if (!isset($rencontresOrganisees[$tourNom])) {
            $rencontresOrganisees[$tourNom] = [];
        }

        $rencontresOrganisees[$tourNom][] = $rencontre;
        $rencontreCompteur++;

        if ($rencontre['equipe1_poule_nom'] !== $rencontre['equipe2_poule_nom']) {
            $tourCompteur++;
            $rencontreCompteur = 0;
            continue; // Continuez avec la prochaine rencontre
        }

        // Si nous avons parcouru le nombre de rencontres pour un tour
        if ($rencontreCompteur == count($equipesParPoule[$poulePrecedente]) / 2) {
            $tourCompteur++;
            $rencontreCompteur = 0;
        }
    }






    $tournois = new tournoiDao();
    (int)$numTerrains = $tournois->getNbTerrainsById($_GET['id_tournoi']);
    $starttime = $tournois->afficherLesTournois();

    foreach ($starttime as $tournoi) {
        if ($tournoi['id'] == $_GET['id_tournoi']) {
            $starttime = $tournoi['heure_debut'];
        }
    }


    $lestournois = $tournois->afficherLesTournois();

    foreach ($lestournois as $tournoi) {
        if ($tournoi['id'] == $_GET['id_tournoi']) {
            $pashoraire = $tournoi['pasHoraire'];
        }
    }




    $endtime = $tournois->afficherLesTournois();
    //var_dump($endtime);
    foreach ($endtime as $time) {
        if ($time['id'] == $_GET['id_tournoi']) {
            $endtime = $time['heure_fin'];
        }
    }





    function generateTable($rencontresOrganisees, $numTerrains, $startTime, $intervalInMinutes, $stopTime, $redirect = false)
    {
        $slots = [];

        $currentSlot = new DateTime($startTime);
        $finalSlot = new DateTime($stopTime);

        while ($currentSlot < $finalSlot) {
            $slots[] = $currentSlot->format('H:i');
            $currentSlot->add(new DateInterval('PT' . $intervalInMinutes . 'M'));
        }

        // Construire une carte de rendez-vous pour un accès facile
        $rencontreMap = [];

        foreach ($rencontresOrganisees as $key => $rencontres) {
            //$tourPart = explode('-', $key)[0]; // Prend la première partie de la clé "tourX-TournoiSurHerbe-U17F-1"

            foreach ($rencontres as $rencontre) {
                if (!empty($rencontre['num_terrain']) && !empty($rencontre['heure_rencontre'])) {
                    //var_dump($rencontre);
                    $tourPart = "tour" . $rencontre['tour'];
                    //  var_dump($tourPart);
                    $mapKey = $rencontre['num_terrain'] . '-' . substr($rencontre['heure_rencontre'], 0, 5); // enlève les secondes de l'heure
                    $rencontreMap[$mapKey] = $tourPart . "-" . $rencontre['equipe1_categorie'] . "-ID" . $rencontre['rencontre_id'];
                }
            }
        }



        // Header

        $table = '<form id="rencontreForm" action="repartionDesToursTraitement.php" method="post">';

        if ($redirect) {
            $table .= '<input type="hidden" value="' . $redirect . ' " name="redirect">';
        }
        $table .= '<table class="table table-bordered">';

        $table .= '<thead>';
        $table .= '<tr>';
        $table .= '<th class="text-center">Créneau</th>';

        for ($i = 1; $i <= $numTerrains; $i++) {
         
       
                $table .= '<th class="text-center">Terrain ' . $i .  '</th>';
           
        }

        $table .= '</tr>';
        $table .= '</thead>';
        $table .= '<tbody>';



        // Content
        foreach ($slots as $slot) {
            $table .= '<tr>';
            $table .= '<td>' . $slot . '</td>';

            for ($i = 1; $i <= $numTerrains; $i++) {
                $selectedValue = isset($rencontreMap[$i . '-' . $slot]) ? $rencontreMap[$i . '-' . $slot] : '';

                $table .= '<td>';
                $table .= '<select class="form-control filterable" data-show-subtext="true" data-live-search="true" name="rencontre[' . $i . '][' . $slot . ']" onchange="updateOptions(this)">';

                $table .= '<option value="default">Choisir une rencontre</option>';

                foreach ($rencontresOrganisees as $key => $rencontres) {
                    foreach ($rencontres as $rencontre) {

                        // $value = explode('-', $key)[0] . "-" . $rencontre['equipe1_categorie'] . "-ID" . $rencontre['rencontre_id'];
                        $value = "tour" . $rencontre['tour'] . "-" . $rencontre['equipe1_categorie'] . "-ID" . $rencontre['rencontre_id'];

                        $selected = $value === $selectedValue ? 'selected' : '';

                        // Vérification si c'est une rencontre de classement
                        if ($rencontre['isClassement'] == 0) {
                            // $displayText = $value;
                            $displayText = "tour" . $rencontre['tour'] . "-" . $rencontre['equipe1_categorie'] . '-' . $rencontre['equipe1_nom'] . '-' . $rencontre['equipe2_nom'];
                        } elseif (isset($rencontre['isClassement']) && $rencontre['isClassement'] == 1) {
                            $displayText = "PF-tour" . $rencontre['tour'] . "-" . $rencontre['equipe1_categorie'] . '-' . $rencontre['equipe1_nom'] . '-' . $rencontre['equipe2_nom'];
                        }


                        $table .= '<option ' . $selected . ' value="' . $value . '">' . $displayText . '</option>';
                    }
                }

                $table .= '</select>';
                if (!empty($selectedValue)) {
                    $table .= ' <a href="deleteRencontre.php?idRencontre=' . explode("-ID", $selectedValue)[1] . '"><img src="img/trash.svg" alt="Supprimer la programmation"></a>'; // Ajoute l'icône de poubelle à côté du select
                }

                $table .= '</td>';
            }

            $table .= '</tr>';
        }


        $table .= '</tbody>';
        $table .= '</table>';
        $table .= '</table>';
        $table .= '<button type="submit" class="btn btn-primary submit">Sauvegarder</button>'; // Ajoutez des classes CSS si vous utilisez un framework comme Bootstrap.
        $table .= '</form>';

        return $table;
    }



    if (isset($_GET['redirect'])) { {

            $id_tournoi = $_GET['id_tournoi'];
            $redirect_url = $_GET['redirect'];
            $idPoule = $_GET['idPoule'];

            // Reconstruire l'URL avec idPoule=xx
            $new_url = $redirect_url . '&idPoule=' . $idPoule;


            $table = generateTable($rencontresOrganisees, $numTerrains, $starttime, $pashoraire, $endtime, $new_url);
        }
    } else {
        $table = generateTable($rencontresOrganisees, $numTerrains, $starttime, $pashoraire, $endtime);
    }




    // N'oubliez pas de lier également le script JavaScript pour la fonction `updateOptions`.




}




echo $template->render([
    'email' => $_COOKIE['email'],
    'pageEnCours' => 'GestionDesRencontres',
    //'afficherRencontreByIdTournoi' =>  $recontreDao->afficherRencontreByIdTournoi($_GET['idTournoi']),
    //'afficherLesTournois' => $tournoi->getAllTournoi(),
    'genererPlacement' => $table,
    'ListeDesTournois' => $listedestournois,



]);
