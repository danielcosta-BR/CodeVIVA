<?php
// Usuarios/verificar_acesso.php

session_start();

// Verifica se o usuário não está logado
if (!isset($_SESSION['id_usuario'])) {
    // Redireciona para o login na pasta raiz
    header('Location: ../login.html?erro=acesso_negado');
    exit;
}

// A variável $funcao_permitida será definida no arquivo que incluir este script.
if (!isset($funcao_permitida) || $_SESSION['funcao'] !== $funcao_permitida) {
    // Redireciona para a própria página de login com erro de permissão
    header('Location: ../login.html?erro=permissao_negada');
    exit;
}

// Se chegou até aqui, o usuário está logado e tem a função correta.
?>