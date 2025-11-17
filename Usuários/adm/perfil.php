<?php
// Define a função permitida para esta página (acesso universal)
// $funcao_permitida = ['paciente', 'enfermeiro', 'administrador'];
$funcao_permitida = 'administrador';
// Inclui o script de verificação
include '../verificar_acesso.php'; 


$id_usuario = $_SESSION['id_usuario'] ?? '0';
$nome_completo = $_SESSION['nome_completo'] ?? 'Usuário Desconhecido';
$funcao = $_SESSION['funcao'] ?? 'Função Não Definida';
$email_usuario = $_SESSION['email'] ?? 'E-mail não disponível';

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Meu Perfil</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleprofile.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../../styleadm.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
</head>
<body>

    <?php 
        include 'header.php'; // Inclui o cabeçalho com o menu de perfil 
    ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2>👤 Meu Perfil</h2>
                
                <p>Aqui você verá suas informações básicas cadastradas.</p>
<<<<<<< HEAD
                <div>
                    
                </div>
=======
                <div class="profile-icon-2">
                    <?php echo $inicial_nome; ?> 
                </div>
                <h3>Dados de Acesso</h3>
>>>>>>> 3792aa76528efdcbae90934ab93bace73c363231
                <div class="data-display">
                    <p><strong>ID de Usuário:</strong> <?php echo htmlspecialchars($id_usuario); ?></p>
                    <p><strong>Nome Completo:</strong> <?php echo htmlspecialchars($nome_completo); ?></p>
                    <p><strong>Função:</strong> <?php echo ucfirst(htmlspecialchars($funcao)); ?></p>
                    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($email_usuario); ?></p>
                </div>

                <p style="margin-top: 30px;">
                    Para alterar seus dados, acesse a seção <b>Configurações</b>.
                </p>
                
            </div>
        </section>
    </main>
    
    <?php 
        include '../modal_logout.html'; // Inclui o modal de logout 
    ?>
    <script src="../modal.js"></script>
</body>
</html>