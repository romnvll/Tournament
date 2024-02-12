<?php
session_start();

require ('class/rencontreDao.class.php');
$rencontreDao = new RencontreDAO();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Supposons que vous ayez un élément select avec le nom "terrain1"
    if (isset($_POST['rencontre'])) {
        foreach ($_POST['rencontre'] as $terrain => $slots) {
            foreach ($slots as $heure => $value) {
                $idRencontre = explode("-ID", $value)[1]; // Pour obtenir l'ID après "-ID"
                
                if ($idRencontre != "") {
                    //echo "Terrain: $terrain, Heure: $heure, ID de la rencontre: $idRencontre<br>";
                    $rencontreDao->modifierRencontre($idRencontre,9999,9999,$terrain,$heure,"dontTouch",$_SESSION['id_tournoi']);

                }
            }
        }
    }
    
}

if (isset ($_POST['redirect'])) {
   echo $_POST['redirect'];
    $referer = $_POST['redirect']; 
}
else {
$referer = $_SERVER['HTTP_REFERER'];
}

header("Location: $referer");
//echo $referer;
?>
