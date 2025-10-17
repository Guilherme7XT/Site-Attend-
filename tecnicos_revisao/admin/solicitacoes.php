<?php
require_once '../config.php';
require_once '../utils.php';

// Verificar se o administrador está logado
check_admin_session();

// Obter solicitações pendentes
$db = get_sqlite_connection();
$stmt = $db->query('SELECT * FROM solicitacao_cadastro WHERE status = "pendente" ORDER BY data_solicitacao DESC');
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter mensagem flash, se houver
$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações Pendentes</title>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="solicitacoes.php">Solicitações</a>
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
                        <h3>Solicitações Pendentes</h3>
                    </div>
                    <div class="card-body">
                        <?php if (count($solicitacoes) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>Usuário</th>
                                            <th>Email</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($solicitacoes as $solicitacao): ?>
                                            <tr>
                                                <td><?php echo $solicitacao['id']; ?></td>
                                                <td><?php echo htmlspecialchars($solicitacao['nome_completo']); ?></td>
                                                <td><?php echo htmlspecialchars($solicitacao['username']); ?></td>
                                                <td><?php echo htmlspecialchars($solicitacao['email']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></td>
                                                <td>
                                                    <a href="ver_solicitacao.php?id=<?php echo $solicitacao['id']; ?>" class="btn btn-info btn-sm">
                                                        <i class="bi bi-eye"></i> Ver
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Não há solicitações pendentes no momento.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>