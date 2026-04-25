<?php
require_once "conexao.php";
require_once "require_login.php";

$id_pedido = intval($_GET['id_pedido'] ?? 0);

if (!$id_pedido) {
    header("Location: verprodutos.php");
    exit;
}

// Verificar que o pedido pertence ao utilizador logado e está pago
$id_usuario = $_SESSION['usuario']['id_usuário'];
$stmt = $conexao->prepare("
    SELECT p.id_pedido, p.valor_total, p.data_pedido, p.email,
           pg.status_pagamento
    FROM pedido p
    LEFT JOIN pagamento pg ON pg.id_pedido = p.id_pedido
    WHERE p.id_pedido = ?
      AND p.id_usuário = ?
      AND p.status_pedido = 'pago'
    LIMIT 1
");
$stmt->bind_param("ii", $id_pedido, $id_usuario);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header("Location: verprodutos.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Pedido Confirmado ✔</title>
    <link rel="stylesheet" href="css/cliente.css">
    <style>
        .sucesso-box { max-width: 600px; margin: 80px auto; padding: 40px;
                       border-radius: 12px; background: #fff; text-align: center;
                       box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .icone { font-size: 64px; }
        h2 { color: #28a745; }
        .detalhe { background: #f8f9fa; padding: 15px; border-radius: 8px;
                   margin: 20px 0; text-align: left; }
        .btn { display: inline-block; margin: 8px; padding: 12px 28px;
               border-radius: 6px; text-decoration: none; font-weight: bold;
               color: white; background: #007bff; }
        .btn-verde { background: #28a745; }
    </style>
</head>
<body>
    <div class="sucesso-box">
        <div class="icone">✅</div>
        <h2>Pagamento Confirmado!</h2>
        <p>O teu pedido foi recebido e o pagamento foi processado com sucesso.</p>

        <div class="detalhe">
            <strong>Pedido #:</strong> <?= $pedido['id_pedido'] ?><br>
            <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?><br>
            <strong>Total Pago:</strong> <?= number_format($pedido['valor_total'], 2, ',', '.') ?> MZN<br>
            <strong>Confirmação enviada para:</strong> <?= htmlspecialchars($pedido['email']) ?>
        </div>

        <a href="historico_compras.php" class="btn">📦 Ver os Meus Pedidos</a>
        <a href="verprodutos.php" class="btn btn-verde">🛍️ Continuar a Comprar</a>
        <a href="gerar_fatura.php?id_pedido=<?= $id_pedido ?>" class="btn" style="background:#6c757d">🖨️ Imprimir Fatura</a>
    </div>
</body>
</html>