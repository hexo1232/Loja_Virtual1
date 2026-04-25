<?php
session_start();

// Captura o perfil do usuário antes de destruir a sessão
$idperfil = $_SESSION['usuario']['idperfil'] ?? null;

// ✅ Limpa somente os dados de login
unset($_SESSION['usuario']);

// 🔒 Fecha e salva a sessão
session_write_close();

// ✅ Redireciona com base no perfil
if ($idperfil == 1) {
    header("Location: login.php");
} else {
    header("Location: verprodutos.php");
}
exit;
?>