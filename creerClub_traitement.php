<?php
require ('security.php');
require ('class/clubDao.class.php');
$clubDao = new ClubDAO();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nom = $_POST["nomduclub"];
    var_dump($_POST);
    $contact = $_POST["contactClub"];

    $targetDir = "logos/"; // Chemin où les logos seront stockés
    $logoPath = "";
    
    if (isset($_FILES["logo"]) && $_FILES["logo"]["error"] === UPLOAD_ERR_OK) {

        
      

        $logoTmpPath = $_FILES["logo"]["tmp_name"];
$logoFileName = uniqid() . "_" . $_FILES["logo"]["name"];
$logoPath = $targetDir . $logoFileName;


// Get the MIME type of the file
$fileMimeType = mime_content_type($logoTmpPath);

// Create an image resource based on the MIME type of the file
if ($fileMimeType === 'image/jpeg' || $fileMimeType === 'image/jpg') {
    $source = imagecreatefromjpeg($logoTmpPath);
} elseif ($fileMimeType === 'image/png') {
    $source = imagecreatefrompng($logoTmpPath);
    
} else {
    // Handle other image formats if necessary
    die("Unsupported image format.");
}




// Redimensionner le logo à 96x96
$logoResized = imagecreatetruecolor(96, 96);
//$source = imagecreatefromjpeg($logoTmpPath); // Utiliser le chemin temporaire du logo

// Redimensionnement avec conservation des proportions
imagecopyresampled($logoResized, $source, 0, 0, 0, 0, 96, 96, imagesx($source), imagesy($source));

// Sauvegarder le logo redimensionné
imagejpeg($logoResized, $logoPath);

// Libérer la mémoire
imagedestroy($source);
imagedestroy($logoResized);

    }

    // Ajouter le club à la base de données avec le chemin du logo
    
        $clubDao->ajouterClub($nom, $contact,$logoPath);
        header("Location: " . $_SERVER['HTTP_REFERER']);
    
}
?>






