<?php

require_once 'conexao.php';

require_once "require_login.php";


// Verifica se é chamada AJAX para retornar os dados
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');

    $periodo = $_GET['periodo'] ?? 'mensal';

    switch ($periodo) {
        case 'diario':
            $groupBy = "DATE(data_pedido)";
            break;
        case 'semanal':
            $groupBy = "YEARWEEK(data_pedido)";
            break;
        case 'anual':
            $groupBy = "YEAR(data_pedido)";
            break;
        case 'mensal':
        default:
            $groupBy = "DATE_FORMAT(data_pedido, '%Y-%m')";
            break;
    }

    $sql = "
        SELECT 
            $groupBy as periodo,
            COUNT(*) as total_pedidos,
            SUM(valor_total) as total_vendas,
            (SELECT SUM(quantidade) FROM Item_Pedido ip 
             JOIN Pedido p2 ON ip.id_pedido = p2.id_pedido 
             WHERE $groupBy = DATE_FORMAT(p2.data_pedido, '%Y-%m')) as total_itens
        FROM Pedido
        WHERE status_pedido = 'aceite'
        GROUP BY periodo
        ORDER BY periodo
    ";

    $resultado = $conexao->query($sql);

    $labels = [];
    $valores = [];
    $total_pedidos = 0;
    $total_vendas = 0;
    $total_itens = 0;

    while ($row = $resultado->fetch_assoc()) {
        $labels[] = $row['periodo'];
        $valores[] = (float) $row['total_vendas'];
        $total_pedidos += $row['total_pedidos'];
        $total_vendas += $row['total_vendas'];
        $total_itens += $row['total_itens'];
    }

    echo json_encode([
        'labels' => $labels,
        'valores' => $valores,
        'total_pedidos' => $total_pedidos,
        'total_vendas' => number_format($total_vendas, 2, '.', ''),
        'total_itens' => (int) $total_itens
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Estatísticas de Compras</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
        <script src="logout_auto.js"></script>
    <style>
        /* Estilo básico para dashboard */
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .filtros {
            margin-bottom: 20px;
        }
        .cards {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: #f3f3f3;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            flex: 1;
            text-align: center;
        }
        .conteudo {
          margin-left: 230px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      }

      

 .sidebar {
          position: fixed;
          left: 0;
          top: 0;
          width: 190px;
          height: 100%;
          background:  #0056b3;
          padding: 20px;
          box-shadow: 2px 0 5px rgba(0,0,0,0.05);
          display:flex;
            color: white;
          flex-direction:column;
          gap:10px;
      }

   
.sidebar a:hover       {
   cursor: pointer; 
   
  transform:scale(1.1);
background-color:  #024185ff;   

}

.sidebar a { text-decoration:none; 
    color:#fff;
margin-bottom:10px;}




.pdf {
  padding: 10px;

  background-color: #00ff88ff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.pdf a { text-decoration:none; color:#fff;
      }
.pdf:hover {
  background-color: #06926fff;
   cursor: pointer; 
   
  transform:scale(1.1);
}
input, select {
           
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #aaa;
        }

.img {
  padding: 10px;

  background-color: #f1bf1bff;
  color: white;
 
  border: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}
.img a { text-decoration:none; color:#fff;    }
.img:hover {
  background-color: #cc9d01ff;
   cursor: pointer; 
   
  transform:scale(1.1);
}


    </style>
</head>
<body>
  <div class="cabecalho">
        <?php include "usuario_info.php"; ?>
    </div>

<sidebar class="sidebar">

        <h2>Menu Admin</h2>

        <a href="dashboard.php"> Voltar ao Menu Principal</a>
                
        <a href="logout.php">Sair</a>
          
    </sidebar>

<div class="conteudo">
<h2>Estatísticas de Compras</h2>

<div class="filtros">
    <label for="periodo">Visualizar por:</label>
    <select id="periodo">
        <option value="diario">Dia</option>
        <option value="semanal">Semana</option>
        <option value="mensal" selected>Mês</option>
        <option value="anual">Ano</option>
    </select>
</div>


<div class="cards">
    <div class="card">
        <h3>Total de Pedidos</h3>
        <p id="total_pedidos">0</p>
    </div>
    <div class="card">
        <h3>Total de Vendas</h3>
        <p id="total_vendas">0</p>
    </div>
    <div class="card">
        <h3>Total de Itens</h3>
        <p id="total_itens">0</p>
    </div>
</div>

<canvas id="grafico" height="80"></canvas>

<div style="margin-top: 20px;">
    <button class="pdf" onclick="exportarImagem()"> Exportar como Imagem</button>
    <button class="img" onclick="exportarPDF()"> Exportar como PDF</button>

    <label style="margin-left: 20px;">
        Tipo de gráfico:
        <select id="tipoGrafico">
            <option value="line">Linha</option>
            <option value="bar">Barra</option>
        </select>
    </label>
</div>
    </div>

<script>
const ctx = document.getElementById('grafico').getContext('2d');
let chart;

function carregarEstatisticas(periodo = 'mensal') {
    const tipo = document.getElementById('tipoGrafico').value;

    fetch(`estatisticas.php?ajax=1&periodo=${periodo}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('total_pedidos').textContent = data.total_pedidos;
            document.getElementById('total_vendas').textContent = 'MZN ' + data.total_vendas;
            document.getElementById('total_itens').textContent = data.total_itens;

            if (chart) chart.destroy();

            chart = new Chart(ctx, {
                type: tipo,
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Total de Vendas',
                        data: data.valores,
                        borderColor: '#007bff',
                        backgroundColor: tipo === 'bar' ? 'rgba(0,123,255,0.6)' : 'rgba(0,123,255,0.2)',
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
}

document.getElementById('periodo').addEventListener('change', function() {
    carregarEstatisticas(this.value);
});

document.getElementById('tipoGrafico').addEventListener('change', function() {
    carregarEstatisticas(document.getElementById('periodo').value);
});

function exportarImagem() {
    const imagemURL = chart.toBase64Image();
    const link = document.createElement('a');
    link.href = imagemURL;
    link.download = 'grafico.png';
    link.click();
}

async function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const imagem = chart.toBase64Image();
    doc.addImage(imagem, 'PNG', 10, 10, 180, 100);
    doc.save('grafico.pdf');
}

carregarEstatisticas();
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

</body>
</html>

