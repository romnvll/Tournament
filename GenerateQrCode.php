<?php
require 'security.php';
require 'class/tournoiDao.class.php';
require_once 'class/SponsorDAO.class.php';
require_once 'Lang/lang.php';
require_once 'class/clubDao.class.php';

$clubDao = new ClubDAO();

use chillerlan\QRCode\{QRCode, QROptions};

require_once('vendor/autoload.php');

$options = new QROptions([
    'eccLevel'   => QRCode::ECC_L,
    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
    'version'    => 5,
]);

$sponsorDao = new SponsorDAO();
$sponsors = $sponsorDao->getSponsorsActifParClub($userData['id']);

$tournoiDao = new tournoiDao();
$tournoiDao->getTournoiById($_GET['idTournoi']);

// Détermine le protocole HTTP ou HTTPS
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$domainName = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
$url = $protocol . $domainName . $basePath . "index.php?id_tournoi=" . urlencode($_GET['idTournoi']);

$qrcode = (new QRCode($options))->render($url);

// Formatage de la date en français
$dateDebut = $tournoiDao->getTournoiById($_GET['idTournoi'])['dateDebut'];
$date = new DateTime($dateDebut);
$formatter = new IntlDateFormatter(
    'fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE, null, IntlDateFormatter::GREGORIAN, 'dd MMMM yyyy'
);
$dateFormatted = $formatter->format($date);

$tournoiNom = htmlspecialchars($tournoiDao->getTournoiById($_GET['idTournoi'])['nom']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $tournoiNom ?> - <?= t('qrCode') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1000px;
            margin-top: 20px;
        }
        .btn-print {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }
        .qr-image {
            max-width: 100%;
            height: 200px;
            border-radius: 10px;
        }

        /* Impression optimisée */
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            body {
                zoom: 85%;
            }
            .no-print {
                display: none !important;
            }
            .container {
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .card {
                box-shadow: none;
                border: 1px solid #ccc;
                padding: 5px !important;
                margin: 3px !important;
            }
           
            /* Sponsors compactés */
            .sponsors {
                 display: flex !important;
        flex-wrap: wrap !important;
        justify-content: center !important; /* centre les sponsors */
        gap: 6px !important;
                
            }
            .sponsors .card {
                max-width: 180px;
                font-size: 0.8rem;
                
            }
            .sponsors img {
                max-height: 60px !important;
                object-fit: contain;
            }
            .sponsors h5,
            .sponsors p {
                margin: 2px 0 !important;
                line-height: 1.2;
                
            }
             
    .sponsors .card {
        max-width: 150px;
        padding: 3px !important;
        margin: 2px !important;
        font-size: 0.7rem;
    }


    
        }
    </style>
</head>
<body>

    <div class="container-fluid text-center">
        <button class="btn btn-primary btn-lg btn-print no-print" onclick="window.print()">
            <i class="fas fa-print"></i> <?= t('imprimer') ?>
        </button>

        <img src="logos/Logo.png" alt="Logo Brackito" style="height:60px;" class="me-3 rounded shadow-sm">

        <p class="mb-0 text-dark fw-semibold fst-italic">
            🎯 Simplifiez, organisez et gagnez du temps avec Brackito — l’outil tout-en-un pour vos compétitions sportives.
        </p>

        <div>
            <h2 class="mb-1 text-primary fw-bold">
                <i class="fas fa-qrcode me-2"></i> 
                <?= t('ScannezPourVoirLesHorairesEtLieuxDeVosRencontres') ?>
            </h2>
        </div>

        <p class="text-primary fw-bold fs-4 mt-3">
            <i class="fas fa-trophy me-2"></i> <?= $tournoiNom ?>
            <br>
            <span class="fs-5 text-secondary">📅 <?= $dateFormatted ?></span>
        </p>

     <div class="row mt-4 align-items-center justify-content-center">
                  <!-- Clubs participants 
    <div class="col-12 mb-4">
        <h5 class="mb-3">Clubs participants</h5>
        <div class="d-flex flex-wrap justify-content-center gap-4">
            <?php foreach ($clubDao->clubsParticipatingInTournoi($_GET['idTournoi']) as $club): ?>
                <div class="text-center">
                    <img src="<?= htmlspecialchars($club['logo']) ?>" 
                         alt="<?= htmlspecialchars($club['nom']) ?>" 
                         style="max-height:60px; object-fit:contain;">
                    <p class="mt-2 mb-0"><?= htmlspecialchars($club['nom']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>-->

    <!-- QR Code -->
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <img src="<?= $qrcode ?>" alt="QR Code" style="max-height:250px; ">
            <p class="mt-2">
                <i class="fas fa-mobile-alt"></i> <?= t('ScannezAvecVotreSmartphone') ?>
            </p>
        </div>
    </div>
                </div>


        <div class="row mt-4 align-items-center no-print">
            <p class="display-6 text-center text-danger">
                <?= t('impressionPaysage') ?>
            </p>
        </div>

        <?php if (!empty($sponsors)) : ?>
            <div class="row mt-1">
                <h3 class="text-center mb-1">
                    <i class="fas fa-handshake me-2"></i>
                    <?= count($sponsors) === 1 ? 'Notre sponsor' : 'Nos sponsors' ?>
                </h3>
                <div class="sponsors d-flex flex-wrap justify-content-center gap-4">
                 <?php foreach ($sponsors as $sponsor) : ?>
    <div class="card text-center p-2">
        <?php if (!empty($sponsor['logo'])) : ?>
            <img src="<?= htmlspecialchars($sponsor['logo']) ?>" 
                 alt="<?= htmlspecialchars($sponsor['nom']) ?>" 
                style="max-height:60px; object-fit:contain;">
        <?php else : ?>
            <div class="mb-1 text-muted" style="font-size: 2rem;">
                <i class="fas fa-image-slash"></i>
            </div>
        <?php endif; ?>

        <h5 class="card-title mb-0"><?= htmlspecialchars($sponsor['nom']) ?></h5>
        <p class="card-text small"><?= htmlspecialchars($sponsor['description']) ?></p>

        <div class="text-center">
            <?php if (!empty($sponsor['lien_web'])) : ?>
                <?php 
                    // Générer le QR code du site web
                    $qrcodeLien = (new QRCode($options))->render($sponsor['lien_web']); 
                ?>
               
                    <img src="<?= $qrcodeLien ?>" alt="QR Code Site Web" class="qr-image" height="300">
                    <p class="mt-1 small text-secondary text-start">🌐 <?= parse_url($sponsor['lien_web'], PHP_URL_HOST) ?></p>
                
            <?php endif; ?>

            <?php if (!empty($sponsor['telephone'])) : ?>
                <p class="mt-1 text-break small text-secondary text-start">
                    📞 <?= htmlspecialchars($sponsor['telephone']) ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($sponsor['adresse'])) : ?>
                <p class="mt-1 text-break small text-secondary text-start">
                    📍 <?= htmlspecialchars($sponsor['adresse']) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php endif; ?>
                </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
