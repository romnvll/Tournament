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

 // Création d'une nouvelle entrée pour une personne avec un terrain assigné
 public function creerPersonneRencontre($personneId, $terrainId, $codePin, $urlKey) {
    $stmt = $this->connexion->prepare("
        INSERT INTO PersonneRencontre (personne_id, terrain_id, code_pin, url_key) 
        VALUES (:personneId, :terrainId, :codePin, :urlKey)
    ");
    $stmt->bindValue(':personneId', $personneId);
    $stmt->bindValue(':terrainId', $terrainId);
    $stmt->bindValue(':codePin', $codePin);
    $stmt->bindValue(':urlKey', $urlKey);
    $stmt->execute();
}

// Vérification du code PIN et retour des détails de la rencontre si valide
public function verifierCodePinEtRecupererRencontres($urlKey, $codePin) {
    $stmt = $this->connexion->prepare("
        SELECT p.*, r.* 
        FROM PersonneRencontre pr 
        JOIN Rencontres r ON pr.terrain_id = r.terrain 
        JOIN Planification p ON r.id = p.rencontre_id 
        WHERE pr.url_key = :urlKey AND pr.code_pin = :codePin
    ");
    $stmt->bindValue(':urlKey', $urlKey);
    $stmt->bindValue(':codePin', $codePin);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Génération d'une URL sécurisée et d'un code PIN pour une personne
public function genererUrlEtCodePin($personneId, $terrainId) {
    $urlKey = $this->genererChaineAleatoire(30) . $terrainId;
    $codePin = $this->genererCodePin();
    $this->creerPersonneRencontre($personneId, $terrainId, $codePin, $urlKey);
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


public function envoyerMail($email, $urlKey, $codePin) {
    $subject = "Accès sécurisé pour saisir les résultats";
    $message = "Bonjour,

    Vous avez été assigné à un terrain pour noter les scores.
    
    Voici votre lien sécurisé : https://yourdomain.com/score-entry?key=$urlKey
    
    Votre code PIN est : $codePin
    
    Merci de votre collaboration.";
    
    $headers = "From: no-reply@yourdomain.com\r\n";
    
    if(mail($email, $subject, $message, $headers)) {
        return true;
    } else {
        return false;
    }
}



}