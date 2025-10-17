<?php
// Arquivo para verificar o IP do servidor
// Este arquivo pode ser útil para debug e configurações

// Obter informações do servidor
$server_ip = $_SERVER['SERVER_ADDR'] ?? 'Não disponível';
$remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'Não disponível';
$http_host = $_SERVER['HTTP_HOST'] ?? 'Não disponível';
$server_name = $_SERVER['SERVER_NAME'] ?? 'Não disponível';

// Headers adicionais
$forwarded_for = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'Não disponível';
$real_ip = $_SERVER['HTTP_X_REAL_IP'] ?? 'Não disponível';
$cf_connecting_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'Não disponível';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informações do Servidor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center">Informações do Servidor</h3>
                    </div>
                    <div class="card-body">
                        <h5>Informações Básicas:</h5>
                        <table class="table table-striped">
                            <tr>
                                <td><strong>IP do Servidor:</strong></td>
                                <td><?php echo htmlspecialchars($server_ip); ?></td>
                            </tr>
                            <tr>
                                <td><strong>IP do Cliente:</strong></td>
                                <td><?php echo htmlspecialchars($remote_ip); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Host HTTP:</strong></td>
                                <td><?php echo htmlspecialchars($http_host); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Nome do Servidor:</strong></td>
                                <td><?php echo htmlspecialchars($server_name); ?></td>
                            </tr>
                        </table>

                        <h5 class="mt-4">Headers de Proxy/Load Balancer:</h5>
                        <table class="table table-striped">
                            <tr>
                                <td><strong>X-Forwarded-For:</strong></td>
                                <td><?php echo htmlspecialchars($forwarded_for); ?></td>
                            </tr>
                            <tr>
                                <td><strong>X-Real-IP:</strong></td>
                                <td><?php echo htmlspecialchars($real_ip); ?></td>
                            </tr>
                            <tr>
                                <td><strong>CF-Connecting-IP:</strong></td>
                                <td><?php echo htmlspecialchars($cf_connecting_ip); ?></td>
                            </tr>
                        </table>

                        <h5 class="mt-4">Informações Adicionais:</h5>
                        <table class="table table-striped">
                            <tr>
                                <td><strong>User Agent:</strong></td>
                                <td><?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Não disponível'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Método HTTP:</strong></td>
                                <td><?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'Não disponível'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>URI:</strong></td>
                                <td><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Não disponível'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Protocolo:</strong></td>
                                <td><?php echo htmlspecialchars($_SERVER['SERVER_PROTOCOL'] ?? 'Não disponível'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Porta:</strong></td>
                                <td><?php echo htmlspecialchars($_SERVER['SERVER_PORT'] ?? 'Não disponível'); ?></td>
                            </tr>
                        </table>

                        <div class="mt-4">
                            <h5>IP Público (se disponível):</h5>
                            <?php
                            // Tentar obter IP público
                            $public_ip = @file_get_contents('https://api.ipify.org');
                            if ($public_ip) {
                                echo "<p class='alert alert-info'><strong>IP Público:</strong> " . htmlspecialchars($public_ip) . "</p>";
} else {
                                echo "<p class='alert alert-warning'>Não foi possível obter o IP público</p>";
                            }
                            ?>
                        </div>

                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary">Voltar ao Sistema</a>
                            <button onclick="window.location.reload()" class="btn btn-secondary">Atualizar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
