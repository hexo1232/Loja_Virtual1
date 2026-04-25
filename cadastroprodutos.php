<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";

// 1. RESPOSTA PARA AJAX (CARREGAR MARCAS) - DEVE VIR ANTES DE QUALQUER HTML
if (isset($_POST['acao']) && $_POST['acao'] == 'carregar_marcas') {
    $id_categoria = $_POST['id_categoria'] ?? 0;
    // Nomes de tabelas em minúsculo para a Render
    $sql = "SELECT m.id_marca, m.nome_marca 
            FROM categoria_marca cm 
            JOIN marca m ON cm.id_marca = m.id_marca 
            WHERE cm.id_categoria = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_categoria);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    echo '<option value="">Selecione a Marca</option>';
    while ($row = $resultado->fetch_assoc()) {
        echo "<option value='{$row['id_marca']}'>{$row['nome_marca']}</option>";
    }
    exit; // Para a execução aqui se for AJAX
}

// 2. PROCESSAMENTO DO FORMULÁRIO (INSERT)
$mensagem = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['acao'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $quantidade = $_POST['quantidade'];
    $id_categoria = $_POST['categoria'];
    $id_marca = $_POST['marca'];
    $id_fornecedor = $_POST['fornecedor'];

    $stmt = $conexao->prepare("INSERT INTO produto (nome_produto, descricao, preco, quantidade_estoque, id_categoria, id_marca, id_fornecedor) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdiiii", $nome, $descricao, $preco, $quantidade, $id_categoria, $id_marca, $id_fornecedor);
    
    if ($stmt->execute()) {
        $id_produto = $stmt->insert_id;

        // Criar pasta de uploads se não existir
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }

        // Upload das imagens
        if (isset($_FILES['imagens'])) {
            foreach ($_FILES['imagens']['tmp_name'] as $index => $tmp_name) {
                if (!empty($tmp_name)) {
                    $nome_arquivo = basename($_FILES['imagens']['name'][$index]);
                    $destino = "uploads/" . time() . "_" . $nome_arquivo;

                    if (move_uploaded_file($tmp_name, $destino)) {
                        $legenda = $_POST['legenda'][$index] ?? '';
                        $imagem_principal = (isset($_POST['imagem_principal']) && $_POST['imagem_principal'] == $index) ? 1 : 0;

                        $stmt_img = $conexao->prepare("INSERT INTO produto_imagem (id_produto, caminho_imagem, legenda, imagem_principal) VALUES (?, ?, ?, ?)");
                        $stmt_img->bind_param("issi", $id_produto, $destino, $legenda, $imagem_principal);
                        $stmt_img->execute();
                    }
                }
            }
        }
        $mensagem = "<div style='color: green; text-align:center;'>Produto cadastrado com sucesso!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Produto</title>
    <script>
        function carregarMarcas() {
            var categoria = document.getElementById('categoria').value;
            var marcaSelect = document.getElementById('marca');
            
            if (!categoria) {
                marcaSelect.innerHTML = '<option value="">Selecione uma categoria primeiro</option>';
                return;
            }

            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'acao=carregar_marcas&id_categoria=' + categoria
            })
            .then(res => res.text())
            .then(html => marcaSelect.innerHTML = html);
        }

        function adicionarCampoImagem() {
            const container = document.getElementById('imagens-container');
            const index = container.children.length;
            const div = document.createElement('div');
            div.style.borderBottom = "1px solid #eee";
            div.style.marginBottom = "10px";
            div.style.paddingBottom = "10px";
            div.innerHTML = `
                <input type="file" name="imagens[]" required>
                <input type="text" name="legenda[]" placeholder="Legenda da imagem">
                <label style="font-size: 0.9em;">
                    <input type="radio" name="imagem_principal" value="${index}" ${index === 0 ? 'checked' : ''}> Principal
                </label>
            `;
            container.appendChild(div);
        }

        // Iniciar com um campo de imagem ao carregar
        window.onload = function() {
            adicionarCampoImagem();
        };
    </script>

    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f5f7fa; color: #333; margin: 0; }
        .sidebar { position: fixed; left: 0; top: 0; width: 190px; height: 100%; background: #0056b3; color: white; padding: 20px; display:flex; flex-direction:column; gap:10px; }
        .sidebar a { text-decoration:none; color:#fff; padding: 10px; border-radius: 5px; transition: 0.3s; }
        .sidebar a:hover { background-color: #024185; transform:scale(1.05); }
        
        .conteudo { margin-left: 230px; padding: 40px; }
        form { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        h2 { text-align: center; color: #0056b3; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 0.9em; }
        input, select, textarea { width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #ddd; box-sizing: border-box; }
        
        .btn-add { background: #6c757d; color: white; border: none; padding: 8px; cursor: pointer; margin-top: 10px; border-radius: 4px; width: auto; }
        .btn-submit { background: #00ff88; color: #004a29; border: none; padding: 15px; width: 100%; font-weight: bold; font-size: 1.1em; cursor: pointer; border-radius: 6px; margin-top: 20px; transition: 0.3s; }
        .btn-submit:hover { background: #06926f; color: white; }
    </style>
</head>
<body>
    
    <div class="sidebar">
        <h2>Admin</h2>
        <a href="gerenciarprodutos.php">← Voltar</a>
        <a href="logout.php">Sair</a>
    </div>

    <div class="conteudo">
        <h2>Cadastrar Novo Produto</h2>
        <?php echo $mensagem; ?>
        
        <form method="post" enctype="multipart/form-data">
            <label>Nome do Produto:</label>
            <input type="text" name="nome" required placeholder="Ex: Smartphone XYZ">

            <label>Descrição Detalhada:</label>
            <textarea name="descricao" rows="4" required placeholder="Caracterize o produto por completo"></textarea>

            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <label>Preço (MT):</label>
                    <input type="number" step="0.01" name="preco" required>
                </div>
                <div style="flex: 1;">
                    <label>Quantidade em Estoque:</label>
                    <input type="number" name="quantidade" required>
                </div>
            </div>

            <label>Categoria:</label>
            <select name="categoria" id="categoria" onchange="carregarMarcas()" required>
                <option value="">Selecione</option>
                <?php
                $categorias = $conexao->query("SELECT * FROM categoria");
                while ($cat = $categorias->fetch_assoc()) {
                    echo "<option value='{$cat['id_categoria']}'>{$cat['nome_categoria']}</option>";
                }
                ?>
            </select>

            <label>Marca:</label>
            <select name="marca" id="marca" required>
                <option value="">Selecione uma categoria primeiro</option>
            </select>

            <label>Fornecedor:</label>
            <select name="fornecedor" required>
                <option value="">Selecione o Fornecedor</option>
                <?php
                $fornecedores = $conexao->query("SELECT * FROM fornecedor");
                while ($f = $fornecedores->fetch_assoc()) {
                    echo "<option value='{$f['id_fornecedor']}'>{$f['nome_fornecedor']}</option>";
                }
                ?>
            </select>

            <hr style="margin-top: 30px; border: 0; border-top: 1px solid #eee;">
            <h4 style="margin-bottom: 10px;">Imagens do Produto</h4>
            <div id="imagens-container">
                </div>
            <button type="button" class="btn-add" onclick="adicionarCampoImagem()">+ Adicionar Outra Imagem</button>

            <button class="btn-submit" type="submit">CADASTRAR PRODUTO</button>
        </form>
    </div>
</body>
</html>