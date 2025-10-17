<?php
require_once '../config.php';
require_once '../utils.php';

// Verificar se o administrador está logado
check_admin_session();

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', 'ID de solicitação inválido.');
    header('Location: solicitacoes.php');
    exit;
}

$id = (int)$_GET['id'];

// Obter detalhes da solicitação
$db = get_sqlite_connection();
$stmt = $db->prepare('SELECT * FROM solicitacao_cadastro WHERE id = :id');
$stmt->execute(['id' => $id]);
$solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitacao) {
    set_flash_message('danger', 'Solicitação não encontrada.');
    header('Location: solicitacoes.php');
    exit;
}

// Converter imagens para base64 para exibição
$foto_perfil_b64 = null;
if ($solicitacao['foto_perfil']) {
    $foto_perfil_b64 = base64_encode($solicitacao['foto_perfil']);
}

$nr10_documento_b64 = null;
if ($solicitacao['nr10_documento']) {
    $nr10_documento_b64 = base64_encode($solicitacao['nr10_documento']);
}

$nr20_documento_b64 = null;
if ($solicitacao['nr20_documento']) {
    $nr20_documento_b64 = base64_encode($solicitacao['nr20_documento']);
}

$nr35_documento_b64 = null;
if ($solicitacao['nr35_documento']) {
    $nr35_documento_b64 = base64_encode($solicitacao['nr35_documento']);
}

$aso_documento_b64 = null;
if ($solicitacao['aso_documento']) {
    $aso_documento_b64 = base64_encode($solicitacao['aso_documento']);
}

