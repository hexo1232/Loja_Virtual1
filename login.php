<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entrada = $_POST['entrada'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (isset($_GET['redir'])) {
    $_SESSION['url_destino'] = basename($_GET['redir']); // segurança contra redirecionamento malicioso
}

    if ($entrada && $senha) {
       // Prepara a consulta para verificar por email, telefone ou nome
    $sql = "SELECT * FROM usuario 
            WHERE email = ? OR telefone = ? OR nome = ?
            LIMIT 1";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("sss", $entrada, $entrada, $entrada);
    $stmt->execute();
    $resultado = $stmt->get_result();
        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();

            if (password_verify($senha, $usuario['senha_hash'])) {
                $_SESSION['usuario'] = $usuario;
                $idUsuario = $usuario['id_usuário'];

                // Verifica se o usuário já tem um carrinho ativo
                $stmt = $conexao->prepare("SELECT id_carrinho FROM carrinho WHERE id_usuário = ? AND status = 'activo'");
                $stmt->bind_param("i", $idUsuario);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($res->num_rows > 0) {
                    $id_carrinho = $res->fetch_assoc()['id_carrinho'];
                } else {
                    $stmt = $conexao->prepare("INSERT INTO carrinho (id_usuário, data_criacao, status) VALUES (?, NOW(), 'activo')");
                    $stmt->bind_param("i", $idUsuario);
                    $stmt->execute();
                    $id_carrinho = $stmt->insert_id;
                }

                // ✅ Migrar carrinho do cookie (anônimo) para banco de dados
                if (isset($_COOKIE['carrinho'])) {
                    $carrinhoCookie = json_decode($_COOKIE['carrinho'], true);
                    foreach ($carrinhoCookie as $item) {
                        $id_produto = $item['id_produto'];
                        $quantidade = $item['quantidade'];
                        $subtotal = $item['subtotal'] ?? 0;

                        // Verifica se o produto já existe no carrinho
                        $stmt = $conexao->prepare("SELECT id_item_carrinho FROM item_carrinho WHERE id_carrinho = ? AND id_produto = ?");
                        $stmt->bind_param("ii", $id_carrinho, $id_produto);
                        $stmt->execute();
                        $res = $stmt->get_result();

                        if ($res->num_rows > 0) {
                            // Atualiza quantidade e subtotal
                            $stmt = $conexao->prepare("UPDATE item_carrinho SET quantidade = quantidade + ?, subtotal = subtotal + ? WHERE id_carrinho = ? AND id_produto = ?");
                            $stmt->bind_param("idii", $quantidade, $subtotal, $id_carrinho, $id_produto);
                        } else {
                            // Insere novo item
                            $stmt = $conexao->prepare("INSERT INTO item_carrinho (id_carrinho, id_produto, quantidade, subtotal) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("iiid", $id_carrinho, $id_produto, $quantidade, $subtotal);
                        }
                        $stmt->execute();
                    }

                    // ✅ Limpa cookie após migração
                    setcookie('carrinho', '', time() - 3600, "/");
                }

                // Redirecionamento pós login
            

            // ✅ Redirecionamento pós-login
            if (isset($_SESSION['url_destino'])) {
                $urlDestino = $_SESSION['url_destino'];
                unset($_SESSION['url_destino']);
                header("Location: $urlDestino");
                exit;
            }

            if ($usuario['idperfil'] == 1) {
                header("Location: dashboard.php");
            } else {
                header("Location: verprodutos.php");
            }
            exit;
        } else {
            $erro = "Senha incorreta.";
        }
    } else {
        $erro = "Credenciais inválidas.";
    }}
}
?>



<!-- Formulário de Login -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login</title>
      <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        form {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #aaa;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #004a99;
        }

        .mensagem {
            max-width: 500px;
            margin: 20px auto;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
        }

        .mensagem.success {
            background-color: #d4edda;
            color: #155724;
        }

        .mensagem.error {
            background-color: #f8d7da;
            color: #721c24;
        }
</style>
</head>
<body>
    
  

<form method="POST" action="login.php<?= isset($_GET['redir']) ? '?redir=' . urlencode($_GET['redir']) : '' ?>">
    <label>Usuário:</label>
    <input type="text" name="entrada" placeholder="nome, email ou número" required><br>

    <label>Senha:</label>
    <input type="password" name="senha" required><br>

    <button type="submit">Entrar</button>
    <label style="margin-left:30%;">Não tem conta? <a href="cadastro.php"> Clique aqui</a></label>
    
    <?php if (!empty($erro)) { echo "<p style='color:red; margin-left:30%;'>$erro</p>"; } ?>
</form>



</body>
</html>
