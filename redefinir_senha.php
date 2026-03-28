<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>VIVA+ | Redefinir Senha</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='style.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='form.css'>
    <script src='login.js'></script>
</head>
<body>
    <header>
        <div class="cabeca">
            <div><img class="logo" src="Img/Logo 2.0 color.png"></div>
            <div class="buttons buttonsR">
                <a class="btn1" href="index.html">Início</a><div class="linhaV"></div>
                <a class="btn2" href="login.html">Entrar</a>
            </div>
        </div>
    </header>
    
    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>Redefinir Senha</h2>
                <p class="instruction-text">
                    Por favor, digite sua nova senha.
                </p>
                
                <?php
                // INÍCIO DO CÓDIGO PHP
                
                // 1. Recebe os parâmetros da URL
                $email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
                $token = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '';
                $erro = isset($_GET['erro']) ? htmlspecialchars($_GET['erro']) : '';

                // 2. Exibe mensagens de erro (enviadas pelo processa_redefinicao.php)
                if ($erro) {
                    $mensagem_erro = '';
                    if ($erro === 'senhas_diferentes') {
                        $mensagem_erro = 'As senhas digitadas não coincidem. Tente novamente.';
                    } elseif ($erro === 'dados_incompletos') {
                        $mensagem_erro = 'Por favor, preencha ambos os campos de senha.';
                    } elseif ($erro === 'falha_redefinicao') {
                        $mensagem_erro = 'Erro interno ao salvar a nova senha. Tente novamente.';
                    }
                    
                    if ($mensagem_erro) {
                        // Estilização básica para a mensagem de erro (você pode customizar com CSS)
                        echo '<p style="color: red; background-color: #ffeaea; padding: 10px; border-radius: 5px;">' . $mensagem_erro . '</p>';
                    }
                }

                // 3. Validação de segurança básica: Se não houver email OU token na URL, mostra erro.
                if (empty($email) || empty($token)) {
                    // Se o token ou email estiver faltando, não permite o formulário.
                    echo '<p class="form-link" style="color: red;">Link de redefinição inválido ou incompleto. Por favor, solicite a redefinição novamente.</p>';
                    echo '<p class="form-link"><a href="forgot-password.html">Voltar para a Recuperação</a></p>';
                    // O exit aqui impede que o formulário seja exibido abaixo.
                    exit; 
                }
                
                // FIM DO CÓDIGO PHP
                ?>

                <form action="processa_redefinicao.php" method="POST">
                    <input type="hidden" name="email" value="<?php echo $email; ?>">
                    <input type="hidden" name="token" value="<?php echo $token; ?>">
                    
                    <div class="input-group">
                        <label for="nova_senha">Nova Senha</label>
                        <div class="input-senha-container">
                            <input type="password" id="nova_senha" name="nova_senha" required>
                            <span class="toggle-password" onclick="togglePasswordVisibility('nova_senha', this)">👁️</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="confirma_senha">Confirmar Nova Senha</label>
                        <div class="input-senha-container">
                            <input type="password" id="confirma_senha" name="confirma_senha" required>
                            <span class="toggle-password" onclick="togglePasswordVisibility('confirma_senha', this)">👁️</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">Salvar Nova Senha</button>
                </form>

                <p class="form-link">Voltar para o <a href="login.html">Login</a></p>
            </div>
        </section>
    </main>
    
</body>
</html>