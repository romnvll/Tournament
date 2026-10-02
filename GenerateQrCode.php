<?php
require 'security.php';
require 'class/tournoiDao.class.php';
require_once 'class/SponsorDAO.class.php';
require_once 'Lang/lang.php';
require_once 'class/clubDao.class.php';
require_once 'class/utilisateurDao.class.php';
require_once 'vendor/autoload.php';

use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Data\QRMatrix;


/*
 * ============================================================
 * FONCTIONS UTILITAIRES
 * ============================================================
 */

/**
 * Convertit le chemin d'un logo (relatif au script ou au site)
 * en chemin lisible par PHP. Retourne null si introuvable.
 */
function resoudreCheminLogo(?string $chemin): ?string
{
    if (!$chemin) {
        return null;
    }

    // URL distante
    if (preg_match('#^https?://#i', $chemin)) {
        return $chemin;
    }

    // Chemin relatif au script
    if (is_file($chemin)) {
        return $chemin;
    }

    // Chemin relatif à la racine du site (/uploads/xxx.png)
    $absolu = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/' . ltrim($chemin, '/');

    return is_file($absolu) ? $absolu : null;
}


/**
 * Retourne jusqu'à $nombre couleurs dominantes (#rrggbb) d'un logo,
 * suffisamment différentes les unes des autres.
 * Tableau vide si l'image est illisible (ex. SVG non supporté par GD)
 * ou ne contient aucune couleur exploitable.
 */
function couleursDominantesLogo(?string $chemin, int $nombre = 2): array
{
    $chemin = resoudreCheminLogo($chemin);

    if (!$chemin || !extension_loaded('gd')) {
        return [];
    }

    $contenu = @file_get_contents($chemin);
    if ($contenu === false) {
        return [];
    }

    $source = @imagecreatefromstring($contenu);
    if (!$source) {
        return [];
    }

    // Réduction pour accélérer l'analyse et lisser les couleurs
    $taille = 50;
    $petit = imagecreatetruecolor($taille, $taille);
    imagealphablending($petit, false);
    imagesavealpha($petit, true);
    imagefill($petit, 0, 0, imagecolorallocatealpha($petit, 0, 0, 0, 127));
    imagecopyresampled(
        $petit, $source, 0, 0, 0, 0,
        $taille, $taille, imagesx($source), imagesy($source)
    );

    $groupes = [];

    for ($x = 0; $x < $taille; $x++) {
        for ($y = 0; $y < $taille; $y++) {
            $rgba  = imagecolorat($petit, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;

            if ($alpha > 60) {
                continue; // pixel (quasi) transparent
            }

            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            if ($r > 235 && $g > 235 && $b > 235) {
                continue; // blanc / fond
            }

            // Les couleurs saturées comptent davantage que les gris
            $max = max($r, $g, $b);
            $min = min($r, $g, $b);
            $saturation = $max > 0 ? ($max - $min) / $max : 0;
            $poids = 1 + $saturation * 3;

            // Regroupement par couleurs proches (16 niveaux par canal)
            $cle = ($r >> 4) . '-' . ($g >> 4) . '-' . ($b >> 4);

            if (!isset($groupes[$cle])) {
                $groupes[$cle] = ['poids' => 0, 'r' => 0, 'g' => 0, 'b' => 0, 'n' => 0];
            }

            $groupes[$cle]['poids'] += $poids;
            $groupes[$cle]['r'] += $r;
            $groupes[$cle]['g'] += $g;
            $groupes[$cle]['b'] += $b;
            $groupes[$cle]['n']++;
        }
    }

    imagedestroy($source);
    imagedestroy($petit);

    if (empty($groupes)) {
        return [];
    }

    uasort($groupes, fn($a, $b) => $b['poids'] <=> $a['poids']);

    // On retient les groupes les plus présents, en écartant ceux
    // trop proches d'une couleur déjà retenue
    $retenues = [];

    foreach ($groupes as $groupe) {
        $r = (int) round($groupe['r'] / $groupe['n']);
        $g = (int) round($groupe['g'] / $groupe['n']);
        $b = (int) round($groupe['b'] / $groupe['n']);

        $tropProche = false;
        foreach ($retenues as [$r2, $g2, $b2]) {
            $distance = sqrt(($r - $r2) ** 2 + ($g - $g2) ** 2 + ($b - $b2) ** 2);
            if ($distance < 90) {
                $tropProche = true;
                break;
            }
        }

        if (!$tropProche) {
            $retenues[] = [$r, $g, $b];
        }

        if (count($retenues) >= $nombre) {
            break;
        }
    }

    return array_map(
        fn($c) => sprintf('#%02x%02x%02x', $c[0], $c[1], $c[2]),
        $retenues
    );
}


/**
 * Génère une seconde couleur pour le dégradé quand le logo n'en
 * fournit qu'une : même couleur décalée de 45° sur le cercle
 * chromatique, assez foncée pour rester scannable.
 */
function secondeCouleurDegrade(string $hex): string
{
    $r = hexdec(substr($hex, 1, 2)) / 255;
    $g = hexdec(substr($hex, 3, 2)) / 255;
    $b = hexdec(substr($hex, 5, 2)) / 255;

    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $d   = $max - $min;
    $l   = ($max + $min) / 2;

    // Noir / gris : simple éclaircissement
    if ($d < 0.05) {
        return '#555555';
    }

    $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

    if ($max === $r) {
        $h = ($g - $b) / $d;
        if ($h < 0) {
            $h += 6;
        }
    } elseif ($max === $g) {
        $h = ($b - $r) / $d + 2;
    } else {
        $h = ($r - $g) / $d + 4;
    }

    $h = fmod($h * 60 + 45, 360);
    $s = max($s, 0.55);
    $l = min(max($l * 0.9, 0.22), 0.38);

    // HSL -> RGB
    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;

    [$r1, $g1, $b1] = match ((int) floor($h / 60)) {
        0       => [$c, $x, 0],
        1       => [$x, $c, 0],
        2       => [0, $c, $x],
        3       => [0, $x, $c],
        4       => [$x, 0, $c],
        default => [$c, 0, $x],
    };

    return sprintf(
        '#%02x%02x%02x',
        (int) round(($r1 + $m) * 255),
        (int) round(($g1 + $m) * 255),
        (int) round(($b1 + $m) * 255)
    );
}


/**
 * Valide la couleur et l'assombrit si elle est trop claire,
 * afin que le QR code reste scannable (surtout à l'impression).
 */
function couleurQrCode(?string $hex, string $defaut = '#000000'): string
{
    if (!$hex || !preg_match('/^#([0-9a-f]{6})$/i', $hex)) {
        return $defaut;
    }

    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));

    // Luminance perçue (0 = noir, 255 = blanc)
    $luminance = 0.299 * $r + 0.587 * $g + 0.114 * $b;

    if ($luminance > 140) {
        $facteur = 0.55;

        return sprintf(
            '#%02x%02x%02x',
            (int) ($r * $facteur),
            (int) ($g * $facteur),
            (int) ($b * $facteur)
        );
    }

    return strtolower($hex);
}


