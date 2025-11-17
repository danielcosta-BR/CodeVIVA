<?php
// Define a função permitida (acesso universal)
// $funcao_permitida = ['paciente', 'enfermeiro', 'administrador'];
$funcao_permitida = 'administrador';
// Inclui o script de verificação
include '../verificar_acesso.php'; 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Configurações</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleprofile.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../../styleadm.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
    <!-- <link rel='stylesheet' type='text/css' media='screen' href='../../form.css'> -->
</head>
<body>

    <?php 
        include 'header.php'; 
    ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>🛠️ Configurações</h2>
                
                <p>Aqui você pode atualizar seus dados cadastrais.</p>

                <form action="processa_configuracoes.php" method="POST">
                    
                    <!-- <h4>Dados Pessoais</h4>
                    <div class="input-group">
                        <label for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00">
                    </div> -->

                    <h4>LOCAL DE ATENDIMENTO:</h4>
                    <div class="input-group">
                        <label for="posto_saude">Posto de Saúde</label>
                        <select id="posto_saude" name="posto_saude">
                            <option value="" disabled selected>Selecione seu posto</option>
                            </select>
                    </div>

                    <button type="submit" class="submit-btn">Salvar Alterações</button>
                </form>
                
            </div>
        </section>
    </main>
    
    <?php 
        include '../modal_logout.html'; 
    ?>
    <script src="../modal.js"></script>
</body>
</html>