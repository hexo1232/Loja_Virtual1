<?php
include "conexao.php";
require_once "require_login.php";
include "usuario_info.php";
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/cliente.css">
    <script src="js/dashboard.js" defer></script>
            <script src="logout_auto.js"></script>
    
    <style>


.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 190px;
  height: 100vh;
  background-color:  #0056b3;
  color: white;
  padding: 20px;
           box-shadow: 2px 0 5px rgba(0,0,0,0.05);
  transition: width 0.3s;
  display:flex;
          flex-direction:column;
          gap:10px;
              animation: fadeIn 2s ease-in;
}
.sidebar a { text-decoration:none; 
    color:#fff;
margin-bottom:10px;}
.sidebar a:hover       {
   cursor: pointer; 
background-color:  #024185ff;  
  transform:scale(1.1); 

}


.card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
margin-left:230px;
width:80%;
}

.card h3 {
  margin-bottom: 10px;
}

    </style>
</head>
<body>
    <div class="dashboard-container">

        <!-- Sidebar -->
        <sidebar class="sidebar">
            <h2>Menu Admin</h2>
     
                <a href="usuarios.php">Gerenciar Usuários</a>
                <a href="gerenciarprodutos.php"> Gerenciar Produtos</a>
                <a href="fornecedores.php">Gerenciar Fornecedores</a>
                <a href="admin_pedidos.php"> Ver Pedidos</a>
              
                <a href="categoria.php"> Categorias</a>
                <a href="marcas.php">Marcas</a>
               
                  <a href="ver_logs.php">Histórico de Logs</a>
                            <a href="estatisticas.php"> Estatísticas de Compras</a>
            <a href="logout.php"> Sair</a>
        </sidebar>

    
        <!-- Área Principal -->
        <div class="main">
            <div class="card">
                <h3>Bem-vindo à área administrativa</h3>
                <p>Use o menu à esquerda para navegar nas funcionalidades.</p>
            </div>
        </div>

    </div>
</body>
</html>
