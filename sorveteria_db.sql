-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 22/06/2026 às 05:14
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sorveteria_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `despesas`
--

CREATE TABLE `despesas` (
  `despesa_id` int(11) NOT NULL,
  `data_despesa` date NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `categoria` varchar(100) NOT NULL DEFAULT 'Geral',
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `usuario_id` int(11) DEFAULT NULL,
  `data_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque`
--

CREATE TABLE `estoque` (
  `estoque_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade_disponivel` decimal(12,3) NOT NULL DEFAULT 0.000,
  `validade` date DEFAULT NULL,
  `custo_medio` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `data_ultima_atualizacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `estoque`
--

INSERT INTO `estoque` (`estoque_id`, `produto_id`, `quantidade_disponivel`, `validade`, `custo_medio`, `fornecedor_id`, `data_ultima_atualizacao`) VALUES
(1, 1, 48.583, '2026-12-20', 28.00, 1, '2026-04-20 12:37:05'),
(2, 2, 32.190, '2026-12-20', 28.00, 1, '2026-04-27 22:28:37'),
(3, 3, 22.000, '2026-11-15', 28.00, 1, '2026-04-19 16:00:38'),
(4, 4, 17.583, '2026-12-10', 28.00, 2, '2026-04-20 12:37:05'),
(5, 5, 14.000, '2026-11-30', 28.00, 2, '2026-04-20 11:37:41'),
(6, 6, 40.000, '2026-12-20', 28.00, 1, '2026-04-27 22:15:28'),
(7, 7, 35.000, '2026-12-05', 28.00, 2, '2026-04-27 22:14:00'),
(8, 8, 12.150, '2026-11-10', 28.00, 1, '2026-04-19 16:04:13'),
(9, 9, 27.333, '2026-12-15', 28.00, 1, '2026-04-27 22:15:28'),
(10, 10, 12.660, '2026-10-25', 28.00, 2, '2026-04-27 22:30:09'),
(11, 11, 24.950, '2026-12-20', 28.00, 1, '2026-04-27 22:17:39'),
(12, 12, 37.699, '2026-12-18', 28.00, 2, '2026-04-27 22:18:41'),
(13, 13, 13.000, '2026-11-05', 28.00, 1, '2026-04-20 11:40:41'),
(14, 14, 29.750, '2026-12-12', 28.00, 2, '2026-04-19 16:00:38'),
(15, 15, 10.500, '2026-10-30', 28.00, 1, '2026-04-20 11:40:41'),
(16, 16, 4.800, '2026-09-20', 32.50, 3, '2026-04-27 22:22:25'),
(17, 17, 4.499, '2026-09-20', 32.50, 3, '2026-04-27 22:25:44'),
(18, 18, 8.500, '2027-06-01', 0.00, 4, '2026-04-19 16:00:38'),
(19, 19, 6.200, '2027-06-01', 0.00, 5, '2026-04-19 16:00:38'),
(20, 20, 15.000, '2027-12-31', 0.00, 4, '2026-04-19 16:00:38'),
(21, 21, 12.000, '2027-12-31', 0.00, 5, '2026-04-19 16:00:38'),
(22, 22, 9.000, '2027-12-31', 0.00, 4, '2026-04-19 16:00:38'),
(23, 23, 18.500, '2027-12-31', 0.00, 5, '2026-04-19 16:00:38'),
(24, 24, 22.000, '2027-12-31', 0.00, 4, '2026-04-19 16:00:38'),
(25, 25, 14.750, '2027-12-31', 0.00, 5, '2026-04-19 16:00:38'),
(26, 26, 10.000, '2027-06-01', 0.00, 4, '2026-04-19 16:00:38'),
(27, 27, 25.000, '2027-12-31', 0.00, 5, '2026-04-19 16:00:38'),
(28, 28, 7.500, '2027-06-01', 0.00, 4, '2026-04-19 16:00:38'),
(29, 29, 11.000, '2027-06-01', 0.00, 5, '2026-04-19 16:00:38'),
(30, 30, 5.250, '2027-06-01', 0.00, 4, '2026-04-19 16:00:38'),
(31, 31, 1200.000, '2027-12-31', 0.15, 7, '2026-04-19 16:00:38'),
(32, 32, 850.000, '2027-12-31', 0.20, 8, '2026-04-19 16:00:38'),
(33, 33, 620.000, '2027-12-31', 0.30, 9, '2026-04-19 16:00:38'),
(34, 34, 180.000, '2027-12-31', 0.80, 7, '2026-04-19 16:00:38'),
(35, 35, 2500.000, '2028-01-01', 0.05, 8, '2026-04-19 16:00:38'),
(36, 36, 450.000, '2027-12-31', 2.50, 9, '2026-04-19 16:00:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `feedbacks_clientes`
--

CREATE TABLE `feedbacks_clientes` (
  `feedback_id` int(11) NOT NULL,
  `data_registro` datetime DEFAULT current_timestamp(),
  `descricao` text NOT NULL,
  `tipo` enum('Duvida','Reclamacao','Sugestao','Elogio') DEFAULT NULL,
  `resolvido` tinyint(1) DEFAULT 0,
  `observacao_resolucao` text DEFAULT NULL,
  `nota` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `feedbacks_clientes`
--

INSERT INTO `feedbacks_clientes` (`feedback_id`, `data_registro`, `descricao`, `tipo`, `resolvido`, `observacao_resolucao`, `nota`) VALUES
(1, '2026-04-19 10:10:00', 'Atendimento excelente e muito rápido. Adorei a experiência!', 'Elogio', 1, '💖✨ Ficamos muito felizes com seu feedback! Volte sempre 🍨', 5),
(2, '2026-04-19 10:20:00', 'Sorvete maravilhoso, textura perfeita!', 'Elogio', 1, '🍦✨ Que bom saber disso! Nosso gelato é feito com muito carinho 💖', 5),
(3, '2026-04-19 10:30:00', 'Ambiente muito bonito e organizado.', 'Elogio', 1, '🌷💖 Preparamos tudo com muito cuidado para você!', 5),
(4, '2026-04-19 10:40:00', 'Equipe super educada e atenciosa.', 'Elogio', 1, '💖🤎 Nosso atendimento é feito com carinho! Obrigado!', 5),
(5, '2026-04-19 10:50:00', 'Melhor sorveteria que já fui!', 'Elogio', 1, '🍨✨ Isso nos motiva muito! Obrigado pelo carinho 💖', 5),
(6, '2026-04-19 11:00:00', 'Muito bom, só achei um pouco cheio no horário.', 'Elogio', 1, '💖✨ Agradecemos! Vamos melhorar o fluxo para te atender melhor 🍨', 4),
(7, '2026-04-19 11:10:00', 'Qualidade ótima, mas poderia ter mais sabores.', 'Sugestao', 1, '🥄✨ Estamos degustando sua ideia! Novidades podem vir por aí 🍧', 4),
(8, '2026-04-19 11:20:00', 'Gostei bastante, voltarei mais vezes.', 'Elogio', 1, '💖🍦 Te esperamos para mais momentos deliciosos!', 4),
(9, '2026-04-19 11:30:00', 'Preço justo pela qualidade oferecida.', 'Elogio', 1, '🤎✨ Esse é nosso objetivo! Obrigado pelo reconhecimento 💖', 4),
(10, '2026-04-19 11:40:00', 'Vocês fazem entrega em toda a cidade?', 'Duvida', 1, '💙❄️ Atendemos grande parte da região! Consulte sua área 📍', 5),
(11, '2026-04-19 11:50:00', 'Tem opção sem lactose?', 'Duvida', 1, '💙🥄 Temos sim! Confira nossas opções especiais no cardápio 🍨', 5),
(12, '2026-04-19 12:00:00', 'Seria legal ter mais opções de toppings.', 'Sugestao', 1, '🥄✨ Ideia incrível! Vamos analisar com carinho 💖', 4),
(13, '2026-04-19 12:10:00', 'Poderia ter um programa de fidelidade.', 'Sugestao', 0, NULL, 4),
(14, '2026-04-19 12:20:00', 'Demorou um pouco mais que o esperado.', 'Reclamacao', 1, '💙❄️ Pedimos desculpas! Já estamos ajustando nosso tempo de atendimento 🤝', 3),
(15, '2026-04-19 12:30:00', 'Veio um pouco derretido, mas estava bom.', 'Reclamacao', 1, '💙❄️ Vamos reforçar o cuidado no transporte! Obrigado pelo aviso 🤝', 3),
(16, '2026-04-19 12:40:00', 'Experiência incrível do começo ao fim.', 'Elogio', 1, '✨🍨 Muito obrigado! Ficamos felizes demais 💖', 5),
(17, '2026-04-19 12:50:00', 'Tudo perfeito, atendimento e produto.', 'Elogio', 1, '💖🍦 Que bom saber disso! Volte sempre!', 5),
(18, '2026-04-19 13:00:00', 'Simplesmente sensacional!', 'Elogio', 1, '✨🍧 Seu feedback deixou nosso dia melhor 💖', 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `fornecedor_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `contato` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tipo_produto` varchar(100) DEFAULT NULL,
  `prazo_entrega_dias` int(11) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `fornecedores`
--

INSERT INTO `fornecedores` (`fornecedor_id`, `nome`, `contato`, `telefone`, `email`, `tipo_produto`, `prazo_entrega_dias`, `endereco`, `data_cadastro`) VALUES
(1, 'Eskimó Sorvetes - Distribuidora Chapecó', 'Atendimento', '(49) 3323-0188', 'chapeco@eskimo.com.br', 'Sorvete', 1, 'R. Mal. Deodoro da Fonseca, 412 - Chapecó - SC', '2026-04-19 16:47:43'),
(2, 'Sorvetes Jojo', 'Comercial', '(49) 3322-0348', 'contato@sorvetesjojo.com.br', 'Sorvete', 2, 'R. Condá, 541 - Chapecó - SC', '2026-04-19 16:47:43'),
(3, 'Duas Rodas (Selecta/Specialitá) - Regional', 'Representante', '0800-707-9500', 'atendimento@duasrodas.com', 'Insumos', 5, 'Distribuição Regional Santa Catarina', '2026-04-19 16:47:43'),
(4, 'Celeiro Supermercados (Atacarejo)', 'Vendas Corp', '(49) 3361-5000', 'comercial@celeiro.com.br', 'Adicionais', 1, 'Av. Getúlio Vargas, 1730 - Chapecó - SC', '2026-04-19 16:47:43'),
(5, 'Gatamel Distribuidora de Doces', 'Vendas', '(49) 3323-3331', 'vendas@gatamel.com.br', 'Adicionais', 2, 'R. Quintino Bocaiúva, 632 - Chapecó - SC', '2026-04-19 16:47:43'),
(6, 'Brasitália Máquinas e Insumos', 'Comercial', '(49) 3331-1050', 'contato@brasitalia.com.br', 'Insumos', 3, 'R. Uruguai, 155 - Chapecó - SC', '2026-04-19 16:47:43'),
(7, 'Copapel Higiene e Limpeza', 'Filial Chapecó', '(49) 3319-9700', 'chapeco@copapel.com.br', 'Embalagem', 1, 'Acesso Plínio Arlindo de Nes, 2380 - Chapecó - SC', '2026-04-19 16:47:43'),
(8, 'Sul Embalagens', 'Vendas', '(49) 3322-2434', 'vendas@sulembalagens.com.br', 'Embalagem', 2, 'Av. Fernando Machado, 2185 - Chapecó - SC', '2026-04-19 16:47:43'),
(9, 'Oeste Embalagens', 'Atendimento', '(49) 3322-0056', 'contato@oesteembalagens.com.br', 'Embalagem', 2, 'R. Mal. Bormann, 128 - Chapecó - SC', '2026-04-19 16:47:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_venda`
--

CREATE TABLE `itens_venda` (
  `item_venda_id` int(11) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `sabor` varchar(100) DEFAULT NULL,
  `quantidade` decimal(8,3) NOT NULL,
  `adicionais` text DEFAULT NULL,
  `coberturas` text DEFAULT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `valor_total_item` decimal(10,2) GENERATED ALWAYS AS (`quantidade` * `valor_unitario`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `itens_venda`
--

INSERT INTO `itens_venda` (`item_venda_id`, `venda_id`, `produto_id`, `sabor`, `quantidade`, `adicionais`, `coberturas`, `valor_unitario`) VALUES
(1, 1, 16, 'Açaí com Leite Ninho', 0.250, NULL, NULL, 70.00),
(2, 1, 1, 'Chocolate', 0.250, NULL, NULL, 70.00),
(3, 2, 17, 'Açaí Tradicional', 1.000, NULL, NULL, 70.00),
(4, 3, 10, 'Abacaxi', 0.250, NULL, NULL, 70.00),
(5, 3, 16, 'Açaí com Leite Ninho', 0.250, NULL, NULL, 70.00),
(6, 4, 16, 'Açaí com Leite Ninho', 1.000, NULL, NULL, 70.00),
(7, 4, 17, 'Açaí Tradicional', 1.000, NULL, NULL, 70.00),
(8, 5, 6, 'Brigadeiro', 0.500, NULL, NULL, 70.00),
(9, 5, 12, 'Doce de Leite', 0.500, NULL, NULL, 70.00),
(10, 6, 4, 'Brownie', 0.500, NULL, NULL, 70.00),
(11, 7, 10, 'Abacaxi', 0.020, NULL, NULL, 70.00),
(12, 8, 10, 'Abacaxi', 0.020, NULL, NULL, 70.00),
(13, 9, 10, 'Abacaxi', 0.100, NULL, NULL, 70.00),
(14, 9, 16, 'Açaí com Leite Ninho', 0.100, NULL, NULL, 70.00),
(15, 10, 5, 'Red Velvet', 1.000, NULL, NULL, 70.00),
(16, 11, 13, 'Floresta Negra', 1.000, NULL, NULL, 70.00),
(17, 11, 15, 'Uva', 1.000, NULL, NULL, 70.00),
(18, 11, 21, NULL, 0.000, 'Brigadeiro (Topping)', NULL, 0.00),
(19, 12, 10, 'Abacaxi', 2.000, NULL, NULL, 70.00),
(20, 13, 16, 'Açaí com Leite Ninho', 1.000, NULL, NULL, 70.00),
(21, 13, 20, NULL, 0.000, 'Balas de Gelatina', NULL, 0.00),
(22, 13, 21, NULL, 0.000, 'Brigadeiro (Topping)', NULL, 0.00),
(23, 14, 10, 'Abacaxi', 2.000, NULL, NULL, 70.00),
(24, 15, 4, 'Brownie', 0.167, NULL, NULL, 70.00),
(25, 15, 9, 'Caramelo', 0.167, NULL, NULL, 70.00),
(26, 15, 1, 'Chocolate', 0.167, NULL, NULL, 70.00),
(27, 17, 10, 'Abacaxi', 1.000, NULL, NULL, 70.00),
(28, 18, 10, 'Abacaxi', 0.200, NULL, NULL, 70.00),
(29, 19, 10, 'Abacaxi', 1.000, NULL, NULL, 70.00),
(30, 20, 16, 'Açaí com Leite Ninho', 0.500, NULL, NULL, 70.00),
(31, 21, 16, 'Açaí com Leite Ninho', 0.250, NULL, NULL, 70.00),
(32, 21, 2, 'Chocolate Branco', 0.250, NULL, NULL, 70.00),
(33, 22, 17, 'Açaí Tradicional', 1.000, NULL, NULL, 70.00),
(34, 23, 16, 'Açaí com Leite Ninho', 0.500, NULL, NULL, 70.00),
(35, 23, 7, 'Creme', 0.500, NULL, NULL, 70.00),
(36, 24, 6, 'Brigadeiro', 0.500, NULL, NULL, 70.00),
(37, 24, 9, 'Caramelo', 0.500, NULL, NULL, 70.00),
(38, 25, 16, 'Açaí com Leite Ninho', 0.050, NULL, NULL, 70.00),
(39, 25, 12, 'Doce de Leite', 0.050, NULL, NULL, 70.00),
(40, 26, 16, 'Açaí com Leite Ninho', 0.050, NULL, NULL, 70.00),
(41, 26, 11, 'Coco', 0.050, NULL, NULL, 70.00),
(42, 27, 17, 'Açaí Tradicional', 0.001, NULL, NULL, 70.00),
(43, 27, 12, 'Doce de Leite', 0.001, NULL, NULL, 70.00),
(44, 28, 17, 'Açaí Tradicional', 1.000, NULL, NULL, 70.00),
(45, 29, 16, 'Açaí com Leite Ninho', 1.000, NULL, NULL, 70.00),
(46, 30, 17, 'Açaí Tradicional', 0.300, NULL, NULL, 70.00),
(47, 31, 2, 'Chocolate Branco', 0.060, NULL, NULL, 70.00),
(48, 32, 10, 'Abacaxi', 0.500, NULL, NULL, 70.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_auditoria`
--

CREATE TABLE `logs_auditoria` (
  `log_id` bigint(20) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(50) NOT NULL,
  `tabela_afetada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `data_hora` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `logs_auditoria`
--

INSERT INTO `logs_auditoria` (`log_id`, `usuario_id`, `acao`, `tabela_afetada`, `registro_id`, `descricao`, `data_hora`) VALUES
(1, 1, 'INSERT', 'vendas', 1, 'Venda #1 - R$ 28.35 - Dinheiro', '2026-04-19 17:48:11'),
(2, 1, 'INSERT', 'vendas', 2, 'Venda #2 - R$ 63.00 - Dinheiro', '2026-04-19 18:01:25'),
(3, 1, 'INSERT', 'vendas', 3, 'Venda #3 - R$ 31.50 - Pix', '2026-04-19 18:10:49'),
(4, 1, 'INSERT', 'vendas', 4, 'Venda #4 - R$ 140.00 - Dinheiro', '2026-04-20 11:14:57'),
(5, 1, 'Saida', 'movimentacoes_estoque', 1, 'Saida de 1.000 kg do produto 16', '2026-04-20 11:14:57'),
(6, 1, 'Saida', 'movimentacoes_estoque', 2, 'Saida de 1.000 kg do produto 17', '2026-04-20 11:14:57'),
(7, 1, 'INSERT', 'vendas', 5, 'Venda #5 - R$ 70.00 - Dinheiro', '2026-04-20 11:22:44'),
(8, 1, 'Saida', 'movimentacoes_estoque', 3, 'Saida de 0.500 kg do produto 6', '2026-04-20 11:22:44'),
(9, 1, 'Saida', 'movimentacoes_estoque', 4, 'Saida de 0.500 kg do produto 12', '2026-04-20 11:22:44'),
(10, 1, 'INSERT', 'vendas', 5, 'Venda #5 - R$ 70,00 - Dinheiro', '2026-04-20 11:22:44'),
(11, 1, 'INSERT', 'vendas', 6, 'Venda #6 - R$ 31.50 - Dinheiro', '2026-04-20 11:24:45'),
(12, 1, 'Saida', 'movimentacoes_estoque', 5, 'Saida de 0.500 kg do produto 4', '2026-04-20 11:24:45'),
(13, 1, 'INSERT', 'vendas', 6, 'Venda #6 - R$ 31,50 - Dinheiro', '2026-04-20 11:24:45'),
(14, 1, 'INSERT', 'vendas', 7, 'Venda #7 - R$ 1.40 - Dinheiro', '2026-04-20 11:28:20'),
(15, 1, 'Saida', 'movimentacoes_estoque', 6, 'Saida de 0.020 kg do produto 10', '2026-04-20 11:28:20'),
(16, 1, 'INSERT', 'vendas', 7, 'Venda #7 - R$ 1,40 - Dinheiro', '2026-04-20 11:28:20'),
(17, 1, 'INSERT', 'vendas', 8, 'Venda #8 - R$ 1.26 - Dinheiro', '2026-04-20 11:29:42'),
(18, 1, 'Saida', 'movimentacoes_estoque', 7, 'Saida de 0.020 kg do produto 10', '2026-04-20 11:29:42'),
(19, 1, 'INSERT', 'vendas', 8, 'Venda #8 - R$ 1,26 - Dinheiro', '2026-04-20 11:29:42'),
(20, 1, 'INSERT', 'vendas', 9, 'Venda #9 - R$ 14.00 - Dinheiro', '2026-04-20 11:34:10'),
(21, 1, 'Saida', 'movimentacoes_estoque', 8, 'Saida de 0.100 kg do produto 10', '2026-04-20 11:34:10'),
(22, 1, 'Saida', 'movimentacoes_estoque', 9, 'Saida de 0.100 kg do produto 16', '2026-04-20 11:34:10'),
(23, 1, 'INSERT', 'vendas', 9, 'Venda #9 - R$ 14,00 - Dinheiro', '2026-04-20 11:34:10'),
(24, 1, 'INSERT', 'vendas', 10, 'Venda #10 - R$ 55.00 - Dinheiro', '2026-04-20 11:37:41'),
(25, 1, 'Saida', 'movimentacoes_estoque', 10, 'Saida de 1.000 kg do produto 5', '2026-04-20 11:37:41'),
(26, 1, 'INSERT', 'vendas', 10, 'Venda #10 - R$ 55,00 - Dinheiro', '2026-04-20 11:37:41'),
(27, 1, 'INSERT', 'vendas', 11, 'Venda #11 - R$ 126.00 - Dinheiro', '2026-04-20 11:40:41'),
(28, 1, 'Saida', 'movimentacoes_estoque', 11, 'Saida de 1.000 kg do produto 13', '2026-04-20 11:40:41'),
(29, 1, 'Saida', 'movimentacoes_estoque', 12, 'Saida de 1.000 kg do produto 15', '2026-04-20 11:40:41'),
(30, 1, 'INSERT', 'vendas', 11, 'Venda #11 - R$ 126,00 - Dinheiro', '2026-04-20 11:40:41'),
(31, 1, 'INSERT', 'vendas', 12, 'Venda #12 - R$ 140.00 - Dinheiro', '2026-04-20 11:45:34'),
(32, 1, 'Saida', 'movimentacoes_estoque', 13, 'Saida de 2.000 kg do produto 10', '2026-04-20 11:45:34'),
(33, 1, 'INSERT', 'vendas', 12, 'Venda #12 - R$ 140,00 - Dinheiro', '2026-04-20 11:45:34'),
(34, 1, 'INSERT', 'vendas', 13, 'Venda #13 - R$ 70.00 - Dinheiro', '2026-04-20 12:26:50'),
(35, 1, 'Saida', 'movimentacoes_estoque', 14, 'Saida de 1.000 kg do produto 16', '2026-04-20 12:26:50'),
(36, 1, 'INSERT', 'vendas', 13, 'Venda #13 - R$ 70,00 - Dinheiro', '2026-04-20 12:26:50'),
(37, 1, 'INSERT', 'vendas', 14, 'Venda #14 - R$ 140.00 - Dinheiro', '2026-04-20 12:32:54'),
(38, 1, 'Saida', 'movimentacoes_estoque', 15, 'Saida de 2.000 kg do produto 10', '2026-04-20 12:32:54'),
(39, 1, 'INSERT', 'vendas', 14, 'Venda #14 - R$ 140,00 - Dinheiro', '2026-04-20 12:32:54'),
(40, 1, 'INSERT', 'vendas', 15, 'Venda #15 - R$ 31.50 - Dinheiro', '2026-04-20 12:37:05'),
(41, 1, 'Saida', 'movimentacoes_estoque', 16, 'Saida de 0.167 kg do produto 4', '2026-04-20 12:37:05'),
(42, 1, 'Saida', 'movimentacoes_estoque', 17, 'Saida de 0.167 kg do produto 9', '2026-04-20 12:37:05'),
(43, 1, 'Saida', 'movimentacoes_estoque', 18, 'Saida de 0.167 kg do produto 1', '2026-04-20 12:37:05'),
(44, 1, 'INSERT', 'vendas', 15, 'Venda #15 - R$ 31,50 - Dinheiro', '2026-04-20 12:37:05'),
(45, 1, 'INSERT', 'vendas', 16, 'Venda #16 - R$ 0.00 - Dinheiro', '2026-04-20 13:06:55'),
(46, 1, 'INSERT', 'vendas', 16, 'Venda #16 - R$ 0,00 - Dinheiro', '2026-04-20 13:06:55'),
(47, 1, 'INSERT', 'vendas', 17, 'Venda #17 - R$ 63.00 - Dinheiro', '2026-04-20 13:23:14'),
(48, 1, 'Saida', 'movimentacoes_estoque', 19, 'Saida de 1.000 kg do produto 10', '2026-04-20 13:23:14'),
(49, 1, 'INSERT', 'vendas', 17, 'Venda #17 - R$ 63,00 - Dinheiro', '2026-04-20 13:23:14'),
(50, 1, 'INSERT', 'vendas', 18, 'Venda #18 - R$ 14.00 - Dinheiro', '2026-04-27 21:38:31'),
(51, 1, 'Saida', 'movimentacoes_estoque', 20, 'Saida de 0.200 kg do produto 10', '2026-04-27 21:38:31'),
(52, 1, 'INSERT', 'vendas', 18, 'Venda #18 - R$ 14,00 - Dinheiro', '2026-04-27 21:38:31'),
(53, 1, 'INSERT', 'vendas', 19, 'Venda #19 - R$ 70.00 - Dinheiro', '2026-04-27 21:41:35'),
(54, 1, 'Saida', 'movimentacoes_estoque', 21, 'Saida de 1.000 kg do produto 10', '2026-04-27 21:41:35'),
(55, 1, 'INSERT', 'vendas', 19, 'Venda #19 - R$ 70,00 - Dinheiro', '2026-04-27 21:41:35'),
(56, 2, 'INSERT', 'vendas', 20, 'Venda #20 - R$ 20.00 - Dinheiro', '2026-04-27 21:44:05'),
(57, 2, 'Saida', 'movimentacoes_estoque', 22, 'Saida de 0.500 kg do produto 16', '2026-04-27 21:44:05'),
(58, 2, 'INSERT', 'vendas', 20, 'Venda #20 - R$ 20,00 - Dinheiro', '2026-04-27 21:44:05'),
(59, 1, 'INSERT', 'vendas', 21, 'Venda #21 - R$ 20.00 - Dinheiro', '2026-04-27 21:49:36'),
(60, 1, 'Saida', 'movimentacoes_estoque', 23, 'Saida de 0.250 kg do produto 16', '2026-04-27 21:49:36'),
(61, 1, 'Saida', 'movimentacoes_estoque', 24, 'Saida de 0.250 kg do produto 2', '2026-04-27 21:49:36'),
(62, 1, 'INSERT', 'vendas', 21, 'Venda #21 - R$ 20,00 - Dinheiro', '2026-04-27 21:49:36'),
(63, 1, 'INSERT', 'vendas', 22, 'Venda #22 - R$ 70.00 - Dinheiro', '2026-04-27 22:04:43'),
(64, 1, 'Saida', 'movimentacoes_estoque', 25, 'Saida de 1.000 kg do produto 17', '2026-04-27 22:04:43'),
(65, 1, 'INSERT', 'vendas', 22, 'Venda #22 - R$ 70,00 - Dinheiro', '2026-04-27 22:04:43'),
(66, 1, 'INSERT', 'vendas', 23, 'Venda #23 - R$ 70.00 - Dinheiro', '2026-04-27 22:14:00'),
(67, 1, 'Saida', 'movimentacoes_estoque', 26, 'Saida de 0.500 kg do produto 16', '2026-04-27 22:14:00'),
(68, 1, 'Saida', 'movimentacoes_estoque', 27, 'Saida de 0.500 kg do produto 7', '2026-04-27 22:14:00'),
(69, 1, 'INSERT', 'vendas', 23, 'Venda #23 - R$ 70,00 - Dinheiro', '2026-04-27 22:14:00'),
(70, 1, 'INSERT', 'vendas', 24, 'Venda #24 - R$ 70.00 - Dinheiro', '2026-04-27 22:15:28'),
(71, 1, 'Saida', 'movimentacoes_estoque', 28, 'Saida de 0.500 kg do produto 6', '2026-04-27 22:15:28'),
(72, 1, 'Saida', 'movimentacoes_estoque', 29, 'Saida de 0.500 kg do produto 9', '2026-04-27 22:15:28'),
(73, 1, 'INSERT', 'vendas', 24, 'Venda #24 - R$ 70,00 - Dinheiro', '2026-04-27 22:15:28'),
(74, 1, 'INSERT', 'vendas', 25, 'Venda #25 - R$ 7.00 - Dinheiro', '2026-04-27 22:17:11'),
(75, 1, 'Saida', 'movimentacoes_estoque', 30, 'Saida de 0.050 kg do produto 16', '2026-04-27 22:17:11'),
(76, 1, 'Saida', 'movimentacoes_estoque', 31, 'Saida de 0.050 kg do produto 12', '2026-04-27 22:17:11'),
(77, 1, 'INSERT', 'vendas', 25, 'Venda #25 - R$ 7,00 - Dinheiro', '2026-04-27 22:17:11'),
(78, 1, 'INSERT', 'vendas', 26, 'Venda #26 - R$ 7.00 - Dinheiro', '2026-04-27 22:17:39'),
(79, 1, 'Saida', 'movimentacoes_estoque', 32, 'Saida de 0.050 kg do produto 16', '2026-04-27 22:17:39'),
(80, 1, 'Saida', 'movimentacoes_estoque', 33, 'Saida de 0.050 kg do produto 11', '2026-04-27 22:17:39'),
(81, 1, 'INSERT', 'vendas', 26, 'Venda #26 - R$ 7,00 - Dinheiro', '2026-04-27 22:17:39'),
(82, 1, 'INSERT', 'vendas', 27, 'Venda #27 - R$ 0.14 - Dinheiro', '2026-04-27 22:18:41'),
(83, 1, 'Saida', 'movimentacoes_estoque', 34, 'Saida de 0.001 kg do produto 17', '2026-04-27 22:18:41'),
(84, 1, 'Saida', 'movimentacoes_estoque', 35, 'Saida de 0.001 kg do produto 12', '2026-04-27 22:18:41'),
(85, 1, 'INSERT', 'vendas', 27, 'Venda #27 - R$ 0,14 - Dinheiro', '2026-04-27 22:18:41'),
(86, 1, 'INSERT', 'vendas', 28, 'Venda #28 - R$ 70.00 - Dinheiro', '2026-04-27 22:19:40'),
(87, 1, 'Saida', 'movimentacoes_estoque', 36, 'Saida de 1.000 kg do produto 17', '2026-04-27 22:19:40'),
(88, 1, 'INSERT', 'vendas', 28, 'Venda #28 - R$ 70,00 - Dinheiro', '2026-04-27 22:19:40'),
(89, 1, 'INSERT', 'vendas', 29, 'Venda #29 - R$ 70.00 - Dinheiro', '2026-04-27 22:22:25'),
(90, 1, 'Saida', 'movimentacoes_estoque', 37, 'Saida de 1.000 kg do produto 16', '2026-04-27 22:22:25'),
(91, 1, 'INSERT', 'vendas', 29, 'Venda #29 - R$ 70,00 - Dinheiro', '2026-04-27 22:22:25'),
(92, 1, 'INSERT', 'vendas', 30, 'Venda #30 - R$ 18.90 - Dinheiro', '2026-04-27 22:25:44'),
(93, 1, 'Saida', 'movimentacoes_estoque', 38, 'Saida de 0.300 kg do produto 17', '2026-04-27 22:25:44'),
(94, 1, 'INSERT', 'vendas', 30, 'Venda #30 - R$ 18,90 - Dinheiro', '2026-04-27 22:25:44'),
(95, 1, 'INSERT', 'vendas', 31, 'Venda #31 - R$ 4.20 - Dinheiro', '2026-04-27 22:28:37'),
(96, 1, 'Saida', 'movimentacoes_estoque', 39, 'Saida de 0.060 kg do produto 2', '2026-04-27 22:28:37'),
(97, 1, 'INSERT', 'vendas', 31, 'Venda #31 - R$ 4,20 - Dinheiro', '2026-04-27 22:28:37'),
(98, 1, 'INSERT', 'vendas', 32, 'Venda #32 - R$ 20.00 - Dinheiro', '2026-04-27 22:30:09'),
(99, 1, 'Saida', 'movimentacoes_estoque', 40, 'Saida de 0.500 kg do produto 10', '2026-04-27 22:30:09'),
(100, 1, 'INSERT', 'vendas', 32, 'Venda #32 - R$ 20,00 - Dinheiro', '2026-04-27 22:30:09');

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_estoque`
--

CREATE TABLE `movimentacoes_estoque` (
  `movimentacao_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `tipo_movimentacao` enum('Entrada','Saida','Ajuste') NOT NULL,
  `quantidade` decimal(12,3) NOT NULL,
  `custo_unitario` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_movimentacao` datetime DEFAULT current_timestamp(),
  `observacao` text DEFAULT NULL,
  `referencia_venda_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `movimentacoes_estoque`
--

INSERT INTO `movimentacoes_estoque` (`movimentacao_id`, `produto_id`, `tipo_movimentacao`, `quantidade`, `custo_unitario`, `fornecedor_id`, `usuario_id`, `data_movimentacao`, `observacao`, `referencia_venda_id`) VALUES
(1, 16, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-20 11:14:57', 'Venda #4 - Açaí com Leite Ninho', 4),
(2, 17, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-20 11:14:57', 'Venda #4 - Açaí Tradicional', 4),
(3, 6, 'Saida', 0.500, 70.00, NULL, 1, '2026-04-20 11:22:44', 'Venda #5 - Brigadeiro', 5),
(4, 12, 'Saida', 0.500, 70.00, NULL, 1, '2026-04-20 11:22:44', 'Venda #5 - Doce de Leite', 5),
(5, 4, 'Saida', 0.500, 70.00, NULL, 1, '2026-04-20 11:24:45', 'Venda #6 - Brownie', 6),
(6, 10, 'Saida', 0.020, 70.00, NULL, 1, '2026-04-20 11:28:20', 'Venda #7 - Abacaxi', 7),
(7, 10, 'Saida', 0.020, 70.00, NULL, 1, '2026-04-20 11:29:42', 'Venda #8 - Abacaxi', 8),
(8, 10, 'Saida', 0.100, 70.00, NULL, 1, '2026-04-20 11:34:10', 'Venda #9 - Abacaxi', 9),
(9, 16, 'Saida', 0.100, 70.00, NULL, 1, '2026-04-20 11:34:10', 'Venda #9 - Açaí com Leite Ninho', 9),
(10, 5, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-20 11:37:41', 'Venda #10 - Red Velvet', 10),
(11, 13, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-20 11:40:41', 'Venda #11 - Floresta Negra', 11),
(12, 15, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-20 11:40:41', 'Venda #11 - Uva', 11),
(13, 10, 'Saida', 2.000, 70.00, NULL, 1, '2026-04-20 11:45:34', 'Venda #12 - Abacaxi', 12),
(14, 16, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-20 12:26:50', 'Venda #13 - Açaí com Leite Ninho', 13),
(15, 10, 'Saida', 2.000, 70.00, NULL, 1, '2026-04-20 12:32:54', 'Venda #14 - Abacaxi', 14),
(16, 4, 'Saida', 0.167, 70.00, NULL, 1, '2026-04-20 12:37:05', 'Venda #15 - Brownie', 15),
(17, 9, 'Saida', 0.167, 70.00, NULL, 1, '2026-04-20 12:37:05', 'Venda #15 - Caramelo', 15),
(18, 1, 'Saida', 0.167, 70.00, NULL, 1, '2026-04-20 12:37:05', 'Venda #15 - Chocolate', 15),
(19, 10, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-20 13:23:14', 'Venda #17 - Abacaxi', 17),
(20, 10, 'Saida', 0.200, 70.00, NULL, 1, '2026-04-27 21:38:31', 'Venda #18 - Abacaxi', 18),
(21, 10, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-27 21:41:35', 'Venda #19 - Abacaxi', 19),
(22, 16, 'Saida', 0.500, 70.00, NULL, 2, '2026-04-27 21:44:05', 'Venda #20 - Açaí com Leite Ninho', 20),
(23, 16, 'Saida', 0.250, 70.00, NULL, 1, '2026-04-27 21:49:36', 'Venda #21 - Açaí com Leite Ninho', 21),
(24, 2, 'Saida', 0.250, 70.00, NULL, 1, '2026-04-27 21:49:36', 'Venda #21 - Chocolate Branco', 21),
(25, 17, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-27 22:04:43', 'Venda #22 - Açaí Tradicional', 22),
(26, 16, 'Saida', 0.500, 70.00, NULL, 1, '2026-04-27 22:14:00', 'Venda #23 - Açaí com Leite Ninho', 23),
(27, 7, 'Saida', 0.500, 70.00, NULL, 1, '2026-04-27 22:14:00', 'Venda #23 - Creme', 23),
(28, 6, 'Saida', 0.500, 70.00, NULL, 1, '2026-04-27 22:15:28', 'Venda #24 - Brigadeiro', 24),
(29, 9, 'Saida', 0.500, 70.00, NULL, 1, '2026-04-27 22:15:28', 'Venda #24 - Caramelo', 24),
(30, 16, 'Saida', 0.050, 70.00, NULL, 1, '2026-04-27 22:17:11', 'Venda #25 - Açaí com Leite Ninho', 25),
(31, 12, 'Saida', 0.050, 70.00, NULL, 1, '2026-04-27 22:17:11', 'Venda #25 - Doce de Leite', 25),
(32, 16, 'Saida', 0.050, 70.00, NULL, 1, '2026-04-27 22:17:39', 'Venda #26 - Açaí com Leite Ninho', 26),
(33, 11, 'Saida', 0.050, 70.00, NULL, 1, '2026-04-27 22:17:39', 'Venda #26 - Coco', 26),
(34, 17, 'Saida', 0.001, 70.00, NULL, 1, '2026-04-27 22:18:41', 'Venda #27 - Açaí Tradicional', 27),
(35, 12, 'Saida', 0.001, 70.00, NULL, 1, '2026-04-27 22:18:41', 'Venda #27 - Doce de Leite', 27),
(36, 17, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-27 22:19:40', 'Venda #28 - Açaí Tradicional', 28),
(37, 16, 'Saida', 1.000, 70.00, NULL, 1, '2026-04-27 22:22:25', 'Venda #29 - Açaí com Leite Ninho', 29),
(38, 17, 'Saida', 0.300, 70.00, NULL, 1, '2026-04-27 22:25:44', 'Venda #30 - Açaí Tradicional', 30),
(39, 2, 'Saida', 0.060, 70.00, NULL, 1, '2026-04-27 22:28:37', 'Venda #31 - Chocolate Branco', 31),
(40, 10, 'Saida', 0.500, 70.00, NULL, 1, '2026-04-27 22:30:09', 'Venda #32 - Abacaxi', 32);

--
-- Acionadores `movimentacoes_estoque`
--
DELIMITER $$
CREATE TRIGGER `trg_atualiza_estoque_after_movimentacao` AFTER INSERT ON `movimentacoes_estoque` FOR EACH ROW BEGIN
    IF NEW.tipo_movimentacao = 'Entrada' THEN
        UPDATE estoque 
        SET quantidade_disponivel = quantidade_disponivel + NEW.quantidade,
            data_ultima_atualizacao = CURRENT_TIMESTAMP
        WHERE produto_id = NEW.produto_id;
        
    ELSEIF NEW.tipo_movimentacao = 'Saida' THEN
        UPDATE estoque 
        SET quantidade_disponivel = quantidade_disponivel - NEW.quantidade,
            data_ultima_atualizacao = CURRENT_TIMESTAMP
        WHERE produto_id = NEW.produto_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_log_movimentacoes_insert` AFTER INSERT ON `movimentacoes_estoque` FOR EACH ROW BEGIN
    INSERT INTO logs_auditoria 
    (usuario_id, acao, tabela_afetada, registro_id, descricao)
    VALUES 
    (NEW.usuario_id, NEW.tipo_movimentacao, 'movimentacoes_estoque', NEW.movimentacao_id, 
     CONCAT(NEW.tipo_movimentacao, ' de ', NEW.quantidade, ' kg do produto ', NEW.produto_id));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `produto_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `categoria` enum('Sorvete','Insumo','Ingrediente','Consumivel','Embalagem','Coberturas','Adicionais') NOT NULL,
  `preco_custo` decimal(10,2) NOT NULL,
  `preco_venda` decimal(10,2) DEFAULT NULL,
  `unidade_medida` varchar(20) NOT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`produto_id`, `nome`, `categoria`, `preco_custo`, `preco_venda`, `unidade_medida`, `criado_por`, `data_cadastro`, `ativo`) VALUES
(1, 'Chocolate', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(2, 'Chocolate Branco', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(3, 'Pistache', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(4, 'Brownie', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(5, 'Red Velvet', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(6, 'Brigadeiro', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(7, 'Creme', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(8, 'Céu-Azul', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(9, 'Caramelo', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(10, 'Abacaxi', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(11, 'Coco', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(12, 'Doce de Leite', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(13, 'Floresta Negra', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(14, 'Ninho', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(15, 'Uva', 'Sorvete', 28.00, 70.00, 'kg', 1, '2026-04-07 21:48:04', 1),
(16, 'Açaí com Leite Ninho', 'Sorvete', 32.50, 70.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(17, 'Açaí Tradicional', 'Sorvete', 32.50, 70.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(18, 'Chocolate Derretido', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(19, 'Chocolate Branco Derretido', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(20, 'Balas de Gelatina', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(21, 'Brigadeiro (Topping)', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(22, 'Beijinho (Topping)', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(23, 'Coco Ralado', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(24, 'Gotas de Chocolate', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(25, 'Chocoballs', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(26, 'Caldas Diversas', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(27, 'Granulado', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(28, 'Marshmallow', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(29, 'Morango', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(30, 'Cremes Diversos', 'Adicionais', 0.00, 0.00, 'kg', 1, '2026-04-07 21:48:05', 1),
(31, 'Copo Descartável 200ml', 'Embalagem', 0.15, 0.00, 'un', 1, '2026-04-07 21:48:05', 1),
(32, 'Copo Descartável 300ml', 'Embalagem', 0.20, 0.00, 'un', 1, '2026-04-07 21:48:05', 1),
(33, 'Copo Descartável 500ml', 'Embalagem', 0.30, 0.00, 'un', 1, '2026-04-07 21:48:05', 1),
(34, 'Pote Térmico 1 Litro', 'Embalagem', 0.80, 0.00, 'un', 1, '2026-04-07 21:48:05', 1),
(35, 'Colherzinha Plástica', 'Embalagem', 0.05, 0.00, 'un', 1, '2026-04-07 21:48:05', 1),
(36, 'Guardanapo (Pacote)', 'Embalagem', 2.50, 0.00, 'un', 1, '2026-04-07 21:48:05', 1);

--
-- Acionadores `produtos`
--
DELIMITER $$
CREATE TRIGGER `trg_log_produtos_update` AFTER UPDATE ON `produtos` FOR EACH ROW BEGIN
    INSERT INTO logs_auditoria 
    (usuario_id, acao, tabela_afetada, registro_id, descricao)
    VALUES 
    (NEW.criado_por, 'UPDATE', 'produtos', NEW.produto_id, 
     CONCAT('Produto alterado: ', NEW.nome));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `promocoes`
--

CREATE TABLE `promocoes` (
  `promocao_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `desconto_percentual` decimal(5,2) DEFAULT NULL,
  `desconto_valor` decimal(10,2) DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `promocoes`
--

INSERT INTO `promocoes` (`promocao_id`, `nome`, `descricao`, `desconto_percentual`, `desconto_valor`, `data_inicio`, `data_fim`, `ativo`, `criado_por`) VALUES
(1, 'DIA DA FAMÍLIA', 'Promoção especial Dia da Família com 10% de desconto', 10.00, NULL, '2026-04-19', '2026-04-26', 1, 7),
(2, 'SEGUNDA DO SORVETE', '10% de desconto em todos os sorvetes na segunda-feira', 10.00, NULL, '2026-04-20', '2026-04-27', 1, 7),
(3, 'PROMO COMBO FAMILIA', 'Desconto de R$ 15,00 em compras acima de R$ 80,00', NULL, 15.00, '2026-04-20', '2026-05-05', 1, 7),
(4, 'Sorvete de morango', 'Sorvete somente de sabor morango', 15.00, NULL, '2026-05-28', '2026-05-30', 1, 7),
(5, 'FESTA JUNINA GELADA', '15% de desconto em todos os sorvetes durante o período junino', 15.00, NULL, '2026-06-01', '2026-06-30', 1, 7),
(6, 'QUARTA DO CLIENTE', '5% de desconto em qualquer compra realizada às quartas-feiras', 5.00, NULL, '2026-05-01', '2026-07-07', 1, 7),
(7, 'COMBO AMIGOS', 'Desconto de R$ 10,00 em compras acima de R$ 50,00', NULL, 10.00, '2026-05-15', '2026-07-07', 1, 7),
(8, 'INVERNO MAIS DOCE', '20% de desconto em produtos selecionados durante o inverno', 20.00, NULL, '2026-06-21', '2026-07-07', 1, 7),
(9, 'PIX PREMIADO', '5% de desconto para pagamentos realizados via PIX', 5.00, NULL, '2026-05-01', '2026-07-07', 1, 7),
(10, 'FAMÍLIA FELIZ', 'Desconto de R$ 20,00 em compras acima de R$ 120,00', NULL, 20.00, '2026-06-01', '2026-07-07', 1, 7),
(11, 'SEMANA DO CLIENTE', '12% de desconto em toda a linha de produtos', 12.00, NULL, '2026-06-29', '2026-07-07', 1, 7);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `login` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo` enum('Gerente','Funcionario') NOT NULL DEFAULT 'Funcionario',
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `ultimo_acesso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`usuario_id`, `nome`, `login`, `senha_hash`, `tipo`, `ativo`, `data_criacao`, `ultimo_acesso`) VALUES
(1, 'Kauã Malagutti', 'kaua', '$2y$10$0i51mfuBvw/FAFNizUk/5exP4yVqmaIRq2iyJUABeQ0qh5zCZhQYu', 'Gerente', 1, '2026-04-07 21:12:10', '2026-04-27 22:29:50'),
(2, 'Arthur Moro', 'arthur', '$2y$10$ze3F6Y3E0ylpxjgOvot2zupnIBU09gm.d3Uvww/5STzuK75ZHyG6q', 'Funcionario', 1, '2026-04-07 21:50:14', '2026-04-27 21:53:10'),
(3, 'Francisco Lessa', 'francisco', '$2y$10$ze3F6Y3E0ylpxjgOvot2zupnIBU09gm.d3Uvww/5STzuK75ZHyG6q', 'Funcionario', 1, '2026-04-07 21:50:37', NULL),
(4, 'Luigi Pretto', 'luigi', '$2y$10$ze3F6Y3E0ylpxjgOvot2zupnIBU09gm.d3Uvww/5STzuK75ZHyG6q', 'Funcionario', 1, '2026-04-07 21:50:55', NULL),
(5, 'Samuel Boita', 'samuel', '$2y$10$ze3F6Y3E0ylpxjgOvot2zupnIBU09gm.d3Uvww/5STzuK75ZHyG6q', 'Funcionario', 1, '2026-04-07 21:51:15', NULL),
(6, 'Taynan Brighenti', 'taynan', '$2y$10$ze3F6Y3E0ylpxjgOvot2zupnIBU09gm.d3Uvww/5STzuK75ZHyG6q', 'Funcionario', 1, '2026-04-07 21:51:37', NULL),
(7, 'Administradores do grupo', 'Admin-TDS', '$2y$10$a0SuOLXcN7JXbj1k1Tl7z.twMcwS0R91RIwMA33hgiYEW6rgPFE2O', 'Gerente', 1, '2026-04-07 21:52:25', '2026-04-20 14:40:25'),
(8, 'Pedro', 'pedro', '$2y$10$ze3F6Y3E0ylpxjgOvot2zupnIBU09gm.d3Uvww/5STzuK75ZHyG6q', 'Funcionario', 1, '2026-04-14 14:37:45', NULL),
(9, 'Rikelme', 'rikelme', '$2y$10$ze3F6Y3E0ylpxjgOvot2zupnIBU09gm.d3Uvww/5STzuK75ZHyG6q', 'Funcionario', 1, '2026-04-14 14:37:45', NULL),
(10, 'Victor', 'victor', '$2y$10$ze3F6Y3E0ylpxjgOvot2zupnIBU09gm.d3Uvww/5STzuK75ZHyG6q', 'Funcionario', 1, '2026-04-14 14:37:45', NULL),
(11, 'Lucas', 'lucas', '$2y$10$ze3F6Y3E0ylpxjgOvot2zupnIBU09gm.d3Uvww/5STzuK75ZHyG6q', 'Funcionario', 1, '2026-04-14 14:37:45', NULL),
(12, 'David', 'david', '$2y$10$ze3F6Y3E0ylpxjgOvot2zupnIBU09gm.d3Uvww/5STzuK75ZHyG6q', 'Gerente', 1, '2026-04-14 14:37:45', '2026-06-22 00:12:19');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `venda_id` int(11) NOT NULL,
  `data_venda` datetime DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  `peso_total` decimal(8,3) DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `promocao_id` int(11) DEFAULT NULL,
  `desconto_aplicado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `forma_pagamento` enum('Dinheiro','Cartao_Credito','Cartao_Debito','Pix') NOT NULL,
  `comprovante_gerado` tinyint(1) DEFAULT 0,
  `status` enum('Pendente','Confirmado','Em_Preparacao','Concluido','Cancelado') DEFAULT 'Confirmado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `vendas`
--

INSERT INTO `vendas` (`venda_id`, `data_venda`, `usuario_id`, `peso_total`, `valor_total`, `promocao_id`, `desconto_aplicado`, `forma_pagamento`, `comprovante_gerado`, `status`) VALUES
(1, '2026-04-19 17:48:11', 1, 0.500, 28.35, 1, 3.15, 'Dinheiro', 0, 'Confirmado'),
(2, '2026-04-19 18:01:25', 1, 1.000, 63.00, 1, 7.00, 'Dinheiro', 0, 'Confirmado'),
(3, '2026-04-19 18:10:49', 1, 0.500, 31.50, 1, 3.50, 'Pix', 0, 'Confirmado'),
(4, '2026-04-20 11:14:57', 1, 2.000, 140.00, NULL, 0.00, 'Dinheiro', 0, 'Confirmado'),
(5, '2026-04-20 11:22:44', 1, 1.000, 70.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(6, '2026-04-20 11:24:45', 1, 0.500, 31.50, 1, 3.50, 'Dinheiro', 1, 'Confirmado'),
(7, '2026-04-20 11:28:20', 1, 0.020, 1.40, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(8, '2026-04-20 11:29:42', 1, 0.020, 1.26, 1, 0.14, 'Dinheiro', 1, 'Confirmado'),
(9, '2026-04-20 11:34:10', 1, 0.200, 14.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(10, '2026-04-20 11:37:41', 1, 1.000, 55.00, 3, 15.00, 'Dinheiro', 1, 'Confirmado'),
(11, '2026-04-20 11:40:41', 1, 2.000, 126.00, 1, 14.00, 'Dinheiro', 1, 'Confirmado'),
(12, '2026-04-20 11:45:34', 1, 2.000, 140.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(13, '2026-04-20 12:26:50', 1, 1.000, 70.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(14, '2026-04-20 12:32:54', 1, 2.000, 140.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(15, '2026-04-20 12:37:05', 1, 0.500, 31.50, 1, 3.50, 'Dinheiro', 1, 'Confirmado'),
(16, '2026-04-20 13:06:55', 1, 1.000, 0.00, 1, 0.00, 'Dinheiro', 1, 'Confirmado'),
(17, '2026-04-20 13:23:14', 1, 1.000, 63.00, 1, 7.00, 'Dinheiro', 1, 'Confirmado'),
(18, '2026-04-27 21:38:31', 1, 0.200, 14.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(19, '2026-04-27 21:41:35', 1, 1.000, 70.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(20, '2026-04-27 21:44:05', 2, 0.500, 20.00, 3, 15.00, 'Dinheiro', 1, 'Confirmado'),
(21, '2026-04-27 21:49:36', 1, 0.500, 20.00, 3, 15.00, 'Dinheiro', 1, 'Confirmado'),
(22, '2026-04-27 22:04:43', 1, 1.000, 70.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(23, '2026-04-27 22:14:00', 1, 1.000, 70.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(24, '2026-04-27 22:15:28', 1, 1.000, 70.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(25, '2026-04-27 22:17:11', 1, 0.100, 7.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(26, '2026-04-27 22:17:39', 1, 0.100, 7.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(27, '2026-04-27 22:18:41', 1, 0.002, 0.14, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(28, '2026-04-27 22:19:40', 1, 1.000, 70.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(29, '2026-04-27 22:22:25', 1, 1.000, 70.00, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(30, '2026-04-27 22:25:44', 1, 0.300, 18.90, 2, 2.10, 'Dinheiro', 1, 'Confirmado'),
(31, '2026-04-27 22:28:37', 1, 0.060, 4.20, NULL, 0.00, 'Dinheiro', 1, 'Confirmado'),
(32, '2026-04-27 22:30:09', 1, 0.500, 20.00, 3, 15.00, 'Dinheiro', 1, 'Confirmado');

--
-- Acionadores `vendas`
--
DELIMITER $$
CREATE TRIGGER `trg_log_vendas_insert` AFTER INSERT ON `vendas` FOR EACH ROW BEGIN
    INSERT INTO logs_auditoria 
    (usuario_id, acao, tabela_afetada, registro_id, descricao)
    VALUES 
    (NEW.usuario_id, 'INSERT', 'vendas', NEW.venda_id, 
     CONCAT('Venda #', NEW.venda_id, ' - R$ ', NEW.valor_total, ' - ', NEW.forma_pagamento));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_estoque_critico`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_estoque_critico` (
`produto` varchar(150)
,`categoria` enum('Sorvete','Insumo','Ingrediente','Consumivel','Embalagem','Coberturas','Adicionais')
,`quantidade_disponivel` decimal(12,3)
,`validade` date
,`dias_para_vencer` int(7)
,`custo_medio` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_relatorio_financeiro`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_relatorio_financeiro` (
`data` date
,`faturamento_liquido` decimal(33,2)
,`kg_vendidos` decimal(30,3)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_relatorio_vendas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_relatorio_vendas` (
`data_venda` date
,`quantidade_vendas` bigint(21)
,`peso_total_kg` decimal(30,3)
,`valor_bruto` decimal(32,2)
,`total_descontos` decimal(32,2)
,`valor_liquido` decimal(33,2)
,`atendente` varchar(100)
);

-- --------------------------------------------------------

--
-- Estrutura para view `vw_estoque_critico`
--
DROP TABLE IF EXISTS `vw_estoque_critico`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_estoque_critico`  AS SELECT `p`.`nome` AS `produto`, `p`.`categoria` AS `categoria`, `e`.`quantidade_disponivel` AS `quantidade_disponivel`, `e`.`validade` AS `validade`, to_days(`e`.`validade`) - to_days(curdate()) AS `dias_para_vencer`, `e`.`custo_medio` AS `custo_medio` FROM (`estoque` `e` join `produtos` `p` on(`e`.`produto_id` = `p`.`produto_id`)) WHERE `e`.`quantidade_disponivel` <= 10 OR `e`.`validade` <= curdate() + interval 10 day ORDER BY `e`.`validade` ASC, `e`.`quantidade_disponivel` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_relatorio_financeiro`
--
DROP TABLE IF EXISTS `vw_relatorio_financeiro`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_relatorio_financeiro`  AS SELECT cast(`v`.`data_venda` as date) AS `data`, sum(`v`.`valor_total` - `v`.`desconto_aplicado`) AS `faturamento_liquido`, sum(`v`.`peso_total`) AS `kg_vendidos` FROM `vendas` AS `v` WHERE `v`.`status` = 'Confirmado' GROUP BY cast(`v`.`data_venda` as date) ORDER BY cast(`v`.`data_venda` as date) DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_relatorio_vendas`
--
DROP TABLE IF EXISTS `vw_relatorio_vendas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_relatorio_vendas`  AS SELECT cast(`v`.`data_venda` as date) AS `data_venda`, count(0) AS `quantidade_vendas`, sum(`v`.`peso_total`) AS `peso_total_kg`, sum(`v`.`valor_total`) AS `valor_bruto`, sum(`v`.`desconto_aplicado`) AS `total_descontos`, sum(`v`.`valor_total` - `v`.`desconto_aplicado`) AS `valor_liquido`, `u`.`nome` AS `atendente` FROM (`vendas` `v` join `usuarios` `u` on(`v`.`usuario_id` = `u`.`usuario_id`)) WHERE `v`.`status` = 'Confirmado' GROUP BY cast(`v`.`data_venda` as date), `u`.`nome` ORDER BY cast(`v`.`data_venda` as date) DESC ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `despesas`
--
ALTER TABLE `despesas`
  ADD PRIMARY KEY (`despesa_id`),
  ADD KEY `idx_despesas_data` (`data_despesa`),
  ADD KEY `idx_despesas_categoria` (`categoria`),
  ADD KEY `idx_despesas_usuario` (`usuario_id`);

--
-- Índices de tabela `estoque`
--
ALTER TABLE `estoque`
  ADD PRIMARY KEY (`estoque_id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`),
  ADD KEY `idx_estoque_produto` (`produto_id`);

--
-- Índices de tabela `feedbacks_clientes`
--
ALTER TABLE `feedbacks_clientes`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `idx_feedback_data` (`data_registro`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`fornecedor_id`);

--
-- Índices de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD PRIMARY KEY (`item_venda_id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  ADD PRIMARY KEY (`movimentacao_id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_movimentacao_produto_data` (`produto_id`,`data_movimentacao`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`produto_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_produto_nome` (`nome`);

--
-- Índices de tabela `promocoes`
--
ALTER TABLE `promocoes`
  ADD PRIMARY KEY (`promocao_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`usuario_id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD KEY `idx_usuario_login` (`login`);

--
-- Índices de tabela `vendas`
--
ALTER TABLE `vendas`
  ADD PRIMARY KEY (`venda_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_venda_data` (`data_venda`),
  ADD KEY `vendas_promocao_fk` (`promocao_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `despesas`
--
ALTER TABLE `despesas`
  MODIFY `despesa_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estoque`
--
ALTER TABLE `estoque`
  MODIFY `estoque_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `feedbacks_clientes`
--
ALTER TABLE `feedbacks_clientes`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `fornecedor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  MODIFY `item_venda_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de tabela `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT de tabela `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  MODIFY `movimentacao_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `produto_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `promocoes`
--
ALTER TABLE `promocoes`
  MODIFY `promocao_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `usuario_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `venda_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `despesas`
--
ALTER TABLE `despesas`
  ADD CONSTRAINT `despesas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `estoque`
--
ALTER TABLE `estoque`
  ADD CONSTRAINT `estoque_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`produto_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `estoque_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`fornecedor_id`);

--
-- Restrições para tabelas `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD CONSTRAINT `itens_venda_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`venda_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itens_venda_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`produto_id`);

--
-- Restrições para tabelas `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  ADD CONSTRAINT `logs_auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`);

--
-- Restrições para tabelas `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  ADD CONSTRAINT `movimentacoes_estoque_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`produto_id`),
  ADD CONSTRAINT `movimentacoes_estoque_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`fornecedor_id`),
  ADD CONSTRAINT `movimentacoes_estoque_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`);

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`usuario_id`);

--
-- Restrições para tabelas `promocoes`
--
ALTER TABLE `promocoes`
  ADD CONSTRAINT `promocoes_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`usuario_id`);

--
-- Restrições para tabelas `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`),
  ADD CONSTRAINT `vendas_promocao_fk` FOREIGN KEY (`promocao_id`) REFERENCES `promocoes` (`promocao_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
