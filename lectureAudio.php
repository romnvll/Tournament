<?php
require_once 'security.php';
require_once 'vendor/autoload.php';
require_once 'class/creneauxDao.class.php';
require_once 'class/arbitreDao.class.php';
require_once 'class/utilisateurDao.class.php';

$utilisateurDao = new utilisateurDao();
$SonEffetUtilisateurDebut = $utilisateurDao->getEffetSonoreByUserId($userData['id'], 'debut')['effetsSonoreDebut'];
$SonEffetUtilisateurFin = $utilisateurDao->getEffetSonoreByUserId($userData['id'], 'fin')['effetsSonoreFin'];


$audioPaths = [];

/* -------------------------
   🔊 MODE 1 : AUDIO PAR CRENEAU
---------------------------- */
if (isset($_GET['idCreneau'])) {

    $idCreneau = (int)$_GET['idCreneau'];
    if ($idCreneau <= 0) {
        die("ID créneau invalide.");
    }

    $audioCreneaux = new creneauxDao();

    foreach ($audioCreneaux->getAudiosPourCreneau($idCreneau) as $audio) {
        foreach (['terrain_audio', 'equipe1_audio', 'equipe2_audio', 'arbitre_audio'] as $key) {
            if (!empty($audio[$key])) {
                $audioPaths[] = $audio[$key];
            }
        }
    }
    
}

/* -------------------------
   🔊 MODE 2 : EFFETS SONORES GENERIQUES
---------------------------- */

if (isset($_GET['type'])) {

    $type = $_GET['type'];

    if ($type === 'debut') {
        
        $audioPaths[] = "$SonEffetUtilisateurDebut"; // mets ton chemin exact
    }
    elseif ($type === 'fin') {
        $audioPaths[] = "$SonEffetUtilisateurFin";   // idem
    }
}

?>
<?php if (!empty($audioPaths)) : ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brackito - Son</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            width: 280px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(102,126,234,0.5); }
            50%       { transform: scale(1.08); box-shadow: 0 0 0 12px rgba(102,126,234,0); }
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
            background: linear-gradient(to top, #667eea, #764ba2);
            animation: bounce 1s ease-in-out infinite;
        }

        .bar:nth-child(1) { animation-delay: 0s;    height: 20px; }
        .bar:nth-child(2) { animation-delay: 0.15s; height: 35px; }
        .bar:nth-child(3) { animation-delay: 0.3s;  height: 25px; }
        .bar:nth-child(4) { animation-delay: 0.45s; height: 38px; }
        .bar:nth-child(5) { animation-delay: 0.6s;  height: 18px; }

        @keyframes bounce {
            0%, 100% { transform: scaleY(0.4); }
            50%       { transform: scaleY(1); }
        }

        .track-info {
            font-size: 0.78rem;
            color: #6b7280;
            background: #f9fafb;
            border-radius: 10px;
            padding: 8px 12px;
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
            <!-- icône haut-parleur SVG -->
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3A4.5 4.5 0 0 0 14 7.97v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
            </svg>
        </div>

        <div class="title">Brackito</div>
        <div class="subtitle">Lecture en cours...</div>

        <div class="bars">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>

        <div class="track-info">
            <span id="trackLabel">Préparation...</span><br>
            <span class="track-count" id="trackCount"></span>
        </div>
    </div>

    <?php foreach ($audioPaths as $path) : ?>
        <?php $version = file_exists($path) ? filemtime($path) : time(); ?>
        <audio class="audio-global" src="<?= htmlspecialchars($path) ?>?v=<?= $version ?>"></audio>
    <?php endforeach; ?>

    <script>
        window.addEventListener('load', function () {
            const audios = Array.from(document.querySelectorAll('.audio-global'));
            const total = audios.length;
            let index = 0;

            function playNext() {
                if (index >= audios.length) {
                    document.getElementById('trackLabel').textContent = 'Terminé';
                    document.getElementById('trackCount').textContent = '';
                    document.querySelector('.bars').style.animation = 'none';
                    document.querySelectorAll('.bar').forEach(b => b.style.animationPlayState = 'paused');
                    document.querySelector('.icon-wrap').style.animation = 'none';
                    setTimeout(() => window.close(), 800);
                    return;
                }

                document.getElementById('trackLabel').textContent = 'Lecture en cours...';
                document.getElementById('trackCount').textContent = (index + 1) + ' / ' + total;

                const audio = audios[index];
                audio.currentTime = 0;

                audio.play().catch(() => { index++; playNext(); });
                audio.onended = () => { index++; playNext(); };
                audio.onerror = () => { index++; playNext(); };
            }

            playNext();
        });
    </script>

</body>
</html>

<?php else : ?>
    <p style="font-family:sans-serif; padding:20px; color:#6b7280;">Aucun audio disponible.</p>
<?php endif; ?>
