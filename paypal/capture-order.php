<?php
session_start();
include "../conexao.php";

// PayPal redireciona com ?token=ORDER_ID&PayerID=...
$order_id = $_GET['token'] ?? '';

if (!$order_id) {
    header('Location: /finalizar_pedido.php?payment=error');
    exit;
}

// Validação cruzada com a sessão
$pending   = $_SESSION['paypal_pending'] ?? [];
$id_pedido = intval($pending['id_pedido'] ?? 0);

if (!$id_pedido || $pending['paypal_order_id'] !== $order_id) {
    // Sessão inválida ou adulterada
    header('Location: /finalizar_pedido.php?payment=error');
    exit;
}

// Credenciais PayPal
$client_id = getenv('PAYPAL_CLIENT_ID');
$secret    = getenv('PAYPAL_SECRET');
$base_url  = getenv('PAYPAL_BASE_URL') ?: 'https://api-m.sandbox.paypal.com';

// 1. Obter access token
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => "$base_url/v1/oauth2/token",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
    CURLOPT_USERPWD        => "$client_id:$secret",
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$token_response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($token_response['access_token'])) {
    header('Location: /finalizar_pedido.php?payment=error');
    exit;
}

$access_token = $token_response['access_token'];

// 2. Capturar o pagamento
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => "$base_url/v2/checkout/orders/$order_id/capture",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => '{}',
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        "Authorization: Bearer $access_token",
    ],
]);
$capture = json_decode(curl_exec($ch), true);
curl_close($ch);

// 3. Verificar se o pagamento foi bem-sucedido
$status = $capture['status'] ?? '';

if ($status !== 'COMPLETED') {
    // Pedido fica como 'pendente' na BD para análise manual
    error_log("PayPal capture falhou para pedido #$id_pedido. Resposta: " . json_encode($capture));
    header('Location: /finalizar_pedido.php?payment=failed');
    exit;
}

// 4. Extrair dados da transação
$capture_unit   = $capture['purchase_units'][0]['payments']['captures'][0] ?? [];
$transaction_id = $capture_unit['id'] ?? 'N/A';
$amount_paid    = $capture_unit['amount']['value'] ?? '0.00';
$currency       = $capture_unit['amount']['currency_code'] ?? 'USD';
$payer_email    = $capture['payer']['email_address'] ?? 'N/A';

// 5. Actualizar o pedido para 'pago' na tabela `pedido`
try {
    $stmtPedido = $conexao->prepare("
        UPDATE pedido
        SET status_pedido = 'pago'
        WHERE id_pedido = ?
          AND status_pedido = 'pendente'
    ");
    $stmtPedido->bind_param("i", $id_pedido);
    $stmtPedido->execute();

    if ($stmtPedido->affected_rows === 0) {
        // Pedido já foi processado (protecção contra duplo clique)
        error_log("Pedido #$id_pedido já estava processado ou não encontrado.");
    }
} catch (Exception $e) {
    error_log('Erro ao actualizar pedido: ' . $e->getMessage());
}

// 6. Inserir registo na tabela `pagamento`
try {
    $stmtPagamento = $conexao->prepare("
        INSERT INTO pagamento (status_pagamento, data_pagamento, valor_pago, id_pedido, idtipo_pagamento)
        VALUES ('pago', NOW(), ?, ?, 1)
    ");
    // idtipo_pagamento = 1 conforme indicado (PayPal)
    $stmtPagamento->bind_param("di", $amount_paid, $id_pedido);
    $stmtPagamento->execute();
} catch (Exception $e) {
    error_log('Erro ao inserir pagamento: ' . $e->getMessage());
}

// 7. Limpar carrinho agora que o pagamento foi confirmado
$id_carrinho = $_SESSION['paypal_carrinho'] ?? 0;
if ($id_carrinho) {
    $stmtFC = $conexao->prepare("UPDATE carrinho SET status = 'finalizado' WHERE id_carrinho = ?");
    $stmtFC->bind_param("i", $id_carrinho);
    $stmtFC->execute();

    $stmtDI = $conexao->prepare("DELETE FROM item_carrinho WHERE id_carrinho = ?");
    $stmtDI->bind_param("i", $id_carrinho);
    $stmtDI->execute();
}

// 8. Limpar sessão
unset($_SESSION['paypal_pending']);
unset($_SESSION['paypal_carrinho']);

// 9. Redirecionar
header("Location: /sucesso.php?id_pedido=$id_pedido");
exit;