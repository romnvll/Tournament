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

// Détermine le protocole HTTP ou HTTPS
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
// Récupère le nom de domaine
$domainName = $_SERVER['HTTP_HOST'];
// Récupère le chemin de base en excluant la page actuelle
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

// Construit l'URL finale avec le chemin de base et "index.php"
$url = $protocol . $domainName . $basePath . "index.php?id_tournoi=" . urlencode($_GET['idTournoi']);

//echo $url;


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
<div class="container-fluid">
<div class="row justify-content-md-center">


<h4 class="text-center">Scanner pour savoir quand et où votre équipe doit jouer</h4>
</div>
<div class="row justify-content-md-center">

<?php
// Définir la locale en français
// Assurez-vous que l'extension Intl est activée sur votre serveur
$dateDebut = $tournoiDao->getTournoiById($_GET['idTournoi'])['dateDebut'];
$date = new DateTime($dateDebut);

// Créer un formatteur pour la date
$formatter = new IntlDateFormatter(
    'fr_FR', // Locale
    IntlDateFormatter::LONG, // Type de format
    IntlDateFormatter::NONE, // Pas d'heure
    null, // Fuseau horaire par défaut
    IntlDateFormatter::GREGORIAN, // Calendrier
    'dd MMMM yyyy' // Format
);

$dateFormatted = $formatter->format($date);

echo "<p class=\"text-primary text-center fs-1\">" . $tournoiDao->getTournoiById($_GET['idTournoi'])['nom'] . " le " . $dateFormatted . "</p>";


?>
</div>
<button class="btn btn-primary no-print mb-3" onclick="window.print()">Imprimer</button>
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