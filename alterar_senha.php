<?php
include "conexao.php";
require_once "require_login.php"; // Garante que o user está logado

$id_usuario = $_SESSION['usuario']['id_usuário'];
$erro = $sucesso = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // 1. Buscar senha atual no banco
    $stmt = $conexao->prepare("SELECT senha_hash FROM usuario WHERE id_usuário = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (password_verify($senha_atual, $res['senha_hash'])) {
        if ($nova_senha === $confirmar_senha) {
            $nova_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            // 2. Atualizar
            $up = $conexao->prepare("UPDATE usuario SET senha_hash = ? WHERE id_usuário = ?");
            $up->bind_param("si", $nova_hash, $id_usuario);
            
            if ($up->execute()) {
                // 3. Logout automático
                session_destroy();
                header("Location: login.php?msg=senha_alterada");
                exit();
            }
        } else { $erro = "As novas senhas não coincidem."; }
    } else { $erro = "Senha atual incorreta."; }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Alterar Senha</title>
    <link rel="stylesheet" href="css/cliente.css">
    <script src="js/hamburger.js" defer></script>
</head>
<body>
<?php
if ($usuario) {
    $nome2        = $usuario['nome']    ?? '';
    $apelido      = $usuario['apelido'] ?? '';
    $email        = $usuario['email']   ?? '';
    $iniciais     = strtoupper(substr($nome2, 0, 1) . substr($apelido, 0, 1));
    $nomeCompleto = trim("$nome2 $apelido");

    function gerarCor($texto) {
        $hash = md5($texto);
        return 'rgb(' . hexdec(substr($hash,0,2)) . ',' . hexdec(substr($hash,2,2)) . ',' . hexdec(substr($hash,4,2)) . ')';
    }
    $corAvatar = gerarCor($nomeCompleto);
}
?>

<!-- ── Botão hamburger ────────────────────────────────────── -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menu" aria-expanded="false">
    <span class="hamburger-bar"></span>
    <span class="hamburger-bar"></span>
    <span class="hamburger-bar"></span>
</button>

<!-- ── Overlay mobile ────────────────────────────────────── -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── Sidebar ───────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <span class="sidebar-logo">&#9679; Loja</span>
    </div>

    <nav class="sidebar-nav">
        <a href="verprodutos.php" class="sidebar-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            Voltar
        </a>
    </nav>


</aside>

    <div class="conteudo">
        <h2>🔒 Alterar Senha</h2>
        <?php if($erro) echo "<p style='color:red'>$erro</p>"; ?>

        <div class="card" style="max-width: 400px; padding: 20px;">
            <form method="post">
                <label>Senha Atual:</label><br>
                <input type="password" name="senha_atual" required style="width:100%"><br><br>

                <label>Nova Senha:</label><br>
                <input type="password" name="nova_senha" required style="width:100%"><br><br>

                <label>Confirmar Nova Senha:</label><br>
                <input type="password" name="confirmar_senha" required style="width:100%"><br><br>

                <button type="submit" class="save" style="width:100%">Atualizar e Sair</button>
            </form>
        </div>
    </div>
</body>
</html>