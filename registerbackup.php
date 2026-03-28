<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Cadastro Completo</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    
    <!-- Linkando os CSS existentes -->
    <link rel='stylesheet' href='style.css'>
    <link rel='stylesheet' href='form.css'>
    
    <style>
        /* Estilos do Wizard (Etapas) */
        .step-container { display: none; animation: fadeIn 0.4s; }
        .step-container.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
            background: #fff;
        }
        .checkbox-item { display: flex; align-items: center; gap: 8px; }
        .checkbox-item label { font-weight: normal; cursor: pointer; margin: 0; }
        
        .termos-container { margin: 20px 0; font-size: 0.9em; display: flex; align-items: center; gap: 8px; }
        
        /* Botões de navegação */
        .nav-buttons { display: flex; gap: 10px; margin-top: 20px; }
        .btn-voltar { background-color: #ccc; color: #333; border: none; padding: 10px; border-radius: 5px; cursor: pointer; width: 30%; font-weight: bold; }
        .btn-voltar:hover { background-color: #bbb; }
    </style>
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
                
                <!-- Exibição de Erros via PHP/GET -->
                <?php if(isset($_GET['erro'])): ?>
                    <p class="msg-erro" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; text-align: center;">
                        <?php 
                            $e = $_GET['erro'];
                            if($e=='campos_vazios') echo "Preencha todos os campos.";
                            elseif($e=='senhas_nao_coincidem') echo "As senhas não conferem.";
                            elseif($e=='email_ja_existe') echo "E-mail já cadastrado.";
                            else echo "Ocorreu um erro no cadastro.";
                        ?>
                    </p>
                <?php endif; ?>

                <form id="cadastroForm" action="processa_cadastro.php" method="POST">
                    
                    <!-- ETAPA 1: Dados de Acesso -->
                    <div id="step1" class="step-container active">
                        <h3>Passo 1: Dados de Acesso</h3>
                        
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
                        </div>
                        <div class="input-group">
                            <label for="confirma_senha">Confirmar Senha</label>
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
                        <h3>Passo 2: Dados Pessoais</h3>
                        
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
                            <p id="msg-erro-posto" style="color:red; font-size: 0.8em; display:none;">Erro ao carregar postos. Recarregue a página.</p>
                        </div>

                        <!-- SEÇÃO EXCLUSIVA DE PACIENTE (DOENÇAS) -->
                        <div id="secao-doencas" style="display:none; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                            <h4>Condições de Saúde</h4>
                            <p style="font-size:0.9em; color:#666;">Selecione suas condições para receber alertas. Se não tiver, marque "Nenhuma".</p>
                            
                            <div class="checkbox-group" id="lista-doencas">
                                <p>Carregando doenças...</p>
                            </div>
                        </div>

                        <div class="termos-container">
                            <input type="checkbox" id="termos" name="termos" required>
                            <label for="termos">Li e estou de acordo com o <a href="#">Termo de Uso</a> e <a href="#">Política de Privacidade</a>.</label>
                        </div>

                        <div class="nav-buttons">
                            <button type="button" class="btn-voltar" onclick="goToStep1()">Voltar</button>
                            <button type="submit" class="submit-btn" style="width:70%;">Concluir Cadastro</button>
                        </div>
                    </div>
                </form>
                
                <p class="form-link" style="text-align:center; margin-top:15px;">Já tem uma conta? <a href="login.html">Faça Login</a></p>
            </div>
        </section>
    </main>

    <!-- Script JS -->
    <script src="register.js"></script>
</body>
</html>