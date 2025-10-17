<?php
require_once '../config.php';
require_once '../utils.php';

// Limpar a sessão
session_unset();
session_destroy();

// Redirecionar para a página de login
set_flash_message('success', 'Logout realizado com sucesso!');
header('Location: login.php');
exit;