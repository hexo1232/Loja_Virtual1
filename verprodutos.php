<?php
include "conexao.php";
include "verifica_login_opcional.php";

// Filtros
$nome = $_GET['nome'] ?? '';
$preco_min = $_GET['preco_min'] ?? '';
$preco_max = $_GET['preco_max'] ?? '';
$id_categoria = $_GET['categoria'] ?? '';
$id_marca = $_GET['marca'] ?? '';

// Construir WHERE
$where = "WHERE 1=1";
$param = [];
$tipos = '';

if (!empty($nome)) {
    $where .= " AND p.nome_produto LIKE ?";
    $param[] = "%$nome%";
    $tipos .= 's';
}
if (!empty($preco_min)) {
    $where .= " AND p.preco >= ?";
    $param[] = $preco_min;
    $tipos .= 'd';
}
if (!empty($preco_max)) {
    $where .= " AND p.preco <= ?";
    $param[] = $preco_max;
    $tipos .= 'd';
}
if (!empty($id_categoria)) {
    $where .= " AND p.id_categoria = ?";
    $param[] = $id_categoria;
    $tipos .= 'i';
}
if (!empty($id_marca)) {
    $where .= " AND p.id_marca = ?";
    $param[] = $id_marca;
    $tipos .= 'i';
}

$sql = "SELECT p.*, m.nome_marca, c.nome_categoria, img.caminho_imagem AS imagem_principal
        FROM produto p
        JOIN marca m ON p.id_marca = m.id_marca
        JOIN categoria c ON p.id_categoria = c.id_categoria
        LEFT JOIN produto_imagem img ON img.id_produto = p.id_produto AND img.imagem_principal = 1
        $where";

$stmt = $conexao->prepare($sql);
if (!empty($param)) {
    $stmt->bind_param($tipos, ...$param);
}
$stmt->execute();
$result = $stmt->get_result();

$categorias = $conexao->query("SELECT * FROM categoria");
$marcas = !empty($id_categoria) ?
    $conexao->query("SELECT m.id_marca, m.nome_marca FROM categoria_marca cm JOIN marca m ON cm.id_marca = m.id_marca WHERE cm.id_categoria = $id_categoria") :
    $conexao->query("SELECT * FROM marca");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Produtos</title>
    <link rel="stylesheet" href="css/cliente.css">
    <script src="js/cliente.js" defer></script>
              
    <?php if ($usuario): ?>
        <script src="logout_auto.js"></script>
    <?php endif; ?>
    
   
    <script>
        function atualizarMarcas() {
            var categoria = document.getElementById('categoria').value;
            window.location.href = 'verprodutos.php?categoria=' + categoria;
        }
    </script>
  
</head>
<body>
<?php if ($usuario): ?>
    <?php 
    $nome2 = $usuario['nome'] ?? '';
    $apelido = $usuario['apelido'] ?? '';
    $iniciais = strtoupper(substr($nome2, 0, 1) . substr($apelido, 0, 1));
    $nomeCompleto = "$nome2 $apelido";
    function gerarCor($texto) {
        $hash = md5($texto);
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));
        return "rgb($r, $g, $b)";
    }
    $corAvatar = gerarCor($nomeCompleto);
    ?>
    <div class="usuario-info">
        <div class="usuario-iniciais" style="background-color: <?= $corAvatar ?>"><?= $iniciais ?></div>
        <div class="usuario-nome"><?= $nomeCompleto ?></div>
    </div>
<?php else: ?>
   <div style="padding: 15px; background: #fffae6; border: 1px solid #ffc107; margin: 15px; margin-left: 250px; border-radius: 5px;">
    <strong>Não tem sessão iniciada?</strong>
    <a href="login.php" style="color: #d35400; text-decoration: underline;">Faça login</a> para adicionar ao carrinho, favoritar produtos e ver histórico.
</div>

<?php endif; ?>

<sidebar class="sidebar">
    <a href="carrinho.php">Ver carrinho</a>
    <a href="historico_compras.php">Ver Histórico de Compras</a>
    <?php if ($usuario): ?><a href="logout.php">Sair</a><?php endif; ?>
</sidebar>

<div class="conteudo">
  <h2 style="padding:0 20px;">Produtos Disponíveis</h2>

  <!-- Filtros -->
  <div class="filtro-produtos">
      <form method="get">
          <input type="text" name="nome" placeholder="Nome do produto" value="<?= htmlspecialchars($nome) ?>">
          <input type="number" step="0.01" name="preco_min" placeholder="Preço mín." value="<?= $preco_min ?>">
          <input type="number" step="0.01" name="preco_max" placeholder="Preço máx." value="<?= $preco_max ?>">

          <select name="categoria" id="categoria" onchange="atualizarMarcas()">
              <option value="">Todas as categorias</option>
              <?php while($c = $categorias->fetch_assoc()): ?>
                  <option value="<?= $c['id_categoria'] ?>" <?= $c['id_categoria'] == $id_categoria ? 'selected' : '' ?>>
                      <?= $c['nome_categoria'] ?>
                  </option>
              <?php endwhile; ?>
          </select>

          <select name="marca">
              <option value="">Todas as marcas</option>
              <?php while($m = $marcas->fetch_assoc()): ?>
                  <option value="<?= $m['id_marca'] ?>" <?= $m['id_marca'] == $id_marca ? 'selected' : '' ?>>
                      <?= $m['nome_marca'] ?>
                  </option>
              <?php endwhile; ?>
          </select>

          <input class="busca" type="submit" value="Filtrar">
      </form>
  </div>

  <!-- Produtos -->
  <div class="container-produtos">
  <?php while ($p = $result->fetch_assoc()): ?>
      <div class="card-produto">
          <?php if ($p['imagem_principal']): ?>
              <img src="<?= htmlspecialchars($p['imagem_principal']) ?>" alt="Imagem do produto">
          <?php else: ?>
              <img src="imagens/sem_imagem.jpg" alt="Sem imagem">
          <?php endif; ?>
         <div class="info">
    <div class="titulo"><?= htmlspecialchars($p['nome_produto']) ?></div>
    <div class="preco"><?= number_format($p['preco'], 2, ',', '.') ?> MZN</div>
    
    <p><strong>Categoria:</strong> <?= $p['nome_categoria'] ?></p>
    <p><strong>Marca:</strong> <?= $p['nome_marca'] ?></p>
    <a href="detalhesproduto.php?id=<?= $p['id_produto'] ?>" class="botao-detalhes">Ver detalhes</a>
</div>

      </div>
  <?php endwhile; ?>
  </div>
</div>

</body>
</html>