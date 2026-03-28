-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 02/12/2025 às 14:58
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

--
-- Despejando dados para a tabela `caderneta`
--

INSERT INTO `caderneta` (`id_caderneta`, `id_paciente`, `id_vacina_modelo`, `data_prevista`, `data_tomada`, `id_enfermeiro_aplicador`, `observacoes`) VALUES
(7, 15, 5, NULL, '2025-11-25', 16, NULL),
(8, 15, 6, '2026-01-24', NULL, NULL, NULL),
(9, 15, 1, NULL, '2025-11-25', 16, NULL),
(10, 21, 5, NULL, '2025-12-01', 16, NULL),
(11, 21, 6, '2026-01-30', NULL, NULL, NULL);

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
(10, 'SL8YP63G', 'enfermeiro', 1, '2025-11-13 22:49:53', '2025-11-13 22:50:03'),
(11, 'EXQEGHMU', 'enfermeiro', 1, '2025-11-23 17:26:46', '2025-11-23 17:26:53'),
(12, 'ANG3JVZV', 'enfermeiro', 0, '2025-11-25 08:38:14', NULL),
(13, 'J58X592C', 'enfermeiro', 1, '2025-11-25 09:39:32', '2025-11-25 09:40:09'),
(14, 'EAWQZVA0', 'enfermeiro', 0, '2025-12-01 09:43:13', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `doencas`
--

CREATE TABLE `doencas` (
  `id_doenca` int(11) NOT NULL,
  `nome_doenca` varchar(100) NOT NULL,
  `mensagem_alerta` text NOT NULL COMMENT 'Dica ou cuidado diário para quem tem essa doença'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `doencas`
--

INSERT INTO `doencas` (`id_doenca`, `nome_doenca`, `mensagem_alerta`) VALUES
(2, 'Síndrome de Down', 'Garanta acompanhamento pediátrico e estimulação precoce.'),
(3, 'Síndrome do X Frágil', 'Procure avaliação neurodesenvolvimental e suporte familiar.'),
(4, 'Síndrome de Klinefelter', 'Faça acompanhamento endocrinológico e avalie fertilidade.'),
(5, 'Síndrome do Triplo X', 'Observe aprendizado; busque apoio educacional quando necessário.'),
(6, 'Síndrome de Turner', 'Realize acompanhamento clínico e tratamento hormonal conforme indicado.'),
(7, 'Trissomia 18', 'Priorize cuidados paliativos, suporte especializado e acompanhamento médico.'),
(8, 'Trissomia 13', 'Ofereça cuidado médico contínuo, conforto e suporte à família.'),
(9, 'Doença de Alzheimer', 'Estimule a mente e monitore qualquer perda de memória.'),
(10, 'Artrite Reumatoide', 'Procure reumatologista ao notar dor ou rigidez persistente.'),
(11, 'Transtorno do Espectro Autista', 'Busque avaliação especializada ao notar sinais de atraso social/linguístico.'),
(12, 'Câncer', 'Realize exames preventivos e procure avaliação diante de sinais suspeitos.'),
(13, 'Doença Arterial Coronariana', 'Controle fatores de risco (pressão, colesterol, tabagismo) e faça check-ups.'),
(14, 'Diabetes', 'Monitore glicemia e consulte profissional ao identificar sintomas ou fatores de risco.'),
(15, 'Espinha Bífida', 'Durante o pré-natal, siga orientações e uso de ácido fólico para reduzir risco.'),
(16, 'Espinha Bífida', 'Durante o pré-natal, siga orientações e uso de ácido fólico para reduzir risco.');

-- --------------------------------------------------------

--
-- Estrutura para tabela `enfermeiros`
--

CREATE TABLE `enfermeiros` (
  `id_enfermeiro` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_posto_saude` int(11) DEFAULT NULL COMMENT 'ID do posto onde o enfermeiro atua.',
  `cpf` varchar(14) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `enfermeiros`
--

INSERT INTO `enfermeiros` (`id_enfermeiro`, `id_usuario`, `id_posto_saude`, `cpf`, `telefone`, `endereco`) VALUES
(7, 16, 2, '14658972600', '35988760675', 'Rua 18, Casa 190, Jardim das Acácias, Três Corações');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id_mensagem` int(11) NOT NULL,
  `id_remetente` int(11) NOT NULL COMMENT 'Quem enviou (Paciente ou Enfermeiro)',
  `id_destinatario` int(11) DEFAULT NULL COMMENT 'Preenchido se for msg direta (Enf -> Paciente)',
  `id_posto_alvo` int(11) DEFAULT NULL COMMENT 'Preenchido se for solicitacao geral (Paciente -> Posto)',
  `assunto` varchar(255) NOT NULL,
  `corpo` text NOT NULL,
  `data_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pendente','atendida') NOT NULL DEFAULT 'pendente',
  `id_atendente` int(11) DEFAULT NULL COMMENT 'Enfermeiro que atendeu a solicitação',
  `data_atendimento` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `mensagens`
--

INSERT INTO `mensagens` (`id_mensagem`, `id_remetente`, `id_destinatario`, `id_posto_alvo`, `assunto`, `corpo`, `data_envio`, `status`, `id_atendente`, `data_atendimento`) VALUES
(1, 21, NULL, 2, 'Visita Domiciliar', 'Por favor preciso que apliquem a Vacina \"tal\" para mim no endereço \"tal\".', '2025-11-25 10:34:25', 'atendida', 16, '2025-11-25 10:35:08'),
(2, 16, 21, NULL, 'Comparecer ao Posto', 'Daniel, venha no posto \"tal\" para tomar a vacina \"tal\".', '2025-11-25 10:37:45', 'pendente', NULL, NULL),
(3, 16, 15, NULL, 'Comparecer ao Posto', 'Bom dia! Por favor, compareça ao posto de saúde \"tal\" para tomar a vacina \"tal\".', '2025-11-26 07:38:59', 'pendente', NULL, NULL),
(4, 16, 22, NULL, 'Sem assunto', 'Olha aqui, se você não aprender PHP, já sabe como o Rodrigo vai reagir... Kk faço menor idéia do que tô falando', '2025-12-01 09:07:52', 'pendente', NULL, NULL),
(5, 22, NULL, 2, 'Visita', 'Fica quietinhoooooii', '2025-12-01 09:13:23', 'pendente', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacientes`
--

CREATE TABLE `pacientes` (
  `id_paciente_data` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `id_posto_saude` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pacientes`
--

INSERT INTO `pacientes` (`id_paciente_data`, `id_usuario`, `cpf`, `telefone`, `endereco`, `id_posto_saude`) VALUES
(15, 15, '14798712664', '35999447236', 'Rua 18, Casa 190, Jardim das Acácias, Três Corações', 2),
(20, 21, '14679958600', '35988760674', 'Rua 14, 188, Jardim Califórnia', 2),
(23, 22, '15065223602', '35997336692', 'Recanto Bom Jardim, Rua Antônio Osvaldo, 261', 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `paciente_doencas`
--

CREATE TABLE `paciente_doencas` (
  `id_paciente` int(11) NOT NULL,
  `id_doenca` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `paciente_doencas`
--

INSERT INTO `paciente_doencas` (`id_paciente`, `id_doenca`) VALUES
(15, 3),
(21, 2),
(21, 3);

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
(1, 'UBS Central VIVA+', 'Rua da Saúde, 100, Centro', ''),
(2, 'ESF COLINAS I, II e III', 'Rua Serenidade, nº 318 - Parque das Colinas', ''),
(5, 'ESF AMADEU MIGUEL', 'Rua Sergipe, nº36 - Amadeu Miguel', ''),
(6, 'ESF BOA VENTURA', 'Rua Lenise Aparecida Ferreira, nº 200 - Jardim Primavera', ''),
(7, 'ESF CAIC', 'Rua Bem-Te-Vi, nº 522 - Bela Vista', ''),
(8, 'ESF CINTURÃO VERDE', 'Rua Miguel Jorge de Oliveira, nº 2 - Jardim Esperança', ''),
(9, 'ESF FÁTIMA I e II', 'Rua Antônio Matias, nº 1093 - Vila Nossa Senhora de Fátima', ''),
(10, 'ESF FLORA', 'Rua Manoel Antônio Teixeira, S/nº - Flora', ''),
(11, 'ESF JARDIM PARAÍSO', 'Rua Joaquim Damas, nº 439 - Jardim Paraíso', ''),
(12, 'ESF MONTE ALEGRE I e II', 'Rua Lambari, S/nº - Monte Alegre', ''),
(13, 'ESF NOVO HORIZONTE', 'Rua Dr. Ricart Naves Rezende, nº 420 - Novo Horizonte', ''),
(14, 'ESF RIO DO PEIXE', 'Rua Pedro Rodrigues Chagas, S/nº Rio do Peixe', ''),
(15, 'ESF SÃO JOSÉ', 'Rua Nhá Chica, nº 345 - Parque São José', ''),
(16, 'ESF SÃO PEDRO', 'Avenida Orlando Rezende Andrade, S/nº - Feira de Gado', ''),
(17, 'UBS NOVA TRÊS CORAÇÕES', 'Rua Projetada 27, nº 350 - Nova Três Corações', ''),
(18, 'UBS TRIÂNGULO', 'Rua Luciano Andrade Peixoto, nº 291, Triângulo', ''),
(19, 'UBS VILA VIANA', 'Rua General Osório, 147, Vila Viana', ''),
(20, 'CLÍNICA CENTRAL - CENTRO DE SAÚDE DR. JOSÉ ANÍSIO LOPES VIEIRA', 'Travessa Casemiro Avelar, nº 24 - Centro', '');

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
(54, 'danielcosta.10d@gmail.com', 'e1f5e63ad0ab368d1ee3196791f5e59a802a153508ddccc24346c3537b03ac55', '2025-11-18 01:57:58', 1),
(55, 'danielpolinfo@gmail.com', 'fa29995f5fa33ab2e1e4b201c84247be4962e77cfbad7f7577e0fb80e94958a5', '2025-11-18 01:58:30', 1);

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
(15, 'Beatryz de Souza Mafra', 'beatryz.souza16f1@gmail.com', '$2y$10$WG1lJyan6p8jtebAeTO2weCw/TYB8l/n.lgGJteANiO301kNmu3su', 'paciente', NULL, '2025-11-24 07:39:10'),
(16, 'Carlos Dutra de Andrade', 'danielpolinfo@gmail.com', '$2y$10$b1DhQScOdVCB8jZwv3qoKeE3zNhLtHWx7/7x.cfTrUc2XI15DgF0O', 'enfermeiro', NULL, '2025-11-25 09:40:09'),
(21, 'Daniel Joás da Costa', 'danielcosta.10d@gmail.com', '$2y$10$EcN6ogH5nNFb5pTHCoExLu19npsl5SQMpf2mtE6xzGNhOYm56NJma', 'paciente', NULL, '2025-11-25 10:30:17'),
(22, 'Isabelle Manoelino De Carvalho', 'Isabellepolinfo@gmail.com', '$2y$10$NrRmAbe/9Nnmm6JixcJPauQda0WedaSNLLVXloW2GooDXqnJo59Uq', 'paciente', NULL, '2025-12-01 08:37:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarioprecadastro`
--

CREATE TABLE `usuarioprecadastro` (
  `id_pre_cadastro` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `data_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `cpf` varchar(14) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `id_posto` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vacinamodelo`
--

CREATE TABLE `vacinamodelo` (
  `id_vacina_modelo` int(11) NOT NULL,
  `nome_vacina` varchar(100) NOT NULL,
  `recomendacao_idade` varchar(50) DEFAULT NULL,
  `intervalo_dias` int(11) DEFAULT NULL COMMENT 'Intervalo entre doses em dias (NULL se dose única)',
  `id_proxima_vacina` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `vacinamodelo`
--

INSERT INTO `vacinamodelo` (`id_vacina_modelo`, `nome_vacina`, `recomendacao_idade`, `intervalo_dias`, `id_proxima_vacina`) VALUES
(1, 'COVID-19 - Bivalente', 'Anual (Adulto)', NULL, NULL),
(2, 'Influenza (Gripe)', 'Anual (Todos)', NULL, NULL),
(3, 'Tríplice Viral (Sarampo, Caxumba e Rubéola)', '1ª Dose: 12 meses', 180, NULL),
(4, 'Hepatite B', 'Recém-nascidos', 30, NULL),
(5, 'Vacina penta (DTP+Hib+HB) (1ª dose)', '2 meses', 60, 6),
(6, 'Vacina penta (DTP+Hib+HB) (2ª dose)', '4 meses', 60, 7),
(7, 'Vacina penta (DTP+Hib+HB) (3ª dose)', '6 meses', NULL, NULL);

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
-- Índices de tabela `doencas`
--
ALTER TABLE `doencas`
  ADD PRIMARY KEY (`id_doenca`);

--
-- Índices de tabela `enfermeiros`
--
ALTER TABLE `enfermeiros`
  ADD PRIMARY KEY (`id_enfermeiro`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_posto_saude` (`id_posto_saude`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id_mensagem`),
  ADD KEY `id_remetente` (`id_remetente`),
  ADD KEY `id_destinatario` (`id_destinatario`),
  ADD KEY `id_posto_alvo` (`id_posto_alvo`),
  ADD KEY `id_atendente` (`id_atendente`);

--
-- Índices de tabela `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id_paciente_data`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD KEY `pacientes_ibfk_2` (`id_posto_saude`);

--
-- Índices de tabela `paciente_doencas`
--
ALTER TABLE `paciente_doencas`
  ADD PRIMARY KEY (`id_paciente`,`id_doenca`),
  ADD KEY `id_doenca` (`id_doenca`);

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
  ADD UNIQUE KEY `nome_vacina` (`nome_vacina`),
  ADD KEY `fk_proxima_vacina` (`id_proxima_vacina`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `caderneta`
--
ALTER TABLE `caderneta`
  MODIFY `id_caderneta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `codigoverificacao`
--
ALTER TABLE `codigoverificacao`
  MODIFY `id_codigo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `doencas`
--
ALTER TABLE `doencas`
  MODIFY `id_doenca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `enfermeiros`
--
ALTER TABLE `enfermeiros`
  MODIFY `id_enfermeiro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id_mensagem` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id_paciente_data` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `postodesaude`
--
ALTER TABLE `postodesaude`
  MODIFY `id_posto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `postosaude`
--
ALTER TABLE `postosaude`
  MODIFY `id_posto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `recuperacaosenha`
--
ALTER TABLE `recuperacaosenha`
  MODIFY `id_recuperacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `usuarioprecadastro`
--
ALTER TABLE `usuarioprecadastro`
  MODIFY `id_pre_cadastro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `vacinamodelo`
--
ALTER TABLE `vacinamodelo`
  MODIFY `id_vacina_modelo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Restrições para tabelas `enfermeiros`
--
ALTER TABLE `enfermeiros`
  ADD CONSTRAINT `enfermeiros_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `enfermeiros_ibfk_2` FOREIGN KEY (`id_posto_saude`) REFERENCES `postosaude` (`id_posto`) ON DELETE SET NULL;

--
-- Restrições para tabelas `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `msg_atendente` FOREIGN KEY (`id_atendente`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL,
  ADD CONSTRAINT `msg_destinatario` FOREIGN KEY (`id_destinatario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `msg_posto` FOREIGN KEY (`id_posto_alvo`) REFERENCES `postosaude` (`id_posto`) ON DELETE SET NULL,
  ADD CONSTRAINT `msg_remetente` FOREIGN KEY (`id_remetente`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `pacientes_ibfk_2` FOREIGN KEY (`id_posto_saude`) REFERENCES `postosaude` (`id_posto`) ON DELETE SET NULL;

--
-- Restrições para tabelas `paciente_doencas`
--
ALTER TABLE `paciente_doencas`
  ADD CONSTRAINT `paciente_doencas_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `paciente_doencas_ibfk_2` FOREIGN KEY (`id_doenca`) REFERENCES `doencas` (`id_doenca`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_posto`) REFERENCES `postosaude` (`id_posto`) ON DELETE SET NULL;

--
-- Restrições para tabelas `vacinamodelo`
--
ALTER TABLE `vacinamodelo`
  ADD CONSTRAINT `fk_proxima_vacina` FOREIGN KEY (`id_proxima_vacina`) REFERENCES `vacinamodelo` (`id_vacina_modelo`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
