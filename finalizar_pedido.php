<?php
// ===== 1. REQUISITOS LOCAIS =====
require_once "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

// Ajax para carregar cidades - Tabela: cidade
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

// Carregar carrinho - Tabela: carrinho
$id_usuario = $_SESSION['usuario']['id_usuário'];
$stmtCarrinho = $conexao->prepare("SELECT * FROM carrinho WHERE id_usuário = ? AND status = 'activo'");
$stmtCarrinho->bind_param("i", $id_usuario);
$stmtCarrinho->execute();
$resultCarrinho = $stmtCarrinho->get_result();

if ($resultCarrinho->num_rows == 0) {
    echo "<p style='color:red;'>Seu carrinho está vazio.</p>";
    exit;
}

$carrinho = $resultCarrinho->fetch_assoc();
$id_carrinho = $carrinho['id_carrinho'];

// Carregar itens do carrinho - Tabelas: item_carrinho, produto, produto_imagem
$stmtItens = $conexao->prepare("SELECT ic.*, p.nome_produto, p.preco, p.quantidade_estoque,
                    (SELECT caminho_imagem FROM produto_imagem WHERE id_produto = p.id_produto AND imagem_principal = 1 LIMIT 1) AS imagem_principal
             FROM item_carrinho ic
             JOIN produto p ON ic.id_produto = p.id_produto
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

// Processar formulário (SIMULAÇÃO TOTAL)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $telefone = filter_var($_POST['telefone'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $idprovincia = filter_var($_POST['idprovincia'], FILTER_VALIDATE_INT);
    $idcidade = filter_var($_POST['idcidade'], FILTER_VALIDATE_INT);
    $metodo = filter_var($_POST['metodo'], FILTER_VALIDATE_INT);

    if (!$telefone || !$email || $idprovincia === false || $idcidade === false || $metodo === false) {
        die("<p style='color:red;'>Dados inválidos fornecidos.</p>");
    }

    // 1. Criar pedido - Tabela: pedido
    $stmtPedido = $conexao->prepare("INSERT INTO pedido (data_pedido, status_pedido, valor_total, telefone, email, idprovíncia, idcidade, idtipo_pagamento, id_usuário)
                                     VALUES (NOW(), 'pendente', ?, ?, ?, ?, ?, ?, ?)");
    $stmtPedido->bind_param("dssiiii", $total, $telefone, $email, $idprovincia, $idcidade, $metodo, $id_usuario);

    if (!$stmtPedido->execute()) {
        die("Erro ao inserir pedido: " . $stmtPedido->error);
    }

    $id_pedido = $stmtPedido->insert_id;

    // 2. Transferir itens e baixar estoque - Tabelas: item_pedido, produto
    foreach ($itens as $item) {
        $stmtItem = $conexao->prepare("INSERT INTO item_pedido (id_pedido, id_produto, quantidade, preco_unitario, subtotal)
                                       VALUES (?, ?, ?, ?, ?)");
        $stmtItem->bind_param("iiidd", $id_pedido, $item['id_produto'], $item['quantidade'], $item['preco'], $item['subtotal']);
        $stmtItem->execute();

        $stmtEstoque = $conexao->prepare("UPDATE produto SET quantidade_estoque = quantidade_estoque - ? WHERE id_produto = ?");
        $stmtEstoque->bind_param("ii", $item['quantidade'], $item['id_produto']);
        $stmtEstoque->execute();
    }

    // 3. Finalizar carrinho - Tabelas: carrinho, item_carrinho
    $conexao->prepare("UPDATE carrinho SET status = 'finalizado' WHERE id_carrinho = ?")->bind_param("i", $id_carrinho)->execute();
    $conexao->prepare("DELETE FROM item_carrinho WHERE id_carrinho = ?")->bind_param("i", $id_carrinho)->execute();

    // 4. Registar pagamento como "pago" (Simulação) - Tabela: pagamento
    $status_pagamento = 'pago';
    $data_pagamento = date("Y-m-d H:i:s");
    $stmtPagamento = $conexao->prepare("INSERT INTO pagamento (status_pagamento, data_pagamento, valor_pago, id_pedido, idtipo_pagamento)
                                        VALUES (?, ?, ?, ?, ?)");
    $stmtPagamento->bind_param("ssdii", $status_pagamento, $data_pagamento, $total, $id_pedido, $metodo);
    $stmtPagamento->execute();

    // Exibir confirmação visual
    echo "<div id='popup-confirmacao' class='popup'>
        <div class='popup-content'>
            <h3 style='color: green;'>✔ Pedido Simulado com Sucesso!</h3>
            <p>Os dados foram gravados no banco de dados (tabelas pedido e pagamento).</p>
            <div class='popup-buttons'>
                <button onclick=\"window.location.href='verprodutos.php'\">Voltar à Loja</button>
                <button onclick=\"window.location.href='historico_compras.php'\">Meu Histórico</button>
                <button style='background:#28a745' onclick=\"window.location.href='gerar_fatura.php?id_pedido=$id_pedido'\">Imprimir Fatura</button>
            </div>
        </div>
    </div>";
    echo "<style>
        .popup { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 1000; font-family: sans-serif; }
        .popup-content { background: white; padding: 40px; border-radius: 15px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .popup-buttons button { margin: 10px; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; color: white; background: #007bff; font-weight: bold; }
    </style>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Finalizar Pedido (Simulação)</title>
    <link rel="stylesheet" href="css/cliente.css">
    <style>
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 10px; display: flex; gap: 15px; background: #fff; width: 90%; max-width: 600px; }
        .card img { width: 70px; height: 70px; object-fit: cover; border-radius: 5px; }
        .sidebar { position: fixed; left: 0; top: 0; width: 180px; height: 100%; background: #f8f9fa; padding: 20px; border-right: 1px solid #ddd; }
        .conteudo { margin-left: 230px; padding: 30px; }
        .metodo-formulario { display: none; margin-top: 15px; padding: 15px; border-left: 4px solid #007bff; background: #f0f7ff; }
        input, select { width: 100%; max-width: 400px; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; }
        .btn-finalizar { background: #28a745; color: white; padding: 15px 40px; border: none; border-radius: 5px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        .btn-finalizar:hover { background: #218838; }
    </style>
    <script>
        function carregarCidades() {
            const idprov = document.getElementById("idprovincia").value;
            if(!idprov) return;
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
        <a href="verprodutos.php" style="text-decoration:none; color:#333;">🏠 Início</a><br><br>
        <a href="carrinho.php" style="text-decoration:none; color:#333;">🛒 Carrinho</a><br><br>
        <a href="logout.php" style="text-decoration:none; color:red;">🚪 Sair</a>
    </div>

    <div class="conteudo">
        <h2>Resumo da Compra</h2>
        
        <?php foreach ($itens as $item): ?>
            <div class="card">
                <img src="<?= htmlspecialchars($item['imagem_principal'] ?? 'imagens/sem_imagem.jpg') ?>">
                <div>
                    <strong><?= htmlspecialchars($item['nome_produto']) ?></strong><br>
                    <small>Qtd: <?= $item['quantidade'] ?> | Subtotal: <?= number_format($item['subtotal'], 2) ?> MZN</small>
                </div>
            </div>
        <?php endforeach; ?>

        <h3 style="color: #d9534f;">Total Geral: <?= number_format($total, 2, ',', '.') ?> MZN</h3>
        <hr>

        <form method="post">
            <h3>Dados de Entrega</h3>
            <label>Telefone para contacto:</label><br>
            <input type="text" name="telefone" required placeholder="Ex: 841234567"><br>
            
            <label>E-mail de confirmação:</label><br>
            <input type="email" name="email" required value="<?= htmlspecialchars($usuario['email'] ?? '') ?>"><br>

            <label>Província:</label><br>
            <select name="idprovincia" id="idprovincia" onchange="carregarCidades()" required>
                <option value="">Selecione a Província</option>
                <?php
                $prov = $conexao->query("SELECT * FROM provincia");
                while ($p = $prov->fetch_assoc()) {
                    echo "<option value='{$p['idprovíncia']}'>{$p['nome_província']}</option>";
                }
                ?>
            </select><br>

            <label>Cidade/Distrito:</label><br>
            <select name="idcidade" id="idcidade" required>
                <option value="">Selecione a província primeiro</option>
            </select><br>

            <h3>Forma de Pagamento</h3>
            <select name="metodo" id="metodo" onchange="mostrarFormularioPagamento()" required>
                <option value="">Escolha como pagar</option>
                <?php
                $met = $conexao->query("SELECT * FROM tipo_pagamento");
                while ($m = $met->fetch_assoc()) {
                    echo "<option value='{$m['idtipo_pagamento']}'>{$m['tipo_pagamento']}</option>";
                }
                ?>
            </select>

            <div id="formulario-1" class="metodo-formulario">
                <h4>💳 Cartão VISA (Simulação)</h4>
                <p>Insira dados fictícios para testar:</p>
                <input type="text" placeholder="Número do Cartão">
            </div>

            <div id="formulario-2" class="metodo-formulario">
                <h4>📱 M-Pesa (Simulação)</h4>
                <p>O sistema simulará o envio do STK Push.</p>
                <input type="text" placeholder="Número M-Pesa">
            </div>

            <div id="formulario-5" class="metodo-formulario">
                <h4>🅿 PayPal (Simulação)</h4>
                <p>Integração via API removida. Clique abaixo para simular o sucesso.</p>
            </div>

            <br>
            <button class="btn-finalizar" type="submit">CONFIRMAR E PAGAR</button>
        </form>
    </div>
</body>
</html>