<?php
require_once 'config.php';
require_once 'utils.php';
require_once 'security.php';

// Inicializar banco de dados SQLite
init_sqlite_db();

// Verificar rate limiting
if (!checkRateLimit('cadastro', 3, 3600)) { // 3 tentativas por hora
    set_flash_message('danger', 'Muitas tentativas de cadastro. Tente novamente em 1 hora.');
    header('Location: cadastro.php');
    exit;
}

// Processar formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', 'Token de segurança inválido. Tente novamente.');
        header('Location: cadastro.php');
        exit;
    }
    
    // Obter e validar dados do formulário
    $username = sanitizeInput($_POST['username'] ?? '', 'string');
    $email = sanitizeInput($_POST['email'] ?? '', 'email');
    $password = $_POST['password'] ?? '';
    $nome_completo = sanitizeInput($_POST['nome_completo'] ?? '', 'string');
    $telefone = sanitizeInput($_POST['telefone'] ?? '', 'string');
    $whatsapp = sanitizeInput($_POST['whatsapp'] ?? '', 'string');
    $endereco = sanitizeInput($_POST['endereco'] ?? '', 'string');
    $cidade = sanitizeInput($_POST['cidade'] ?? '', 'string');
    $estado = sanitizeInput($_POST['estado'] ?? '', 'string');
    $cpf = sanitizeInput($_POST['cpf'] ?? '', 'string');
    $rg = sanitizeInput($_POST['rg'] ?? '', 'string');
    
    // Validações básicas
    if (empty($username) || empty($email) || empty($password) || empty($nome_completo)) {
        set_flash_message('danger', 'Todos os campos obrigatórios devem ser preenchidos.');
        header('Location: cadastro.php');
        exit;
    }
    
    if (!is_valid_username($username)) {
        set_flash_message('danger', 'Nome de usuário inválido. Use apenas letras, números e underscore (3-20 caracteres).');
        header('Location: cadastro.php');
        exit;
    }
    
    if (!is_valid_email($email)) {
        set_flash_message('danger', 'Endereço de e-mail inválido.');
        header('Location: cadastro.php');
        exit;
    }
    
    if (!is_valid_password($password)) {
        set_flash_message('danger', 'A senha deve ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas e números.');
        header('Location: cadastro.php');
        exit;
    }
    
    // Verificar se usuário ou email já existem
    $db = get_sqlite_connection();
    $stmt = $db->prepare('
        SELECT id FROM solicitacao_cadastro 
        WHERE (username = :username OR email = :email) AND status = "pendente"
    ');
    $stmt->execute([
        'username' => $username,
        'email' => $email
    ]);
    
    if ($stmt->fetch()) {
        set_flash_message('warning', 'Já existe uma solicitação pendente com este nome de usuário ou e-mail.');
        header('Location: cadastro.php');
        exit;
    }
    
    // Verificar certificações
    $nr10 = isset($_POST['nr10']) ? 1 : 0;
    $nr10_validade = !empty($_POST['nr10_validade']) ? $_POST['nr10_validade'] : null;
    
    $nr20 = isset($_POST['nr20']) ? 1 : 0;
    $nr20_validade = !empty($_POST['nr20_validade']) ? $_POST['nr20_validade'] : null;
    
    $nr35 = isset($_POST['nr35']) ? 1 : 0;
    $nr35_validade = !empty($_POST['nr35_validade']) ? $_POST['nr35_validade'] : null;
    
    $aso = isset($_POST['aso']) ? 1 : 0;
    $aso_validade = !empty($_POST['aso_validade']) ? $_POST['aso_validade'] : null;
    
    // Processar uploads de arquivos
    $foto_perfil = null;
    $foto_perfil_tipo = null;
    $nr10_documento = null;
    $nr10_documento_tipo = null;
    $nr20_documento = null;
    $nr20_documento_tipo = null;
    $nr35_documento = null;
    $nr35_documento_tipo = null;
    $aso_documento = null;
    $aso_documento_tipo = null;
    
    // Processar foto de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $validation = validateFileUpload($_FILES['foto_perfil'], ['image/jpeg', 'image/png', 'image/gif']);
        if ($validation['valid']) {
            $foto_perfil = file_get_contents($_FILES['foto_perfil']['tmp_name']);
            $foto_perfil_tipo = $validation['mime_type'];
        } else {
            set_flash_message('danger', 'Erro no upload da foto de perfil: ' . $validation['error']);
            header('Location: cadastro.php');
            exit;
        }
    }
    
    // Processar documentos das certificações
    $certificacoes = [
        'nr10' => ['documento' => &$nr10_documento, 'tipo' => &$nr10_documento_tipo],
        'nr20' => ['documento' => &$nr20_documento, 'tipo' => &$nr20_documento_tipo],
        'nr35' => ['documento' => &$nr35_documento, 'tipo' => &$nr35_documento_tipo],
        'aso' => ['documento' => &$aso_documento, 'tipo' => &$aso_documento_tipo]
    ];
    
    foreach ($certificacoes as $cert => $refs) {
        if (isset($_FILES[$cert . '_documento']) && $_FILES[$cert . '_documento']['error'] === UPLOAD_ERR_OK) {
            $validation = validateFileUpload($_FILES[$cert . '_documento'], ['application/pdf', 'image/jpeg', 'image/png']);
            if ($validation['valid']) {
                $refs['documento'] = file_get_contents($_FILES[$cert . '_documento']['tmp_name']);
                $refs['tipo'] = $validation['mime_type'];
            } else {
                set_flash_message('danger', "Erro no upload do documento {$cert}: " . $validation['error']);
                header('Location: cadastro.php');
                exit;
            }
        }
    }
    
    // Inserir solicitação no banco de dados
    try {
        $stmt = $db->prepare('
            INSERT INTO solicitacao_cadastro (
                username, email, password_hash, nome_completo, telefone, whatsapp,
                endereco, cidade, estado, cpf, rg,
                foto_perfil, foto_perfil_tipo,
                nr10, nr10_validade, nr10_documento, nr10_documento_tipo,
                nr20, nr20_validade, nr20_documento, nr20_documento_tipo,
                nr35, nr35_validade, nr35_documento, nr35_documento_tipo,
                aso, aso_validade, aso_documento, aso_documento_tipo,
                data_solicitacao, status
            ) VALUES (
                :username, :email, :password_hash, :nome_completo, :telefone, :whatsapp,
                :endereco, :cidade, :estado, :cpf, :rg,
                :foto_perfil, :foto_perfil_tipo,
                :nr10, :nr10_validade, :nr10_documento, :nr10_documento_tipo,
                :nr20, :nr20_validade, :nr20_documento, :nr20_documento_tipo,
                :nr35, :nr35_validade, :nr35_documento, :nr35_documento_tipo,
                :aso, :aso_validade, :aso_documento, :aso_documento_tipo,
                :data_solicitacao, :status
            )
        ');
        
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => hash_password($password),
            'nome_completo' => $nome_completo,
            'telefone' => $telefone,
            'whatsapp' => $whatsapp,
            'endereco' => $endereco,
            'cidade' => $cidade,
            'estado' => $estado,
            'cpf' => $cpf,
            'rg' => $rg,
            'foto_perfil' => $foto_perfil,
            'foto_perfil_tipo' => $foto_perfil_tipo,
            'nr10' => $nr10,
            'nr10_validade' => $nr10_validade,
            'nr10_documento' => $nr10_documento,
            'nr10_documento_tipo' => $nr10_documento_tipo,
            'nr20' => $nr20,
            'nr20_validade' => $nr20_validade,
            'nr20_documento' => $nr20_documento,
            'nr20_documento_tipo' => $nr20_documento_tipo,
            'nr35' => $nr35,
            'nr35_validade' => $nr35_validade,
            'nr35_documento' => $nr35_documento,
            'nr35_documento_tipo' => $nr35_documento_tipo,
            'aso' => $aso,
            'aso_validade' => $aso_validade,
            'aso_documento' => $aso_documento,
            'aso_documento_tipo' => $aso_documento_tipo,
            'data_solicitacao' => date('Y-m-d H:i:s'),
            'status' => 'pendente'
        ]);
        
        set_flash_message('success', 'Solicitação de cadastro enviada com sucesso! Aguarde a aprovação do administrador.');
        logSecurityEvent('CADASTRO_SOLICITADO', "Usuário: {$username}, Email: {$email}");
        
    } catch (Exception $e) {
        error_log('Erro ao inserir solicitação: ' . $e->getMessage());
        set_flash_message('danger', 'Erro interno do servidor. Tente novamente mais tarde.');
    }
    
    header('Location: cadastro.php');
    exit;
}

