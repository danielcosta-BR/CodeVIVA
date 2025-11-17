-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 15/11/2025 às 21:25
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
-- Banco de dados: `viva_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `caderneta`
--

CREATE TABLE `caderneta` (
  `id_caderneta` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `id_vacina_modelo` int(11) NOT NULL,
  `data_prevista` date DEFAULT NULL,
  `data_tomada` date DEFAULT NULL COMMENT 'NULL se a vacina ainda não foi tomada',
  `id_enfermeiro_aplicador` int(11) DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `codigoverificacao`
--

CREATE TABLE `codigoverificacao` (
  `id_codigo` int(11) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `funcao_alvo` varchar(50) NOT NULL DEFAULT 'enfermeiro',
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_uso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `codigoverificacao`
--

INSERT INTO `codigoverificacao` (`id_codigo`, `codigo`, `funcao_alvo`, `usado`, `data_criacao`, `data_uso`) VALUES
(9, '5F7663J3', 'enfermeiro', 1, '2025-11-08 17:09:35', '2025-11-08 17:09:40'),
(10, 'SL8YP63G', 'enfermeiro', 1, '2025-11-13 22:49:53', '2025-11-13 22:50:03');

-- --------------------------------------------------------

--
-- Estrutura para tabela `postodesaude`
--

CREATE TABLE `postodesaude` (
  `id_posto` int(11) NOT NULL,
  `nome_posto` varchar(255) NOT NULL,
  `endereco` varchar(512) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `postosaude`
--

CREATE TABLE `postosaude` (
  `id_posto` int(11) NOT NULL,
  `nome_posto` varchar(255) NOT NULL,
  `endereco` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `postosaude`
--

INSERT INTO `postosaude` (`id_posto`, `nome_posto`, `endereco`, `telefone`) VALUES
(1, 'UBS Central VIVA+', 'Rua da Saúde, 100, Centro', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `recuperacaosenha`
--

CREATE TABLE `recuperacaosenha` (
  `id_recuperacao` int(11) NOT NULL,
  `email_usuario` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `recuperacaosenha`
--

INSERT INTO `recuperacaosenha` (`id_recuperacao`, `email_usuario`, `token`, `expira_em`, `usado`) VALUES
(52, 'danielcosta.10d@gmail.com', '50c0b4a30a89d0c5e3075e23d67b9452ecbd1eb52a921dcea3382d4aa704d7b7', '2025-11-13 03:22:50', 1),
(53, 'danielcosta.10d@gmail.com', 'ded21520501c6d64f5b569082def6e5f9ab09274fdce63fa9a54fedaaf81b94c', '2025-11-13 03:40:02', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL COMMENT 'Senha deve ser armazenada como um HASH (ex: usando PHP password_hash())',
  `funcao` enum('paciente','enfermeiro','administrador') NOT NULL,
  `id_posto` int(11) DEFAULT NULL COMMENT 'Vincula a unidade de saúde',
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome_completo`, `email`, `senha`, `funcao`, `id_posto`, `data_cadastro`) VALUES
(1, 'Admin Geral', 'vivaplus.3inf@gmail.com', '$2y$10$FkCWB.omXJvvJUzunnAso.C378QJ7Etno5fSti1s4e/1IrFtMam7q', 'administrador', 1, '2025-11-04 00:04:31'),
(3, 'Daniel Joás da Costa', 'danielcosta.10d@gmail.com', '$2y$10$.q5FljoeQhGpXxo7xreQ/upVJB8WCundLVoR5byK/vLVM9GAH9RqG', 'paciente', NULL, '2025-11-05 00:21:27'),
(7, 'Wagner Soares Dionísio Cardoso', 'danielpolinfo@gmail.com', '$2y$10$EyG.2P3jWz7PdXtsPs1vsewX8v1BZJLt4JhXfyibmNXSv11yrlNF6', 'enfermeiro', NULL, '2025-11-13 22:50:03');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarioprecadastro`
--

CREATE TABLE `usuarioprecadastro` (
  `id_pre_cadastro` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `data_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vacinamodelo`
--

CREATE TABLE `vacinamodelo` (
  `id_vacina_modelo` int(11) NOT NULL,
  `nome_vacina` varchar(100) NOT NULL,
  `recomendacao_idade` varchar(50) DEFAULT NULL,
  `intervalo_dias` int(11) DEFAULT NULL COMMENT 'Intervalo entre doses em dias (NULL se dose única)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `vacinamodelo`
--

INSERT INTO `vacinamodelo` (`id_vacina_modelo`, `nome_vacina`, `recomendacao_idade`, `intervalo_dias`) VALUES
(1, 'COVID-19 - Bivalente', 'Anual (Adulto)', NULL),
(2, 'Influenza (Gripe)', 'Anual (Todos)', NULL),
(3, 'Tríplice Viral (Sarampo, Caxumba e Rubéola)', '1ª Dose: 12 meses', 180),
(4, 'Hepatite B', 'Recém-nascidos', 30);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `caderneta`
--
ALTER TABLE `caderneta`
  ADD PRIMARY KEY (`id_caderneta`),
  ADD UNIQUE KEY `uc_caderneta` (`id_paciente`,`id_vacina_modelo`,`data_prevista`),
  ADD KEY `id_vacina_modelo` (`id_vacina_modelo`),
  ADD KEY `id_enfermeiro_aplicador` (`id_enfermeiro_aplicador`);

--
-- Índices de tabela `codigoverificacao`
--
ALTER TABLE `codigoverificacao`
  ADD PRIMARY KEY (`id_codigo`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `postodesaude`
--
ALTER TABLE `postodesaude`
  ADD PRIMARY KEY (`id_posto`),
  ADD UNIQUE KEY `nome_posto` (`nome_posto`);

--
-- Índices de tabela `postosaude`
--
ALTER TABLE `postosaude`
  ADD PRIMARY KEY (`id_posto`);

--
-- Índices de tabela `recuperacaosenha`
--
ALTER TABLE `recuperacaosenha`
  ADD PRIMARY KEY (`id_recuperacao`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_posto` (`id_posto`);

