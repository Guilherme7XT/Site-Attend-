<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Proibido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <h1 class="display-1">403</h1>
                <h2>Acesso Proibido</h2>
                <p class="lead">Você não tem permissão para acessar este recurso.</p>
                <a href="index.php" class="btn btn-primary">Voltar para a página inicial</a>
            </div>
        </div>
    </div>
</body>
</html>