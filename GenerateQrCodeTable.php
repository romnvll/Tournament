<?php
require 'security.php';
require 'class/tournoiDao.class.php';
require 'class/terrainDao.class.php';
use chillerlan\QRCode\{QRCode, QROptions};

require_once('vendor/autoload.php');

$options = new QROptions(
  [
    'eccLevel' => QRCode::ECC_L,
    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
    'version' => 5,
  ]
);

$terrainDao = new TerrainDao();
$tournoiDao = new tournoiDao();


$tournoiDao->getTournoiById($_GET['idTournoi']);

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$domainName = $_SERVER['HTTP_HOST'];
$path = explode('/Tournament/', $_SERVER['REQUEST_URI'])[0] . '/Tournament/';

$url = $protocol . $domainName . $path ."vueTerrain.php?id_tournoi=".$_GET['idTournoi']."&terrain=".$_GET['terrain'] ;
//echo $url;  // Cela affichera "http://172.17.12.231/Tournament/"



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