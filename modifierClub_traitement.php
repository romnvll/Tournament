<?php
require ('class/clubDao.class.php');



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES["logo"]) && $_FILES["logo"]["error"] == 0) {
        $logo = $_FILES["logo"];
        $fileName = $logo["name"];
        $fileTmpName = $logo["tmp_name"];
        $fileType = $logo["type"];
        
        // Vérifiez le type du fichier
        if ($fileType == "image/jpeg" || $fileType == "image/png") {
            // Redimensionnez l'image
            if ($fileType == "image/jpeg") {
                $srcImage = imagecreatefromjpeg($fileTmpName);
            } else {
                $srcImage = imagecreatefrompng($fileTmpName);
            }

            $dstImage = imagecreatetruecolor(96, 96);
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, 96, 96, imagesx($srcImage), imagesy($srcImage));

            // Sauvegardez l'image redimensionnée
            $uploadDirectory = "logos/";
            $uploadPath = $uploadDirectory . basename($fileName);

            if ($fileType == "image/jpeg") {
                imagejpeg($dstImage, $uploadPath);
            } else {
                imagepng($dstImage, $uploadPath);
            }

            imagedestroy($srcImage);
            imagedestroy($dstImage);

            echo "Le fichier a été uploadé et redimensionné avec succès.";
            echo $uploadPath;
        } else {
            echo "Format de fichier non supporté. Seuls les formats JPEG et PNG sont acceptés.";
        }
    } else {
        echo "Erreur lors de l'upload : " . $_FILES["logo"]["error"];
    }

    $clubdao = new ClubDAO();
    $clubdao->updateClub($_POST['idclub'],$_POST['nomduclub'],null,null,$_POST['contactClub'],$uploadPath);


}

$referer = $_SERVER['HTTP_REFERER'];
header("Location: $referer");
?>