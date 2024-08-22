<?php
require 'security.php';
require 'class/tournoiDao.class.php';
use chillerlan\QRCode\{QRCode, QROptions};

require_once('vendor/autoload.php');

$options = new QROptions(
  [
    'eccLevel' => QRCode::ECC_L,
    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
    'version' => 5,
  ]
);


$tournoiDao = new tournoiDao();
$tournoiDao->getTournoiById($_GET['idTournoi']);

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$domainName = $_SERVER['HTTP_HOST'];
$path = explode('/Tournament/', $_SERVER['REQUEST_URI'])[0] . '/Tournament/';

$url = $protocol . $domainName . "/index.php?id_tournoi=".$_GET['idTournoi'] ;
//echo $url;  // Cela affichera "http://172.17.12.231/Tournament/"



$qrcode = (new QRCode($options))->render($url);
?>
<!DOCTYPE html>
<html>


<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Tournoi Handball</title>
  <link rel="stylesheet" href="/css/styles.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>
<body>
<div class="container-fluid">
<div class="row justify-content-md-center">
<h4>Scanner pour savoir quand et où votre équipe doit jouer</h4>
</div>
  <div class="row">
    <div class="col-6">
  <img class="img-fluid" src='<?= $qrcode ?>' alt='QR Code'>
    </div>
    <div class="col-6">
  <img class="img-fluid" src='Qr.png' alt='QR Code'>
  </div>
  </div>
</div>
</body>
</html>