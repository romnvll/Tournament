
<?php

require_once 'security.php';
require_once 'vendor/autoload.php';
require_once 'class/creneauxDao.class.php';
require_once 'class/arbitreDao.class.php';
require_once 'class/utilisateurDao.class.php';

$messages = [];



/**
 * Ajoute une annonce. Si $audio est vide, le JS utilisera la synthèse vocale.
 */
function ajouterAnnonce(array &$messages, string $text, ?string $audio = null): void
{
    $audio = trim((string) $audio);

    $messages[] = [
        'text'  => $text,
        'audio' => $audio !== '' ? $audio : null,
    ];
}

if (isset($_GET['idCreneau'])) {

    $idCreneau = (int) $_GET['idCreneau'];

    if ($idCreneau <= 0) {
        die("ID créneau invalide.");
    }

    $creneauxDao = new creneauxDao();

    foreach ($creneauxDao->getAudiosPourCreneau($idCreneau) as $audio) {

        $rencontreValide =
            !empty($audio['rencontre_id']) &&
            $audio['equipe1_isPresent'] == 1 &&
            $audio['equipe2_isPresent'] == 1;

        if (!$rencontreValide) {
            continue;
        }

// TERRAIN
$terrainNom   = trim((string) ($audio['terrain_nom'] ?? ''));
$terrainAudio = trim((string) ($audio['terrain_audio'] ?? ''));

if ($terrainAudio !== '' || $terrainNom !== '') {
    ajouterAnnonce(
        $messages,
        trim("Se prépare sur le terrain " . $terrainNom),
        $terrainAudio
    );
}

// ÉQUIPE 1
$equipe1Nom   = trim((string) ($audio['equipe1_nom'] ?? ''));
$equipe1Audio = trim((string) ($audio['equipe1_audio'] ?? ''));

if ($equipe1Audio !== '' || $equipe1Nom !== '') {
    ajouterAnnonce($messages, $equipe1Nom, $equipe1Audio);
}

// ÉQUIPE 2
$equipe2Nom   = trim((string) ($audio['equipe2_nom'] ?? ''));
$equipe2Audio = trim((string) ($audio['equipe2_audio'] ?? ''));

if ($equipe2Audio !== '' || $equipe2Nom !== '') {
    ajouterAnnonce($messages, $equipe2Nom, $equipe2Audio);
}
        
       
            // ARBITRE
$arbitreNom   = trim((string) ($audio['arbitre_nom'] ?? ''));
$arbitreClub  = trim((string) ($audio['arbitre_club_nom'] ?? ''));
$arbitreAudio = trim((string) ($audio['arbitre_audio'] ?? ''));

if ($arbitreAudio !== '') {

    // Le fichier contient déjà la phrase complète : on le joue seul.
    // Le texte sert uniquement d'affichage et de repli si le fichier est injouable.
    $texteSecours = "Arbitré par " . $arbitreNom;

    if ($arbitreClub !== '') {
        $texteSecours .= " du club de " . $arbitreClub;
    }

    ajouterAnnonce($messages, trim($texteSecours), $arbitreAudio);

} elseif ($arbitreNom !== '') {

    // Nom d'arbitre sans audio : tout en synthèse vocale
    $texte = "Arbitré par " . $arbitreNom;

    if ($arbitreClub !== '') {
        $texte .= " du club de " . $arbitreClub;
    }

    ajouterAnnonce($messages, $texte);

} elseif ($arbitreClub !== '') {

    // Pas de nom d'arbitre : on annonce uniquement le club
    ajouterAnnonce($messages, "Arbitré par " . $arbitreClub);
}


var_dump($audio);


    }
}

/*
|--------------------------------------------------------------------------
| MODE 2 : ANNONCES GÉNÉRIQUES
|--------------------------------------------------------------------------
*/

