<?php
// lang.php

// Démarre la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Détection ou force de la langue
if (isset($_GET['lang'])) {
    $userLang = $_GET['lang']; // 'en', 'fr', etc.
    $_SESSION['lang'] = $userLang; // sauvegarde pour navigation suivante
} elseif (isset($_SESSION['lang'])) {
    $userLang = $_SESSION['lang'];
} else {
    $userLang = 'fr'; // valeur par défaut
}

// Fichier unique contenant toutes les langues
$allLangs = [
    'fr' => [
        
        'login' => 'Connexion',
        'CreateTournament' => 'Créer un tournoi',
        'typeDeSport' => 'Type de sport',
        'nameOfTournament' => 'Nom du tournoi',
        'hourStart' => 'Heure de début',
        'numberOfFields' => 'Nombre de terrains',
        'dureeRencontre' => 'Durée de la rencontre (en minutes)',
        'dureeRencontreDisable' => 'Durée de la rencontre  (pour modifier ce pas, rendez vous dans le placement des rencontres)',
        'dateDuTournoi' => 'Date du tournoi',
        'limit_tournament_warning' => 'Vous ne pouvez pas créer de nouveau tournoi car vous avez atteint la limite de %limit% tournoi(s) pour cette licence.',
        'createTournament' => 'Créer le tournoi',
        'creerEquipe' => 'Créer les équipes',
        'creerClub' => 'Créer un club',
        'explicationClub' => "Un club sert à rattacher vos équipes. Son logo apparaîtra dans les rencontres, ce qui facilite la sélection pour un coach et améliore la visibilité des équipes. Le club peut déjà exister avec le bon logo créé par un autre utilisateur de la plateforme. Vous pouvez les lister à cette adresse : <a href=\"modifierClub.php\">lister les clubs</a>.",
        'explicationEquipe' => "Une équipe est rattachée à un club et participe aux tournois. Ajouter un logo permet aux coachs de la reconnaître rapidement lors des rencontres, ce qui facilite la sélection et la gestion des matchs.",
        'generateQrCode' => 'Génération de QR Code(s)',
        'gestionDesTables' => 'Gestion des tables',
        'gestionDesAnnoncesVocales' => 'Gestion des annonces vocales',
        'gestionDesArbitres' => 'Gestion des arbitres',
        'gestionDesPartenaires' => 'Gestion des partenaires',
        'tournoiVisible' => 'Tournoi visible',
        'horaireIsVisible' => 'Horaire visible',
        'classementIsVisible' => 'Classement visible',
        'archiverLeTournoi' => 'Archiver le tournoi ( pour pouvoir le supprimer ) ',
        'parametresDuTournoi' => 'Paramètres du tournoi',
        'selectionTournoi' => 'Sélectionner un tournoi',
        'QrpourLesCoachsetPublic' =>  'QR Code pour les coachs et le public',
        'qrCode' => 'QR Code',
        'arbitres' => 'Arbitres',
        'ajouterDesArbitres' => 'Ajouter des arbitres',
        'nomArbitreOptionnel' => 'Nom (optionnel)',
        'Club' => 'Club',
        'ajouter' => 'Ajouter', 
        'action' => 'Action',
        'supprimer' => 'Supprimer',
        'partenairesDansLesPreferences' => 'sont gérés par compte dans <a href="mesPreferences.php?id_tournoi=%tournoiId%">Mes préférences</a>.',
        'suppressionDuTournoi' => 'Suppression du tournoi',
        'enregistrer' => 'Enregistrer',
        'stop' => 'Stop',
        'aideEnregistrementArbitre' => '( annoncer: Rencontre arbitrée par ...)',
        'ajouterPersonneTerrainTitre' => 'Ajouter une personne pour gérer les scores à une table d\'un terrain',
        'email'=>"Email",
        'entrerValidAdressMail' => 'Veuillez entrer une adresse email valide.',
        'name' => 'Nom',
        'prenom' => 'Prénom',
        'postionnerPersonneTerrain' => 'Positionner cette personne sur le terrain N°',
        'terrainN' => 'Terrain N°',
        'codePIN' => 'Code PIN',
        'cleSecurisee' => 'Clé sécurisée',
        'envoiDesInformations' => 'Envoyer les informations',
        'supprimerPersonneTerrain' => 'Supprimer cette personne du terrain',
        'envoyer' => 'Envoyer',
        'envoye' => 'Envoyé',
        'nonEnvoye' => 'Non envoyé',
        'emailEnvoyeSuccess' => 'Email envoyé avec succès',
        'emailNonEnvoyeEchec' => 'echec de l\'envoi de mail',
        'pourLesArbitres' => 'Pour les arbitres',
        'imprimer' => 'Imprimer',
        'ScannezPourVoirLesHorairesEtLieuxDeVosRencontres' => 'Scannez pour voir les horaires et lieux de vos rencontres !',
        'ScannezAvecVotreSmartphone' => 'Scannez avec votre smartphone',
        'BrackitoVotreGestionnaireDeTournois' => 'Brackito - Votre gestionnaire de tournois',
        'impressionPaysage' => 'Pour un meilleur résultat, imprimer cette affiche en paysage.',



        
        // ajoute toutes tes traductions françaises ici
    ],
    'en' => [
        
        'login' => 'Login',
        'CreateTournament' => 'Create a tournament',
        'typeDeSport' => 'Type of sport',
        'nameOfTournament' => 'Name of the tournament',
        'hourStart' => 'Start time',
        'numberOfFields' => 'Number of fields',
        'dureeRencontre' => 'Duration of the match (in minutes)',
        'dateDuTournoi' => 'Tournament date',
        'limit_tournament_warning' => 'You cannot create a new tournament because you have reached the limit of %limit% tournament(s) for this license.',
        'createTournament' => 'Create tournament',
        'creerEquipe' => 'Create teams',
        'creerClub' => 'Create a club',
        'explicationClub' => "A club allows you to attach your teams. Its logo will appear in matches, making it easier for a coach to select teams and improving visibility. The club may already exist with the correct logo created by another user of the platform. You can list them here: <a href=\"modifierClub.php\">list the clubs</a>.",
        'explicationEquipe' => "A team is linked to a club and participates in tournaments. Adding a logo helps coaches quickly identify it during matches, making selection and match management easier.",
        'dureeRencontreDisable' => 'Match duration (to modify this step, go to the match scheduling section)',
        'generateQrCode' => 'QR Code Generation(s)',
        'gestionDesTables' => 'Table Management',
        'gestionDesAnnoncesVocales' => 'Voice Announcements Management',
        'gestionDesArbitres' => 'Referee Management',
        'gestionDesPartenaires' => 'Partner Management',
        'tournoiVisible' => 'Tournament visible',
        'horaireIsVisible' => 'Schedule visible',
        'classementIsVisible' => 'Ranking visible',
        'archiverLeTournoi' => 'Archive the tournament (to be able to delete it)',
        'parametresDuTournoi' => 'Tournament settings',
        'selectionTournoi' => 'Select a tournament',
        'QrpourLesCoachsetPublic' =>  'QR code for coaches and the public',
        'qrCode' => 'QR Code',
        'arbitres' => 'Referees',
        'ajouterDesArbitres' => 'Add referees',
        'nomArbitreOptionnel' => 'Name (optional)',
        'Club' => 'Club',
        'ajouter' => 'Add',
        'action' => 'Action',
        'supprimer' => 'Delete',
        'partenairesDansLesPreferences' => 'are managed by account in <a href="mesPreferences.php?id_tournoi=%s">My Preferences</a>.',
        'suppressionDuTournoi' => 'Delete the tournament',
        'enregistrer' => 'Record',
        'stop' => 'Stop',
        'aideEnregistrementArbitre' => '( announce: Match refereed by ...)',
        'ajouterPersonneTerrainTitre' => 'Add a person to manage scores at a table on a field',
        'email' => "Email",
        'entrerValidAdressMail' => 'Please enter a valid email address.',
        'name' => 'Name',
        'prenom' => 'First Name',
        'postionnerPersonneTerrain' => 'Position this person on field number',
        'terrainN' => 'Field number',
        'codePIN' => 'PIN Code',
        'cleSecurisee' => 'Secure key',
        'envoiDesInformations' => 'Send information',
        'supprimerPersonneTerrain' => 'Remove this person from the field',
        'envoye' => 'Sent',
        'envoyer' => 'Send',
        'nonEnvoye' => 'Not sent',
        'emailEnvoyeSuccess' => 'Email sent successfully',
        'emailNonEnvoyeEchec' => 'Failed to send email',
        'pourLesArbitres' => 'For referees',
        'imprimer' => 'Print',
        'ScannezPourVoirLesHorairesEtLieuxDeVosRencontres' => 'Scan to see the schedules and locations of your matches!',
        'ScannezAvecVotreSmartphone' => 'Scan with your smartphone',
        'BrackitoVotreGestionnaireDeTournois' => 'Brackito - Your tournament manager',
        'impressionPaysage' => 'For best results, print this poster in landscape mode.',
        
        

        // ajoute toutes tes traductions anglaises ici
    ],
    // ajoute d'autres langues si besoin
];

// Vérifie que la langue existe, sinon fallback sur 'fr'
if (!isset($allLangs[$userLang])) {
    $userLang = 'fr';
}

// Sélectionne les traductions de la langue active
$lang = $allLangs[$userLang];
//echo "Langue sélectionnée : " . $userLang; // pour débogage, à supprimer en production
// Fonction de traduction
function t($key, $params = []) {
    global $lang;

    $text = $lang[$key] ?? $key;

    // Remplace les paramètres dynamiques
    foreach ($params as $param => $value) {
        $text = str_replace($param, $value, $text);
    }

    return $text;
}
