<?php
// save_plan.php
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['idTournoi'], $data['image'], $data['shapes'])) {
    http_response_code(400);
    echo "Requête invalide";
    exit;
}

$idTournoi = intval($data['idTournoi']);
$imageData = $data['image'];
$shapesData = $data['shapes'];

// Dossier de stockage
$dir = __DIR__ . "/img/planTournoi";
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Sauvegarde PNG
$image = str_replace("data:image/png;base64,", "", $imageData);
$image = str_replace(" ", "+", $image);
$imageBinary = base64_decode($image);
$imagePath = "$dir/{$idTournoi}-plan.png";
file_put_contents($imagePath, $imageBinary);

// Sauvegarde JSON
$jsonPath = "$dir/{$idTournoi}-plan.json";
file_put_contents($jsonPath, json_encode($shapesData, JSON_PRETTY_PRINT));

echo "Plan sauvegardé avec succès (PNG + JSON)";
