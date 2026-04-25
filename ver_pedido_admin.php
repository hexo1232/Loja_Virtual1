<?php
require_once 'conexao.php';
require_once 'vendor/autoload.php'; 
require_once "require_login.php";
include "usuario_info.php";

if (!isset($_GET['id'])) {
    echo "<p>Pedido não especificado.</p>";
    exit;
}

$idpedido = intval($_GET['id']);

// Consulta os dados do pedido com cliente e pagamento
$sql = "
SELECT p.id_pedido, p.data_pedido, p.valor_total,
       u.nome, u.apelido, u.email, u.telefone,     
       pr.nome_província AS nome_provincia, c.nome_cidade,
       tp.tipo_pagamento
FROM Pedido p
JOIN usuario u ON p.id_usuário = u.id_usuário
JOIN provincia pr ON p.idprovíncia = pr.idprovíncia
JOIN cidade c ON p.idcidade = c.idcidade
JOIN tipo_pagamento tp ON p.idtipo_pagamento = tp.idtipo_pagamento
WHERE p.id_pedido = ?
";


$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $idpedido);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Pedido não encontrado.</p>";
    exit;
}

$pedido = $result->fetch_assoc();

// Consulta os produtos do pedido
$sqlProdutos = " SELECT p.nome_produto, ip.quantidade, ip.preco_unitario, ip.subtotal,
                   pi.caminho_imagem
            FROM Item_Pedido ip
            INNER JOIN Produto p ON ip.id_produto = p.id_produto
            LEFT JOIN produto_imagem pi ON pi.id_produto = p.id_produto AND pi.imagem_principal = 1
            WHERE ip.id_pedido = ?
        ;";

$stmt = $conexao->prepare($sqlProdutos);
$stmt->bind_param("i", $idpedido);
$stmt->execute();
$produtos = $stmt->get_result();

// Exportar para PDF
if (isset($_GET['exportar']) && $_GET['exportar'] === 'pdf') {
    ob_start();
    include 'template_pedido_pdf.php';
    $html = ob_get_clean();

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream("relatorio_pedido.pdf", ["Attachment" => false]);
    exit;
}
?>




<!DOCTYPE html>
<html>
<head>
    <title>Detalhes do Pedido</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px }
        table { border-collapse: collapse; width: 100%; margin-top: 20px }
        th, td { border: 1px solid #ccc; padding: 8px }
        th { background-color: #f2f2f2 }
        h2, h3 { margin-bottom: 10px }
    </style>
</head>
<body>
    <h2>Detalhes do Pedido #<?= $pedido['id_pedido'] ?></h2>

    <h3>Informações do Cliente</h3>
    <p><strong>Nome:</strong> <?= $pedido['nome'] . ' ' . $pedido['apelido'] ?></p>
    <p><strong>Email:</strong> <?= $pedido['email'] ?></p>
    <p><strong>Telefone:</strong> <?= $pedido['telefone'] ?></p>

    <h3>Endereço</h3>
    <p><strong>Província:</strong> <?= $pedido['nome_provincia'] ?></p>
    <p><strong>Cidade:</strong> <?= $pedido['nome_cidade'] ?></p>

    <h3>Pagamento</h3>
    <p><strong>Método:</strong> <?= $pedido['tipo_pagamento'] ?></p>
    <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
    <p><strong>Total:</strong> <?= number_format($pedido['valor_total'], 2, ',', '.') ?> MZN</p>

    <h3>Produtos</h3>
    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Preço Unitário</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($prod = $produtos->fetch_assoc()): ?>
                <tr>
                    <td><?= $prod['nome_produto'] ?></td>
                    <td><?= $prod['quantidade'] ?></td>
                    <td><?= number_format($prod['preco_unitario'], 2, ',', '.') ?> MZN</td>
                    <td><?= number_format($prod['quantidade'] * $prod['preco_unitario'], 2, ',', '.') ?> MZN</td>
                </tr>
            <?php endwhile; ?>

        </tbody>
        
    </table><br>


        <a href="?<?= http_build_query(array_merge($_GET, ['exportar' => 'pdf'])) ?>" target="_blank">Exportar PDF</a><br>
  

    <br><a href="pagamentos.php">← Voltar</a>
</body>
</html>
