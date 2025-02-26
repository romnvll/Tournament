<?php



if (!isset($_COOKIE['auth'])) {
  header("Location: index.php");
  
  exit;
}
else {
 
  $secret = "ma_clé_ultra_sécurisée"; 

if (isset($_COOKIE['auth'])) {
    // Décoder le cookie
    $decoded = base64_decode($_COOKIE['auth']);
    //var_dump("Contenu décodé : ", $decoded); // DEBUG

    // Décoder le JSON contenu dans le cookie
    $data = json_decode($decoded, true);
    //var_dump("Données après json_decode : ", $data); // DEBUG

    if ($data && isset($data['payload'], $data['signature'])) {
        // Vérifier la signature
        $expectedSignature = hash_hmac('sha256', $data['payload'], $secret);
       // var_dump("Signature attendue : ", $expectedSignature); // DEBUG

        if ($expectedSignature === $data['signature']) {
            // Décoder le payload
            $userData = json_decode($data['payload'], true);

            // Vérifier l'expiration
            if ($userData['exp'] > time()) {
              //  echo "✅ Authentification réussie !";
            } else {
                echo "⏳ Le cookie a expiré.";
            }
        } else {
            echo "❌ Tentative de falsification détectée !";
        }
    } else {
        echo "⚠️ Cookie invalide.";
    }
} else {
    echo "⚠️ Aucun cookie trouvé.";
}


}







?>