<?php
include 'Usuários/conexao.php'; 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Cadastro Completo</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' href='style.css'>
    <link rel='stylesheet' href='form.css'>
    <link rel='stylesheet' href='Usuários/modal.css'> 

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
                
                <?php if(isset($_GET['erro'])): ?>
                    <p class="msg-erro" style="color: red; background: #ffe6e6; padding: 10px;">
                        <?php echo htmlspecialchars($_GET['erro']); ?>
                    </p>
                <?php endif; ?>

                <form id="cadastroForm" action="processa_cadastro.php" method="POST">
                    
                    <div id="step1" class="step-container active">                        
                        <div class="input-group">
                            <label>Nome Completo</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>
                        <div class="input-group">
                            <label>E-mail</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="input-group">
                            <label>Senha</label>
                            <div class="input-senha-container">
                                <input type="password" id="senha" name="senha" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility('senha', this)">👁️</span>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Confirme a Senha</label>
                            <div class="input-senha-container">
                                <input type="password" id="confirma_senha" name="confirma_senha" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility('confirma_senha', this)">👁️</span>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Função</label>
                            <select id="funcao_usuario" name="funcao_usuario" required>
                                <option value="" disabled selected>Selecione...</option>
                                <option value="paciente">Paciente</option>
                                <option value="enfermeiro">Enfermeiro</option>
                            </select>
                        </div>
                        <button type="button" class="submit-btn" onclick="goToStep2()">Próximo</button>
                    </div>

                    <div id="step2" class="step-container">                        
                        <div class="input-group">
                            <label>CPF</label>
                            <input type="text" id="cpf" name="cpf" required oninput="maskCPF(this)" maxlength="14">
                        </div>
                        <div class="input-group">
                            <label>Telefone</label>
                            <input type="text" id="telefone" name="telefone" required oninput="maskTelefone(this)" maxlength="15">
                        </div>
                        <div class="input-group">
                            <label>Endereço</label>
                            <input type="text" id="endereco" name="endereco" required>
                        </div>
                        <div class="input-group">
                            <label>Posto de Saúde</label>
                            <select id="id_posto" name="id_posto" required>
                                <option value="">Carregando...</option>
                            </select>
                        </div>

                        <div class="input-group secao-doencas" id="secao-doencas" style="display: none;">
                            <label>Condições de Saúde</label>
                            <button type="button" class="submit-btn" id="btn-modal-doencas" style="background-color: rgb(173, 204, 216)">
                                Selecionar Condições (0 selecionadas)
                            </button>
                        </div>

                        <div class="termos-container">
                            <input type="checkbox" id="termos" name="termos" required>
                            <label for="termos">Li e aceito os Termos.</label>
                        </div>

                        <div class="nav-buttons">
                            <button type="button" class="btn-voltar" onclick="goToStep1()">Voltar</button>
                            <button type="submit" class="submit-btn">Concluir Cadastro</button>
                        </div>
                    </div>
                </form>
                
                <p class="form-link" style="text-align:center; margin-top:15px;">Já tem uma conta? <a href="login.html">Faça Login</a></p>
            </div>
        </section>
    </main>

    <div id="modal-doencas" class="modal-logout" style="display: none;">
        <div class="modal-content-logout">
            <h3>Selecione suas Condições</h3>
            <p style="font-size:0.9em; color:#667;">Se não tiver condições, marque 'Nenhuma'.</p>
            
            <div id="lista-doencas" class="lista-doencas-grid"></div>

            <div style="margin-top: 15px; text-align: center;">
                <button type="button" id="btn-confirmar-selecao" class="submit-btn">Confirmar Seleção</button>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>
    <script src="register.js"></script>
</body>
</html>