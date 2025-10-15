<?php
// Incluir PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Se você instalou via Composer

// Configurações de cabeçalho para AJAX
header('Content-Type: application/json');

// Verificação de honeypot para evitar spam
if (!empty($_POST['website'])) {
    // Se o campo honeypot foi preenchido, é provavelmente um bot
    echo json_encode(['success' => true]); // Simula sucesso para o bot
    exit;
}

// Função para sanitizar entradas
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Inicializar array de erros
$errors = [];

// Validar campos obrigatórios (apenas os 3 campos do formulário)
if (empty($_POST['nome']) || strlen($_POST['nome']) < 3) {
    $errors[] = 'Nome completo é obrigatório e deve ter pelo menos 3 caracteres.';
}

if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'E-mail válido é obrigatório.';
}

if (empty($_POST['contato'])) {
    $errors[] = 'Contato (WhatsApp) é obrigatório.';
}

// Se houver erros, retorne-os
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

// Capturar e sanitizar os dados do formulário (apenas os 3 campos)
$nome = sanitize_input($_POST['nome']);
$email = sanitize_input($_POST['email']);
$contato = sanitize_input($_POST['contato']);

try {
    // Criar uma nova instância do PHPMailer
    $mail = new PHPMailer(true);

    // Configurações do servidor
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'attendos@attend.services';
    $mail->Password   = 'Master330781!)';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Remetente
    $mail->setFrom('attendos@attend.services', 'Attend Services - Formulário de Contato');
    $mail->addReplyTo($email, $name);

    // Destinatários
    $mail->addAddress('elber.viana@att-br.com', 'Elber Viana');
    $mail->addAddress('renatogames2@gmail.com', 'Renato');
    $mail->addAddress('contato@attendservice.com.br', 'Contato Attend');

    // Conteúdo do email
    $mail->isHTML(true);
    $mail->Subject = 'Nova solicitação de contato - Attend Services';

    // Data atual formatada
    $data_atual = date('d/m/Y H:i');

    // Corpo do email em HTML com layout melhorado
    $mailBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Nova Solicitação - Attend Services</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');
            
            body {
                font-family: 'Roboto', Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f9f9f9;
                margin: 0;
                padding: 0;
            }
            
            .email-container {
                max-width: 650px;
                margin: 0 auto;
                background-color: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
            
            .header {
                background-color: #FF6B00;
                padding: 30px;
                text-align: center;
            }
            
            .header h1 {
                color: white;
                margin: 0;
                font-size: 28px;
                font-weight: 700;
            }
            
            .content {
                padding: 30px;
            }
            
            .intro {
                margin-bottom: 25px;
                font-size: 16px;
            }
            
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
                border-radius: 6px;
                overflow: hidden;
                border: 1px solid #e0e0e0;
            }
            
            .data-table th, .data-table td {
                padding: 12px 15px;
                text-align: left;
                border: 1px solid #e0e0e0;
            }
            
            .data-table th {
                background-color: #f2f2f2;
                font-weight: 500;
                color: #333;
            }
            
            .data-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            
            .message-box {
                background-color: #f9f9f9;
                border-left: 4px solid #FF6B00;
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .message-heading {
                color: #FF6B00;
                font-weight: 500;
                margin-top: 0;
                margin-bottom: 10px;
            }
            
            .footer {
                background-color: #f2f2f2;
                padding: 20px;
                text-align: center;
                font-size: 14px;
                color: #666;
                border-top: 3px solid #FF6B00;
            }
            
            .timestamp {
                text-align: right;
                font-size: 13px;
                color: #888;
                margin-top: 20px;
            }
            
            @media only screen and (max-width: 600px) {
                .header {
                    padding: 20px;
                }
                
                .content {
                    padding: 20px;
                }
                
                .header h1 {
                    font-size: 24px;
                }
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>ATTEND SERVICES</h1>
            </div>
            
            <div class='content'>
                <div class='intro'>
                    <h2>Nova Solicitação de Contato</h2>
                    <p>Uma nova solicitação foi enviada através do formulário de contato do site. Abaixo estão os detalhes fornecidos pelo cliente:</p>
                </div>
                
                <table class='data-table'>
                    <tr>
                        <th width='35%'>Nome Completo</th>
                        <td>{$nome}</td>
                    </tr>
                    <tr>
                        <th>E-mail</th>
                        <td><a href='mailto:{$email}'>{$email}</a></td>
                    </tr>
                    <tr>
                        <th>Contato (WhatsApp)</th>
                        <td>{$contato}</td>
                    </tr>
                </table>
                
                <div class='timestamp'>
                    <p>Enviado em: {$data_atual}</p>
                </div>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " Attend Services. Todos os direitos reservados.</p>
                <p>Este é um e-mail automático, por favor não responda diretamente.</p>
                <p>Para responder ao cliente, utilize o e-mail: <a href='mailto:{$email}'>{$email}</a></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $mail->Body = $mailBody;
    
    // Versão alternativa em texto puro para clientes de email que não suportam HTML
    $mail->AltBody = "Nova solicitação de contato - Attend Services\n\n".
                    "Nome Completo: {$nome}\n".
                    "E-mail: {$email}\n".
                    "Contato (WhatsApp): {$contato}\n\n".
                    "Enviado em: {$data_atual}";
    
    // Enviar o email
    $mail->send();
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true
    ]);
    
} catch (Exception $e) {
    // Registrar o erro em um arquivo de log
    error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
    
    // Resposta de erro
    echo json_encode([
        'success' => false,
        'errors' => ["Não foi possível enviar o e-mail. Erro: {$mail->ErrorInfo}"]
    ]);
}
?>