<?php
require 'class/clubDao.class.php';
require 'vendor/autoload.php';


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$clubDao = new ClubDAO();
$clubs = $clubDao->afficherClubs();

// Si un ID de club est envoyé via POST (quand le formulaire est soumis)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selectedClubId'])) {
    $selectedClub = (int) $_POST['selectedClubId'];
    //var_dump($selectedClub);
 
    // Assurez-vous que $clubDAO est correctement initialisé
    $clubDAO = new ClubDAO();
 
    // Si getClubById() renvoie un entier, vous n'avez pas besoin du "+ 0"
    $selectedClubDetail = $clubDAO->getClubById($selectedClub);
    
}
// Récupération du club si un ID est fourni
if(isset($_GET['id'])) {
    $club = $clubDao->getClubById($_GET['id']);
}

// Mise à jour du club si le formulaire est soumis

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $password = $_POST['password']; 
    $contact = $_POST['contact'];

    $logoPath = $club['logo']; // Default to the old logo
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $uploadDir = '/logos/';
        $uploadFile = $uploadDir . basename($_FILES['logo']['name']);
        
        if(move_uploaded_file($_FILES['logo']['tmp_name'], $uploadFile)) {
            $logoPath = $uploadFile;
        }
    }
    
    $clubDao->updateClub($id, $nom, $email, $password, $contact, $logoPath);
    
    //header("Location: listClubs.php"); 
    exit;
}

$template = $twig->load('editClub.twig');
echo $template->render([
    'clubs' => $clubs,
    'selectedClub' => $selectedClubDetail
    
    ]);
?>
