<?php

require 'vendor/autoload.php';
require 'class/rencontreDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/messageDao.class.php';
require 'class/creneauxDao.class.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

ignore_user_abort(true);

if (function_exists('set_time_limit')) {
    @set_time_limit(0);
}

/*
|--------------------------------------------------------------------------
| Paramètres
|--------------------------------------------------------------------------
*/

$idTournoi = (int) ($_GET['id_tournoi'] ?? 0);

$idPoule = isset($_GET['idPoule'])
    ? (int) $_GET['idPoule']
    : null;

$idEquipe = isset($_GET['id_equipe'])
    ? (int) $_GET['id_equipe']
    : null;

$idCategorie = isset($_GET['idCategorie'])
    ? (int) $_GET['idCategorie']
    : null;


if (!$idTournoi) {
    http_response_code(400);
    exit;
}

/*
|--------------------------------------------------------------------------
| DAO
|--------------------------------------------------------------------------
*/

$rencontreDao = new RencontreDAO();
$poulemanager = new PouleManager();
$messageDao   = new MessageDAO();
$creneauxDao  = new creneauxDao();

/*
|--------------------------------------------------------------------------
| Envoi d'un événement SSE
|--------------------------------------------------------------------------
*/

function sendEvent(string $event, $data): void
{
    echo "event: {$event}\n";
    echo 'data: ' . json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) . "\n\n";

    if (ob_get_level() > 0) {
        @ob_flush();
    }

    flush();
}

/*
|--------------------------------------------------------------------------
| Hash précédents
|--------------------------------------------------------------------------
*/

$hashes = [
    'score'   => null,
    'ranking' => null,
    'finale'  => null,
    'bell'    => null,
    'creneau' => null,
    'classement_final' => null,
];

/*
|--------------------------------------------------------------------------
| Durée maximale de connexion
|--------------------------------------------------------------------------
*/

$start = time();
$maxDuration = 90;

/*
|--------------------------------------------------------------------------
| Boucle SSE
|--------------------------------------------------------------------------
*/

while (time() - $start < $maxDuration) {

    /*
     * Si le navigateur a fermé la connexion
     */
    if (connection_aborted()) {
        break;
    }

    /*
    |--------------------------------------------------------------------------
    | RENCONTRES CLASSIQUES
    |--------------------------------------------------------------------------
    */

    if ($idPoule) {

        /*
         * On récupère la poule
         */
        $poule = $poulemanager->getPouleById($idPoule);

        if ($poule && isset($poule['is_classement']) && $poule['is_classement'] == 3) {

            /*
             * Rencontre de type 3
             *
             * Cette partie correspond à ton ancienne logique.
             */
            $rencontres = $rencontreDao->getRencontreByPoule(
                $idPoule,
                3,
                'index',
                false
            );

        } else {

            /*
             * Rencontres classiques
             */
            $rencontres = $rencontreDao->getRencontreByPoule(
                $idPoule,
                1,
                'index',
                true
            );
        }

    } else {

        /*
         * Vue par équipe
         */
        $rencontres = $rencontreDao->afficherRencontreByTournoiByEquipe(
            $idTournoi,
            $idEquipe
        );
    }

    /*
     * Détection d'un changement dans les rencontres classiques
     */
    $hash = md5(json_encode($rencontres));

    if ($hash !== $hashes['score']) {

        $hashes['score'] = $hash;

        sendEvent('score_update', $rencontres);
    }




    if ($idCategorie) {
    // Utiliser getRencontresPhasesFinales, pas getRencontreByCategorie
    $rencontresFinale = $rencontreDao->getRencontreByCategorie(
     $idCategorie,    
    $idTournoi,       
        3,
        'index',
    );

   

    $hash = md5(json_encode($rencontresFinale));

    if ($hash !== $hashes['finale']) {
        $hashes['finale'] = $hash;
        sendEvent('finale_update', $rencontresFinale);
    }
}


    /*
    |--------------------------------------------------------------------------
    | CLASSEMENT
    |--------------------------------------------------------------------------
    */

    if ($idPoule) {     

            $classement = $rencontreDao->GetResultatDesPoules(
                $idPoule,
                1
            );
       

        /*
         * Détection d'un changement dans le classement
         */
        $hash = md5(json_encode($classement));

        if ($hash !== $hashes['ranking']) {

            $hashes['ranking'] = $hash;

            sendEvent('ranking_update', $classement);
        }
    }

    if ($idCategorie) {

        $classement = $poulemanager->getClassementFinal($idTournoi,
            $idCategorie,
            
        );
var_dump($classement);
        /*
         * Détection d'un changement dans le classement
         */
        $hash = md5(json_encode($classement));

        if ($hash !== $hashes['classement_final']) {

            $hashes['classement_final'] = $hash;

            sendEvent('classement_final_update', $classement);
        }
    }   


    /*
    |--------------------------------------------------------------------------
    | MESSAGES NON LUS
    |--------------------------------------------------------------------------
    */

    if ($idEquipe) {

        $visiteurId = $_COOKIE['visiteur_id'] ?? null;

        $nbNonLus = $visiteurId
            ? $messageDao->compterMessagesNonLusParEquipe(
                $idEquipe,
                $visiteurId
            )
            : 0;

        $hash = md5((string) $nbNonLus);

        if ($hash !== $hashes['bell']) {

            $hashes['bell'] = $hash;

            sendEvent(
                'bell_update',
                [
                    'nbMessagesNonLus' => $nbNonLus
                ]
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CRÉNEAU EN COURS / SUIVANT
    |--------------------------------------------------------------------------
    */

    $timers = $creneauxDao->getCreneauEnCoursEtSuivant(
        $idTournoi
    );

    $hash = md5(json_encode($timers));

    if ($hash !== $hashes['creneau']) {

        $hashes['creneau'] = $hash;

        sendEvent('creneau_update', $timers);
    }


    /*
    |--------------------------------------------------------------------------
    | Petite pause avant la prochaine vérification
    |--------------------------------------------------------------------------
    */

    sleep(30);
}