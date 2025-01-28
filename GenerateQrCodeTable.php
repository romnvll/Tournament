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





$qrcode = (new QRCode($options))->render($url);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Tables</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
      @media print {
      body {
        margin: 0;
        padding: 0;
        background-color: white; /* Fond blanc pour éviter les problèmes */
      }
      .container {
        width: 100%;
        max-width: 100%;
      }
      .qr-code {
        max-width: 80%; /* QR code plus grand */
        height: auto;
      }
      .logo {
        max-width: 30%; /* Logo plus petit */
        height: auto;
        margin-top: 1rem;
      }
      h1 {
        font-size: 1.5rem;
        text-align: center;
      }
      /* Masquer le bouton lors de l'impression */
      .no-print {
        display: none !important;
      }
    }
  </style>
 
</head>
<body>
  <div class="container text-center">
  <button class="btn btn-primary no-print mb-3" onclick="window.print()">Imprimer</button>

    <h1>Scanner pour accéder aux rencontres du terrain <?= $terrainDao->AfficherTerrainParId($_GET['terrain'])['nom']; ?></h1>
    <div class="row">
      <div class="col-6">
        <img src='<?= $qrcode ?>' alt='QR Code' class="img-fluid img-thumbnail"  width="800" height="500" />
      </div>
      <div class="col-6">
        <img src="logos/matcheventPro.webp" alt="Logo" class="img-fluid img-thumbnail "  width="800" height="500" />
      </div>
    </div>
  </div>
</body>
</html>
