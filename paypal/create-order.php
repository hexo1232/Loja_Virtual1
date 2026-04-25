<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://loja-virtual1.onrender.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include "../conexao.php";

// Ler o id_pedido enviado pelo frontend
$input     = json_decode(file_get_contents('php://input'), true);
$id_pedido = intval($input['id_pedido'] ?? 0);

if (!$id_pedido) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de pedido inválido']);
    exit;
}

// Buscar o pedido na base de dados (apenas pedidos pendentes com PayPal = idtipo_pagamento=1)
$stmt = $conexao->prepare("
    SELECT id_pedido, valor_total, email
    FROM pedido
    WHERE id_pedido = ?
      AND status_pedido = 'pendente'
      AND idtipo_pagamento = 1
");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();

if (!$pedido) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido não encontrado ou já processado']);
    exit;
}

// Formatar o valor total
// NOTA: O PayPal exige USD. Se a tua loja usa MZN, precisas converter aqui.
// Por agora assumimos que valor_total já está na moeda correcta para o PayPal.
$valor_total = number_format((float)$pedido['valor_total'], 2, '.', '');

// Obter access token do PayPal
$client_id = getenv('PAYPAL_CLIENT_ID');
$secret    = getenv('PAYPAL_SECRET');
$base_url  = getenv('PAYPAL_BASE_URL') ?: 'https://api-m.sandbox.paypal.com';

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
    http_response_code(500);
    echo json_encode(['error' => 'Falha ao autenticar com PayPal']);
    exit;
}

$access_token = $token_response['access_token'];

// Criar a ordem de pagamento no PayPal
$order_data = [
    'intent' => 'CAPTURE',
    'purchase_units' => [[
        'reference_id' => 'pedido_' . $id_pedido,
        'description'  => 'Loja Virtual - Pedido #' . $id_pedido,
        'amount'       => [
            'currency_code' => 'USD', // Muda para a tua moeda se o PayPal suportar MZN
            'value'         => $valor_total,
        ],
    ]],
    'application_context' => [
        'brand_name'  => 'Loja Virtual',
        'landing_page' => 'NO_PREFERENCE',
        'user_action'  => 'PAY_NOW',
        'return_url'   => 'https://loja-virtual1.onrender.com/paypal/capture-order.php',
        'cancel_url'   => 'https://loja-virtual1.onrender.com/finalizar_pedido.php?payment=cancelled',
    ],
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => "$base_url/v2/checkout/orders",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($order_data),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        "Authorization: Bearer $access_token",
    ],
]);
$order_response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($order_response['id'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha ao criar ordem PayPal']);
    exit;
}

// Guardar na sessão para validação cruzada na captura
$_SESSION['paypal_pending'] = [
    'paypal_order_id' => $order_response['id'],
    'id_pedido'       => $id_pedido,
    'valor_total'     => $valor_total,
];

echo json_encode([
    'id'     => $order_response['id'],
    'status' => $order_response['status'],
]);