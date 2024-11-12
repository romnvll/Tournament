<?php
require 'security.php';
require 'class/tournoiDao.class.php';
require 'class/terrainDao.class.php';
use chillerlan\QRCode\{QRCode, QROptions};

require_once('vendor/autoload.php');

$options = new QROptions(
  [
    'eccLevel' => QRCode::ECC_L,
    'outputType' => QRCode::OUTPUT_IMAGE_PNG ,
    
    'version' => 5,
  ]
);

$terrainDao = new TerrainDao();
$tournoiDao = new tournoiDao();


$tournoiDao->getTournoiById($_GET['idTournoi']);

// Détermine le protocole HTTP ou HTTPS
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
// Récupère le nom de domaine
$domainName = $_SERVER['HTTP_HOST'];
// Récupère le chemin de base sans inclure le fichier actuel
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

// Construit l'URL finale avec le chemin de base et "vueTerrain.php"
$url = $protocol . $domainName . $basePath . "vueTerrain.php?id_tournoi=" . urlencode($_GET['idTournoi']) . "&terrain=" . urlencode($_GET['terrain']);

// Affiche l'URL générée
echo $url;




$qrcode = (new QRCode($options))->render($url);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Tables</title>
  <link rel="stylesheet" href="/css/styles.min.css">
</head>
<body>
<h1>Scanner pour accèder aux rencontres du terrain <?= $terrainDao->AfficherTerrainParId($_GET['terrain'])['nom'];?> </h1>
<div class="container">
  <img src='<?= $qrcode ?>' alt='QR Code' width='800' height='800'>
</div>
</body>
</html>