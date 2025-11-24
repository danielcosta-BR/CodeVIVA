<?php
// ... (seu código de session_start() e includes existentes, se houver)

// Adicionar a inclusão da conexão
include 'Usuários/conexao.php'; // Ajuste o caminho se necessário

// ----------------------------------------------------
// 1. LÓGICA PARA BUSCAR DOENÇAS
// ----------------------------------------------------
$doencas = [];
// Seleciona todas as doenças ordenadas por nome
$sql_doencas = "SELECT id_doenca, nome_doenca FROM doencas ORDER BY nome_doenca ASC";
$res_doencas = $conn->query($sql_doencas);

if ($res_doencas && $res_doencas->num_rows > 0) {
    while ($row = $res_doencas->fetch_assoc()) {
        $doencas[] = $row;
    }
}
$conn->close(); // Fecha a conexão
?>

<!DOCTYPE html>
<html lang="pt-br">
    <style>
        /* Adicione estes estilos para o novo modal dentro da tag <style> do register.php 
           ou mova-os para um arquivo CSS (ex: form.css ou modal.css)
        */
        .modal-logout {
            display: none; 
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6); 
            padding-top: 50px;
        }

        .modal-content-logout {
            background-color: #fefefe;
            margin: 5% auto; 
            padding: 30px;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .lista-doencas-grid {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .doenca-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .doenca-item:last-child {
            border-bottom: none;
        }

        .doenca-item label {
            margin-left: 10px;
            cursor: pointer;
            font-weight: normal;
        }

        .doenca-item input[type="checkbox"] {
            transform: scale(1.2);
            cursor: pointer;
        }
        
        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }

        .btn-secundario {
            /* Cor verde para o botão de selecionar */
            padding: 10px;
            background-color: rgb(173, 204, 216);
            color: black;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-secundario:hover {
            background-color: rgb(140, 180, 195);
            transform: scale(1.01); 
        }
    </style>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Cadastro Completo</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    
    <!-- Linkando os CSS existentes -->
    <link rel='stylesheet' href='style.css'>
    <link rel='stylesheet' href='form.css'>

</head>
<body>
    <header>
        <div class="cabeca">
            <div><img class="logo" src="Img/Logo 2.0 color.png" alt="VIVA+"></div>
            <div class="buttons buttonsR">
                <a class="btn1" href="index.html">Início</a><div class="linhaV"></div>
                <a class="btn2" href="login.html">Entrar</a>
            </div>
        </div>
    </header>
    
    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>Crie sua conta VIVA+</h2>
                
                <!-- 1. SEÇÃO DE MENSAGEM DE PRÉ-CADASTRO EXISTENTE (O RESGATE) -->
                <?php if(isset($_GET['erro'])): ?>
                    <div class="msg-erro">
                        <?php 
                            if($_GET['erro'] == 'email_ja_existe') echo 'Este e-mail já está cadastrado. Por favor, faça login ou use outro e-mail.';
                            elseif($_GET['erro'] == 'falha_db') echo 'Ocorreu um erro inesperado ao tentar cadastrar. Tente novamente mais tarde.';
                            // NOVO: Mensagem de erro para senha curta
                            elseif($_GET['erro'] == 'senha_curta') echo 'A senha deve ter no mínimo 8 caracteres.';
                            else echo 'Ocorreu um erro no cadastro.';
                        ?>
                    </div>
                <?php endif; ?>

                <!-- 2. SEÇÃO DE ERROS PADRÃO -->
                <?php if(isset($_GET['erro'])): ?>
                    <p class="msg-erro" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px;">
                        <?php 
                            $e = $_GET['erro'];
                            if($e=='campos_vazios') echo "Preencha todos os campos obrigatórios.";
                            elseif($e=='senhas_nao_coincidem') echo "As senhas digitadas não conferem.";
                            elseif($e=='email_ja_existe') echo "Este e-mail já está cadastrado no sistema.";
                            else echo "Ocorreu um erro no processamento do cadastro.";
                        ?>
                    </p>
                <?php endif; ?>

                <?php 
                    if(isset($_GET['status']) && $_GET['status'] == 'ja_cadastrado' && isset($_GET['email'])): 
                        $email_recuperacao = htmlspecialchars($_GET['email']); // Proteção contra XSS
                ?>
                    <div class="msg-alerta-recuperacao">
                        <p>⚠️ <b>Atenção!</b> O e-mail <b><?php echo $email_recuperacao; ?></b> já possui um pré-cadastro de Enfermeiro em nosso sistema.</p>
                        <p>Você não pode se cadastrar novamente. Por favor, utilize o código de verificação para concluir seu registro.</p>
                        <p>
                            <a href="verificacao_enfermeiro.php?email=<?php echo urlencode($email_recuperacao); ?>" class="link-recuperacao" style="font-weight: bold; color: #007bff; text-decoration: underline;">
                                Clique aqui para ir para a tela de Verificação
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <form id="cadastroForm" action="processa_cadastro.php" method="POST">
                    
                    <!-- ETAPA 1: Dados de Acesso -->
                    <div id="step1" class="step-container active">                        
                        <div class="input-group">
                            <label for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>
                        <div class="input-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="input-group">
                            <label for="senha">Senha</label>
                            <div class="input-senha-container">
                                <input type="password" id="senha" name="senha" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility('senha', this)">👁️</span>
                            </div>
                            <p class="hint" style="font-size: 12px; color: #666; margin-top: 5px;">Mínimo de 8 caracteres.</p>
                        </div>

                        <div class="input-group">
                            <label for="confirma_senha">Confirme a Senha</label>
                            <div class="input-senha-container">
                                <input type="password" id="confirma_senha" name="confirma_senha" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility('confirma_senha', this)">👁️</span>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="funcao_usuario">Função</label>
                            <select id="funcao_usuario" name="funcao_usuario" required>
                                <option value="" disabled selected>Selecione...</option>
                                <option value="paciente">Paciente</option>
                                <option value="enfermeiro">Enfermeiro</option>
                            </select>
                        </div>
                        
                        <button type="button" class="submit-btn" onclick="goToStep2()">
                            Próximo <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>

                    <!-- ETAPA 2: Dados Pessoais e Configuração -->
                    <div id="step2" class="step-container">                        
                        <div class="input-group">
                            <label for="cpf">CPF</label>
                            <input type="text" id="cpf" name="cpf" required placeholder="000.000.000-00" maxlength="14">
                        </div>
                        <div class="input-group">
                            <label for="telefone">Telefone</label>
                            <input type="text" id="telefone" name="telefone" required placeholder="(00) 00000-0000" maxlength="15">
                        </div>
                        <div class="input-group">
                            <label for="endereco">Endereço Completo</label>
                            <input type="text" id="endereco" name="endereco" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="id_posto">Local de Atendimento (Posto de Saúde)</label>
                            <select id="id_posto" name="id_posto" required>
                                <option value="">Carregando postos...</option>
                            </select>
                            <p id="msg-erro-posto" style="color:red; font-size: 0.8em; display:none;">Erro ao carregar postos.</p>
                        </div>

                        <!-- SEÇÃO EXCLUSIVA DE PACIENTE (DOENÇAS) -->
                        <div class="input-group">
                            <label for="doencas">Doenças / Condições Existentes <span style="font-weight: normal; font-size: 0.9em; color: #555;">(Selecione ao menos uma ou 'Nenhuma')</span></label>
                            
                            <button type="button" class="btn-secundario" id="btn-modal-doencas" style="width: 100%; margin-top: 5px;" onclick="abrirModalDoencas()">
                                Selecionar Doenças (0 selecionadas)
                            </button>
                            <input type="hidden" name="doencas_selecionadas_count" id="doencas_selecionadas_count" value="0">
                        </div>

                        <div class="termos-container">
                            <input type="checkbox" id="termos" name="termos" required>
                            <label for="termos">
                                Li e estou de acordo com os 
                                <a href="#" style="display: inline; color: blue; text-decoration: underline;">
                                    Termos de Uso
                                </a> e a 
                                <a href="#" style="display: inline; color: blue; text-decoration: underline;">
                                    Política de Privacidade
                                </a>.
                            </label>
                        </div>

                        <div class="nav-buttons">
                            <button type="button" class="btn-voltar" onclick="goToStep1()">Voltar</button>
                            <button type="submit" class="submit-btn" style="width:70%;">Concluir Cadastro</button>
                        </div>
                    </div>
                </form>
                
                <p class="form-link" style="text-align:center; margin-top:15px;">Já tem uma conta? <a href="login.html">Faça Login</a></p>
            </div>



            <div id="modal-doencas" class="modal-logout" style="display: none;">
                <div class="modal-content-logout" style="max-width: 600px; height: auto;">
                    <h3>Selecione suas Condições de Saúde</h3>
                    
                    <p style="font-size: 0.9em; color: #666; margin-bottom: 15px;">Marque as opções que se aplicam. Se não houver, marque 'Nenhuma'.</p>
                    
                    <div id="lista-doencas" class="lista-doencas-grid">
                        <?php 
                        // Verifica se o array de doenças não está vazio
                        if (!empty($doencas)):
                            foreach ($doencas as $d): 
                                // Verifica se é a opção "Nenhuma" (pelo ID 1 ou pelo nome)
                                $is_nenhuma = ($d['id_doenca'] == 1 || strtolower($d['nome_doenca']) == 'nenhuma');
                                
                                // Define o ID e o evento onchange condicionalmente
                                $input_id = $is_nenhuma ? 'chk_nenhuma' : 'chk_doenca_' . $d['id_doenca'];
                                $onchange_val = $is_nenhuma ? 'Nenhuma' : htmlspecialchars($d['nome_doenca']);
                        ?>
                            <div class="checkbox-item">
                                <input type="checkbox" 
                                    id="<?php echo $input_id; ?>" 
                                    class="chk-doenca" 
                                    value="<?php echo $d['id_doenca']; ?>" 
                                    data-nome="<?php echo htmlspecialchars($d['nome_doenca']); ?>"
                                    onchange="gerenciarSelecaoDoencas(this, '<?php echo $onchange_val; ?>');"
                                >
                                <label for="<?php echo $input_id; ?>" style="font-weight: <?php echo $is_nenhuma ? 'bold' : 'normal'; ?>;">
                                    <?php echo htmlspecialchars($d['nome_doenca']); ?>
                                </label>
                            </div>
                        <?php 
                            endforeach; 
                        else:
                        ?>
                            <p style="padding: 10px; color: #666;">Nenhuma condição encontrada no sistema.</p>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 20px; text-align: center;">
                        <button type="button" id="btn-confirmar-selecao" class="submit-btn" style="width: 100%;">
                            Confirmar Seleção
                        </button>
                    </div>
                </div>
            </div>


        </section>
    </main>

    <!-- Script JS -->
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>
    <script src="register.js"></script>
</body>
</html>