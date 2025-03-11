<?php
require 'security.php';
require 'class/tournoiDao.class.php';

use chillerlan\QRCode\{QRCode, QROptions};

require_once('vendor/autoload.php');

$options = new QROptions([
    'eccLevel'   => QRCode::ECC_L,
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    'version'    => 5,
]);

$tournoiDao = new tournoiDao();
$tournoiDao->getTournoiById($_GET['idTournoi']);

// Détermine le protocole HTTP ou HTTPS
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$domainName = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
$url = $protocol . $domainName . $basePath . "affichagePlanningArbitre.php?idTournoi=" . urlencode($_GET['idTournoi']);

$qrcode = (new QRCode($options))->render($url);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès aux Rencontres</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin-top: 50px;
        }
        .btn-print {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="container text-center">
        <button class="btn btn-primary btn-lg btn-print no-print" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimer
        </button>

        <h1 class="mt-4">
            <i class="fas fa-qrcode"></i> Scannez pour accéder à vos rencontres à arbitrer
        </h1>

        <div class="row mt-4 align-items-center">
            <div class="col-md-6">
                <div class="card p-3">
                    <img src="<?= $qrcode ?>" alt="QR Code" class="img-fluid rounded">
                    <p class="mt-2"><i class="fas fa-mobile-alt"></i> Scannez avec votre smartphone</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-3">
                    <img src="logos/matcheventPro.webp" alt="Logo" class="img-fluid rounded">
                    <p class="mt-2"><i class="fas fa-handshake"></i> Matchevent Pro - Votre gestionnaire de tournois</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
