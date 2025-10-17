<?php
// Configurações do ambiente
$dotenv = [];
if (file_exists('.env')) {
    $dotenv = parse_ini_file('.env');
} else {
    // Configurações padrão se .env não existir
    $dotenv = [
        'SECRET_KEY' => 'default_secret_key_change_in_production',
        'DEBUG' => 'false', // Mudado para false em produção
        'DB_HOST' => '66.70.203.223',
        'DB_USER' => 'RenatoMaster',
        'DB_PASSWORD' => 'Renato44404031',
        'DB_NAME' => 'sistema_os',
        'ADMIN_PASSWORD' => '#Master@445566#'
    ];
}

// Configurações do aplicativo
$config = [
    'app' => [
        'secret_key' => $dotenv['SECRET_KEY'] ?? bin2hex(random_bytes(32)),
        'session_lifetime' => 3600, // 1 hora em segundos
        'debug' => $dotenv['DEBUG'] ?? false,
        'upload_folder' => 'uploads',
        'max_content_length' => 5 * 1024 * 1024, // 5MB
    ],
    'db' => [
        'sqlite' => [
            'path' => __DIR__ . '/database/admin.db'
        ],
        'mysql' => [
            'host' => $dotenv['DB_HOST'] ?? '66.70.203.223',
            'user' => $dotenv['DB_USER'] ?? 'RenatoMaster',
            'password' => $dotenv['DB_PASSWORD'] ?? 'Renato44404031',
            'database' => $dotenv['DB_NAME'] ?? 'sistema_os',
            'charset' => 'utf8mb4'
        ]
    ],
    'admin' => [
        'username' => 'Admin',
        'password' => $dotenv['ADMIN_PASSWORD'] ?? '#Master@445566#'
    ],
    'security' => [
        'allowed_image_extensions' => ['jpg', 'jpeg', 'png', 'gif'],
        'allowed_document_extensions' => ['pdf'],
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
        'session_timeout' => 1800, // 30 minutos
        'csrf_token_lifetime' => 3600, // 1 hora
        'rate_limit_requests' => 100, // por hora
        'enable_security_logging' => true,
        'blocked_ips' => [],
        'allowed_file_types' => [
            'image/jpeg',
            'image/png', 
            'image/gif',
            'application/pdf'
        ]
    ]
];

// Criar pasta de uploads se não existir
if (!file_exists($config['app']['upload_folder'])) {
    mkdir($config['app']['upload_folder'], 0755, true);
}

// Criar pasta de banco de dados se não existir
if (!file_exists(dirname($config['db']['sqlite']['path']))) {
    mkdir(dirname($config['db']['sqlite']['path']), 0755, true);
}

// Inicializar sessão
session_start();
session_regenerate_id(true);

// Definir tempo de vida da sessão
ini_set('session.gc_maxlifetime', $config['app']['session_lifetime']);
session_set_cookie_params($config['app']['session_lifetime']);

// Configurações de exibição de erros
if ($config['app']['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

// Funções auxiliares
function get_allowed_extensions() {
    global $config;
    return array_merge(
        $config['security']['allowed_image_extensions'],
        $config['security']['allowed_document_extensions']
    );
}