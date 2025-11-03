<?php
// processa_verificacao_enfermeiro.php (NA PASTA RAIZ)

// Inclui a configuração de sessão, embora não a use diretamente para login,
// é boa prática para controle de fluxo.
session_start(); 

// -----------------------------------------------------
// 1. CONFIGURAÇÃO DO BANCO DE DADOS
// -----------------------------------------------------

$host = 'localhost';
$db   = 'viva_db'; 
$user = 'root';   
$pass = 'b@N¢0_|)Ad05';       

try {
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
     // Em caso de falha na conexão, redireciona de volta
     header('Location: verificacao_enfermeiro.html?erro=falha_sistema');
     exit;
}

// -----------------------------------------------------
// 2. RECEBIMENTO E SANITIZAÇÃO DE DADOS
// -----------------------------------------------------

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
// O código deve ser em letras maiúsculas para correspondência (assumindo que foi gerado em maiúsculas)
$codigo_digitado = strtoupper(filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_SPECIAL_CHARS)); 

if (!$email || empty($codigo_digitado)) {
    header('Location: verificacao_enfermeiro.html?erro=campos_vazios');
    exit;
}

// -----------------------------------------------------
// 3. INÍCIO DA TRANSAÇÃO
// -----------------------------------------------------

// Usamos transação para garantir que TODAS as operações (SELECT, INSERT, UPDATE, DELETE)
// sejam bem-sucedidas. Se uma falhar, todas são desfeitas (ROLLBACK).
$pdo->beginTransaction();

try {
    // -----------------------------------------------------
    // 4. VERIFICAÇÃO DO CÓDIGO
    // -----------------------------------------------------
    
    $stmt_codigo = $pdo->prepare("SELECT id_codigo, usado FROM CodigoVerificacao WHERE codigo = ? AND funcao_alvo = 'enfermeiro' LIMIT 1");
    $stmt_codigo->execute([$codigo_digitado]);
    $codigo_info = $stmt_codigo->fetch(PDO::FETCH_ASSOC);

    if (!$codigo_info || $codigo_info['usado']) {
        // Código inválido, expirado ou já usado
        $pdo->rollBack();
        header('Location: verificacao_enfermeiro.html?erro=codigo_invalido');
        exit;
    }
    
    $id_codigo = $codigo_info['id_codigo'];

    // -----------------------------------------------------
    // 5. BUSCA DADOS DO PRÉ-CADASTRO
    // -----------------------------------------------------
    
    $stmt_pre = $pdo->prepare("SELECT id_pre_cadastro, nome_completo, senha_hash FROM UsuarioPreCadastro WHERE email = ? LIMIT 1");
    $stmt_pre->execute([$email]);
    $pre_cadastro = $stmt_pre->fetch(PDO::FETCH_ASSOC);

    if (!$pre_cadastro) {
        // E-mail não encontrado no pré-cadastro (ou já cadastrado)
        $pdo->rollBack();
        header('Location: verificacao_enfermeiro.html?erro=email_nao_encontrado');
        exit;
    }
    
    // -----------------------------------------------------
    // 6. INSERÇÃO FINAL NA TABELA 'USUARIO'
    // -----------------------------------------------------
    
    $stmt_insert = $pdo->prepare("
        INSERT INTO Usuario (nome_completo, email, senha, funcao, id_posto) 
        VALUES (?, ?, ?, 'enfermeiro', NULL)
    ");
    // Nota: Enfermeiro tem id_posto NULL na inserção e será vinculado depois por um Admin.
    $stmt_insert->execute([$pre_cadastro['nome_completo'], $email, $pre_cadastro['senha_hash']]);

    // -----------------------------------------------------
    // 7. ATUALIZAÇÃO E LIMPEZA
    // -----------------------------------------------------
    
    // Marca o código de verificação como USADO
    $stmt_update_codigo = $pdo->prepare("UPDATE CodigoVerificacao SET usado = 1, data_uso = NOW() WHERE id_codigo = ?");
    $stmt_update_codigo->execute([$id_codigo]);
    
    // Deleta o registro do pré-cadastro (limpeza)
    $stmt_delete_pre = $pdo->prepare("DELETE FROM UsuarioPreCadastro WHERE id_pre_cadastro = ?");
    $stmt_delete_pre->execute([$pre_cadastro['id_pre_cadastro']]);

    // -----------------------------------------------------
    // 8. FINALIZAÇÃO DA TRANSAÇÃO
    // -----------------------------------------------------
    
    $pdo->commit();

    // Sucesso! Redireciona para o login
    header('Location: login.html?status=enfermeiro_verificado');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack(); // Desfaz todas as operações em caso de erro
    // Em ambientes de desenvolvimento: echo "Erro: " . $e->getMessage();
    header('Location: verificacao_enfermeiro.html?erro=falha_processamento');
    exit;
}
?>