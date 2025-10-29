-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 15/10/2025 às 03:19
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
-- Banco de dados: `dbbigode`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categoria`
--

CREATE TABLE `categoria` (
  `id` int(11) NOT NULL,
  `categoria` varchar(255) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `horarios`
--

CREATE TABLE `horarios` (
  `id` int(11) NOT NULL,
  `nome` text NOT NULL,
  `corte` varchar(100) DEFAULT NULL,
  `data` date NOT NULL,
  `hora` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `horarios`
--

INSERT INTO `horarios` (`id`, `nome`, `corte`, `data`, `hora`) VALUES
(37, 'gustavo', 'Corte Degradê', '2025-10-15', '10:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `horarios_funcionamento`
--

CREATE TABLE `horarios_funcionamento` (
  `id` int(11) NOT NULL,
  `dia_semana` int(1) NOT NULL COMMENT '1=Segunda, 2=Terça, 3=Quarta, 4=Quinta, 5=Sexta, 6=Sábado, 7=Domingo',
  `nome_dia` varchar(20) NOT NULL,
  `aberto` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Aberto, 0=Fechado',
  `hora_abertura` time DEFAULT NULL,
  `hora_fechamento` time DEFAULT NULL,
  `hora_pausa_inicio` time DEFAULT NULL COMMENT 'Início do horário de almoço/pausa',
  `hora_pausa_fim` time DEFAULT NULL COMMENT 'Fim do horário de almoço/pausa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `horarios_funcionamento`
--

INSERT INTO `horarios_funcionamento` (`id`, `dia_semana`, `nome_dia`, `aberto`, `hora_abertura`, `hora_fechamento`, `hora_pausa_inicio`, `hora_pausa_fim`) VALUES
(1, 1, 'Segunda-feira', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00'),
(2, 2, 'Terça-feira', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00'),
(3, 3, 'Quarta-feira', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00'),
(4, 4, 'Quinta-feira', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00'),
(5, 5, 'Sexta-feira', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00'),
(6, 6, 'Sábado', 1, '09:00:00', '17:00:00', NULL, NULL),
(7, 7, 'Domingo', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pessoa`
--

CREATE TABLE `pessoa` (
  `id` int(255) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cpf` char(15) NOT NULL,
  `cep` char(9) NOT NULL,
  `endereco` varchar(255) NOT NULL,
  `cidade` varchar(255) NOT NULL,
  `estado` char(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pessoa`
--

INSERT INTO `pessoa` (`id`, `nome`, `email`, `cpf`, `cep`, `endereco`, `cidade`, `estado`) VALUES
(13, 'dfsdfsdf', 'fsdfs@sdsf', '141', 'dfsfsdfs', '14', 'sdfsd', 'sdfs');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto`
--

CREATE TABLE `produto` (
  `id` int(255) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `estoque` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id` varchar(255) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id`, `nome`, `email`, `senha`, `ativo`) VALUES
('', 'gctxt', 'ocajafeegvrgbrbv@gmail.com', 'oi', 1),
('', 'gustavo', 'lealgustavo792@gmail.com', 'ef41a5f59e8d3cd9874e4d21da2a6679', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `horarios_funcionamento`
--
ALTER TABLE `horarios_funcionamento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dia_semana` (`dia_semana`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de tabela `horarios_funcionamento`
--
ALTER TABLE `horarios_funcionamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

-- --------------------------------------------------------

--
-- Configurações do Sistema Administrativo
--

-- 1. Adicionar coluna tipo_usuario na tabela usuario para sistema de permissões
ALTER TABLE `usuario` ADD COLUMN `tipo_usuario` ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente';

-- 2. Criar usuário administrador padrão (altere os dados conforme necessário)
INSERT INTO `usuario` (`id`, `nome`, `email`, `senha`, `tipo_usuario`, `ativo`) 
VALUES (UUID(), 'Administrador', 'admin@barbearia.com', MD5('admin123'), 'admin', 1)
ON DUPLICATE KEY UPDATE `tipo_usuario` = 'admin';

-- 3. Promover usuário existente para admin (opcional - descomente e altere o email)
-- UPDATE `usuario` SET `tipo_usuario` = 'admin' WHERE `email` = 'seu_email@gmail.com';

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
