<?php
require ('security.php');
require ('class/clubDao.class.php');
require 'vendor/autoload.php';
require 'class/labelsDao.class.php';
require 'class/categorie.class.php';
require_once 'class/SponsorDAO.class.php';
require 'Lang/lang.php';
require_once 'class/utilisateurDao.class.php';

$sponsorDao = new SponsorDAO();
$categories = new CategorieDao();

$categorie = $categories->obtenirToutesLesCategories($userData['id'], 'id_categorie DESC');

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('mesPreferences.twig');


$club = new ClubDAO();
$label = new LabelDao();

$utilisateurDao = new UtilisateurDAO();
$utilisateurData = $utilisateurDao->getUtilisateurById($userData['id']);


if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $message = "Mot de passe mis à jour.";
} else {
    $message = null;
}

/**
 * Récupère les sons disponibles dans la banque
 * @param string $type 'debut' ou 'fin'
 * @return array Liste des sons avec nom et chemin
 */
function getBanqueSons($type) {
    $banqueDir = 'Audio/Effets/Banque/' . $type . '/';
    $sons = [];
    
    if (is_dir($banqueDir)) {
        $files = scandir($banqueDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'mp3') {
                $sons[] = [
                    'nom' => ucfirst(str_replace(['-', '_'], ' ', pathinfo($file, PATHINFO_FILENAME))),
                    'chemin' => $banqueDir . $file
                ];
            }
        }
    }
    
    return $sons;
}

$idClubChoisit = $utilisateurDao->getClubFromIdUser($userData['id'])['club_id'] ?? null;
$logoClubChoisit = $utilisateurDao->getClubFromIdUser($userData['id'])['club_logo'] ?? null;    

echo $template->render([
  'email' => $userData['email'],
  
  'pageEnCours' =>  'Users',
  'categories' => $categorie,
  'message' => $message,
  'idClub' => $userData['id'],
  'listeClubs' => $club->afficherClubs(),
  'sponsors' => $sponsorDao->getSponsorsParClub($userData['id']),
  'idTournoi' => $_GET['id_tournoi'],
  'affichageSponsors' => isset($_GET['affichageSponsors']) && $_GET['affichageSponsors'] === 'true',
  'monMotDePasse' => isset($_GET['monMotDePasse']) && $_GET['monMotDePasse'] === 'true',
  'gestionCategorie' => isset($_GET['gestionCategorie']) && $_GET['gestionCategorie'] === 'true',
 'gestionEffetsSonores' => isset($_GET['gestionEffetsSonores']),
    'effetsSonoreDebut' => $utilisateurData['effetsSonoreDebut'] ?? null,
    'effetsSonoreFin' => $utilisateurData['effetsSonoreFin'] ?? null,
    'userId' => $userData['id'],
     'banqueSonsDebut' => getBanqueSons('debut'),
    'banqueSonsFin' => getBanqueSons('fin'),
    'idClubChoisit' => $idClubChoisit,
    'logoClubChoisit' => $logoClubChoisit,
    'monClub' => isset($_GET['monClub']) && $_GET['monClub'] === 'true',
    'affichageFlyer' => isset($_GET['affichageFlyer']),
   


]);
