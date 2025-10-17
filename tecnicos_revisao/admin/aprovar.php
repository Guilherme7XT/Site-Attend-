<?php
require_once '../config.php';
require_once '../utils.php';

// Verificar se o administrador está logado
check_admin_session();

// Verificar se o ID foi fornecido
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !is_numeric($_POST['id'])) {
    set_flash_message('danger', 'Requisição inválida.');
    header('Location: solicitacoes.php');
    exit;
}

$id = (int)$_POST['id'];

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

// Verificar se a solicitação já foi processada
if ($solicitacao['status'] !== 'pendente') {
    set_flash_message('warning', 'Esta solicitação já foi processada anteriormente.');
    header('Location: solicitacoes.php');
    exit;
}

// Iniciar transação
$db->beginTransaction();

try {
    // Conectar ao banco MySQL
    $mysql = get_mysql_connection();
    
    if (!$mysql) {
        throw new Exception('Não foi possível conectar ao banco de dados MySQL.');
    }
    
    // Iniciar transação MySQL
    $mysql->beginTransaction();
    
    // Inserir na tabela de usuários
    $stmt = $mysql->prepare('
        INSERT INTO usuario (username, email, password_hash, role, nome_completo, telefone, whatsapp, foto_perfil, ativo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $solicitacao['username'],
        $solicitacao['email'],
        $solicitacao['password_hash'],
        'tecnico',
        $solicitacao['nome_completo'],
        $solicitacao['telefone'],
        $solicitacao['whatsapp'],
        $solicitacao['foto_perfil'],
        1 // ativo
    ]);
    
    // Obter o ID do usuário inserido
    $usuario_id = $mysql->lastInsertId();
    
    // Inserir informações técnicas
    $stmt = $mysql->prepare('
        INSERT INTO tecnico_info (
            usuario_id, endereco, cidade, estado, cpf, rg,
            nr10, nr10_validade, nr10_documento,
            nr20, nr20_validade, nr20_documento, 
            nr35, nr35_validade, nr35_documento,
            aso, aso_validade, aso_documento
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $usuario_id,
        $solicitacao['endereco'],
        $solicitacao['cidade'],
        $solicitacao['estado'],
        $solicitacao['cpf'],
        $solicitacao['rg'],
        $solicitacao['nr10'],
        $solicitacao['nr10_validade'],
        $solicitacao['nr10_documento'],
        $solicitacao['nr20'],
        $solicitacao['nr20_validade'],
        $solicitacao['nr20_documento'],
        $solicitacao['nr35'],
        $solicitacao['nr35_validade'],
        $solicitacao['nr35_documento'],
        $solicitacao['aso'],
        $solicitacao['aso_validade'],
        $solicitacao['aso_documento'],
    ]);
    
    // Confirmar transação MySQL
    $mysql->commit();
    
    // Atualizar status da solicitação
    $stmt = $db->prepare('UPDATE solicitacao_cadastro SET status = :status WHERE id = :id');
    $stmt->execute([
        'status' => 'aprovado',
        'id' => $id
    ]);
    
    // Confirmar transação SQLite
    $db->commit();
    
    set_flash_message('success', 'Solicitação aprovada com sucesso!');
} catch (Exception $e) {
    // Rollback em ambos os bancos de dados em caso de erro
    if (isset($mysql) && $mysql) {
        $mysql->rollBack();
    }
    $db->rollBack();
    
    error_log('Erro ao aprovar solicitação #' . $id . ': ' . $e->getMessage());
    set_flash_message('danger', 'Erro ao aprovar solicitação: ' . $e->getMessage());
}

header('Location: solicitacoes.php');
exit;