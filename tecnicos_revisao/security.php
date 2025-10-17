<?php
/**
 * Arquivo de Segurança - Sistema de Cadastro de Técnicos
 * Implementa proteções avançadas contra ataques comuns
 */

// Prevenir acesso direto
if (!defined('SECURITY_INCLUDED')) {
    define('SECURITY_INCLUDED', true);
}

// Configurações de segurança
class SecurityManager {
    private static $instance = null;
    private $config;
    
    private function __construct() {
        global $config;
        $this->config = $config;
        $this->initSecurity();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initSecurity() {
        // Headers de segurança
        $this->setSecurityHeaders();
        
        // Configurações de sessão segura
        $this->configureSecureSession();
        
        // Proteção contra ataques comuns
        $this->protectAgainstCommonAttacks();
    }
    
    private function setSecurityHeaders() {
        // Prevenir clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevenir MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "img-src 'self' data:; " .
               "font-src 'self' https://cdn.jsdelivr.net; " .
               "connect-src 'self';";
        header("Content-Security-Policy: $csp");
    }
    
    private function configureSecureSession() {
        // Configurações de cookie seguras (apenas se a sessão não estiver ativa)
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
        }
        
        // Regenerar ID de sessão periodicamente
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    private function protectAgainstCommonAttacks() {
        // Proteção contra SQL Injection (já implementada com PDO)
        // Proteção contra XSS (sanitização de entrada)
        // Proteção contra CSRF (tokens)
        // Rate limiting (implementado abaixo)
    }
    
    // Geração de token CSRF
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Validação de token CSRF
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Rate limiting
    public function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
        $ip = $this->getClientIP();
        $key = "rate_limit_{$action}_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        }
        
        $data = $_SESSION[$key];
        
        // Reset se passou o tempo
        if (time() - $data['first_attempt'] > $timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
            return true;
        }
        
        // Verificar limite
        if ($data['count'] >= $maxAttempts) {
            return false;
        }
        
        // Incrementar contador
        $_SESSION[$key]['count']++;
        return true;
    }
    
    // Obter IP real do cliente
    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                  'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // Sanitização avançada de entrada
    public function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Validação de arquivo mais rigorosa
    public function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']) {
        // Verificar se o arquivo foi enviado
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Arquivo não foi enviado corretamente'];
        }
        
        // Verificar tamanho
        if ($file['size'] > $this->config['app']['max_content_length']) {
            return ['valid' => false, 'error' => 'Arquivo muito grande'];
        }
        
        // Verificar tipo MIME real
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Tipo de arquivo não permitido'];
        }
        
        // Verificar extensão
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        
        if (!in_array($extension, $allowedExtensions)) {
            return ['valid' => false, 'error' => 'Extensão não permitida'];
        }
        
        // Verificar se é realmente uma imagem (para arquivos de imagem)
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return ['valid' => false, 'error' => 'Arquivo de imagem inválido'];
            }
        }
        
        // Verificar assinatura do PDF
        if ($mimeType === 'application/pdf') {
            $handle = fopen($file['tmp_name'], 'rb');
            $signature = fread($handle, 4);
            fclose($handle);
            
            if ($signature !== '%PDF') {
                return ['valid' => false, 'error' => 'Arquivo PDF inválido'];
            }
        }
        
        return ['valid' => true, 'mime_type' => $mimeType, 'extension' => $extension];
    }
    
    // Log de segurança
    public function logSecurityEvent($event, $details = '') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'event' => $event,
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/logs/security.log';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    // Verificar se a requisição é suspeita
    public function isSuspiciousRequest() {
        $suspiciousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/union.*select/i',
            '/drop.*table/i',
            '/insert.*into/i',
            '/delete.*from/i',
            '/update.*set/i'
        ];
        
        $input = json_encode($_REQUEST);
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityEvent('SUSPICIOUS_REQUEST', "Pattern: $pattern");
                return true;
            }
        }
        
        return false;
    }
}

// Inicializar sistema de segurança
$security = SecurityManager::getInstance();

// Verificar requisições suspeitas
if ($security->isSuspiciousRequest()) {
    http_response_code(403);
    die('Acesso negado');
}

// Funções auxiliares de segurança
function generateCSRFToken() {
    return SecurityManager::getInstance()->generateCSRFToken();
}

function validateCSRFToken($token) {
    return SecurityManager::getInstance()->validateCSRFToken($token);
}

function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    return SecurityManager::getInstance()->checkRateLimit($action, $maxAttempts, $timeWindow);
}

function sanitizeInput($input, $type = 'string') {
    return SecurityManager::getInstance()->sanitizeInput($input, $type);
}

function validateFileUpload($file, $allowedTypes = null) {
    return SecurityManager::getInstance()->validateFileUpload($file, $allowedTypes);
}

function logSecurityEvent($event, $details = '') {
    SecurityManager::getInstance()->logSecurityEvent($event, $details);
}
?>
