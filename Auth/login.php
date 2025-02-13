<?php

ob_start();
session_start();
require_once '../class/databaseInformations.php';


// if session is set direct to index
if (isset($_COOKIE['user'])) {
    phpinfo();
    header("Location: ../creerClub.php");
    exit;
}

if (isset($_POST['btn-login'])) {
    $email = $_POST['email'];
    $upass = $_POST['pass'];

    $password = hash('sha256', $upass); // password hashing using SHA256
    
    $stmt = $conn->prepare("SELECT id, nom, email,logo, password FROM Clubs WHERE email= ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    $row = mysqli_fetch_array($res, MYSQLI_ASSOC);

    $count = $res->num_rows;
    
    if ($count == 1 && $row['password'] == $password) {
       
        $secret = "ma_clé_ultra_sécurisée"; 

        // Données utilisateur
        $data = [
            'id' => $row['id'],
            'email' => $row['email'],
            'logo' => $row['logo'],
            'exp' => time() + (48 * 60 * 60) // Expiration dans 48h
        ];
        
        // Encodage JSON sécurisé
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // Génération de la signature sécurisée
        $signature = hash_hmac('sha256', $payload, $secret);
        
        // Créer un tableau avec les deux parties
        $tokenData = [
            'payload' => $payload,
            'signature' => $signature
        ];
        
        // Encodage en Base64
        $token = base64_encode(json_encode($tokenData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        
        // Stockage du cookie
        setcookie('auth', $token, time() + (48 * 60 * 60), "/", "", false, true);
        

        
        
        // Vérifier si tous les tournois sont archivés
        $stmtTournois = $conn->prepare("SELECT COUNT(*) AS total, SUM(isArchived) AS archived FROM Tournois");
        $stmtTournois->execute();
        $resultTournois = $stmtTournois->get_result();
        $dataTournois = $resultTournois->fetch_assoc();
        $stmtTournois->close();

        if ($dataTournois['total'] > 0 && $dataTournois['total'] == $dataTournois['archived']) {
            // Tous les tournois sont archivés
            header("Location: ../ajoutTournoi.php");
        } else {
            // Il y a au moins un tournoi non archivé
            header("Location: ../ajoutEquipe.php?idTournoi=0");
        }
        exit;
    } elseif ($count == 1) {
        $errMSG = "Mauvais mot de passe";
       
    } else {
        $errMSG = "Club non trouvé";
    }
}

?>

<!DOCTYPE html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>

    <title>Login</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css"/>
    <link rel="stylesheet" href="assets/css/style.css" type="text/css"/>
</head>
<body>

<div class="container">


    <div id="login-form">
        <form method="post" autocomplete="on">

            <div class="col-md-12">
            <div class="form-group">
                    <img src="../logos/matcheventPro.webp" class="img-thumbnail">
                </div>

              
                <div class="form-group">
                    <hr/>
                </div>

                <?php
                if (isset($errMSG)) {

                    ?>
                    <div class="form-group">
                        <div class="alert alert-danger">
                            <span class="glyphicon glyphicon-info-sign"></span> <?php echo $errMSG; ?>
                        </div>
                    </div>
                    <?php
                }
                ?>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
                        <input type="email" name="email" class="form-control" placeholder="Email" required/>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
                        <input type="password" name="pass" class="form-control" placeholder="Password" required/>
                    </div>
                </div>

                <div class="form-group">
                    <hr/>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-block btn-primary" name="btn-login">Login</button>
                </div>

                <div class="form-group">
                    <hr/>
                </div>
                <!--
                <div class="form-group">
                    <a href="register.php" type="button" class="btn btn-block btn-danger"
                       name="btn-login">Register</a>
                </div>
            -->
            </div>

        </form>
    </div>

</div>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script type="text/javascript" src="assets/js/bootstrap.min.js"></script>
</body>
</html>
