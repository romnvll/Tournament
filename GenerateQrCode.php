<?php
require 'security.php';
require 'class/tournoiDao.class.php';
require_once 'class/SponsorDAO.class.php';
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
    <title><?= $tournoiNom ?> - QR Code</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        .qr-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="container-fluid text-center">
        <button class="btn btn-primary btn-lg btn-print no-print" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimer
        </button>

        <h2 class="mt-1">
            <i class="fas fa-qrcode"></i> Scannez pour voir les horaires et lieux de vos rencontres !
        </h2>

        <p class="text-primary fw-bold fs-4">
            <i class="fas fa-trophy me-2"></i> <?= $tournoiNom ?>
            <br><span class="fs-5 text-secondary">📅 <?= $dateFormatted ?></span>
        </p>

        <div class="row mt-4 align-items-center justify-content-center">
            <div class="col-md-3 ">
                <div class="card p-3">
                    <img src="<?= $qrcode ?>" alt="QR Code" class="qr-image">
                    <p class="mt-2"><i class="fas fa-mobile-alt"></i> Scannez avec votre smartphone</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <img src="Qr.png" alt="QR Code" class="qr-image">
                    <p class="mt-2"><i class="fas fa-handshake"></i> Matchevent Pro - Votre gestionnaire de tournois</p>
                </div>
            </div>
        </div>
        <div class="row mt-4 align-items-center no-print">
            <p class="display-6 text-center text-danger">
                Pour un meilleur résultat, imprimer cette affiche en paysage.
            </p>
        </div>

<?php if (!empty($sponsors)) : ?>
    <div class="row mt-5">
            <h3 class="text-center mb-1">
                <i class="fas fa-handshake me-2"></i>
                <?= count($sponsors) === 1 ? 'Notre sponsor' : 'Nos sponsors' ?>
            </h3>
        
    <div class="d-flex flex-wrap justify-content-center gap-4">
        <?php foreach ($sponsors as $sponsor) : ?>
            <div class="card text-center p-3" style="width: 18rem;">
                <?php if (!empty($sponsor['logo'])) : ?>
                    <img src="<?= htmlspecialchars($sponsor['logo']) ?>" alt="<?= htmlspecialchars($sponsor['nom']) ?>" class="img-fluid mb-3" style="max-height: 100px; object-fit: contain;">
                <?php else : ?>
                    <div class="mb-1 text-muted" style="font-size: 3rem;">
                        <i class="fas fa-image-slash"></i>
                    </div>
                <?php endif; ?>

                <h5 class="card-title"><?= htmlspecialchars($sponsor['nom']) ?></h5>
                <p class="card-text"><?= htmlspecialchars($sponsor['description']) ?></p>

                <?php if (!empty($sponsor['lien_web'])) : ?>
                    <p class="mt-2 text-break small text-secondary">
                        🌐 <?= htmlspecialchars($sponsor['lien_web']) ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

 
<?php endif; ?>




    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
