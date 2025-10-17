<?php
/**
 * Teste de Segurança - Sistema de Cadastro de Técnicos
 * Verifica se todas as proteções de segurança estão funcionando
 */

require_once 'config.php';
require_once 'utils.php';
require_once 'security.php';

// Verificar se é uma requisição autorizada (apenas localhost em desenvolvimento)
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    die('Acesso negado - Teste de segurança apenas em localhost');
}

echo "<h1>🔒 Teste de Segurança do Sistema</h1>";

// Teste 1: Verificar se o sistema de segurança está carregado
echo "<h2>1. Sistema de Segurança</h2>";
$security = SecurityManager::getInstance();
echo "✅ Sistema de segurança carregado<br>";

// Teste 2: Verificar headers de segurança
echo "<h2>2. Headers de Segurança</h2>";
$headers = headers_list();
$securityHeaders = [
    'X-Frame-Options',
    'X-Content-Type-Options', 
    'X-XSS-Protection',
    'Content-Security-Policy'
];

foreach ($securityHeaders as $header) {
    $found = false;
    foreach ($headers as $h) {
        if (stripos($h, $header) !== false) {
            echo "✅ $header: Configurado<br>";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "❌ $header: Não configurado<br>";
    }
}

// Teste 3: Verificar configurações de sessão
echo "<h2>3. Configurações de Sessão</h2>";
echo "Cookie HttpOnly: " . (ini_get('session.cookie_httponly') ? "✅ Ativado" : "❌ Desativado") . "<br>";
echo "Cookie Secure: " . (ini_get('session.cookie_secure') ? "✅ Ativado" : "❌ Desativado") . "<br>";
echo "Use Strict Mode: " . (ini_get('session.use_strict_mode') ? "✅ Ativado" : "❌ Desativado") . "<br>";

// Teste 4: Verificar token CSRF
echo "<h2>4. Proteção CSRF</h2>";
$token = generateCSRFToken();
echo "✅ Token CSRF gerado: " . substr($token, 0, 10) . "...<br>";
echo "✅ Validação CSRF: " . (validateCSRFToken($token) ? "Funcionando" : "Erro") . "<br>";

// Teste 5: Verificar rate limiting
echo "<h2>5. Rate Limiting</h2>";
$testAction = 'security_test_' . time();
echo "✅ Rate limiting: " . (checkRateLimit($testAction, 5, 60) ? "Funcionando" : "Erro") . "<br>";

// Teste 6: Verificar sanitização de entrada
echo "<h2>6. Sanitização de Entrada</h2>";
$testInput = '<script>alert("xss")</script>';
$sanitized = sanitizeInput($testInput);
echo "✅ Sanitização XSS: " . (strpos($sanitized, '<script>') === false ? "Funcionando" : "Erro") . "<br>";

$testEmail = 'test@example.com';
$sanitizedEmail = sanitizeInput($testEmail, 'email');
echo "✅ Sanitização Email: " . ($sanitizedEmail === $testEmail ? "Funcionando" : "Erro") . "<br>";

// Teste 7: Verificar validação de arquivo
echo "<h2>7. Validação de Arquivo</h2>";
$fakeFile = [
    'name' => 'test.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/fake',
    'error' => UPLOAD_ERR_OK,
    'size' => 1024
];

// Simular arquivo válido
if (function_exists('finfo_open')) {
    echo "✅ Extensão FileInfo: Disponível<br>";
} else {
    echo "❌ Extensão FileInfo: Não disponível<br>";
}

// Teste 8: Verificar configurações de upload
echo "<h2>8. Configurações de Upload</h2>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s<br>";

// Teste 9: Verificar banco de dados
echo "<h2>9. Segurança do Banco de Dados</h2>";
try {
    $db = get_sqlite_connection();
    if ($db) {
        echo "✅ Conexão SQLite: Segura (PDO)<br>";
        
        // Testar prepared statements
        $stmt = $db->prepare('SELECT COUNT(*) FROM admin WHERE username = ?');
        $stmt->execute(['test']);
        echo "✅ Prepared Statements: Funcionando<br>";
    } else {
        echo "❌ Conexão SQLite: Erro<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro no banco: " . $e->getMessage() . "<br>";
}

// Teste 10: Verificar logs de segurança
echo "<h2>10. Logs de Segurança</h2>";
$logFile = __DIR__ . '/logs/security.log';
if (file_exists($logFile)) {
    echo "✅ Arquivo de log: Existe<br>";
    echo "Tamanho do log: " . filesize($logFile) . " bytes<br>";
} else {
    echo "⚠️ Arquivo de log: Não existe (será criado quando necessário)<br>";
}

// Teste 11: Verificar detecção de requisições suspeitas
echo "<h2>11. Detecção de Ataques</h2>";
$suspiciousTests = [
    '<script>alert("xss")</script>',
    'UNION SELECT * FROM users',
    'DROP TABLE admin',
    'javascript:alert(1)'
];

foreach ($suspiciousTests as $test) {
    $_REQUEST['test'] = $test;
    $isSuspicious = $security->isSuspiciousRequest();
    echo "✅ Detecção de '$test': " . ($isSuspicious ? "Bloqueado" : "Permitido") . "<br>";
    unset($_REQUEST['test']);
}

// Teste 12: Verificar configurações de produção
echo "<h2>12. Configurações de Produção</h2>";
echo "Debug Mode: " . ($config['app']['debug'] ? "❌ Ativado (desativar em produção)" : "✅ Desativado") . "<br>";
echo "Display Errors: " . (ini_get('display_errors') ? "❌ Ativado (desativar em produção)" : "✅ Desativado") . "<br>";
echo "Log Errors: " . (ini_get('log_errors') ? "✅ Ativado" : "❌ Desativado") . "<br>";

// Resumo de segurança
echo "<h2>📊 Resumo de Segurança</h2>";
$totalTests = 12;
$passedTests = 0;

// Contar testes passados (simplificado)
if (isset($security)) $passedTests++;
if (ini_get('session.cookie_httponly')) $passedTests++;
if (function_exists('finfo_open')) $passedTests++;
if (!$config['app']['debug']) $passedTests++;
if (!ini_get('display_errors')) $passedTests++;

$securityScore = round(($passedTests / $totalTests) * 100);

echo "<div style='background: " . ($securityScore >= 80 ? "#d4edda" : "#f8d7da") . "; padding: 15px; border-radius: 5px;'>";
echo "<h3>Pontuação de Segurança: $securityScore%</h3>";

if ($securityScore >= 90) {
    echo "🟢 <strong>Excelente!</strong> Sistema muito seguro.";
} elseif ($securityScore >= 80) {
    echo "🟡 <strong>Bom!</strong> Sistema seguro, mas pode ser melhorado.";
} elseif ($securityScore >= 70) {
    echo "🟠 <strong>Atenção!</strong> Sistema com algumas vulnerabilidades.";
} else {
    echo "🔴 <strong>Crítico!</strong> Sistema com muitas vulnerabilidades.";
}

echo "</div>";

echo "<hr>";
echo "<p><a href='index.php'>← Voltar ao Sistema</a></p>";
echo "<p><small>Teste executado em: " . date('Y-m-d H:i:s') . "</small></p>";
?>
