<?php
// ===== 1. REQUISITOS LOCAIS =====
require_once "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

// Ajax para carregar cidades
if (isset($_GET['ajax']) && $_GET['ajax'] === 'cidades' && isset($_GET['provincia'])) {
    $idprovincia = intval($_GET['provincia']);
    $stmt = $conexao->prepare("SELECT idcidade, nome_cidade FROM cidade WHERE idprovíncia = ?");
    $stmt->bind_param("i", $idprovincia);
    $stmt->execute();
    $res = $stmt->get_result();
    echo '<option value="">Selecione</option>';
    while ($row = $res->fetch_assoc()) {
        echo "<option value='{$row['idcidade']}'>{$row['nome_cidade']}</option>";
    }
    exit;
}

// Verificar login
if (!isset($_SESSION['usuario']['id_usuário'])) {
    header("Location: login.php");
    exit;
}

// Carregar carrinho
$id_usuario = $_SESSION['usuario']['id_usuário'];
$stmtCarrinho = $conexao->prepare("SELECT * FROM Carrinho WHERE id_usuário = ? AND status = 'activo'");
$stmtCarrinho->bind_param("i", $id_usuario);
$stmtCarrinho->execute();
$resultCarrinho = $stmtCarrinho->get_result();

if ($resultCarrinho->num_rows == 0) {
    echo "<p style='color:red;'>Seu carrinho está vazio.</p>";
    exit;
}

$carrinho = $resultCarrinho->fetch_assoc();
$id_carrinho = $carrinho['id_carrinho'];

