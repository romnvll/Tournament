<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification du Code PIN</title>
    <!-- Inclure Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                <img src="logos/matcheventPro.webp" class="card-img-top img-thumbnail" alt="...">
                    <div class="card-body">
                        <form method="POST" action="authPersonneTableVerif.php" id="verificationForm">
                            <div class="form-group">
                                <label for="codePin" class="visually-hidden">Code pin</label>
                                <input type="number" class="form-control" name="codePin" id="codePin" placeholder="Code pin reçu par mail" required>
                            </div>
                            <input type="hidden" name="key" value="<?php echo $_GET['key'] ?>">
                            <input type="hidden" name="tournoiId" value="<?php echo $_GET['tournoi_id'] ?>">
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary mb-3">OK</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inclure Bootstrap JS et ses dépendances -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
