<?php
// Define a função permitida para esta página
$funcao_permitida = 'paciente';
// Inclui o script de verificação
include 'verificar_acesso.php'; 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Painel do Paciente</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../style.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='paciente.css'>
    <!-- <link rel='stylesheet' type='text/css' media='screen' href='../form.css'> -->
     <script src="../login.js"></script>
</head>
<body>

    <header>
        <div class="cabeca">
            <div><img class="logo" src="../Img/Logo 2.0 color.png"></div>
            <div class="buttons buttonsR">
                <a class="btn2" href="../logout.php">Sair</a>
            </div>
        </div>
    </header>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>👋 Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome_completo']); ?>!</h2>
                <h3>PACIENTE</h3>
                <!-- <p>Precisamos que você insira o número de seu cartão de CPF.
                Também precisamos saber qual posto você costuma ir:</p> -->
                <p>Por favor, antes de visualizar sua cardeneta, <a>clique</a> no botão abaixo para configurar 
                seu local de atendimento e outros dados para validarmos suas vacinas de forma correta.</p>

                <div class="form-section">
                    <button onclick="alert('Funcionalidade ainda não implementada!')">
                        Configurações
                    </button>
                </div>
            
                <!-- <form action="">
                    <div class="input-group">
                        <label for="cpf">CPF</label>
                        <input autocomplete="off" type="text" id="cpf" name="cpf" required maxlength="14" placeholder="000.000.000-00">
                    </div>
                    <div class="input-group">
                        <label for="posto_saude">Posto de Saúde</label>
                        <select id="posto_saude" name="posto_saude" required>
                            <option value="" disabled selected>Seu postinho de saúde</option>
                            <option value="posto1">Posto1</option>
                            <option value="posto2">Posto2</option>
                            <option value="posto3">Posto3</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="submit-btn">Próximo</button>
                </form> -->
            </div>
        </section>

        </main>

    <script src='paciente.js'></script>

</body>
</html>