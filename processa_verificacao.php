<?php
// Inclua o arquivo de conexão com o banco de dados.
// Exemplo: require_once 'conexao.php';

// Simulação de conexão (substitua pelo seu código de conexão real)
$host = 'localhost';
$db   = 'viva_db';
$user = 'root';
$pass = 'b@N¢0_|)Ad05';

try {
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
     die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// 1. Receber e sanitizar dados
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$codigo_enviado = filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$email || !$codigo_enviado) {
    header('Location: verificacao_enfermeiro.html?erro=dados_incompletos');
    exit;
}

// Inicia uma transação para garantir que ambas as operações (inserção final e marcação de código) sejam bem-sucedidas ou falhem juntas.
$pdo->beginTransaction();

try {
    // 2. Buscar o Código de Verificação (deve existir, não estar usado e ser para enfermeiro)
    $stmt_codigo = $pdo->prepare("SELECT id_codigo FROM CodigoVerificacao WHERE codigo = ? AND usado = 0 AND funcao_alvo = 'enfermeiro' LIMIT 1");
    $stmt_codigo->execute([$codigo_enviado]);
    $codigo_db = $stmt_codigo->fetch(PDO::FETCH_ASSOC);

    // 3. Buscar os dados de Pré-Cadastro
    $stmt_pre = $pdo->prepare("SELECT id_pre_cadastro, nome_completo, email, senha_hash FROM UsuarioPreCadastro WHERE email = ? LIMIT 1");
    $stmt_pre->execute([$email]);
    $pre_usuario = $stmt_pre->fetch(PDO::FETCH_ASSOC);

    if ($codigo_db && $pre_usuario) {
        // SUCESSO: O código é válido e o pré-cadastro existe.
        
        // 4. Inserir o Usuário na Tabela FINAL (Usuario)
        // Assume id_posto NULL por padrão, será configurado depois
        $stmt_final = $pdo->prepare("INSERT INTO Usuario (nome_completo, email, senha, funcao, id_posto) VALUES (?, ?, ?, 'enfermeiro', NULL)");
        $stmt_final->execute([$pre_usuario['nome_completo'], $pre_usuario['email'], $pre_usuario['senha_hash']]);
        
        // 5. Marcar o código como USADO
        $stmt_uso = $pdo->prepare("UPDATE CodigoVerificacao SET usado = 1, data_uso = NOW() WHERE id_codigo = ?");
        $stmt_uso->execute([$codigo_db['id_codigo']]);
        
        // 6. Remover o registro temporário
        $stmt_del = $pdo->prepare("DELETE FROM UsuarioPreCadastro WHERE id_pre_cadastro = ?");
        $stmt_del->execute([$pre_usuario['id_pre_cadastro']]);
        
        // Finaliza a transação
        $pdo->commit();
        
        // Redireciona com sucesso!
        header('Location: login.html?status=cadastro_enfermeiro_finalizado');
        exit;

    } else {
        // FALHA: Código inválido ou e-mail não encontrado/não corresponde.
        $pdo->rollBack();
        header('Location: verificacao_enfermeiro.html?email=' . urlencode($email) . '&erro=codigo_ou_email_invalido');
        exit;
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erro durante a finalização do cadastro: " . $e->getMessage());
}
?>