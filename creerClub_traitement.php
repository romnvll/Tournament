<?php
require ('security.php');
require ('class/clubDao.class.php');
$clubDao = new ClubDAO();

define('DEFAULT_LOGO_URL', 'https://brackito.net/logos/Logo.png');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom = $_POST["nomduclub"];
    $contact = $_POST["contactClub"];

    $targetDir = "logos/";
    $logoPath = "";

    $hasUploadedLogo = isset($_FILES["logo"])
        && $_FILES["logo"]["error"] === UPLOAD_ERR_OK
        && $_FILES["logo"]["size"] > 0;

    if ($hasUploadedLogo) {
        $logoTmpPath = $_FILES["logo"]["tmp_name"];
    } else {
        // Aucun logo choisi : on récupère le logo par défaut
        $logoTmpPath = tempnam(sys_get_temp_dir(), 'logo');
        $defaultContent = @file_get_contents(DEFAULT_LOGO_URL);
        if ($defaultContent === false) {
            die("Impossible de récupérer le logo par défaut.");
        }
        file_put_contents($logoTmpPath, $defaultContent);
    }

    $fileMimeType = mime_content_type($logoTmpPath);

    switch ($fileMimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $source = imagecreatefromjpeg($logoTmpPath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($logoTmpPath);
            break;
        default:
            die("Unsupported image format.");
    }

    $logoFileName = uniqid() . "_logo.jpg";
    $logoPath = $targetDir . $logoFileName;

    // Redimensionnement "contain" : on garde les proportions et on centre
    // sur un fond blanc pour éviter toute déformation / disparition du logo
    $targetSize = 96;
    $srcWidth  = imagesx($source);
    $srcHeight = imagesy($source);
    $ratio     = min($targetSize / $srcWidth, $targetSize / $srcHeight);
    $newWidth  = (int) round($srcWidth  * $ratio);
    $newHeight = (int) round($srcHeight * $ratio);
    $offsetX   = (int) round(($targetSize - $newWidth)  / 2);
    $offsetY   = (int) round(($targetSize - $newHeight) / 2);

    $logoResized = imagecreatetruecolor($targetSize, $targetSize);
    $white = imagecolorallocate($logoResized, 255, 255, 255);
    imagefill($logoResized, 0, 0, $white);

    imagecopyresampled(
        $logoResized, $source,
        $offsetX, $offsetY, 0, 0,
        $newWidth, $newHeight, $srcWidth, $srcHeight
    );

    imagejpeg($logoResized, $logoPath, 90);

    imagedestroy($source);
    imagedestroy($logoResized);

    if (!$hasUploadedLogo) {
        unlink($logoTmpPath); // nettoyage du fichier temporaire
    }

    $clubDao->ajouterClub($nom, $logoPath, $_POST['typeSport'], $userData['id']);
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}