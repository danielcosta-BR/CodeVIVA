<header>
    <div class="cabeca">
        <div><img class="logo" src="../Img/Logo 2.0 color.png" alt="Logo VIVA+"></div>
        
        <div class="buttons buttonsR profile-menu-container">
            <button id="profile-btn" class="profile-icon">
                <?php echo strtoupper(substr($_SESSION['nome_completo'], 0, 1)); ?> 
            </button>
            
            <div id="profile-dropdown" class="dropdown-content">
                <a href="adm/perfil.php">Perfil</a>
                <a href="adm/configuracoes.php">Configurações</a>
                <a id="logout-trigger" href="#">Sair</a>
            </div>
        </div>
    </div>
</header>