<?php

class PersonneTableDao {
private $connexion;

public function __construct() {
    require 'databaseInformations.php';

    try {
        $this->connexion = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $this->connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo "Erreur de connexion à la base de données : " . $e->getMessage();
        exit;
    }
}


public function recupererInformationsParCle($urlKey) {
    $stmt = $this->connexion->prepare("
        SELECT pr.*, p.*, t.nom AS terrain_nom
        FROM PersonneTable pr
        INNER JOIN Personne p ON pr.personne_id = p.id
        INNER JOIN Terrains t ON pr.terrain_id = t.terrain_id
        WHERE pr.url_key = :urlKey
    ");
    $stmt->bindValue(':urlKey', $urlKey, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


public function chercherCleUrl($urlKey) {
    $stmt = $this->connexion->prepare("
        SELECT 1 
        FROM PersonneTable
        WHERE url_key = :urlKey
    ");
    $stmt->bindValue(':urlKey', $urlKey, PDO::PARAM_STR);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? true : false;
}

 // Création d'une nouvelle entrée pour une personne avec un terrain assigné
 public function creerPersonneRencontre($personneId, $terrainId, $codePin, $urlKey, $tournoi_id) {
    $stmt = $this->connexion->prepare("
        INSERT INTO PersonneTable (personne_id, terrain_id, code_pin, url_key, tournoi_id) 
        VALUES (:personneId, :terrainId, :codePin, :urlKey, :tournoi_id)
    ");
    $stmt->bindValue(':personneId', $personneId);
    $stmt->bindValue(':terrainId', $terrainId);
    $stmt->bindValue(':codePin', $codePin);
    $stmt->bindValue(':urlKey', $urlKey);
    $stmt->bindValue(':tournoi_id', $tournoi_id);
    $stmt->execute();
}

// Vérification du code PIN et retour des détails de la rencontre si valide
public function verifierCodePinEtRecupererRencontres($urlKey, $codePin) {
    $stmt = $this->connexion->prepare("
       SELECT pr.*, p.*, ter.nom as terrainNom
FROM PersonneTable pr
JOIN Personne p ON pr.personne_id = p.id
JOIN Terrains ter ON pr.terrain_id = ter.terrain_id
WHERE pr.url_key = :urlKey AND pr.code_pin = :codePin
    ");

    $stmt->bindValue(':urlKey', $urlKey);
    $stmt->bindValue(':codePin', $codePin);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
   
}

// Génération d'une URL sécurisée et d'un code PIN pour une personne
public function genererUrlEtCodePin($personneId, $terrainId, $tournoi_id) {
    $urlKey = $this->genererChaineAleatoire(30) . $terrainId;
    $codePin = $this->genererCodePin();
    $this->creerPersonneRencontre($personneId, $terrainId, $codePin, $urlKey, $tournoi_id);
    return ['urlKey' => $urlKey, 'codePin' => $codePin];
}

// Génération d'une chaîne aléatoire
private function genererChaineAleatoire($longueur) {
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $caracteresLongueur = strlen($caracteres);
    $chaineAleatoire = '';
    for ($i = 0; $i < $longueur; $i++) {
        $chaineAleatoire .= $caracteres[rand(0, $caracteresLongueur - 1)];
    }
    return $chaineAleatoire;
}

// Génération d'un code PIN à 6 chiffres
private function genererCodePin() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}


 // Méthode pour envoyer un mail
 public function envoyerMail($PersonneTableId) {
    // Récupérer les informations de la personne, du terrain et du tournoi à partir de PersonneTable
    $stmt = $this->connexion->prepare("
        SELECT pr.id AS personne_rencontre_id, p.Mail, p.Prenom, p.Nom, t.nom AS terrain_nom, pr.code_pin, pr.url_key,pr.tournoi_id
        FROM PersonneTable pr
        INNER JOIN Personne p ON pr.personne_id = p.id
        INNER JOIN Terrains t ON pr.terrain_id = t.terrain_id
        WHERE pr.id = :PersonneTableId
    ");
    $stmt->bindValue(':PersonneTableId', $PersonneTableId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $email = $result['Mail'];
        $prenom = $result['Prenom'];
        $nom = $result['Nom'];
        $terrainNom = $result['terrain_nom'];
        $codePin = $result['code_pin'];
        $urlKey = $result['url_key'];
        $tournoi_id = $result['tournoi_id'];

        $subject = "Accès sécurisé pour saisir les résultats";
        $message = "Bonjour $prenom $nom,

        Vous avez été assigné au terrain '$terrainNom' pour noter les scores.
        
        Voici votre lien sécurisé : http://192.168.92.211/Tournament/vuePersonneTable.php?key=$urlKey&tournoi_id=$tournoi_id
        
        Votre code PIN est : $codePin 
        
        Merci de votre collaboration.";
        
        $headers = "From: noreply@hbcat.fr\r\n";
        
        if(mail($email, $subject, $message, $headers)) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}



public function recupererToutesLesPersonnesParTournoi($tournoi_id) {
    $stmt = $this->connexion->prepare("
         SELECT pr.id AS personne_rencontre_id, pr.personne_id, pr.terrain_id, pr.tournoi_id, p.*,pr.*, t.nom AS terrain_nom
        FROM PersonneTable pr
        INNER JOIN Personne p ON pr.personne_id = p.id
        INNER JOIN Terrains t ON pr.terrain_id = t.terrain_id
        WHERE pr.tournoi_id = :tournoi_id
    ");
    $stmt->bindValue(':tournoi_id', $tournoi_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Méthode pour supprimer une personne
public function supprimerPersonneTable($id) {
    $stmt = $this->connexion->prepare("
        DELETE FROM PersonneTable WHERE id = :id;
        
    ");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
}




}