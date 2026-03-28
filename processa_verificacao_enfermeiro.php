<?php
// processa_verificacao_enfermeiro.php (NA PASTA RAIZ)

session_start(); 

// Configurações do Banco (ajuste se necessário)
$host = 'localhost';
$db = 'viva_db'; 
$user = 'root';  
$pass = 'b@N¢0_|)Ad05'; 

try {
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
     header('Location: verificacao_enfermeiro.php?erro=falha_sistema');
     exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$codigo_digitado = strtoupper(filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_SPECIAL_CHARS)); 

if (!$email || empty($codigo_digitado)) {
     header('Location: verificacao_enfermeiro.php?erro=campos_vazios');
     exit;
}

$pdo->beginTransaction();

try {
     // 1. Verifica Código
     $stmt_codigo = $pdo->prepare("SELECT id_codigo, usado FROM CodigoVerificacao WHERE codigo = ? AND funcao_alvo = 'enfermeiro' LIMIT 1");
     $stmt_codigo->execute([$codigo_digitado]);
     $codigo_info = $stmt_codigo->fetch(PDO::FETCH_ASSOC);

     if (!$codigo_info || $codigo_info['usado']) {
          $pdo->rollBack();
          header('Location: verificacao_enfermeiro.php?erro=codigo_invalido');
          exit;
     }

     // 2. Busca TODOS os dados do Pré-Cadastro
     // IMPORTANTE: Estamos buscando cpf, telefone, endereco e id_posto aqui!
     $stmt_pre = $pdo->prepare("SELECT id_pre_cadastro, nome_completo, senha_hash, cpf, telefone, endereco, id_posto FROM UsuarioPreCadastro WHERE email = ? LIMIT 1");
     $stmt_pre->execute([$email]);
     $pre_cadastro = $stmt_pre->fetch(PDO::FETCH_ASSOC);

     if (!$pre_cadastro) {
          $pdo->rollBack();
          header('Location: verificacao_enfermeiro.php?erro=email_nao_encontrado');
          exit;
     }

     // 3. Insere na tabela USUARIO
     $stmt_insert = $pdo->prepare("INSERT INTO Usuario (nome_completo, email, senha, funcao) VALUES (?, ?, ?, 'enfermeiro')");
     $stmt_insert->execute([$pre_cadastro['nome_completo'], $email, $pre_cadastro['senha_hash']]);
     
     $id_novo_usuario = $pdo->lastInsertId();

     // 4. Insere na tabela ENFERMEIROS (AQUI ESTÁ A CORREÇÃO CRUCIAL)
     // Vincula o novo usuário ao posto e dados pessoais que vieram do pré-cadastro
     $stmt_enf = $pdo->prepare("INSERT INTO enfermeiros (id_usuario, id_posto_saude, cpf, telefone, endereco) VALUES (?, ?, ?, ?, ?)");
     $stmt_enf->execute([
          $id_novo_usuario,
          $pre_cadastro['id_posto'], // ID do Posto (obrigatório)
          $pre_cadastro['cpf'],
          $pre_cadastro['telefone'],
          $pre_cadastro['endereco']
     ]);

     // 5. Atualiza Código e Remove Pré-Cadastro
     $pdo->prepare("UPDATE CodigoVerificacao SET usado = 1, data_uso = NOW() WHERE id_codigo = ?")->execute([$codigo_info['id_codigo']]);
     $pdo->prepare("DELETE FROM UsuarioPreCadastro WHERE id_pre_cadastro = ?")->execute([$pre_cadastro['id_pre_cadastro']]);

     $pdo->commit();

     // 6. Auto-Login
     $_SESSION['id_usuario'] = $id_novo_usuario;
     $_SESSION['nome_completo'] = $pre_cadastro['nome_completo'];
     $_SESSION['email'] = $email;
     $_SESSION['funcao'] = 'enfermeiro';

     // Redireciona direto para o painel
     header('Location: Usuários/enfermeiro.php');
     exit;

} catch (PDOException $e) {
     if ($pdo->inTransaction()) $pdo->rollBack();
     header('Location: verificacao_enfermeiro.php?erro=falha_processamento');
     exit;
}
?>