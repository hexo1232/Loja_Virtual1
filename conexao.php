<?php
// Tenta ler as variáveis do Render, se não existirem, usa o padrão localhost
$host     = getenv('DB_HOST') ?: "localhost";
$usuario  = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$basededados = getenv('DB_NAME') ?: "loja";
$port     = getenv('DB_PORT') ?: "3306";

// No Aiven, a conexão costuma exigir a porta explicitamente
$conexao = new mysqli($host, $usuario, $password, $basededados, $port);

// Verifica a conexão
if ($conexao->connect_error) {
    error_log("Erro de conexão: " . $conexao->connect_error);
    die("Erro interno no servidor.");
}

// Opcional: Definir charset para evitar problemas com acentos (como os que tens nas tabelas)
$conexao->set_charset("utf8mb4");
?>