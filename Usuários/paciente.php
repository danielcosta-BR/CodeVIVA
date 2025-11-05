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
                <p>Precisamos que você insira os dados de seus cartões de CPF e SUS.
                Também precisamos saber qual posto você costuma ir:</p>
            
                <form action="">
                    <div class="input-group">
                        <label for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" required>
                    </div>
                    <div class="input-group">
                        <label for="sus">SUS</label>
                        <input type="text" id="sus" name="sus" required>
                    </div>
                    <div class="input-group">
                        <label for="posto_saude">Posto de Saúde</label>
                        <select id="posto_saude" name="posto_saude">
                            <option value="" disabled selected>Seu postinho de saúde</option>
                            <option value="posto1">Posto1</option>
                            <option value="posto2">Posto2</option>
                            <option value="posto3">Posto3</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="submit-btn">Próximo</button>
                </form>
            </div>
        </section>

        </main>
</body>
</html>