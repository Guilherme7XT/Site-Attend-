<?php
require_once 'config.php';

// Funções de segurança para validação de dados
function is_valid_username($username) {
    // Verifica se o nome de usuário é válido (apenas letras, números e underscore)
    return preg_match('/^[a-zA-Z0-9_]{3,20}/', $username);
}

function is_valid_email($email) {
    // Verifica se o email é válido
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_password($password) {
    // Verifica se a senha atende aos requisitos mínimos de segurança
    // Pelo menos 8 caracteres, uma letra maiúscula, uma minúscula e um número
    if (strlen($password) < 8) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    return true;
}

function is_valid_image($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Verificar extensão
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    global $config;
    if (!in_array($ext, $config['security']['allowed_image_extensions'])) {
        return false;
    }
    
    // Verificar tipo MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    $allowed_mime_types = [
        'image/jpeg',
        'image/png',
        'image/gif'
    ];
    
    if (!in_array($mime_type, $allowed_mime_types)) {
        return false;
    }
    
    // Verificação adicional com getimagesize
    $image_info = @getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return false;
    }
    
    return true;
}

function is_valid_pdf($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Verificar extensão
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return false;
    }
    
    // Verificar tipo MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    if ($mime_type !== 'application/pdf') {
        return false;
    }
    
    // Verificar assinatura do PDF
    $handle = fopen($file['tmp_name'], 'rb');
    $signature = fread($handle, 4);
    fclose($handle);
    
    return $signature === '%PDF';
}

function is_safe_file($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    global $config;
    
    if (in_array($ext, $config['security']['allowed_image_extensions'])) {
        return is_valid_image($file);
    } elseif (in_array($ext, $config['security']['allowed_document_extensions'])) {
        return is_valid_pdf($file);
    }
    
    return false;
}

function sanitize_filename($filename) {
    // Gera um nome de arquivo aleatório mantendo a extensão original
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

function get_file_type($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    global $config;
    
    if (in_array($ext, $config['security']['allowed_image_extensions'])) {
        return 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
    } elseif ($ext === 'pdf') {
        return 'application/pdf';
    }
    
    return null;
}

// Funções para banco de dados
function get_sqlite_connection() {
    global $config;
    
    try {
        $db = new PDO('sqlite:' . $config['db']['sqlite']['path']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        error_log('SQLite Connection Error: ' . $e->getMessage());
        return null;
    }
}

function get_mysql_connection() {
    global $config;
    
    try {
        $dsn = "mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['database']};charset={$config['db']['mysql']['charset']}";
        $db = new PDO($dsn, $config['db']['mysql']['user'], $config['db']['mysql']['password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        error_log('MySQL Connection Error: ' . $e->getMessage());
        return null;
    }
}

// Funções para mensagens flash
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// Funções para autenticação
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function check_admin_session() {
    if (!is_admin_logged_in()) {
        header('Location: admin/login.php');
        exit;
    }
    
    // Verificar se a sessão expirou
    if (!isset($_SESSION['last_activity'])) {
        session_unset();
        session_destroy();
        set_flash_message('warning', 'Sua sessão expirou. Por favor, faça login novamente.');
        header('Location: admin/login.php');
        exit;
    }
    
    // Verificar se passou mais de 30 minutos desde a última atividade
    if (time() - $_SESSION['last_activity'] > 1800) { // 30 minutos
        session_unset();
        session_destroy();
        set_flash_message('warning', 'Sua sessão expirou por inatividade. Por favor, faça login novamente.');
        header('Location: admin/login.php');
        exit;
    }
    
    // Atualizar timestamp da última atividade
    $_SESSION['last_activity'] = time();
}

// Inicializar banco de dados SQLite
function init_sqlite_db() {
    $db = get_sqlite_connection();
    
    if ($db) {
        // Criar tabela de administradores
        $db->exec('
            CREATE TABLE IF NOT EXISTS admin (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                last_login DATETIME,
                login_attempts INTEGER DEFAULT 0,
                locked_until DATETIME
            )
        ');
        
        // Criar tabela de solicitações de cadastro
        $db->exec('
            CREATE TABLE IF NOT EXISTS solicitacao_cadastro (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                email TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                nome_completo TEXT NOT NULL,
                telefone TEXT,
                whatsapp TEXT,
                foto_perfil BLOB,
                foto_perfil_tipo TEXT,
                endereco TEXT,
                cidade TEXT,
                estado TEXT,
                cpf TEXT,
                rg TEXT,
                nr10 INTEGER DEFAULT 0,
                nr10_validade DATE,
                nr10_documento BLOB,
                nr10_documento_tipo TEXT,
                nr20 INTEGER DEFAULT 0,
                nr20_validade DATE,
                nr20_documento BLOB,
                nr20_documento_tipo TEXT,
                nr35 INTEGER DEFAULT 0,
                nr35_validade DATE,
                nr35_documento BLOB,
                nr35_documento_tipo TEXT,
                aso INTEGER DEFAULT 0,
                aso_validade DATE,
                aso_documento BLOB,
                aso_documento_tipo TEXT,
                data_solicitacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                status TEXT DEFAULT "pendente"
            )
        ');
        
        // Verificar se o admin padrão existe
        $stmt = $db->prepare('SELECT id FROM admin WHERE username = :username');
        $stmt->execute(['username' => 'Admin']);
        
        if (!$stmt->fetch()) {
            global $config;
            $password_hash = hash_password($config['admin']['password']);
            
            $stmt = $db->prepare('
                INSERT INTO admin (username, password_hash)
                VALUES (:username, :password_hash)
            ');
            
            $stmt->execute([
                'username' => 'Admin',
                'password_hash' => $password_hash
            ]);
            
            echo "Usuário Admin criado com sucesso!\n";
        }
        
        return true;
    }
    
    return false;
}