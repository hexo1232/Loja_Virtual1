schema  Loja:
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- ESTRUTURAS BÁSICAS (Sem dados, apenas estrutura)
-- --------------------------------------------------------

CREATE TABLE `avaliacoes_produto` (
  `id_avaliacao` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `id_usuário` int(11) DEFAULT NULL,
  `id_produto` int(11) DEFAULT NULL,
  `nota` int(11) DEFAULT NULL CHECK (`nota` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `data_avaliacao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `carrinho` (
  `id_carrinho` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `id_usuário` int(11) DEFAULT NULL,
  `data_criacao` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome_categoria` varchar(100) NOT NULL,
  `descricao_categoria` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `categoria_marca` (
  `id_categoria` int(11) NOT NULL,
  `id_marca` int(11) NOT NULL,
  PRIMARY KEY (`id_categoria`,`id_marca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `fornecedor` (
  `id_fornecedor` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome_fornecedor` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `idcidade` int(11) DEFAULT NULL,
  `idprovíncia` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `item_carrinho` (
  `id_item_carrinho` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `id_carrinho` int(11) DEFAULT NULL,
  `id_produto` int(11) DEFAULT NULL,
  `quantidade` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `uuid` varchar(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `item_pedido` (
  `id_item_pedido` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `id_pedido` int(11) DEFAULT NULL,
  `id_produto` int(11) DEFAULT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `logs` (
  `id_log` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `id_usuário` int(11) DEFAULT NULL,
  `data_hora` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `marca` (
  `id_marca` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome_marca` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pagamento` (
  `id_pagamento` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `status_pagamento` varchar(50) DEFAULT NULL,
  `data_pagamento` datetime DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
transaction_id VARCHAR(100) DEFAULT NULL;
  `id_pedido` int(11) DEFAULT NULL,
  `idtipo_pagamento` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `data_pedido` datetime DEFAULT NULL,
  `status_pedido` varchar(50) DEFAULT 'pendente',
  `valor_total` decimal(10,2) DEFAULT NULL,
  `telefone` int(200) DEFAULT NULL,
  `email` varchar(250) NOT NULL,
  `idprovíncia` int(11) DEFAULT NULL,
  `id_usuário` int(11) DEFAULT NULL,
  `idcidade` int(11) DEFAULT NULL,
  `idtipo_pagamento` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `produto` (
  `id_produto` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome_produto` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `quantidade_estoque` int(11) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_marca` int(11) DEFAULT NULL,
  `id_fornecedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `produto_imagem` (
  `id_imagem` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `id_produto` int(11) DEFAULT NULL,
  `caminho_imagem` varchar(255) DEFAULT NULL,
  `legenda` varchar(100) DEFAULT NULL,
  `imagem_principal` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tipo_pagamento` (
  `idtipo_pagamento` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `tipo_pagamento` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `upload` (
  `idupload` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL,
  `id_usuário` int(11) NOT NULL,
  `data_upload` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- TABELAS COM DADOS PRESERVADOS (Usuário e Contexto)
-- --------------------------------------------------------

-- Províncias
CREATE TABLE `provincia` (
  `idprovíncia` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome_província` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `provincia` (`idprovíncia`, `nome_província`) VALUES
(1, 'Maputo'), (2, 'Gaza'), (3, 'Inhambane'), (4, 'Sofala'), (5, 'Manica'),
(6, 'Tete'), (7, 'Zambézia'), (8, 'Nampula'), (9, 'Cabo Delgado'), (10, 'Niassa');

-- Cidades
CREATE TABLE `cidade` (
  `idcidade` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome_cidade` varchar(250) DEFAULT NULL,
  `idprovíncia` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cidade` (`idcidade`, `nome_cidade`, `idprovíncia`) VALUES
(1, 'Maputo', 1), (2, 'Xai-xai', 2), (3, 'Inhambane', 3), (4, 'Beira', 4),
(5, 'Chimoio', 5), (6, 'Tete', 6), (7, 'Quelimane', 7), (8, 'Nampula', 8),
(9, 'Pemba', 9), (10, 'Lichinga', 10);

-- Perfis
CREATE TABLE `perfil` (
  `idperfil` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome_perfil` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `perfil` (`idperfil`, `nome_perfil`) VALUES
(1, 'Administrador'), (2, 'Funcionário'), (3, 'Cliente');

-- Usuários
CREATE TABLE `usuario` (
  `id_usuário` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `apelido` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `idprovíncia` int(11) DEFAULT NULL,
  `idcidade` int(11) DEFAULT NULL,
  `idperfil` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `usuario` (`id_usuário`, `nome`, `apelido`, `email`, `senha_hash`, `telefone`, `idprovíncia`, `idcidade`, `idperfil`) VALUES
(1, 'Matias', 'Matavel', 'matiasmatavel1232@gmail.com', '$2y$10$4tMK3F2uL80D6BfIHjxWOeDgjLeA9TnZ9nF/pKKrTZDNv.rRWNsdy', '876821594', 2, 2, 1),
(3, 'Ataíde', 'Mulane', 'ataidemulane@gmail.com', '$2y$10$/mxiBsuAHdO/UW6CRp54I.gcCbaDk/gkNYXYIiPoonbBfGncOKl0q', '845630278', 7, 7, 3),
(4, 'Mauro', 'Machine', 'mauromachine@gmail.com', '$2y$10$3LHDy.mZoi/ellfcagRiQuZ85VOioCwlc4CSMXvvjhWV2BmE7ad/O', '833938581', 6, 6, 3),
(5, 'Jorge', 'Marcos', 'jorgemarcos@gmail.com', '$2y$10$3vWs9A.0q26JSvoNLuwl7eH4Gqgh3NxbzQxrJmCfxuuUnI6ybf6f6', '866821594', 3, 3, 3),
(6, 'Jorge', 'Milton', 'jorgemilton@gmail.com', '$2y$10$Q/TQz1XEROi7szHd879BF.gY7kA4qaNp6qZf8VTknaDnNBceVDa1O', '824630261', 1, 1, 3);

-- --------------------------------------------------------
-- VIEWS (Sem DEFINER para compatibilidade Cloud)
-- --------------------------------------------------------

CREATE VIEW `vw_fornecedores` AS 
SELECT `f`.`id_fornecedor`, `f`.`nome_fornecedor`, `f`.`email`, `f`.`telefone`, `p`.`nome_província` AS `Provincia`, `c`.`nome_cidade` AS `Cidade` 
FROM ((`fornecedor` `f` JOIN `provincia` `p` ON(`f`.`idprovíncia` = `p`.`idprovíncia`)) JOIN `cidade` `c` ON(`f`.`idcidade` = `c`.`idcidade`));

CREATE VIEW `vw_usuarios_com_detalhes` AS 
SELECT `u`.`id_usuário`, `u`.`nome`, `u`.`apelido`, `u`.`telefone`, `u`.`email`, `u`.`idprovíncia`, `p`.`nome_província` AS `provincia_nome`, `u`.`idcidade`, `c`.`nome_cidade` AS `cidade_nome`, `u`.`idperfil`, `pf`.`nome_perfil` AS `perfil_nome` 
FROM (((`usuario` `u` JOIN `provincia` `p` ON(`u`.`idprovíncia` = `p`.`idprovíncia`)) JOIN `cidade` `c` ON(`u`.`idcidade` = `c`.`idcidade`)) JOIN `perfil` `pf` ON(`u`.`idperfil` = `pf`.`idperfil`));

-- --------------------------------------------------------
-- TRIGGERS
-- --------------------------------------------------------
DELIMITER $$
CREATE TRIGGER `before_insert_item_carrinho` BEFORE INSERT ON `item_carrinho` FOR EACH ROW BEGIN
  IF NEW.uuid IS NULL OR NEW.uuid = '' THEN
    SET NEW.uuid = UUID();
  END IF;
END$$
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;