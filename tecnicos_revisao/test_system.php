<?php
// Arquivo de teste para verificar se o sistema está funcionando
require_once 'config.php';
require_once 'utils.php';

echo "<h1>Teste do Sistema de Cadastro de Técnicos</h1>";

// Teste 1: Verificar se as configurações estão carregadas
echo "<h2>1. Teste de Configurações</h2>";
echo "Configurações carregadas: " . (isset($config) ? "✅ OK" : "❌ ERRO") . "<br>";
echo "Modo debug: " . ($config['app']['debug'] ? "Ativado" : "Desativado") . "<br>";

// Teste 2: Verificar se as pastas necessárias existem
echo "<h2>2. Teste de Pastas</h2>";
$folders = ['uploads', 'database', 'admin/uploads'];
foreach ($folders as $folder) {
    if (file_exists($folder)) {
        echo "Pasta '$folder': ✅ Existe<br>";
        if (is_writable($folder)) {
            echo "Pasta '$folder': ✅ Tem permissão de escrita<br>";
        } else {
            echo "Pasta '$folder': ⚠️ Sem permissão de escrita<br>";
        }
    } else {
        echo "Pasta '$folder': ❌ Não existe<br>";
    }
}

// Teste 3: Verificar banco de dados SQLite
echo "<h2>3. Teste de Banco de Dados SQLite</h2>";
try {
    $db = get_sqlite_connection();
    if ($db) {
        echo "Conexão SQLite: ✅ OK<br>";
        
        // Verificar se as tabelas existem
        $tables = ['admin', 'solicitacao_cadastro'];
        foreach ($tables as $table) {
            $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
            $stmt->execute([$table]);
            if ($stmt->fetch()) {
                echo "Tabela '$table': ✅ Existe<br>";
            } else {
                echo "Tabela '$table': ❌ Não existe<br>";
            }
        }
    } else {
        echo "Conexão SQLite: ❌ ERRO<br>";
    }
} catch (Exception $e) {
    echo "Erro SQLite: " . $e->getMessage() . "<br>";
}

// Teste 4: Verificar banco de dados MySQL
echo "<h2>4. Teste de Banco de Dados MySQL</h2>";
try {
    $db = get_mysql_connection();
    if ($db) {
        echo "Conexão MySQL: ✅ OK<br>";
        
        // Verificar se as tabelas existem
        $stmt = $db->prepare("SHOW TABLES");
        $stmt->execute();
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tabelas encontradas: " . implode(', ', $tables) . "<br>";
    } else {
        echo "Conexão MySQL: ❌ ERRO<br>";
    }
} catch (Exception $e) {
    echo "Erro MySQL: " . $e->getMessage() . "<br>";
}

// Teste 5: Verificar extensões PHP necessárias
echo "<h2>5. Teste de Extensões PHP</h2>";
$extensions = ['pdo', 'pdo_sqlite', 'pdo_mysql', 'gd', 'fileinfo'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "Extensão '$ext': ✅ Carregada<br>";
    } else {
        echo "Extensão '$ext': ❌ Não carregada<br>";
    }
}

// Teste 6: Verificar permissões de sessão
echo "<h2>6. Teste de Sessão</h2>";
if (session_start()) {
    echo "Sessão: ✅ Iniciada<br>";
    $_SESSION['test'] = 'test_value';
    if (isset($_SESSION['test'])) {
        echo "Sessão: ✅ Funcionando<br>";
        unset($_SESSION['test']);
    }
} else {
    echo "Sessão: ❌ ERRO<br>";
}

echo "<h2>7. Informações do Sistema</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Path: " . __DIR__ . "<br>";

echo "<hr>";
echo "<p><a href='index.php'>Voltar ao Sistema</a></p>";
?>
