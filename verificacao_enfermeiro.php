<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Verificação de Enfermeiro</title>
    <link rel='stylesheet' type='text/css' media='screen' href='verificacao.css'>

</head>
<body>

    <div class="container">
        <img class="logo" src="Img/Logo 2.0 color.png" alt="Logo VIVA+">
        
        <h2>Verificação de Acesso</h2>
        <p>Insira seu e-mail de pré-cadastro e o código de verificação fornecido pelo Administrador para finalizar seu registro como Enfermeiro.</p>

        <?php
        // Usa o bloco PHP para verificar parâmetros de erro na URL
        if (isset($_GET['erro'])) {
            $erro_msg = '';
            $erro_param = $_GET['erro'];

            if ($erro_param === 'codigo_invalido') {
                $erro_msg = 'Código de verificação inválido, expirado ou já utilizado. Verifique o código e tente novamente.';
            } elseif ($erro_param === 'email_nao_encontrado') {
                $erro_msg = 'E-mail não encontrado no pré-cadastro. Verifique se o e-mail está correto.';
            } elseif ($erro_param === 'falha_processamento') {
                $erro_msg = 'Falha no processamento. Tente novamente mais tarde.';
            } else {
                $erro_msg = 'Ocorreu um erro. Tente novamente.';
            }
            echo "<div class='message-box error'><strong>ERRO:</strong> {$erro_msg}</div>";
        }
        ?>

        <form action="processa_verificacao_enfermeiro.php" method="POST">
            
            <label for="email">E-mail de Pré-cadastro</label>
            <input type="email" id="email" name="email" required placeholder="seu.email@exemplo.com">
            
            <label for="codigo">Código de Verificação</label>
            <input autocomplete="off" type="text" id="codigo" name="codigo" required maxlength="10" placeholder="Ex: ABC123XYZ">
            
            <button type="submit">Finalizar Cadastro</button>
            
        </form>

    </div>

</body>
</html>