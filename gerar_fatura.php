<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
include "conexao.php";
require_once "require_login.php";

if (!isset($_GET['id_pedido']) || !is_numeric($_GET['id_pedido'])) {
    die("Pedido inválido.");
}

$id_pedido = $_GET['id_pedido'];

// Buscar dados do pedido
$sql = "SELECT p.*, u.nome AS nome_usuario, u.email, u.telefone,
               prov.nome_província, c.nome_cidade, tp.tipo_pagamento
        FROM Pedido p
        JOIN usuario u ON p.id_usuário = u.id_usuário
        JOIN provincia prov ON p.idprovíncia = prov.idprovíncia
        JOIN cidade c ON p.idcidade = c.idcidade
        JOIN tipo_pagamento tp ON p.idtipo_pagamento = tp.idtipo_pagamento
        WHERE p.id_pedido = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    die("Pedido não encontrado.");
}

// Buscar itens do pedido
$itens = [];
$sqlItens = "SELECT ip.*, pr.nome_produto, pr.preco,
                (SELECT caminho_imagem FROM produto_imagem 
                 WHERE id_produto = pr.id_produto AND imagem_principal = 1 LIMIT 1) AS imagem
             FROM Item_Pedido ip
             JOIN Produto pr ON ip.id_produto = pr.id_produto
             WHERE ip.id_pedido = ?";
$stmt = $conexao->prepare($sqlItens);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$resultItens = $stmt->get_result();
while ($row = $resultItens->fetch_assoc()) {
    $itens[] = $row;
}

// Gerar QR code
$qr_data = "Pedido Nº {$id_pedido}";
$qr_code = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qr_data) . "&size=100x100";

// Estilos + Cabeçalho
$html = '
<style>
    body { font-family: Arial, sans-serif; }
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
    .logo { height: 60px; }
    .info-pedido, .cliente { margin: 10px 0; }
    .card { display: flex; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 8px; }
    .card img { width: 100px; height: 100px; object-fit: cover; margin-right: 15px; }
    .card .detalhes { flex: 1; }
    .total { text-align: right; font-size: 1.2em; margin-top: 15px; font-weight: bold; }
    .qr { float: right; }
</style>

<div class="header">
    <img src="http://localhost/Loja_Virtual/lg.png" class="logo">
    <div class="qr">
        <img src="' . $qr_code . '">
    </div>
</div>

<h2>Fatura Nº ' . $id_pedido . '</h2>

<div class="cliente">
    <strong>Cliente:</strong> ' . $pedido['nome_usuario'] . '<br>
    <strong>Email:</strong> ' . $pedido['email'] . '<br>
    <strong>Telefone:</strong> ' . $pedido['telefone'] . '<br>
    <strong>Província:</strong> ' . $pedido['nome_província'] . '<br>
    <strong>Cidade:</strong> ' . $pedido['nome_cidade'] . '<br>
    <strong>Método:</strong> ' . $pedido['tipo_pagamento'] . '<br>
</div>

<h3>Produtos</h3>
';

$base_img_dir = 'C:/xampp/htdocs/Loja_Virtual/uploads/'; // Caminho físico completo

foreach ($itens as $item) {
    $imagem_nome = $item['imagem'] ?? 'sem_foto.png';
    $caminho_fisico = $base_img_dir . basename($imagem_nome);

    if (file_exists($caminho_fisico)) {
        $tipo = pathinfo($caminho_fisico, PATHINFO_EXTENSION);
        $base64 = base64_encode(file_get_contents($caminho_fisico));
        $src_img = 'data:image/' . $tipo . ';base64,' . $base64;
    } else {
        $src_img = 'https://via.placeholder.com/100?text=Sem+Imagem';
    }

    $html .= '
    <div class="card">
        <img src="' . $src_img . '" alt="">
        <div class="detalhes">
            <strong>' . $item['nome_produto'] . '</strong><br>
            Quantidade: ' . $item['quantidade'] . '<br>
            Preço: ' . number_format($item['preco_unitario'], 2, ',', '.') . ' MZN<br>
            Subtotal: ' . number_format($item['subtotal'], 2, ',', '.') . ' MZN
        </div>
    </div>';
}

$html .= '<div class="total">Total Geral: ' . number_format($pedido['valor_total'], 2, ',', '.') . ' MZN</div>';

// Geração do PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Exibir ou baixar
if (isset($_GET['view'])) {
    echo $html;
    echo '<br><br><button onclick="window.print()">Imprimir</button>';
    echo '<a href="?id=' . $id_pedido . '">Baixar PDF</a>';
} else {
    $dompdf->stream("fatura_pedido_{$id_pedido}.pdf", ["Attachment" => false]);
}
