<style>
    body { font-family: Arial; font-size: 12px }
    table { width: 100%; border-collapse: collapse }
    th, td { border: 1px solid #ccc; padding: 5px }
    th { background: #eee }
</style>
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
        
    </table>