/**
 * Crée les options d'un QR code au style commun de la page :
 * modules ronds, repères d'angle carrés, dégradé, fond transparent.
 * $extra permet de surcharger ou d'ajouter des options.
 */
function creerOptionsQr(string $remplissage, string $degradeSvg, array $extra = []): QROptions
{
    return new QROptions(array_merge([
        'eccLevel'     => QRCode::ECC_H,
        'outputType'   => QRCode::OUTPUT_MARKUP_SVG,
        'outputBase64' => true,

        // Modules arrondis
        'drawCircularModules' => true,
        'circleRadius'        => 0.45,   // 0.5 = les cercles se touchent
        'drawLightModules'    => false,  // fond transparent

        'keepAsSquare' => [
            QRMatrix::M_FINDER_DARK,
            QRMatrix::M_FINDER_DOT,
            QRMatrix::M_ALIGNMENT_DARK,
        ],

        // Dégradé
        'svgDefs'      => $degradeSvg,
        'moduleValues' => [
            QRMatrix::M_DATA_DARK      => $remplissage,
            QRMatrix::M_FINDER_DARK    => $remplissage,
            QRMatrix::M_FINDER_DOT     => $remplissage,
            QRMatrix::M_ALIGNMENT_DARK => $remplissage,
            QRMatrix::M_TIMING_DARK    => $remplissage,
            QRMatrix::M_FORMAT_DARK    => $remplissage,
            QRMatrix::M_VERSION_DARK   => $remplissage,
            QRMatrix::M_DARKMODULE     => $remplissage,
        ],
    ], $extra));
}