--
-- Índices de tabela `usuarioprecadastro`
--
ALTER TABLE `usuarioprecadastro`
  ADD PRIMARY KEY (`id_pre_cadastro`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `vacinamodelo`
--
ALTER TABLE `vacinamodelo`
  ADD PRIMARY KEY (`id_vacina_modelo`),
  ADD UNIQUE KEY `nome_vacina` (`nome_vacina`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `caderneta`
--
ALTER TABLE `caderneta`
  MODIFY `id_caderneta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `codigoverificacao`
--
ALTER TABLE `codigoverificacao`
  MODIFY `id_codigo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `postodesaude`
--
ALTER TABLE `postodesaude`
  MODIFY `id_posto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `postosaude`
--
ALTER TABLE `postosaude`
  MODIFY `id_posto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `recuperacaosenha`
--
ALTER TABLE `recuperacaosenha`
  MODIFY `id_recuperacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `usuarioprecadastro`
--
ALTER TABLE `usuarioprecadastro`
  MODIFY `id_pre_cadastro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `vacinamodelo`
--
ALTER TABLE `vacinamodelo`
  MODIFY `id_vacina_modelo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `caderneta`
--
ALTER TABLE `caderneta`
  ADD CONSTRAINT `caderneta_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `caderneta_ibfk_2` FOREIGN KEY (`id_vacina_modelo`) REFERENCES `vacinamodelo` (`id_vacina_modelo`),
  ADD CONSTRAINT `caderneta_ibfk_3` FOREIGN KEY (`id_enfermeiro_aplicador`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL;

--
-- Restrições para tabelas `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_posto`) REFERENCES `postosaude` (`id_posto`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
