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
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleprofile.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../../styleadm.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <?php 
        include 'header.php'; // Inclui o cabeçalho com o menu de perfil 
    ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2><i class="fas fa-user-circle"></i> Meu Perfil</h2>
                <div class="title_function" style="padding: 10px;
                            background-color: #5ab4c086;
                            text-align: center;
                            border-radius: 5px;">
                    <h3>ADMINISTRADOR</h3>
                </div>
                <p>Aqui você verá suas informações básicas cadastradas.</p>
                <div class="profile-icon-2">
                    <?php echo $inicial_nome; ?> 
                </div>
                <h3>Dados de Acesso</h3>
                <div class="data-display">
                    <p><strong><i class="fas fa-id-badge"></i> ID de Usuário:</strong> <?php echo htmlspecialchars($id_usuario); ?></p>
                    <p><strong><i class="fas fa-user-circle"></i> Nome Completo:</strong> <?php echo htmlspecialchars($nome_completo); ?></p>
                    <p><strong><i class="fas fa-at"></i> E-mail:</strong> <?php echo htmlspecialchars($email_usuario); ?></p>
                    <p><strong><i class="fas fa-user-tag"></i> Função:</strong> <?php echo ucfirst(htmlspecialchars($funcao)); ?></p>
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