// Obter mensagem flash, se houver
$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Solicitação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .doc-preview {
            max-width: 100%;
            max-height: 300px;
            border: 1px solid #ddd;
        }
    </style>
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
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h3>Detalhes da Solicitação #<?php echo $solicitacao['id']; ?></h3>
                        <a href="solicitacoes.php" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <?php if ($foto_perfil_b64): ?>
                                            <img src="data:<?php echo $solicitacao['foto_perfil_tipo']; ?>;base64,<?php echo $foto_perfil_b64; ?>" 
                                                 alt="Foto de Perfil" class="img-fluid rounded mb-3" style="max-height: 200px;">
                                        <?php else: ?>
                                            <div class="bg-light p-5 mb-3 rounded">
                                                <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <h5><?php echo htmlspecialchars($solicitacao['nome_completo']); ?></h5>
                                        <p class="text-muted">@<?php echo htmlspecialchars($solicitacao['username']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Informações Pessoais</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($solicitacao['email']); ?></p>
                                                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($solicitacao['telefone'] ?? 'Não informado'); ?></p>
                                                <p><strong>WhatsApp:</strong> <?php echo htmlspecialchars($solicitacao['whatsapp'] ?? 'Não informado'); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>CPF:</strong> <?php echo htmlspecialchars($solicitacao['cpf'] ?? 'Não informado'); ?></p>
                                                <p><strong>RG:</strong> <?php echo htmlspecialchars($solicitacao['rg'] ?? 'Não informado'); ?></p>
                                                <p><strong>Endereço:</strong> <?php echo htmlspecialchars($solicitacao['endereco'] ?? 'Não informado'); ?></p>
                                                <p><strong>Cidade/Estado:</strong> <?php echo htmlspecialchars(($solicitacao['cidade'] ?? 'Não informado') . '/' . ($solicitacao['estado'] ?? '')); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Certificações</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card mb-3">
                                                    <div class="card-header">
                                                        <h6>NR-10</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Status:</strong> <?php echo $solicitacao['nr10'] ? 'Sim' : 'Não'; ?></p>
                                                        <?php if ($solicitacao['nr10']): ?>
                                                            <p><strong>Validade:</strong> <?php echo $solicitacao['nr10_validade'] ? date('d/m/Y', strtotime($solicitacao['nr10_validade'])) : 'Não informado'; ?></p>
                                                            <?php if ($nr10_documento_b64): ?>
                                                                <p><strong>Documento:</strong></p>
                                                                <?php if (strpos($solicitacao['nr10_documento_tipo'], 'image/') === 0): ?>
                                                                    <img src="data:<?php echo $solicitacao['nr10_documento_tipo']; ?>;base64,<?php echo $nr10_documento_b64; ?>" 
                                                                         alt="Documento NR-10" class="doc-preview">
                                                                <?php else: ?>
                                                                    <object data="data:<?php echo $solicitacao['nr10_documento_tipo']; ?>;base64,<?php echo $nr10_documento_b64; ?>" 
                                                                            type="<?php echo $solicitacao['nr10_documento_tipo']; ?>" 
                                                                            class="doc-preview" style="height: 300px;">
                                                                        <p>Seu navegador não suporta a visualização de PDF.</p>
                                                                        <a href="visualizar_documento.php?id=<?php echo $solicitacao['id']; ?>&tipo=nr10" 
                                                                           target="_blank" class="btn btn-sm btn-primary">
                                                                            <i class="bi bi-file-earmark-pdf"></i> Abrir em nova janela
                                                                        </a>
                                                                    </object>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <p>Nenhum documento anexado.</p>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card mb-3">
                                                    <div class="card-header">
                                                        <h6>NR-20</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Status:</strong> <?php echo $solicitacao['nr20'] ? 'Sim' : 'Não'; ?></p>
                                                        <?php if ($solicitacao['nr20']): ?>
                                                            <p><strong>Validade:</strong> <?php echo $solicitacao['nr20_validade'] ? date('d/m/Y', strtotime($solicitacao['nr20_validade'])) : 'Não informado'; ?></p>
                                                            <?php if ($nr20_documento_b64): ?>
                                                                <p><strong>Documento:</strong></p>
                                                                <?php if (strpos($solicitacao['nr20_documento_tipo'], 'image/') === 0): ?>
                                                                    <img src="data:<?php echo $solicitacao['nr20_documento_tipo']; ?>;base64,<?php echo $nr20_documento_b64; ?>" 
                                                                         alt="Documento NR-20" class="doc-preview">
                                                                <?php else: ?>
                                                                    <object data="data:<?php echo $solicitacao['nr20_documento_tipo']; ?>;base64,<?php echo $nr20_documento_b64; ?>" 
                                                                            type="<?php echo $solicitacao['nr20_documento_tipo']; ?>" 
                                                                            class="doc-preview" style="height: 300px;">
                                                                        <p>Seu navegador não suporta a visualização de PDF.</p>
                                                                        <a href="visualizar_documento.php?id=<?php echo $solicitacao['id']; ?>&tipo=nr20" 
                                                                           target="_blank" class="btn btn-sm btn-primary">
                                                                            <i class="bi bi-file-earmark-pdf"></i> Abrir em nova janela
                                                                        </a>
                                                                    </object>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <p>Nenhum documento anexado.</p>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card mb-3">
                                                    <div class="card-header">
                                                        <h6>NR-35</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Status:</strong> <?php echo $solicitacao['nr35'] ? 'Sim' : 'Não'; ?></p>
                                                        <?php if ($solicitacao['nr35']): ?>
                                                            <p><strong>Validade:</strong> <?php echo $solicitacao['nr35_validade'] ? date('d/m/Y', strtotime($solicitacao['nr35_validade'])) : 'Não informado'; ?></p>
                                                            <?php if ($nr35_documento_b64): ?>
                                                                <p><strong>Documento:</strong></p>
                                                                <?php if (strpos($solicitacao['nr35_documento_tipo'], 'image/') === 0): ?>
                                                                    <img src="data:<?php echo $solicitacao['nr35_documento_tipo']; ?>;base64,<?php echo $nr35_documento_b64; ?>" 
                                                                         alt="Documento NR-35" class="doc-preview">
                                                                <?php else: ?>
                                                                    <object data="data:<?php echo $solicitacao['nr35_documento_tipo']; ?>;base64,<?php echo $nr35_documento_b64; ?>" 
                                                                            type="<?php echo $solicitacao['nr35_documento_tipo']; ?>" 
                                                                            class="doc-preview" style="height: 300px;">
                                                                        <p>Seu navegador não suporta a visualização de PDF.</p>
                                                                        <a href="visualizar_documento.php?id=<?php echo $solicitacao['id']; ?>&tipo=nr35" 
                                                                           target="_blank" class="btn btn-sm btn-primary">
                                                                            <i class="bi bi-file-earmark-pdf"></i> Abrir em nova janela
                                                                        </a>
                                                                    </object>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <p>Nenhum documento anexado.</p>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card mb-3">
                                                    <div class="card-header">
                                                        <h6>ASO</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Status:</strong> <?php echo $solicitacao['aso'] ? 'Sim' : 'Não'; ?></p>
                                                        <?php if ($solicitacao['aso']): ?>
                                                            <p><strong>Validade:</strong> <?php echo $solicitacao['aso_validade'] ? date('d/m/Y', strtotime($solicitacao['aso_validade'])) : 'Não informado'; ?></p>
                                                            <?php if ($aso_documento_b64): ?>
                                                                <p><strong>Documento:</strong></p>
                                                                <?php if (strpos($solicitacao['aso_documento_tipo'], 'image/') === 0): ?>
                                                                    <img src="data:<?php echo $solicitacao['aso_documento_tipo']; ?>;base64,<?php echo $aso_documento_b64; ?>" 
                                                                         alt="Documento ASO" class="doc-preview">
                                                                <?php else: ?>
                                                                    <object data="data:<?php echo $solicitacao['aso_documento_tipo']; ?>;base64,<?php echo $aso_documento_b64; ?>" 
                                                                            type="<?php echo $solicitacao['aso_documento_tipo']; ?>" 
                                                                            class="doc-preview" style="height: 300px;">
                                                                        <p>Seu navegador não suporta a visualização de PDF.</p>
                                                                        <a href="visualizar_documento.php?id=<?php echo $solicitacao['id']; ?>&tipo=aso" 
                                                                           target="_blank" class="btn btn-sm btn-primary">
                                                                            <i class="bi bi-file-earmark-pdf"></i> Abrir em nova janela
                                                                        </a>
                                                                    </object>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <p>Nenhum documento anexado.</p>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Ações</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-center gap-3">
                                            <?php if ($solicitacao['status'] === 'pendente'): ?>
                                                <form action="aprovar.php" method="post" class="d-inline">
                                                    <input type="hidden" name="id" value="<?php echo $solicitacao['id']; ?>">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="bi bi-check-circle"></i> Aprovar Solicitação
                                                    </button>
                                                </form>
                                                
                                                <form action="rejeitar.php" method="post" class="d-inline">
                                                    <input type="hidden" name="id" value="<?php echo $solicitacao['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="bi bi-x-circle"></i> Rejeitar Solicitação
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <div class="alert alert-info">
                                                    Esta solicitação já foi processada. Status: <?php echo ucfirst($solicitacao['status']); ?>
                                                </div>
                                            <?php endif; ?>
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