/**
 * Choisit la plus petite version de QR code (niveau H) pouvant
 * contenir $donnees, et calcule l'espace central à réserver au logo
 * (environ 20 % de la largeur, en nombre impair de modules).
 *
 * Retourne null si les données sont trop longues : le QR code est
 * alors généré sans logo.
 */
function configLogoQr(string $donnees): ?array
{
    // Capacité en octets par version, niveau de correction H
    $capacites = [5 => 44, 6 => 58, 7 => 64, 8 => 84, 9 => 98, 10 => 119];
    $longueur  = strlen($donnees);

    foreach ($capacites as $version => $capacite) {
        if ($longueur <= $capacite) {
            $taille      = 17 + 4 * $version;
            $modulesLogo = (int) round($taille * 0.2);

            if ($modulesLogo % 2 === 0) {
                $modulesLogo++;
            }

            return [
                'version'     => $version,
                'modulesLogo' => $modulesLogo,
                'pourcentage' => round($modulesLogo / $taille * 100, 2),
            ];
        }
    }

    return null;
}


/*
 * ============================================================
 * DONNÉES
 * ============================================================
 */
$idTournoi = $_GET['idTournoi'];

$clubDao        = new ClubDAO();
$sponsorDao     = new SponsorDAO();
$tournoiDao     = new tournoiDao();
$utilisateurDao = new UtilisateurDAO();

$sponsors = $sponsorDao->getSponsorsActifParClub($userData['id']);
$tournoi  = $tournoiDao->getTournoiById($idTournoi);

$clubs      = $clubDao->clubsParticipatingInTournoi($idTournoi);
$totalClubs = count($clubs);
$midpoint   = (int) ceil($totalClubs / 2);
$clubsLeft  = array_slice($clubs, 0, $midpoint);
$clubsRight = array_slice($clubs, $midpoint);

// Club organisateur
$clubOrganisateur = $utilisateurDao->getClubFromIdUser($userData['id']);
$logoClub         = $clubOrganisateur ? $clubOrganisateur['club_logo'] : null;
$nomClub          = $clubOrganisateur ? $clubOrganisateur['club_nom'] : 'Brackito';


/*
 * ============================================================
 * COULEURS DU QR CODE (DÉGRADÉ BRACKITO)
 * ============================================================
 *
 * Pour imposer les couleurs exactes de Brackito, renseignez
 * deux codes hexadécimaux ci-dessous, par exemple :
 *     $couleursBrackito = ['#0d6efd', '#6610f2'];
 *
 * Tant que le tableau est vide, les deux couleurs dominantes
 * sont extraites automatiquement du logo Brackito.
 * Les couleurs sont assombries si besoin (contraste du scan).
 */
$couleursBrackito = [];

if (count($couleursBrackito) < 2) {
    $couleursBrackito = couleursDominantesLogo(__DIR__ . '/logos/Logo.png', 2);
}

$couleurDebut = couleurQrCode($couleursBrackito[0] ?? null);

$couleurFin = isset($couleursBrackito[1])
    ? couleurQrCode($couleursBrackito[1])
    : secondeCouleurDegrade($couleurDebut);

// Dégradé diagonal (haut gauche -> bas droite) sur tout le QR code.
// Pour un dégradé horizontal : x2="100%" y2="0%"
// Pour un dégradé vertical   : x2="0%"   y2="100%"
$degradeSvg = '<linearGradient id="qrDegrade" gradientUnits="userSpaceOnUse"'
    . ' x1="0%" y1="0%" x2="100%" y2="100%">'
    . '<stop offset="0%" stop-color="' . $couleurDebut . '"/>'
    . '<stop offset="100%" stop-color="' . $couleurFin . '"/>'
    . '</linearGradient>';

$remplissage = 'url(#qrDegrade)';


/*
 * ============================================================
 * QR CODE DU TOURNOI
 * ============================================================
 *
 * - ECC H : 30 % de correction d'erreur (nécessaire avec un logo)
 * - modules ronds, dégradé aux couleurs de Brackito
 * - repères d'angle carrés (meilleure lisibilité au scan)
 * - espace central de 9x9 modules réservé au logo
 */
