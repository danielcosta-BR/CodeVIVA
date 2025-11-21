<?php
// Usuarios/header_enf.php

// Verificamos se o arquivo atual está dentro da pasta 'enf' para ajustar os caminhos
// Se o script atual contiver '/enf/' no caminho, estamos na subpasta.
$in_subfolder = (strpos($_SERVER['SCRIPT_NAME'], '/enf/') !== false);

// Define os prefixos
$path_raiz = $in_subfolder ? '../' : '';      // Volta para Usuarios/
$path_enf  = $in_subfolder ? '' : 'enf/';     // Entra em enf/
$path_img  = $in_subfolder ? '../../Img/' : '../Img/'; // Volta para raiz do site e entra em Img/

// Define o link de logout
$link_logout = $path_raiz . 'logout.php';

// Inicial da foto
$inicial = strtoupper(substr($_SESSION['nome_completo'] ?? 'E', 0, 1));
?>

<header>
    <div class="cabeca">
        <div><img class="logo" src="<?php echo $path_img; ?>Logo 2.0 color.png" alt="Logo VIVA+"></div>
        
        <div class="buttons buttonsR profile-menu-container">
            <button id="profile-btn" class="profile-icon">
                <?php echo $inicial; ?> 
            </button>
            
            <div id="profile-dropdown" class="dropdown-content">
                <!-- Link Início sempre leva para enfermeiro.php na pasta Usuarios -->
                <a href="<?php echo $path_raiz; ?>enfermeiro.php">Início</a>
                
                <!-- Links internos da pasta enf -->
                <a href="<?php echo $path_enf; ?>perfil_enf.php">Perfil</a>
                <a href="<?php echo $path_enf; ?>configuracoes_enf.php">Configurações</a>
                
                <a id="logout-trigger" href="<?php echo $link_logout; ?>">Sair</a>
            </div>
        </div>
    </div>
</header>