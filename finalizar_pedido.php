<?php
// ===== 1. REQUISITOS LOCAIS =====
require_once "conexao.php";
require_once "require_login.php";

// Bloquear output do usuario_info.php quando é chamada AJAX do PayPal
$is_paypal_ajax = isset($_POST['apenas_criar_pedido']);
if (!$is_paypal_ajax) {
    include "usuario_info.php";
}

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

$id_usuario = $_SESSION['usuario']['id_usuário'];

// Carregar carrinho
$stmtCarrinho = $conexao->prepare("SELECT * FROM carrinho WHERE id_usuário = ? AND status = 'activo'");
$stmtCarrinho->bind_param("i", $id_usuario);
$stmtCarrinho->execute();
$resultCarrinho = $stmtCarrinho->get_result();

if ($resultCarrinho->num_rows == 0) {
    echo "<p style='color:red;'>Seu carrinho está vazio.</p>";
    exit;
}

$carrinho    = $resultCarrinho->fetch_assoc();
$id_carrinho = $carrinho['id_carrinho'];

// Carregar itens do carrinho
$stmtItens = $conexao->prepare("
    SELECT ic.*, p.nome_produto, p.preco, p.quantidade_estoque,
           (SELECT caminho_imagem FROM produto_imagem WHERE id_produto = p.id_produto AND imagem_principal = 1 LIMIT 1) AS imagem_principal
    FROM item_carrinho ic
    JOIN produto p ON ic.id_produto = p.id_produto
    WHERE ic.id_carrinho = ?
");
$stmtItens->bind_param("i", $id_carrinho);
$stmtItens->execute();
$resultItens = $stmtItens->get_result();

$total = 0;
$itens = [];
while ($item = $resultItens->fetch_assoc()) {
    $itens[] = $item;
    $total  += $item['subtotal'];
}

// ===== PROCESSAR POST =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $telefone    = htmlspecialchars(trim($_POST['telefone']));
    $email       = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $idprovincia = filter_var($_POST['idprovincia'], FILTER_VALIDATE_INT);
    $idcidade    = filter_var($_POST['idcidade'], FILTER_VALIDATE_INT);
    $metodo      = filter_var($_POST['metodo'], FILTER_VALIDATE_INT);

    if (!$telefone || !$email || $idprovincia === false || $idcidade === false || $metodo === false) {
        $erro = json_encode(['error' => 'Dados inválidos fornecidos.']);
        if (isset($_POST['apenas_criar_pedido'])) {
            header('Content-Type: application/json');
            echo $erro;
        } else {
            die("<p style='color:red;'>Dados inválidos fornecidos.</p>");
        }
        exit;
    }

    // 1. Criar pedido
    $stmtPedido = $conexao->prepare("
        INSERT INTO pedido (data_pedido, status_pedido, valor_total, telefone, email, idprovíncia, idcidade, idtipo_pagamento, id_usuário)
        VALUES (NOW(), 'pendente', ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtPedido->bind_param("dssiiii", $total, $telefone, $email, $idprovincia, $idcidade, $metodo, $id_usuario);

    if (!$stmtPedido->execute()) {
        $erro = json_encode(['error' => 'Erro ao inserir pedido: ' . $stmtPedido->error]);
        if (isset($_POST['apenas_criar_pedido'])) {
            header('Content-Type: application/json');
            echo $erro;
        } else {
            die("Erro ao inserir pedido: " . $stmtPedido->error);
        }
        exit;
    }

    $id_pedido = $stmtPedido->insert_id;

    // 2. Transferir itens e baixar estoque
    foreach ($itens as $item) {
        $stmtItem = $conexao->prepare("
            INSERT INTO item_pedido (id_pedido, id_produto, quantidade, preco_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtItem->bind_param("iiidd", $id_pedido, $item['id_produto'], $item['quantidade'], $item['preco'], $item['subtotal']);
        $stmtItem->execute();

        $stmtEstoque = $conexao->prepare("UPDATE produto SET quantidade_estoque = quantidade_estoque - ? WHERE id_produto = ?");
        $stmtEstoque->bind_param("ii", $item['quantidade'], $item['id_produto']);
        $stmtEstoque->execute();
    }

    // BIFURCAÇÃO
    if (isset($_POST['apenas_criar_pedido'])) {
        $_SESSION['paypal_carrinho'] = $id_carrinho;
        header('Content-Type: application/json');
        echo json_encode(['id_pedido' => $id_pedido]);
        exit;
    }

    // 4. Para outros métodos: registar pagamento imediatamente (simulação)
    $status_pagamento = 'pago';
    $data_pagamento   = date("Y-m-d H:i:s");
    $stmtPagamento    = $conexao->prepare("
        INSERT INTO pagamento (status_pagamento, data_pagamento, valor_pago, id_pedido, idtipo_pagamento)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtPagamento->bind_param("ssdii", $status_pagamento, $data_pagamento, $total, $id_pedido, $metodo);
    $stmtPagamento->execute();

    // Confirmação visual para métodos não-PayPal
    echo "<div id='popup-confirmacao' class='popup'>
        <div class='popup-content'>
            <h3 style='color: green;'>✔ Pedido Confirmado!</h3>
            <p>Os dados foram gravados com sucesso.</p>
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
    <script src="js/hamburger.js" defer></script>
    <script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars(getenv('PAYPAL_CLIENT_ID')) ?>&currency=USD"></script>
</head>
<body>

<?php
if ($usuario) {
    $nome2        = $usuario['nome']    ?? '';
    $apelido      = $usuario['apelido'] ?? '';
    $email        = $usuario['email']   ?? '';
    $iniciais     = strtoupper(substr($nome2, 0, 1) . substr($apelido, 0, 1));
    $nomeCompleto = trim("$nome2 $apelido");

    // Guarda contra redeclaração — gerarCor pode já ter sido declarada em usuario_info.php
    if (!function_exists('gerarCor')) {
        function gerarCor($texto) {
            $hash = md5($texto);
            return 'rgb(' . hexdec(substr($hash,0,2)) . ',' . hexdec(substr($hash,2,2)) . ',' . hexdec(substr($hash,4,2)) . ')';
        }
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
            Início
        </a>
        <a href="carrinho.php" class="sidebar-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Carrinho
        </a>
        <?php if ($usuario): ?>
        <a href="historico_compras.php" class="sidebar-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Histórico de Compras
        </a>
        <?php endif; ?>
    </nav>

    <?php if ($usuario): ?>
    <div class="sidebar-footer">
        <button class="sidebar-user" id="sidebarUserBtn" aria-haspopup="true" aria-expanded="false">
            <div class="sidebar-avatar" style="background-color: <?= $corAvatar ?>"><?= $iniciais ?></div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= htmlspecialchars($nomeCompleto) ?></span>
                <?php if ($email): ?>
                    <span class="sidebar-user-email"><?= htmlspecialchars($email) ?></span>
                <?php endif; ?>
            </div>
            <svg class="sidebar-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="18 15 12 9 6 15"/>
            </svg>
        </button>
        <div class="sidebar-dropdown" id="sidebarDropdown" role="menu">
            <a href="alterar_senha.php" class="sidebar-dropdown-item" role="menuitem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Alterar senha
            </a>
            <div class="sidebar-dropdown-divider"></div>
            <a href="logout.php" class="sidebar-dropdown-item sidebar-dropdown-item--danger" role="menuitem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sair
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="sidebar-footer">
        <a href="login.php" class="sidebar-login-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Fazer login
        </a>
    </div>
    <?php endif; ?>

</aside>

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
            <h4>🅿 Pagar com PayPal</h4>
            <div id="paypal-button-container"></div>
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

<script>
    function carregarCidades() {
        const idprov = document.getElementById("idprovincia").value;
        if (!idprov) return;
        fetch("?ajax=cidades&provincia=" + idprov)
            .then(res => res.text())
            .then(html => document.getElementById("idcidade").innerHTML = html);
    }

    function mostrarFormularioPagamento() {
        const metodo = document.getElementById("metodo").value;
        document.querySelectorAll('.metodo-formulario').forEach(div => div.style.display = 'none');
        const alvo = document.getElementById("formulario-" + metodo);
        if (alvo) alvo.style.display = 'block';

        document.querySelector('.btn-finalizar').style.display = (metodo == '1') ? 'none' : 'block';

        if (metodo == '1') inicializarPayPal();
    }

    function inicializarPayPal() {
        document.getElementById('paypal-button-container').innerHTML = '';

        paypal.Buttons({
            createOrder: function(data, actions) {
                const form = document.querySelector('form');
                const formData = new FormData(form);
                formData.append('apenas_criar_pedido', '1');

                return fetch('finalizar_pedido.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(pedidoData => {
                    if (pedidoData.error) throw new Error(pedidoData.error);
                    return fetch('paypal/create-order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_pedido: pedidoData.id_pedido })
                    });
                })
                .then(res => res.json())
                .then(orderData => {
                    if (orderData.error) throw new Error(orderData.error);
                    return orderData.id;
                });
            },
            onApprove: function(data, actions) {
                window.location.href = 'paypal/capture-order.php?token=' + data.orderID;
            },
            onError: function(err) {
                alert('Erro no pagamento PayPal. Por favor tenta novamente.');
                console.error(err);
            },
            onCancel: function() {
                alert('Pagamento cancelado.');
            }
        }).render('#paypal-button-container');
    }
</script>

</body>
</html>