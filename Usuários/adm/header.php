<?php
// Usuários/adm/header.php

// 1. Garante que a sessão está iniciada para acessar $_SESSION['funcao']
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inicializa a variável para a função (protege contra chaves indefinidas)
$funcao_usuario = $_SESSION['funcao'] ?? 'paciente'; // Padrão para paciente se não houver função

// 2. Define o link inicial (Dashboard) com base na função
$link_inicial = '../paciente.php'; // Padrão

if ($funcao_usuario === 'administrador') {
    // Sai da pasta adm/ (..) e vai para o arquivo do administrador
    $link_inicial = '../administrador.php'; 
} elseif ($funcao_usuario === 'enfermeiro') {
    // Sai da pasta adm/ (..) e vai para o arquivo do enfermeiro
    $link_inicial = '../enfermeiro.php'; 
} else {
    // paciente (ou qualquer outra função que caia no padrão)
    $link_inicial = '../paciente.php';
}

// Para evitar erro no substr caso nome_completo não esteja definido (embora já tenhamos corrigido no login)
$inicial_nome = strtoupper(substr($_SESSION['nome_completo'] ?? 'U', 0, 1));
?>
<header>
    <div class="cabeca">
        <div><img class="logo" src="../../Img/Logo 2.0 color.png" alt="Logo VIVA+"></div>
        
        <div class="buttons buttonsR profile-menu-container">
            <button id="profile-btn" class="profile-icon">
                <?php echo $inicial_nome; ?> 
            </button>
            
            <div id="profile-dropdown" class="dropdown-content">
                <a href="<?php echo htmlspecialchars($link_inicial); ?>">Início</a>
                <a href="perfil.php">Perfil</a>
                <a href="configuracoes.php">Configurações</a>
                <a id="logout-trigger" href="../logout.php">Sair</a>
            </div>
        </div>
    </div>
</header>