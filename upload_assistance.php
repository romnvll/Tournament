<?php
$idTournoi = $_GET['idTournoi'] ?? null;

if (!$idTournoi) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing tournoi id']);
    exit;
}

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // Dossier spécifique au tournoi
    $uploadDir = __DIR__ . '/uploads/' . intval($idTournoi) . '/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', basename($file['name']));
    $uploadFile = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
        // URL accessible depuis ton site
        $url = 'uploads/' . intval($idTournoi) . '/' . $fileName;
        echo json_encode(['location' => $url]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Upload failed']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
}
