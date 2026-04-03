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

    <?php foreach ($audioPaths as $path) : ?>
        <?php $version = file_exists($path) ? filemtime($path) : time(); ?>
        <audio class="audio-global" src="<?= htmlspecialchars($path) ?>?v=<?= $version ?>"></audio>
    <?php endforeach; ?>

    <script>
        window.addEventListener('load', function () {
            const audios = Array.from(document.querySelectorAll('.audio-global'));
            let index = 0;

            function playNext() {
                if (index >= audios.length) {
                    window.close();
                    return;
                }
                const audio = audios[index];
                audio.currentTime = 0;

                audio.play().catch(() => {
                    index++;
                    playNext();
                });

                audio.onended = () => {
                    index++;
                    playNext();
                };

                audio.onerror = () => {
                    index++;
                    playNext();
                };
            }

            playNext();
        });
    </script>

<?php else : ?>
    <p>Aucun audio disponible.</p>
<?php endif; ?>
