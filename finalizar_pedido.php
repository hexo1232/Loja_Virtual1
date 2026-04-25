<?php
// ===== 1. IMPORTAÇÕES (use) DEVEM VIR PRIMEIRO =====
use CyberSource\ApiClient;
use CyberSource\Configuration;
use CyberSource\Api\PaymentsApi;
use CyberSource\Model\CreatePaymentRequest;
use CyberSource\Model\Ptsv2paymentsClientReferenceInformation;
use CyberSource\Model\Ptsv2paymentsOrderInformation;
use CyberSource\Model\Ptsv2paymentsOrderInformationAmountDetails;
use CyberSource\Model\Ptsv2paymentsPaymentInformation;
use CyberSource\Model\Ptsv2paymentsPaymentInformationCard;
use SampleCode\ExternalConfiguration;

// ===== 2. REQUISITOS =====
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/API/VISA/ExternalConfiguration.php';
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

if ($resultItens->num_rows == 0) {
    echo "<p style='color:red;'>Seu carrinho está vazio.</p>";
    exit;
}

$total = 0;
$itens = [];
while ($item = $resultItens->fetch_assoc()) {
    $itens[] = $item;
    $total += $item['subtotal'];
}

// Processar formulário
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $telefone = filter_var($_POST['telefone'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $idprovincia = filter_var($_POST['idprovincia'], FILTER_VALIDATE_INT);
    $idcidade = filter_var($_POST['idcidade'], FILTER_VALIDATE_INT);
    $metodo = filter_var($_POST['metodo'], FILTER_VALIDATE_INT);
  

    if (!$telefone || !$email || $idprovincia === false || $idcidade === false || $metodo === false) {
        die("<p style='color:red;'>Dados inválidos fornecidos.</p>");
    }

    if ($metodo == 1) {
        try {
            $merchantConfig = ExternalConfiguration::getMerchantConfig();
            if (!$merchantConfig || !$merchantConfig->getAuthenticationType()) {
                throw new Exception("Configuração do comerciante inválida ou ausente. Verifique ExternalConfiguration.php ou crie Cybs.json.");
            }

            $config = CyberSource\Configuration::getDefaultConfiguration();

            echo "<pre>";
            echo "Merchant Config:\n";
            var_dump($merchantConfig);
            echo "Configuration:\n";
            var_dump($config);
            echo "Log Directory: " . ($merchantConfig->getLogConfiguration()->logDirectory ?? 'Não definido') . "\n";
            echo "Log File: " . ($merchantConfig->getLogConfiguration()->logFilename ?? 'Não definido') . "\n";
            echo "Log Enabled: " . ($merchantConfig->getLogConfiguration()->enableLog ?? false ? 'true' : 'false') . "\n";
            // exit; // Descomente para depuração

            $logDirectory = $merchantConfig->getLogConfiguration()->logDirectory;
            $logFile = $logDirectory . '/' . $merchantConfig->getLogConfiguration()->logFilename;
            if (!is_dir($logDirectory)) mkdir($logDirectory, 0777, true);
            if (!is_writable($logDirectory)) {
                echo "Erro: Diretório de logs '$logDirectory' não é gravável. Verifique permissões.\n";
            } else {
                $testMessage = "Teste de gravação às " . date('Y-m-d H:i:s') . "\n";
                if (file_put_contents($logFile, $testMessage, FILE_APPEND) === false) {
                    echo "Falha ao gravar teste no log. Verifique o diretório ou disco.\n";
                } else {
                    echo "Gravação de teste no log realizada com sucesso.\n";
                }
            }

            try {
                $apiClient = new CyberSource\ApiClient($config, $merchantConfig);
                echo "ApiClient instanciado com sucesso.\n";
                file_put_contents($logFile, "ApiClient instanciado às " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            } catch (Exception $e) {
                echo "Erro ao instanciar ApiClient: " . htmlspecialchars($e->getMessage()) . "\n";
                file_put_contents($logFile, "Erro ao instanciar ApiClient às " . date('Y-m-d H:i:s') . ": " . $e->getMessage() . "\n", FILE_APPEND);
                exit;
            }

            $paymentsApi = new CyberSource\Api\PaymentsApi($apiClient);

            $num = preg_replace('/\D/', '', $_POST['visa_num'] ?? '');
            $validade = explode("-", $_POST['visa_validade'] ?? '12-2031');
            $cvv = $_POST['visa_cvv'] ?? '';

            if (empty($num) || strlen($num) < 13 || strlen($num) > 19 || empty($validade) || count($validade) !== 2 || empty($cvv) || strlen($cvv) < 3 || strlen($cvv) > 4) {
                throw new Exception("Dados do cartão incompletos ou inválidos.");
            }

            $card = new CyberSource\Model\Ptsv2paymentsPaymentInformationCard([
                "number" => $num,
                "expirationMonth" => $validade[1],
                "expirationYear" => $validade[0],
                "securityCode" => $cvv
            ]);

            $paymentInformation = new CyberSource\Model\Ptsv2paymentsPaymentInformation(["card" => $card]);
            $amountDetails = new CyberSource\Model\Ptsv2paymentsOrderInformationAmountDetails([
                "totalAmount" => number_format($total, 2, '.', ''),
                "currency" => "MZN"
            ]);
            $orderInformation = new CyberSource\Model\Ptsv2paymentsOrderInformation(["amountDetails" => $amountDetails]);
            $clientReference = new CyberSource\Model\Ptsv2paymentsClientReferenceInformation(["code" => "Pedido_" . uniqid()]);
            $paymentRequest = new CyberSource\Model\CreatePaymentRequest([
                "clientReferenceInformation" => $clientReference,
                "orderInformation" => $orderInformation,
                "paymentInformation" => $paymentInformation
            ]);

            $response = $paymentsApi->createPayment($paymentRequest);
            file_put_contents($logFile, "Requisição enviada às " . date('Y-m-d H:i:s') . ", Status: " . $response->getStatus() . "\n", FILE_APPEND);

            if ($response->getStatus() !== 'AUTHORIZED') {
                echo "<p style='color:red;'>Pagamento com VISA falhou: " . htmlspecialchars($response->getStatus()) . "</p>";
                file_put_contents($logFile, "Falha no pagamento: " . $response->getStatus() . "\n", FILE_APPEND);
                exit;
            } else {
                echo "<p style='color:green;'>Pagamento com VISA autorizado com sucesso!</p>";
                file_put_contents($logFile, "Pagamento autorizado com sucesso.\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            echo "<p style='color:red;'>Erro ao processar o pagamento com VISA: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>Detalhes do erro:\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            $logFile = $merchantConfig->getLogConfiguration()->logDirectory . '/' . $merchantConfig->getLogConfiguration()->logFilename;
            file_put_contents($logFile, "Erro às " . date('Y-m-d H:i:s') . ": " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            exit;
        }
    } elseif ($metodo == 5) {
        echo "<div id='paypal-button-container'></div>";
        // O processamento será feito pelo JavaScript e pelos endpoints
    } else {
        echo "<p style='color:red'>Método de pagamento inválido.</p>";
        exit;
    }

    // Criar pedido e atualizar banco de dados
    $stmtPedido = $conexao->prepare("INSERT INTO Pedido (data_pedido, status_pedido, valor_total, telefone, email, idprovíncia, idcidade, idtipo_pagamento, id_usuário)
                                     VALUES (NOW(), 'pendente', ?, ?, ?, ?, ?, ?, ?)");
    $stmtPedido->bind_param("dssiiii", $total, $telefone, $email, $idprovincia, $idcidade, $metodo, $id_usuario);

    if (!$stmtPedido->execute()) {
        die("Erro ao inserir pedido: " . $stmtPedido->error);
    }

    $id_pedido = $stmtPedido->insert_id;

    foreach ($itens as $item) {
        $stmtItem = $conexao->prepare("INSERT INTO Item_Pedido (id_pedido, id_produto, quantidade, preco_unitario, subtotal)
                                       VALUES (?, ?, ?, ?, ?)");
        $stmtItem->bind_param("iiidd", $id_pedido, $item['id_produto'], $item['quantidade'], $item['preco'], $item['subtotal']);
        $stmtItem->execute();

        $stmtEstoque = $conexao->prepare("UPDATE Produto SET quantidade_estoque = quantidade_estoque - ? WHERE id_produto = ?");
        $stmtEstoque->bind_param("ii", $item['quantidade'], $item['id_produto']);
        $stmtEstoque->execute();
    }

    $conexao->prepare("UPDATE Carrinho SET status = 'finalizado' WHERE id_carrinho = ?")->bind_param("i", $id_carrinho)->execute();
    $conexao->prepare("DELETE FROM Item_Carrinho WHERE id_carrinho = ?")->bind_param("i", $id_carrinho)->execute();

    $status_pagamento = 'pago';
    $data_pagamento = date("Y-m-d H:i:s");
    $valor_pago = $total;

    $stmtPagamento = $conexao->prepare("INSERT INTO Pagamento (status_pagamento, data_pagamento, valor_pago, id_pedido, idtipo_pagamento)
                                        VALUES (?, ?, ?, ?, ?)");
    $stmtPagamento->bind_param("ssdii", $status_pagamento, $data_pagamento, $valor_pago, $id_pedido, $metodo);
    $stmtPagamento->execute();

    echo "<div id='popup-confirmacao' class='popup'>
        <div class='popup-content'>
            <h3>Pedido finalizado com sucesso!</h3>
            <p>O que deseja fazer a seguir?</p>
            <div class='popup-buttons'>
                <button onclick=\"window.location.href='verprodutos.php'\">Voltar às compras</button>
                <button onclick=\"window.location.href='historico_compras.php'\">Ver histórico de compras</button>
                <button onclick=\"window.location.href='gerar_fatura.php?id_pedido=$id_pedido'\">Imprimir fatura</button>
            </div>
        </div>
    </div>
    <style>
        .popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .popup-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }
        .popup-buttons button {
            margin: 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }
        .popup-buttons button:hover {
            background-color: #0056b3;
        }
    </style>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Finalizar Pedido</title>
    <link rel="stylesheet" href="css/cliente.css">
    <script src="js/cliente.js" defer></script>
        <script src="logout_auto.js"></script>

    <style>
        .card { border: 1px solid #ccc; border-radius: 10px; padding: 20px; margin: 15px 0; display: flex; gap: 20px; background: #fefefe; box-shadow: 0 2px 6px rgba(0,0,0,0.1); width: 75%; }
        .card img { width: 120px; height: 120px; object-fit: cover; border-radius: 8px; }
        .card img:hover { cursor: pointer; }
        .info { flex: 1; }
        .metodo-formulario { display: none; margin-top: 10px; padding: 10px; border: 1px dashed #aaa; background: #f5f5f5; }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 170px;
            height: 100%;
            background: #eee;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .sidebar a { text-decoration: none; color: #333; }
        .sidebar a:hover { background-color: gray; }
        
        .conteudo { margin-left: 240px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 80%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #aaa; }
        .end {
            padding: 10px;
            width: 120px;
            background-color: #00ff88ff;
            color: white;
            border: none;
            border-radius: 6px;
            transition: background-color 0.3s;
        }
        .end a { text-decoration: none; color: #fff; }
        .end:hover { background-color: #06926fff; cursor: pointer; }
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
    <sidebar class="sidebar">
        <a href="carrinho.php">Voltar ao Carrinho</a>
        <?php if ($usuario): ?><a href="logout.php">Sair</a><?php endif; ?>
    </sidebar>

    <div class="conteudo">
        <h2>Finalizar Pedido</h2>
        <form method="post">
            <?php foreach ($itens as $item): ?>
                <div class="card">
                    <img src="<?= htmlspecialchars($item['imagem_principal'] ?? 'imagens/sem_imagem.jpg') ?>" alt="Imagem">
                    <div class="info">
                        <h3><?= htmlspecialchars($item['nome_produto']) ?></h3>
                        <p>Quantidade: <?= $item['quantidade'] ?></p>
                        <p>Preço Unitário: <?= number_format($item['preco'], 2, ',', '.') ?> MZN</p>
                        <p><strong>Subtotal: <?= number_format($item['subtotal'], 2, ',', '.') ?> MZN</strong></p>
                    </div>
                </div>
            <?php endforeach; ?>

            <p><strong>Total: <?= number_format($total, 2, ',', '.') ?> MZN</strong></p>

            <h3>Dados para entrega</h3>
            <label>Telefone:</label><input type="text" name="telefone" required placeholder="84/83/87"><br>
            <label>Email:</label><input type="email" name="email" required><br>

            <label>Província:</label>
            <select name="idprovincia" id="idprovincia" onchange="carregarCidades()" required>
                <option value="">Província</option>
                <?php
                $prov = $conexao->query("SELECT * FROM provincia");
                while ($p = $prov->fetch_assoc()) {
                    echo "<option value='{$p['idprovíncia']}'>{$p['nome_província']}</option>";
                }
                ?>
            </select><br>

            <label>Cidade:</label>
            <select name="idcidade" id="idcidade" required>
                <option value="">Selecione a Província Primeiro</option>
            </select><br>

            <label>Método de Pagamento:</label>
            <select name="metodo" id="metodo" onchange="mostrarFormularioPagamento()" required>
                <option value="">Selecione</option>
                <?php
                $met = $conexao->query("SELECT * FROM tipo_pagamento");
                while ($m = $met->fetch_assoc()) {
                    echo "<option value='{$m['idtipo_pagamento']}'>{$m['tipo_pagamento']}</option>";
                }
                ?>
            </select><br><br>

            <!-- Formulários por método de pagamento -->
            <!-- <div id="formulario-1" class="metodo-formulario">
                <h4>Pagamento com VISA</h4>
                Nº do Cartão: <input type="tel" name="visa_num" placeholder="XXXX XXXX XXXX XXXX" pattern="[0-9]{16}" maxlength="19"><br>
                Validade: <input type="month" name="visa_validade"><br>
                CVV: <input type="text" name="visa_cvv" maxlength="3"><br>
            </div> -->

            <div id="formulario-2" class="metodo-formulario">
                <h4>Pagamento com M-Pesa</h4>
                Nº de telefone M-Pesa: <input type="text" name="mpesa_num"><br>
                Senha do M-Pesa: <input type="password" name="mpesa_cod"><br>
            </div>

            <div id="formulario-3" class="metodo-formulario">
                <h4>Pagamento com E-Mola</h4>
                Nº de telefone E-Mola: <input type="text" name="emola_num"><br>
                Senha do E-Mola: <input type="password" name="emola_cod"><br>
            </div>

            <div id="formulario-4" class="metodo-formulario">
                <h4>Pagamento com Mkesh</h4>
                Nº Mkesh: <input type="text" name="mkesh_num"><br>
                Senha do Mkesh: <input type="password" name="mkesh_cod"><br>
            </div>

            <button class="end" type="submit">Finalizar Pedido</button>
        </form>

        <!-- Adicione isso antes do </body> -->
        <script src="https://www.paypal.com/sdk/js?client-id=AaZBsrjQVwGJ2C6jdCa2UJkrIdarfHzhfBBDMr039wp11qkERhID8eKOZWdnLKPkKE8tPkGhuqhOVQ9z&currency=USD"></script>
        <div id="paypal-button-container" style="display: none;"></div>
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
                if (metodo === "5") {
                    document.getElementById("paypal-button-container").style.display = 'block';
                } else {
                    document.getElementById("paypal-button-container").style.display = 'none';
                }
            }

            paypal.Buttons({
                createOrder: function(data, actions) {
                    return fetch('/Loja_Virtual/API/Paypal/create-paypal-order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            total: <?php echo number_format($total, 2, '.', ''); ?>
                        })
                    })
                    .then(response => response.json())
                    .then(order => {
                        if (order.error) throw new Error(order.error);
                        return order.id;
                    });
                },
                onApprove: function(data, actions) {
                    return fetch('/Loja_Virtual/API/Paypal/capture-paypal-order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            orderID: data.orderID
                        })
                    })
                    .then(response => response.json())
                    .then(details => {
                        if (details.status === 'COMPLETED') {
                            alert('Pagamento com PayPal concluído com sucesso!');
                            window.location.href = 'payment-success.php';
                        } else if (details.error) {
                            alert('Erro ao processar o pagamento: ' + details.error);
                        } else {
                            alert('Erro desconhecido ao processar o pagamento.');
                        }
                    });
                },
                onError: function(err) {
                    alert('Ocorreu um erro no pagamento: ' + err.message);
                },
                onCancel: function(data) {
                    alert('Pagamento com PayPal foi cancelado.');
                }
            }).render('#paypal-button-container');
        </script>
    </div>
</body>
</html>