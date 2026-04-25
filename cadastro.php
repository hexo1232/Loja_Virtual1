<?php
include "conexao.php";

if (session_status() === PHP_SESSION_NONE) session_start();


$mensagem = "";
$redirecionar = false; // sinaliza se deve redirecionar após o cadastro

// 🧾 Processa o cadastro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome     = htmlspecialchars(trim($_POST['nome']));
    $apelido  = htmlspecialchars(trim($_POST['apelido']));
    $numero   = htmlspecialchars(trim($_POST['numero']));
    $email    = htmlspecialchars(trim($_POST['email']));
    $senha    = trim($_POST['senha']);
    $conf     = trim($_POST['conf']);
    $perfil   = 3;

    if (empty($nome) || empty($apelido) || empty($numero) || empty($email) || empty($senha) || empty($conf)) {
        $mensagem = "⚠️ Todos os campos são obrigatórios!";
    } elseif ($senha !== $conf) {
        $mensagem = "❌ A senha e a confirmação não coincidem.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "❌ Email inválido.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{6,}$/', $senha)) {
        $mensagem = "❌ A senha deve ter pelo menos 6 caracteres, uma letra maiúscula, uma minúscula e um número.";
    } else {
        // 🔍 Verificar se o email já está cadastrado
        $check = $conexao->prepare("SELECT id_usuário FROM usuario WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $mensagem = "❌ Este email já está cadastrado.";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $conexao->prepare("INSERT INTO usuario (nome, apelido, telefone, email, senha_hash,idperfil) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $nome, $apelido, $numero, $email, $senha_hash, $perfil);

            if ($stmt->execute()) {
                $mensagem = "✅ Cadastro realizado com sucesso! Redirecionando para a tela de login...";
                $redirecionar = true;
            } else {
                $mensagem = "❌ Erro ao cadastrar: " . $stmt->error;
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Usuário</title>
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

    <h2>Cadastro de Usuário</h2>

    <?php if ($mensagem): ?>
        <div class="mensagem <?= str_contains($mensagem, '✅') ? 'success' : 'error' ?>">
            <?= $mensagem ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <label>Nome:</label>
        <input type="text" name="nome" required>

        <label>Apelido:</label>
        <input type="text" name="apelido" required>

        <label>Telefone:</label>
        <input type="tel" name="numero" required placeholder="84/87/83*******" pattern="8[234567]\d{7}" maxlength="9">

        <label>Email:</label>
        <input type="email" name="email" required>

        


        <label>Senha:</label>
        <input type="password" name="senha" required minlength="6">

        <label>Confirme a Senha:</label>
        <input type="password" name="conf" required minlength="6">


        <button type="submit">Cadastrar</button>
    </form>

    <?php if ($redirecionar): ?>
<script>
    // Redireciona em 3 segundos
    setTimeout(() => {
        window.location.href = 'login.php';
    }, 3000);
</script>
<?php endif; ?>

</body>
</html>
