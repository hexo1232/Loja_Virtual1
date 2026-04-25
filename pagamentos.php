<?php
require_once 'conexao.php';
require_once 'vendor/autoload.php'; 
require_once "require_login.php";
include "usuario_info.php";// Para DomPDF

// Filtros
$filtroData = isset($_GET['periodo']) ? $_GET['periodo'] : '30';
$filtroMetodo = isset($_GET['metodo']) ? $_GET['metodo'] : '';

// Data inicial
$dataInicio = date('Y-m-d', strtotime("-$filtroData days"));

// Consulta com filtros
$sql = "
SELECT p.id_pagamento, p.data_pagamento, p.valor_pago, p.status_pagamento,
       u.nome, u.apelido, tp.tipo_pagamento, ped.id_pedido
FROM Pagamento p
JOIN Pedido ped ON ped.id_pedido = p.id_pedido
JOIN usuario u ON ped.id_usuário = u.id_usuário
JOIN tipo_pagamento tp ON p.idtipo_pagamento = tp.idtipo_pagamento
WHERE p.data_pagamento >= ?
";

$params = [$dataInicio];
$types = "s";

if (!empty($filtroMetodo)) {
    $sql .= " AND tp.idtipo_pagamento = ?";
    $params[] = $filtroMetodo;
    $types .= "i";
}

$stmt = $conexao->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Para o filtro de métodos
$metodos = $conexao->query("SELECT * FROM tipo_pagamento")->fetch_all(MYSQLI_ASSOC);

// Exportar para PDF
if (isset($_GET['exportar']) && $_GET['exportar'] === 'pdf') {
    ob_start();
    include 'template_pagamento_pdf.php';
    $html = ob_get_clean();

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream("relatorio_pagamentos.pdf", ["Attachment" => false]);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pagamentos</title>
    
        <script src="logout_auto.js"></script>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px }
        th, td { border: 1px solid #ccc; padding: 8px }
        th { background: #eee }
    </style>
</head>
<body>

 <div class="sidebar">
        <div>
            <h2>Menu Admin</h2>
            <a href="usuarios.php">👤 Gerenciar Usuários</a>
            <a href="gerenciarprodutos.php">Produtos</a>
            <a href="fonecedores.php">Fornecedores</a>
       
            <a href="">📜 Histórico de Logs</a>
            <a href="">⬆️ Histórico de Uploads</a>
            <a href="">📈 Estatísticas de Compras</a>
            <a href="categoria.php">Categorias</a>
            <a href="marcas.php">Marcas</a>
            <a href="logout.php">⬅Logout</a>
        </div>

    <h2>Pagamentos Recebidos</h2>

    <form method="GET">
        <label>Período:
            <select name="periodo">
                <option value="7" <?= $filtroData == '7' ? 'selected' : '' ?>>Últimos 7 dias</option>
                <option value="30" <?= $filtroData == '30' ? 'selected' : '' ?>>Últimos 30 dias</option>
                <option value="90" <?= $filtroData == '90' ? 'selected' : '' ?>>Últimos 90 dias</option>
            </select>
        </label>

        <label>Método de Pagamento:
            <select name="metodo">
                <option value="">Todos</option>
                <?php foreach ($metodos as $m): ?>
                    <option value="<?= $m['idtipo_pagamento'] ?>" <?= $filtroMetodo == $m['idtipo_pagamento'] ? 'selected' : '' ?>>
                        <?= $m['tipo_pagamento'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit">Filtrar</button>
        <a href="?<?= http_build_query(array_merge($_GET, ['exportar' => 'pdf'])) ?>" target="_blank">Exportar PDF</a>
    </form>

    <table>
        <tr>
            <th>Data</th>
            <th>Cliente</th>
            <th>Método</th>
            <th>Status</th>
            <th>Valor</th>
            <th>Pedido</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($row['data_pagamento'])) ?></td>
                <td><?= $row['nome'] . ' ' . $row['apelido'] ?></td>
                <td><?= $row['tipo_pagamento'] ?></td>
                <td><?= ucfirst($row['status_pagamento']) ?></td>
                <td><?= number_format($row['valor_pago'], 2, ',', '.') ?> MZN</td>
                <td><a href="ver_pedido_admin.php?id=<?= $row['id_pedido'] ?>">Ver detalhes</a></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