// Obter mensagem flash, se houver
$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Técnico - Attend Services</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #000000;
            --secondary: #f6b21b;
            --accent: #f6b21b;
            --dark: #121212;
            --light: #FFFFFF;
            --light-gray: #f8f9fa;
            --gray: #808080;
            --dark-gray: #333333;
            --font-primary: 'Roboto', sans-serif;
            --font-secondary: 'Montserrat', sans-serif;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-primary);
            background: linear-gradient(135deg, var(--dark) 0%, #1a1a1a 100%);
            min-height: 100vh;
            color: var(--light);
            overflow-x: hidden;
        }

        .header-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            padding: 20px 0;
            border-bottom: 1px solid rgba(246, 178, 27, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: var(--secondary);
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
        }

        .back-btn {
            background: transparent;
            border: 2px solid var(--secondary);
            color: var(--secondary);
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .back-btn:hover {
            background: var(--secondary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(246, 178, 27, 0.2);
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary), #ffd700);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h1 {
            color: var(--secondary);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(246, 178, 27, 0.3);
        }

        .form-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .form-section {
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 15px;
            border: 1px solid rgba(246, 178, 27, 0.1);
        }

        .form-section h3 {
            color: var(--secondary);
            font-size: 1.5rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--light);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(246, 178, 27, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
            font-size: 1rem;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 20px rgba(246, 178, 27, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--gray);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .form-check input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .form-check label {
            margin: 0;
            color: var(--light);
            font-weight: 500;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--secondary), #ffd700);
            border: none;
            border-radius: 12px;
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            padding: 15px 40px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-right: 15px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(246, 178, 27, 0.4);
        }

        .btn-cancel {
            background: transparent;
            border: 2px solid var(--secondary);
            border-radius: 12px;
            color: var(--secondary);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            padding: 13px 30px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cancel:hover {
            background: var(--secondary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: none;
            font-weight: 500;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border-left: 4px solid #ff6b6b;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
            border-left: 4px solid #51cf66;
        }

        .alert-warning {
            background: rgba(255, 193, 7, 0.2);
            color: var(--secondary);
            border-left: 4px solid var(--secondary);
        }

        .form-text {
            color: var(--gray);
            font-size: 0.85rem;
            margin-top: 5px;
        }

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-elements::before,
        .floating-elements::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(246, 178, 27, 0.05) 0%, transparent 70%);
            animation: float 8s ease-in-out infinite;
        }

        .floating-elements::before {
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .floating-elements::after {
            bottom: 10%;
            right: 5%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-container {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .form-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-elements"></div>
    
    <div class="header-section">
        <div class="header-content">
            <a href="../pages/html/areaT.html" class="logo">
                <i class="fas fa-shield-alt"></i> Attend Services
            </a>
            <a href="../pages/html/areaT.html" class="back-btn">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h1><i class="fas fa-user-plus"></i> Cadastro de Técnico</h1>
                <p>Preencha todos os campos para se cadastrar como técnico especializado</p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'danger' ? 'exclamation-triangle' : ($flash['type'] === 'success' ? 'check-circle' : 'info-circle'); ?>"></i>
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <form action="cadastro.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-section">
                    <h3><i class="fas fa-key"></i> Informações de Acesso</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Nome de Usuário *</label>
                            <input type="text" id="username" name="username" placeholder="Digite seu nome de usuário" required>
                            <div class="form-text">Entre 3 e 20 caracteres, apenas letras, números e underscore.</div>
                        </div>
                        <div class="form-group">
                            <label for="email">E-mail *</label>
                            <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Senha *</label>
                            <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
                            <div class="form-text">Mínimo 8 caracteres, incluindo maiúsculas, minúsculas e números.</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Dados Pessoais</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome_completo">Nome Completo *</label>
                            <input type="text" id="nome_completo" name="nome_completo" placeholder="Digite seu nome completo" required>
                        </div>
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="tel" id="telefone" name="telefone" placeholder="(00) 0000-0000">
                        </div>
                        <div class="form-group">
                            <label for="whatsapp">WhatsApp</label>
                            <input type="tel" id="whatsapp" name="whatsapp" placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cpf">CPF</label>
                            <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00">
                        </div>
                        <div class="form-group">
                            <label for="rg">RG</label>
                            <input type="text" id="rg" name="rg" placeholder="Digite seu RG">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Endereço</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="endereco">Endereço</label>
                            <input type="text" id="endereco" name="endereco" placeholder="Rua, número, bairro">
                        </div>
                        <div class="form-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" id="cidade" name="cidade" placeholder="Digite sua cidade">
                        </div>
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <option value="">Selecione o estado</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-id-card"></i> Certificações</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="nr10" name="nr10">
                                <label for="nr10">NR-10</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nr10_validade">Validade</label>
                            <input type="date" id="nr10_validade" name="nr10_validade">
                        </div>
                        <div class="form-group">
                            <label for="nr10_documento">Documento</label>
                            <input type="file" id="nr10_documento" name="nr10_documento">
                            <div class="form-text">Formatos aceitos: PDF, JPG, PNG. Tamanho máximo: 5MB.</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="nr20" name="nr20">
                                <label for="nr20">NR-20</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nr20_validade">Validade</label>
                            <input type="date" id="nr20_validade" name="nr20_validade">
                        </div>
                        <div class="form-group">
                            <label for="nr20_documento">Documento</label>
                            <input type="file" id="nr20_documento" name="nr20_documento">
                            <div class="form-text">Formatos aceitos: PDF, JPG, PNG. Tamanho máximo: 5MB.</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="nr35" name="nr35">
                                <label for="nr35">NR-35</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nr35_validade">Validade</label>
                            <input type="date" id="nr35_validade" name="nr35_validade">
                        </div>
                        <div class="form-group">
                            <label for="nr35_documento">Documento</label>
                            <input type="file" id="nr35_documento" name="nr35_documento">
                            <div class="form-text">Formatos aceitos: PDF, JPG, PNG. Tamanho máximo: 5MB.</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="aso" name="aso">
                                <label for="aso">ASO</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="aso_validade">Validade</label>
                            <input type="date" id="aso_validade" name="aso_validade">
                        </div>
                        <div class="form-group">
                            <label for="aso_documento">Documento</label>
                            <input type="file" id="aso_documento" name="aso_documento">
                            <div class="form-text">Formatos aceitos: PDF, JPG, PNG. Tamanho máximo: 5MB.</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-camera"></i> Foto de Perfil</h3>
                    <div class="form-group">
                        <label for="foto_perfil">Foto de Perfil</label>
                        <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                        <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB.</div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Enviar Solicitação
                    </button>
                    <a href="../pages/html/areaT.html" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
