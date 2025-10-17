<?php
require_once '../config.php';
require_once '../utils.php';

// Verificar se o administrador está logado
check_admin_session();

// Obter mensagem flash, se houver
$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Painel Administrativo</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="solicitacoes.php">Solicitações</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3>Dashboard</h3>
                    </div>
                    <div class="card-body">
                        <h4>Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h4>
                        <p>Este é o painel administrativo do Sistema de Cadastro de Técnicos.</p>
                        
                        <div class="row mt-4">
                            <?php
                            // Obter contagem de solicitações pendentes
                            $db = get_sqlite_connection();
                            $stmt = $db->query('SELECT COUNT(*) as count FROM solicitacao_cadastro WHERE status = "pendente"');
                            $pendentes = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            
                            // Obter contagem de solicitações aprovadas
                            $stmt = $db->query('SELECT COUNT(*) as count FROM solicitacao_cadastro WHERE status = "aprovado"');
                            $aprovadas = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            
                            // Obter contagem de solicitações rejeitadas
                            $stmt = $db->query('SELECT COUNT(*) as count FROM solicitacao_cadastro WHERE status = "rejeitado"');
                            $rejeitadas = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            
                            <div class="col-md-4">
                                <div class="card text-white bg-warning mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">Solicitações Pendentes</h5>
                                                <h2 class="card-text"><?php echo $pendentes; ?></h2>
                                            </div>
                                            <i class="bi bi-hourglass-split fs-1"></i>
                                        </div>
                                        <a href="solicitacoes.php" class="btn btn-light btn-sm mt-2">Ver Detalhes</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card text-white bg-success mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">Solicitações Aprovadas</h5>
                                                <h2 class="card-text"><?php echo $aprovadas; ?></h2>
                                            </div>
                                            <i class="bi bi-check-circle fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card text-white bg-danger mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">Solicitações Rejeitadas</h5>
                                                <h2 class="card-text"><?php echo $rejeitadas; ?></h2>
                                            </div>
                                            <i class="bi bi-x-circle fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>