<style>
    body { font-family: Arial; font-size: 12px }
    table { width: 100%; border-collapse: collapse }
    th, td { border: 1px solid #ccc; padding: 5px }
    th { background: #eee }
</style>
<h2>Relatório de Pagamentos</h2>
<table>
    <tr>
        <th>Data</th>
        <th>Cliente</th>
        <th>Método</th>
        <th>Status</th>
        <th>Valor</th>
    </tr>
    <?php mysqli_data_seek($result, 0); while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($row['data_pagamento'])) ?></td>
            <td><?= $row['nome'] . ' ' . $row['apelido'] ?></td>
            <td><?= $row['tipo_pagamento'] ?></td>
            <td><?= ucfirst($row['status_pagamento']) ?></td>
            <td><?= number_format($row['valor_pago'], 2, ',', '.') ?> MZN</td>
        </tr>
    <?php endwhile; ?>
</table>
