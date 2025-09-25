<?php
require_once 'security.php';
require_once 'vendor/autoload.php';
require_once 'class/creneauxDao.class.php';
require_once 'class/arbitreDao.class.php';

$idCreneau = isset($_GET['idCreneau']) ? (int)$_GET['idCreneau'] : 0;
if ($idCreneau <= 0) {
    die("ID créneau invalide.");
}

$audioCreneaux = new creneauxDao();

$audioPaths = [];
foreach ($audioCreneaux->getAudiosPourCreneau($idCreneau) as $audio) {
    
    foreach (['terrain_audio', 'equipe1_audio', 'equipe2_audio', 'arbitre_audio'] as $key) {
        if (!empty($audio[$key])) {
            $audioPaths[] = $audio[$key];
        }
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
                    // Tous les audios sont joués, on ferme la fenêtre
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
    <p>Aucun audio disponible pour ce créneau.</p>
<?php endif; ?>
