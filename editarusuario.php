<?php
include "conexao.php";
require_once "require_login.php";

$id_usuario = $_SESSION['usuario']['id_usuário'];

// Carregar dados atuais
$stmt = $conexao->prepare("SELECT * FROM usuario WHERE id_usuário = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $apelido = $_POST['apelido'];
    $telefone = $_POST['telefone'];
    $provincia = $_POST['idprovincia'];
    $cidade = $_POST['idcidade'];

    $sql = "UPDATE usuario SET nome=?, apelido=?, telefone=?, idprovíncia=?, idcidade=? WHERE id_usuário=?";
    $up = $conexao->prepare($sql);
    $up->bind_param("sssiii", $nome, $apelido, $telefone, $provincia, $cidade, $id_usuario);

    if ($up->execute()) {
        session_destroy();
        header("Location: login.php?msg=perfil_atualizado");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="css/cliente.css">
    <script src="js/hamburger.js" defer></script>
    <script>
        function carregarCidades() {
            const idprov = document.getElementById("idprovincia").value;
            fetch("finalizar_pedido.php?ajax=cidades&provincia=" + idprov)
                .then(res => res.text())
                .then(html => document.getElementById("idcidade").innerHTML = html);
        }
    </script>
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
        <h2>👤 Editar Meus Dados</h2>
        
        <div class="card" style="max-width: 500px; padding: 20px;">
            <form method="post">
                <label>Nome:</label>
                <input type="text" name="nome" value="<?= $user_data['nome'] ?>" required style="width:100%"><br><br>

                <label>Apelido:</label>
                <input type="text" name="apelido" value="<?= $user_data['apelido'] ?>" required style="width:100%"><br><br>

                <label>Telefone:</label>
                <input type="text" name="telefone" value="<?= $user_data['telefone'] ?>" required style="width:100%"><br><br>

                <label>Província:</label>
                <select name="idprovincia" id="idprovincia" onchange="carregarCidades()" style="width:100%">
                    <?php
                    $provs = $conexao->query("SELECT * FROM provincia");
                    while($p = $provs->fetch_assoc()){
                        $sel = ($p['idprovíncia'] == $user_data['idprovíncia']) ? "selected" : "";
                        echo "<option value='{$p['idprovíncia']}' $sel>{$p['nome_província']}</option>";
                    }
                    ?>
                </select><br><br>

                <label>Cidade:</label>
                <select name="idcidade" id="idcidade" style="width:100%">
                    <option value="<?= $user_data['idcidade'] ?>">Manter atual</option>
                </select><br><br>

                <button type="submit" class="save" style="width:100%">Salvar Alterações e Sair</button>
            </form>
        </div>
    </div>
</body>
</html>