$optionsTournoi = creerOptionsQr($remplissage, $degradeSvg, [
    'version'         => 7,
    'addLogoSpace'    => !empty($logoClub),
    'logoSpaceWidth'  => 9,
    'logoSpaceHeight' => 9,
]);


// Protocole, domaine et URL du tournoi
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ? 'https://'
    : 'http://';

$domainName = $_SERVER['HTTP_HOST'];
$basePath   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

$url = $protocol
    . $domainName
    . $basePath
    . 'index.php?id_tournoi='
    . urlencode($idTournoi);

$qrcode = (new QRCode($optionsTournoi))->render($url);


// Date en français
$formatter = new IntlDateFormatter(
    'fr_FR',
    IntlDateFormatter::LONG,
    IntlDateFormatter::NONE,
    null,
    IntlDateFormatter::GREGORIAN,
    'dd MMMM yyyy'
);

$dateFormatted = $formatter->format(new DateTime($tournoi['dateDebut']));
$tournoiNom    = htmlspecialchars($tournoi['nom']);

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $tournoiNom ?> - <?= t('qrCode') ?></title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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


        /* ====== QR CODE DU TOURNOI AVEC LOGO ====== */

        .qr-container {
            position: relative;
            display: inline-block;
            line-height: 0;
            background: #fff; /* fond blanc : les modules clairs sont transparents */
        }

        .qr-logo-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);

            /* 9 modules sur 45 = 20 % du QR code */
            width: 20%;
            height: 20%;

            background: white;
            padding: 4px;
            border-radius: 8px;

            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }

        .qr-logo {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }


        /* ====== QR CODES DES SPONSORS ====== */

        .qr-sponsor .qr-image {
            display: block;
            max-width: none;
        }

        .qr-sponsor .qr-logo-container {
            padding: 2px;
            border-radius: 4px;
        }


        /* ====== IMPRESSION ====== */

        @media print {

            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            body {
                zoom: 85%;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
                justify-content: center !important;
                gap: 6px !important;
            }

            .sponsors img {
                max-height: 60px !important;
                object-fit: contain;
            }

            /* QR codes des sponsors : assez grands pour être scannés */
            .sponsors .qr-sponsor .qr-image {
                max-height: 100px !important;
            }

            .sponsors .qr-sponsor .qr-logo {
                max-height: 100% !important;
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
        <i class="fas fa-print"></i>
        <?= t('imprimer') ?>
    </button>


    <img src="logos/Logo.png"
         alt="Logo Brackito"
         style="height:60px;"
         class="me-3 rounded shadow-sm">


    <p class="mb-0 text-dark fw-semibold fst-italic">

        Tournoi organisé par
        <?= htmlspecialchars($nomClub) ?>

        <?php if (!empty($logoClub)) : ?>
            <img src="<?= htmlspecialchars($logoClub) ?>"
                 alt="Logo club"
                 style="height:30px; object-fit:contain;"
                 class="ms-2 rounded shadow-sm">
        <?php endif; ?>

    </p>


    <p class="text-primary fw-bold fs-4 mt-3">

        <i class="fas fa-trophy me-2"></i>
        <?= $tournoiNom ?>

        <br>

        <span class="fs-5 text-secondary">
            📅 <?= $dateFormatted ?>
        </span>

    </p>


    <div class="row mt-4 align-items-center justify-content-center">

        <div class="col-md-8">

            <div class="card p-3 text-center position-relative">

                <div class="d-flex justify-content-center align-items-center">

                    <!-- COLONNE GAUCHE : clubs participants -->
                    <div class="d-flex flex-column align-items-center justify-content-around me-3"
                         style="height:350px; min-width:80px;">

                        <?php foreach ($clubsLeft as $club): ?>
                            <img src="<?= htmlspecialchars($club['logo']) ?>"
                                 alt="<?= htmlspecialchars($club['nom']) ?>"
                                 title="<?= htmlspecialchars($club['nom']) ?>"
                                 style="max-height:<?= max(30, min(60, (int) (320 / max(count($clubsLeft), 1)))) ?>px;
                                        max-width:80px;
                                        object-fit:contain;">
                        <?php endforeach; ?>

                    </div>


                    <!-- QR CODE DU TOURNOI + LOGO DU CLUB ORGANISATEUR -->
                    <div>

                        <div class="qr-container">

                            <img src="<?= htmlspecialchars($qrcode) ?>"
                                 alt="QR Code"
                                 width="350"
                                 height="350"
                                 style="display:block;">

                            <?php if (!empty($logoClub)) : ?>
                                <div class="qr-logo-container">
                                    <img src="<?= htmlspecialchars($logoClub) ?>"
                                         alt="Logo du club organisateur"
                                         class="qr-logo">
                                </div>
                            <?php endif; ?>

                        </div>

                        <p class="mt-2">
                            <i class="fas fa-mobile-alt"></i>
                            <?= t('ScannezPourVoirLesHorairesEtLieuxDeVosRencontres') ?>
                        </p>

                    </div>


                    <!-- COLONNE DROITE : clubs participants -->
                    <div class="d-flex flex-column align-items-center justify-content-around ms-3"
                         style="height:350px; min-width:80px;">

                        <?php foreach ($clubsRight as $club): ?>
                            <img src="<?= htmlspecialchars($club['logo']) ?>"
                                 alt="<?= htmlspecialchars($club['nom']) ?>"
                                 title="<?= htmlspecialchars($club['nom']) ?>"
                                 style="max-height:<?= max(30, min(60, (int) (320 / max(count($clubsRight), 1)))) ?>px;
                                        max-width:80px;
                                        object-fit:contain;">
                        <?php endforeach; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="row mt-4 align-items-center no-print">
        <p class="display-6 text-center text-danger">
            <?= t('impressionPaysage') ?>
        </p>
    </div>


    <?php if (!empty($sponsors) && $tournoi['gestionPartenaires'] == 1) : ?>

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

                        <h5 class="card-title mb-0">
                            <?= htmlspecialchars($sponsor['nom']) ?>
                        </h5>

                        <p class="card-text small">
                            <?= htmlspecialchars($sponsor['description']) ?>
                        </p>

                        <div class="text-center">

                            <?php if (!empty($sponsor['lien_web'])) : ?>

                                <?php
                                // QR code du sponsor : même style que celui du tournoi,
                                // avec le logo du sponsor au centre s'il en a un
                                $logoSponsor = !empty($sponsor['logo']) ? $sponsor['logo'] : null;
                                $configLogo  = $logoSponsor ? configLogoQr($sponsor['lien_web']) : null;

                                $optionsSponsor = $configLogo
                                    ? creerOptionsQr($remplissage, $degradeSvg, [
                                        'version'         => $configLogo['version'],
                                        'addLogoSpace'    => true,
                                        'logoSpaceWidth'  => $configLogo['modulesLogo'],
                                        'logoSpaceHeight' => $configLogo['modulesLogo'],
                                    ])
                                    : creerOptionsQr($remplissage, $degradeSvg, [
                                        'eccLevel'   => QRCode::ECC_M,
                                        'versionMin' => 5,
                                    ]);

                                $qrcodeLien = (new QRCode($optionsSponsor))
                                    ->render($sponsor['lien_web']);
                                ?>

                                <div class="qr-container qr-sponsor">

                                    <img src="<?= $qrcodeLien ?>"
                                         alt="QR Code Site Web"
                                         class="qr-image">

                                    <?php if ($configLogo) : ?>
                                        <div class="qr-logo-container"
                                             style="width:<?= $configLogo['pourcentage'] ?>%;
                                                    height:<?= $configLogo['pourcentage'] ?>%;">
                                            <img src="<?= htmlspecialchars($logoSponsor) ?>"
                                                 alt="<?= htmlspecialchars($sponsor['nom']) ?>"
                                                 class="qr-logo">
                                        </div>
                                    <?php endif; ?>

                                </div>

                                <p class="mt-1 small text-secondary text-start">
                                    🌐
                                    <?= htmlspecialchars(
                                        (string) parse_url($sponsor['lien_web'], PHP_URL_HOST)
                                    ) ?>
                                </p>

                            <?php endif; ?>

                            <?php if (!empty($sponsor['telephone'])) : ?>
                                <p class="mt-1 text-break small text-secondary text-start">
                                    📞
                                    <?= htmlspecialchars($sponsor['telephone']) ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($sponsor['adresse'])) : ?>
                                <p class="mt-1 text-break small text-secondary text-start">
                                    📍
                                    <?= htmlspecialchars($sponsor['adresse']) ?>
                                </p>
                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>