if (isset($_GET['type'])) {

    $type = $_GET['type'];

    if ($type === 'debut') {
    ajouterAnnonce($messages, "Le tournoi va commencer. Bonne chance à toutes les équipes.");
}
elseif ($type === 'fin') {
    ajouterAnnonce($messages, "Le tournoi est terminé. Merci à toutes les équipes.");
}
}


/*
|--------------------------------------------------------------------------
| Aucun message
|--------------------------------------------------------------------------
*/

if (empty($messages)) {

    ?>
    <p style="
        font-family:sans-serif;
        padding:20px;
        color:#6b7280;
    ">
        Aucune annonce disponible.
    </p>
    <?php

    exit;
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Brackito - Annonce</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {

            font-family: 'Segoe UI', sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #667eea 0%,
                    #764ba2 100%
                );

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .player {

            background: white;

            border-radius: 24px;

            padding: 32px 28px;

            text-align: center;

            width: 300px;

            box-shadow:
                0 20px 60px rgba(0,0,0,0.3);
        }


        .icon-wrap {

            width: 80px;
            height: 80px;

            border-radius: 50%;

            background:
                linear-gradient(
                    135deg,
                    #667eea,
                    #764ba2
                );

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 20px;

            animation:
                pulse 1.5s ease-in-out infinite;
        }


        @keyframes pulse {

            0%, 100% {

                transform: scale(1);

                box-shadow:
                    0 0 0 0
                    rgba(102,126,234,0.5);
            }

            50% {

                transform: scale(1.08);

                box-shadow:
                    0 0 0 12px
                    rgba(102,126,234,0);
            }
        }


        .icon-wrap svg {

            width: 36px;
            height: 36px;

            fill: white;
        }


        .title {

            font-size: 1.1rem;

            font-weight: 700;

            color: #1f2937;

            margin-bottom: 6px;
        }


        .subtitle {

            font-size: 0.8rem;

            color: #9ca3af;

            margin-bottom: 24px;
        }


        .bars {

            display: flex;

            align-items: flex-end;

            justify-content: center;

            gap: 5px;

            height: 40px;

            margin-bottom: 20px;
        }


        .bar {

            width: 6px;

            border-radius: 3px;

            background:
                linear-gradient(
                    to top,
                    #667eea,
                    #764ba2
                );

            animation:
                bounce 1s ease-in-out infinite;
        }


        .bar:nth-child(1) {
            animation-delay: 0s;
            height: 20px;
        }

        .bar:nth-child(2) {
            animation-delay: 0.15s;
            height: 35px;
        }

        .bar:nth-child(3) {
            animation-delay: 0.3s;
            height: 25px;
        }

        .bar:nth-child(4) {
            animation-delay: 0.45s;
            height: 38px;
        }

        .bar:nth-child(5) {
            animation-delay: 0.6s;
            height: 18px;
        }


        @keyframes bounce {

            0%, 100% {
                transform: scaleY(0.4);
            }

            50% {
                transform: scaleY(1);
            }
        }


        .track-info {

            font-size: 0.78rem;

            color: #6b7280;

            background: #f9fafb;

            border-radius: 10px;

            padding: 10px 12px;

            line-height: 1.5;
        }


        .track-count {

            font-weight: 700;

            color: #4f46e5;
        }

    </style>

</head>


<body>

<div class="player">

    <div class="icon-wrap">

        <svg
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
        >

            <path d="
                M3 9v6h4l5 5V4L7 9H3zm13.5
                3A4.5 4.5 0 0 0 14 7.97v8.05
                c1.48-.73 2.5-2.25 2.5-4.02z
                M14 3.23v2.06c2.89.86 5 3.54
                5 6.71s-2.11 5.85-5 6.71v2.06
                c4.01-.91 7-4.49 7-8.77
                s-2.99-7.86-7-8.77z"
            />

        </svg>

    </div>


    <div class="title">
        Brackito
    </div>


    <div class="subtitle">
        Annonce en cours...
    </div>


    <div class="bars">

        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>

    </div>


    <div class="track-info">

        <span id="trackLabel">
            Préparation...
        </span>

        <br>

        <span
            class="track-count"
            id="trackCount"
        ></span>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Messages envoyés par PHP
|--------------------------------------------------------------------------
*/

const messages = <?= json_encode(
    $messages,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;


/*
|--------------------------------------------------------------------------
| Synthèse vocale
|--------------------------------------------------------------------------
*/

const synth = window.speechSynthesis;

let voices = [];

let index = 0;


/*
|--------------------------------------------------------------------------
| Chargement des voix
|--------------------------------------------------------------------------
*/

function loadVoices() {

    voices = synth.getVoices();

}


/*
|--------------------------------------------------------------------------
| Recherche d'une voix française
|--------------------------------------------------------------------------
*/

function getFrenchVoice() {

    /*
     * Priorité à une voix française de France.
     */

    let voice = voices.find(
        voice =>
            voice.lang &&
            voice.lang.toLowerCase() === 'fr-fr'
    );

    if (voice) {
        return voice;
    }


    /*
     * Sinon, première voix française disponible.
     */

    voice = voices.find(
        voice =>
            voice.lang &&
            voice.lang.toLowerCase().startsWith('fr')
    );

    return voice || null;
}


/*
|--------------------------------------------------------------------------
| Lecture des annonces
|--------------------------------------------------------------------------
*/

function finish() {
    document.getElementById('trackLabel').textContent = 'Terminé';
    document.getElementById('trackCount').textContent = '';

    document.querySelectorAll('.bar').forEach(
        bar => bar.style.animationPlayState = 'paused'
    );
    document.querySelector('.icon-wrap').style.animation = 'none';

    setTimeout(() => window.close(), 800);
}

function nextItem() {
    index++;
    setTimeout(playNext, 500);
}

/* Lecture par synthèse vocale */
function speak(text, onDone) {
    const utterance = new SpeechSynthesisUtterance(text);
    const frenchVoice = getFrenchVoice();

    if (frenchVoice) {
        utterance.voice = frenchVoice;
        utterance.lang  = frenchVoice.lang;
    } else {
        utterance.lang = 'fr-FR';
    }

    utterance.rate  = 0.90;
    utterance.pitch = 1;

    utterance.onend   = onDone;
    utterance.onerror = function (event) {
        console.error('Erreur synthèse vocale :', event);
        onDone();
    };

    synth.speak(utterance);
}

/* Lecture d'un fichier audio, avec repli sur la synthèse en cas de problème */
function playAudioFile(url, text, onDone) {
    const audio = new Audio(url);
    let fallbackDone = false;

    const fallback = function () {
        if (fallbackDone) return;
        fallbackDone = true;
        console.warn('Audio indisponible, repli sur la synthèse :', url);
        speak(text, onDone);
    };

    audio.onended = onDone;
    audio.onerror = fallback;

    audio.play().catch(fallback);
}

function playNext() {

    if (index >= messages.length) {
        finish();
        return;
    }

    const item = messages[index];

    document.getElementById('trackLabel').textContent = item.text;
    document.getElementById('trackCount').textContent =
        (index + 1) + ' / ' + messages.length;

    if (item.audio) {
        playAudioFile(item.audio, item.text, nextItem);
    } else {
        speak(item.text, nextItem);
    }
}


/*
|--------------------------------------------------------------------------
| Chargement initial des voix
|--------------------------------------------------------------------------
*/

loadVoices();


if (
    speechSynthesis.onvoiceschanged !== undefined
) {

    speechSynthesis.onvoiceschanged =
        function () {

            loadVoices();

        };
}


/*
|--------------------------------------------------------------------------
| Démarrage
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'load',
    function () {

        setTimeout(
            playNext,
            500
        );

    }
);

</script>

</body>

</html>

