<?php
include "conexao.php";
require_once "require_login.php";

if (!isset($_GET['id_pedido']) || !is_numeric($_GET['id_pedido'])) {
    die("Pedido inválido.");
}

$id_pedido = intval($_GET['id_pedido']);

$sql = "SELECT p.*, u.nome AS nome_usuario, u.email, u.telefone,
               prov.nome_província, c.nome_cidade, tp.tipo_pagamento
        FROM pedido p
        JOIN usuario u ON p.id_usuário = u.id_usuário
        JOIN provincia prov ON p.idprovíncia = prov.idprovíncia
        JOIN cidade c ON p.idcidade = c.idcidade
        JOIN tipo_pagamento tp ON p.idtipo_pagamento = tp.idtipo_pagamento
        WHERE p.id_pedido = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) die("Pedido não encontrado.");

$sqlItens = "SELECT ip.*, pr.nome_produto, pr.preco
             FROM item_pedido ip
             JOIN produto pr ON ip.id_produto = pr.id_produto
             WHERE ip.id_pedido = ?";
$stmt = $conexao->prepare($sqlItens);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$qr_url = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode("Pedido #{$id_pedido}") . "&size=100x100";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Fatura #<?= $id_pedido ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 30px auto; color: #333; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #0056b3; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .total { text-align: right; font-size: 1.3em; font-weight: bold; margin-top: 20px; }
        .btn { padding: 10px 25px; background: #0056b3; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-right: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h2>Fatura Nº <?= $id_pedido ?></h2>
        <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
    </div>
    <img src="<?= $qr_url ?>" alt="QR Code">
</div>

<h3>Dados do Cliente</h3>
<p><strong>Nome:</strong> <?= htmlspecialchars($pedido['nome_usuario']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($pedido['email']) ?></p>
<p><strong>Telefone:</strong> <?= htmlspecialchars($pedido['telefone']) ?></p>
<p><strong>Província:</strong> <?= htmlspecialchars($pedido['nome_província']) ?></p>
<p><strong>Cidade:</strong> <?= htmlspecialchars($pedido['nome_cidade']) ?></p>
<p><strong>Método de Pagamento:</strong> <?= htmlspecialchars($pedido['tipo_pagamento']) ?></p>

<h3>Produtos</h3>
<table>
    <tr>
        <th>Produto</th>
        <th>Qtd</th>
        <th>Preço Unit.</th>
        <th>Subtotal</th>
    </tr>
    <?php foreach ($itens as $item): ?>
    <tr>
        <td><?= htmlspecialchars($item['nome_produto']) ?></td>
        <td><?= $item['quantidade'] ?></td>
        <td><?= number_format($item['preco_unitario'], 2, ',', '.') ?> MZN</td>
        <td><?= number_format($item['subtotal'], 2, ',', '.') ?> MZN</td>
    </tr>
    <?php endforeach; ?>
</table>

<div class="total">Total Geral: <?= number_format($pedido['valor_total'], 2, ',', '.') ?> MZN</div>

<br><br>
<div class="no-print">
    <button class="btn" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    <button class="btn" onclick="history.back()" style="background:#6c757d">← Voltar</button>
</div>

</body>
</html>