# Sistema de Cadastro de Técnicos

Este sistema permite o cadastro e gerenciamento de técnicos especializados, com validação de certificações como NR-10, NR-20, NR-35 e ASO.

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Extensões PHP: PDO, PDO_MySQL, PDO_SQLite, GD, FileInfo

## Instalação

1. Clone este repositório para seu servidor web
2. Crie um banco de dados MySQL para o sistema
3. Copie o arquivo `.env.example` para `.env` e configure as variáveis de ambiente
4. Importe o esquema do banco de dados MySQL executando o arquivo `database/schema.sql`
5. Certifique-se de que as pastas `uploads` e `database` têm permissões de escrita
6. Acesse o sistema pelo navegador

## Estrutura do Banco de Dados

### SQLite (admin.db)
- Tabela `admin`: Armazena os administradores do sistema
- Tabela `solicitacao_cadastro`: Armazena as solicitações de cadastro pendentes

### MySQL
- Tabela `usuario`: Armazena os usuários do sistema
- Tabela `tecnico_info`: Armazena informações específicas dos técnicos

## Funcionalidades

- Cadastro de técnicos com upload de documentos
- Validação de certificações (NR-10, NR-20, NR-35, ASO)
- Painel administrativo para aprovação/rejeição de solicitações
- Armazenamento seguro de senhas e documentos

## Segurança

- Proteção contra ataques XSS, CSRF, SQL Injection
- Validação de arquivos enviados
- Bloqueio de conta após múltiplas tentativas de login
- Sessões seguras com tempo de expiração