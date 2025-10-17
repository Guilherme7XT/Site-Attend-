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

// Atualizar status da solicitação
try {
    $stmt = $db->prepare('UPDATE solicitacao_cadastro SET status = :status WHERE id = :id');
    $stmt->execute([
        'status' => 'rejeitado',
        'id' => $id
    ]);
    
    set_flash_message('success', 'Solicitação rejeitada com sucesso!');
} catch (Exception $e) {
    error_log('Erro ao rejeitar solicitação #' . $id . ': ' . $e->getMessage());
    set_flash_message('danger', 'Erro ao rejeitar solicitação: ' . $e->getMessage());
}

header('Location: solicitacoes.php');
exit;