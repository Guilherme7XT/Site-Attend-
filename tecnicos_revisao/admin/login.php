<?php
require_once '../config.php';
require_once '../utils.php';
require_once '../security.php';

// Inicializar banco de dados SQLite
init_sqlite_db();

// Verificar rate limiting
if (!checkRateLimit('admin_login', 5, 900)) { // 5 tentativas por 15 minutos
    set_flash_message('danger', 'Muitas tentativas de login. Tente novamente em 15 minutos.');
    header('Location: login.php');
    exit;
}

// Verificar se já está logado
if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', 'Token de segurança inválido. Tente novamente.');
        header('Location: login.php');
        exit;
    }
    
    $username = sanitizeInput($_POST['username'] ?? '', 'string');
    $password = $_POST['password'] ?? '';
    
    // Verificar se o usuário existe
    $db = get_sqlite_connection();
    $stmt = $db->prepare('SELECT * FROM admin WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar se a conta está bloqueada
    if ($admin && isset($admin['locked_until']) && $admin['locked_until'] !== null) {
        $locked_until = new DateTime($admin['locked_until']);
        $now = new DateTime();
        
        if ($locked_until > $now) {
            set_flash_message('danger', 'Conta bloqueada temporariamente devido a múltiplas tentativas de login. Tente novamente mais tarde.');
            header('Location: login.php');
            exit;
        }
    }
    
    // Verificar se é o admin padrão (caso não exista no banco)
    global $config;
    if ($username === 'Admin' && $password === $config['admin']['password']) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['last_activity'] = time();
        
        header('Location: dashboard.php');
        exit;
    }
    
    // Verificar credenciais no banco de dados
    if ($admin && verify_password($password, $admin['password_hash'])) {
        // Reset login attempts on successful login
        $stmt = $db->prepare('
            UPDATE admin 
            SET login_attempts = 0, last_login = :last_login 
            WHERE id = :id
        ');
        $stmt->execute([
            'last_login' => date('Y-m-d H:i:s'),
            'id' => $admin['id']
        ]);
        
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['last_activity'] = time();
        
        header('Location: dashboard.php');
        exit;
    }
    
    // Incrementar tentativas de login e possivelmente bloquear a conta
    if ($admin) {
        $login_attempts = $admin['login_attempts'] + 1;
        
        // Bloquear a conta após 5 tentativas falhas
        if ($login_attempts >= 5) {
            $locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $stmt = $db->prepare('
                UPDATE admin 
                SET login_attempts = :login_attempts, locked_until = :locked_until 
                WHERE id = :id
            ');
            $stmt->execute([
                'login_attempts' => $login_attempts,
                'locked_until' => $locked_until,
                'id' => $admin['id']
            ]);
            
            set_flash_message('danger', 'Conta bloqueada temporariamente devido a múltiplas tentativas de login. Tente novamente em 15 minutos.');
        } else {
            $stmt = $db->prepare('
                UPDATE admin 
                SET login_attempts = :login_attempts 
                WHERE id = :id
            ');
            $stmt->execute([
                'login_attempts' => $login_attempts,
                'id' => $admin['id']
            ]);
            
            set_flash_message('danger', 'Credenciais inválidas!');
        }
    } else {
        // Mesmo que o usuário não exista, mostrar a mesma mensagem para evitar enumeração de usuários
        set_flash_message('danger', 'Credenciais inválidas!');
    }
    
    // Adicionar um pequeno atraso para dificultar ataques de força bruta
    sleep(1);
    
    header('Location: login.php');
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
    <title>Login Administrativo - Attend Services</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--light);
            overflow-x: hidden;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(246, 178, 27, 0.2);
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary), #ffd700);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section h1 {
            color: var(--secondary);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(246, 178, 27, 0.3);
        }

        .logo-section p {
            color: var(--gray);
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--light);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
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

        .form-group input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 20px rgba(246, 178, 27, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-group input::placeholder {
            color: var(--gray);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--secondary), #ffd700);
            border: none;
            border-radius: 12px;
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(246, 178, 27, 0.4);
        }

        .btn-back {
            width: 100%;
            padding: 12px;
            background: transparent;
            border: 2px solid var(--secondary);
            border-radius: 12px;
            color: var(--secondary);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-back:hover {
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
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(246, 178, 27, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        .floating-elements::before {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-elements::after {
            bottom: 20%;
            right: 10%;
            animation-delay: 3s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        @media (max-width: 768px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .logo-section h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-elements"></div>
    
    <div class="login-container">
        <div class="logo-section">
            <h1><i class="fas fa-shield-alt"></i> Attend</h1>
            <p>Login Administrativo</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fas fa-<?php echo $flash['type'] === 'danger' ? 'exclamation-triangle' : ($flash['type'] === 'success' ? 'check-circle' : 'info-circle'); ?>"></i>
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Nome de Usuário</label>
                <input type="text" id="username" name="username" placeholder="Digite seu nome de usuário" required>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Senha</label>
                <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
            
            <a href="../cadastro.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </form>
    </div>
</body>
</html>