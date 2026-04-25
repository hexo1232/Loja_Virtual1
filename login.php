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
    * { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: 'Segoe UI', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1a2a3a 0%, #2c3e50 60%, #1a4a6a 100%);
  padding: 40px 20px;
}

.login-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 40px 36px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 48px rgba(0,0,0,.2);
  animation: fadeUp .6s ease both;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: translateY(0); }
}

.login-logo {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: #2c3e50;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.login-title {
  text-align: center;
  font-size: 20px;
  font-weight: 600;
  color: #1a2a3a;
  margin-bottom: 6px;
}

.login-sub {
  text-align: center;
  font-size: 13px;
  color: #777;
  margin-bottom: 28px;
}

.field { margin-bottom: 16px; }

.field label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #555;
  margin-bottom: 6px;
}

.field-wrap { position: relative; }

.field-wrap .icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #aaa;
  font-size: 15px;
  pointer-events: none;
}

.field input {
  width: 100%;
  padding: 10px 12px 10px 38px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  background: #f9f9f9;
  color: #333;
  transition: border-color .2s, box-shadow .2s;
  outline: none;
}

.field input:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52,152,219,.15);
  background: #fff;
}

.btn-login {
  width: 100%;
  padding: 12px;
  background: #2c3e50;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background .2s, transform .15s;
  margin-top: 4px;
}

.btn-login:hover {
  background: #3d5166;
  transform: translateY(-1px);
}

.btn-login:active { transform: scale(.98); }

.divider {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 20px 0;
  color: #bbb;
  font-size: 12px;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #eee;
}

.cadastro-link {
  text-align: center;
  font-size: 13px;
  color: #777;
}

.cadastro-link a {
  color: #3498db;
  text-decoration: none;
  font-weight: 600;
}

.cadastro-link a:hover { text-decoration: underline; }

.mensagem {
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  margin-bottom: 16px;
  font-weight: 500;
}

.mensagem.error {
  background: #fdf0f0;
  color: #a94442;
  border: 1px solid #f5c6cb;
}

@media (max-width: 480px) {
  .login-card { padding: 30px 20px; }
}
</style>
</head>
<body>
    
  

<div class="login-card">
  <div class="login-logo">
    <!-- ícone simples ou logo da loja -->
    <svg width="28" height="28" fill="white" viewBox="0 0 24 24">
      <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
      <polyline points="9 22 9 12 15 12 15 22" fill="none" stroke="white" stroke-width="1.5"/>
    </svg>
  </div>

  <p class="login-title">Bem-vindo de volta</p>
  <p class="login-sub">Acesse a sua conta para continuar</p>

  <?php if (!empty($erro)): ?>
    <div class="mensagem error"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php<?= isset($_GET['redir']) ? '?redir='.urlencode($_GET['redir']) : '' ?>">
    <div class="field">
      <label>Utilizador</label>
      <div class="field-wrap">
        <span class="icon">&#128100;</span>
        <input type="text" name="entrada" placeholder="nome, email ou número" required>
      </div>
    </div>

    <div class="field">
      <label>Senha</label>
      <div class="field-wrap">
        <span class="icon">&#128274;</span>
        <input type="password" name="senha" placeholder="••••••••" required>
      </div>
    </div>

    <button type="submit" class="btn-login">Entrar</button>
  </form>

  <div class="divider">ou</div>
  <p class="cadastro-link">Não tem conta? <a href="cadastro.php">Criar conta</a></p>
</div>



</body>
</html>