// Carregar itens do carrinho
$stmtItens = $conexao->prepare("SELECT ic.*, p.nome_produto, p.preco, p.quantidade_estoque,
                    (SELECT caminho_imagem FROM produto_imagem WHERE id_produto = p.id_produto AND imagem_principal = 1 LIMIT 1) AS imagem_principal
             FROM Item_Carrinho ic
             JOIN Produto p ON ic.id_produto = p.id_produto
             WHERE ic.id_carrinho = ?");
$stmtItens->bind_param("i", $id_carrinho);
$stmtItens->execute();
$resultItens = $stmtItens->get_result();

$total = 0;
$itens = [];
while ($item = $resultItens->fetch_assoc()) {
    $itens[] = $item;
    $total += $item['subtotal'];
}

// Processar formulário (SIMULAÇÃO)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $telefone = filter_var($_POST['telefone'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $idprovincia = filter_var($_POST['idprovincia'], FILTER_VALIDATE_INT);
    $idcidade = filter_var($_POST['idcidade'], FILTER_VALIDATE_INT);
    $metodo = filter_var($_POST['metodo'], FILTER_VALIDATE_INT);

    if (!$telefone || !$email || $idprovincia === false || $idcidade === false || $metodo === false) {
        die("<p style='color:red;'>Dados inválidos fornecidos.</p>");
    }

    // --- AQUI ACONTECIA A INTEGRAÇÃO REAL (AGORA É SIMULADO) ---
    // O sistema apenas assume que o pagamento foi bem-sucedido para fins de teste de fluxo.

    // 1. Criar pedido
    $stmtPedido = $conexao->prepare("INSERT INTO Pedido (data_pedido, status_pedido, valor_total, telefone, email, idprovíncia, idcidade, idtipo_pagamento, id_usuário)
                                     VALUES (NOW(), 'pendente', ?, ?, ?, ?, ?, ?, ?)");
    $stmtPedido->bind_param("dssiiii", $total, $telefone, $email, $idprovincia, $idcidade, $metodo, $id_usuario);

    if (!$stmtPedido->execute()) {
        die("Erro ao inserir pedido: " . $stmtPedido->error);
    }

    $id_pedido = $stmtPedido->insert_id;

    // 2. Transferir itens e baixar estoque
    foreach ($itens as $item) {
        $stmtItem = $conexao->prepare("INSERT INTO Item_Pedido (id_pedido, id_produto, quantidade, preco_unitario, subtotal)
                                       VALUES (?, ?, ?, ?, ?)");
        $stmtItem->bind_param("iiidd", $id_pedido, $item['id_produto'], $item['quantidade'], $item['preco'], $item['subtotal']);
        $stmtItem->execute();

        $stmtEstoque = $conexao->prepare("UPDATE Produto SET quantidade_estoque = quantidade_estoque - ? WHERE id_produto = ?");
        $stmtEstoque->bind_param("ii", $item['quantidade'], $item['id_produto']);
        $stmtEstoque->execute();
    }

    // 3. Finalizar carrinho
    $conexao->prepare("UPDATE Carrinho SET status = 'finalizado' WHERE id_carrinho = ?")->bind_param("i", $id_carrinho)->execute();
    $conexao->prepare("DELETE FROM Item_Carrinho WHERE id_carrinho = ?")->bind_param("i", $id_carrinho)->execute();

    // 4. Registar pagamento como "pago" (Simulado)
    $status_pagamento = 'pago';
    $data_pagamento = date("Y-m-d H:i:s");
    $stmtPagamento = $conexao->prepare("INSERT INTO Pagamento (status_pagamento, data_pagamento, valor_pago, id_pedido, idtipo_pagamento)
                                        VALUES (?, ?, ?, ?, ?)");
    $stmtPagamento->bind_param("ssdii", $status_pagamento, $data_pagamento, $total, $id_pedido, $metodo);
    $stmtPagamento->execute();

    // Exibir confirmação
    echo "<div id='popup-confirmacao' class='popup'>
        <div class='popup-content'>
            <h3 style='color: green;'>✔ Pedido Finalizado (Simulação)</h3>
            <p>O fluxo de pagamento foi simulado com sucesso.</p>
            <div class='popup-buttons'>
                <button onclick=\"window.location.href='verprodutos.php'\">Voltar às compras</button>
                <button onclick=\"window.location.href='historico_compras.php'\">Ver histórico</button>
                <button style='background:#28a745' onclick=\"window.location.href='gerar_fatura.php?id_pedido=$id_pedido'\">Imprimir fatura</button>
            </div>
        </div>
    </div>";
    echo "<style>
        .popup { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .popup-content { background: white; padding: 40px; border-radius: 15px; text-align: center; }
        .popup-buttons button { margin: 10px; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; color: white; background: #007bff; }
    </style>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Finalizar Pedido (Modo Simulação)</title>
    <link rel="stylesheet" href="css/cliente.css">
    <style>
        .card { border: 1px solid #ccc; border-radius: 10px; padding: 15px; margin-bottom: 10px; display: flex; gap: 15px; background: #fff; width: 80%; }
        .card img { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; }
        .sidebar { position: fixed; left: 0; top: 0; width: 170px; height: 100%; background: #f4f4f4; padding: 20px; border-right: 1px solid #ddd; }
        .conteudo { margin-left: 220px; padding: 20px; }
        .metodo-formulario { display: none; margin: 15px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; width: 80%; }
        input, select { width: 100%; max-width: 400px; padding: 8px; margin: 10px 0; border-radius: 4px; border: 1px solid #ccc; }
        .btn-finalizar { background: #00ff88; color: #333; padding: 15px 30px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
    </style>
    <script>
        function carregarCidades() {
            const idprov = document.getElementById("idprovincia").value;
            fetch("?ajax=cidades&provincia=" + idprov)
                .then(res => res.text())
                .then(html => document.getElementById("idcidade").innerHTML = html);
        }

        function mostrarFormularioPagamento() {
            const metodo = document.getElementById("metodo").value;
            document.querySelectorAll('.metodo-formulario').forEach(div => div.style.display = 'none');
            const alvo = document.getElementById("formulario-" + metodo);
            if (alvo) alvo.style.display = 'block';
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <h3>Menu</h3>
        <a href="carrinho.php">🛒 Ver Carrinho</a><br><br>
        <a href="logout.php">🚪 Sair</a>
    </div>

    <div class="conteudo">
        <h2>Resumo do Pedido</h2>
        <?php foreach ($itens as $item): ?>
            <div class="card">
                <img src="<?= htmlspecialchars($item['imagem_principal'] ?? 'imagens/sem_imagem.jpg') ?>">
                <div>
                    <h4><?= htmlspecialchars($item['nome_produto']) ?></h4>
                    <p>Qtd: <?= $item['quantidade'] ?> | Total: <?= number_format($item['subtotal'], 2) ?> MZN</p>
                </div>
            </div>
        <?php endforeach; ?>

        <h3 style="color: #0056b3;">Total a Pagar: <?= number_format($total, 2, ',', '.') ?> MZN</h3>

        <form method="post">
            <h3>Informações de Entrega</h3>
            <label>Telefone:</label><br>
            <input type="text" name="telefone" required placeholder="84XXXXXXX"><br>
            
            <label>Email:</label><br>
            <input type="email" name="email" required value="<?= htmlspecialchars($usuario['email'] ?? '') ?>"><br>

            <label>Província:</label><br>
            <select name="idprovincia" id="idprovincia" onchange="carregarCidades()" required>
                <option value="">Selecione</option>
                <?php
                $prov = $conexao->query("SELECT * FROM provincia");
                while ($p = $prov->fetch_assoc()) {
                    echo "<option value='{$p['idprovíncia']}'>{$p['nome_província']}</option>";
                }
                ?>
            </select><br>

            <label>Cidade:</label><br>
            <select name="idcidade" id="idcidade" required>
                <option value="">Selecione a província</option>
            </select><br>

            <h3>Método de Pagamento (Simulação)</h3>
            <select name="metodo" id="metodo" onchange="mostrarFormularioPagamento()" required>
                <option value="">Selecione</option>
                <?php
                $met = $conexao->query("SELECT * FROM tipo_pagamento");
                while ($m = $met->fetch_assoc()) {
                    echo "<option value='{$m['idtipo_pagamento']}'>{$m['tipo_pagamento']}</option>";
                }
                ?>
            </select>

            <div id="formulario-1" class="metodo-formulario">
                <h4>Simulação VISA</h4>
                <input type="text" placeholder="Número do Cartão (Simulado)">
            </div>

            <div id="formulario-2" class="metodo-formulario">
                <h4>Simulação M-Pesa</h4>
                <input type="text" placeholder="Número M-Pesa">
            </div>

            <div id="formulario-5" class="metodo-formulario">
                <h4>Simulação PayPal</h4>
                <p>O botão real foi removido. Clique em finalizar para simular aprovação.</p>
            </div>

            <br><br>
            <button class="btn-finalizar" type="submit">FINALIZAR PEDIDO</button>
        </form>
    </div>
</body>
</html>