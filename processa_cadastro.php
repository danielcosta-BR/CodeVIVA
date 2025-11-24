<?php
// processa_cadastro.php
session_start();
include 'Usuários/conexao.php'; 

// Habilita exceções para capturar erros de banco (Duplicate entry)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $funcao = $_POST['funcao_usuario'];

    // NOVO: Validação de Segurança da Senha (Mínimo de 8 caracteres)
    if (strlen($senha) < 8) {
        // Redireciona de volta com uma mensagem de erro
        header('Location: register.php?erro=senha_curta');
        exit;
    }

    // Dados Passo 2
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $endereco = trim($_POST['endereco']);
    $id_posto = (int)$_POST['id_posto'];
    $doencas = $_POST['doencas'] ?? [];

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // --- PACIENTE ---
    if ($funcao === 'paciente') {
        $conn->begin_transaction();
        try {
            // 1. Usuario
            $stmt_u = $conn->prepare("INSERT INTO usuario (nome_completo, email, senha, funcao) VALUES (?, ?, ?, 'paciente')");
            $stmt_u->bind_param("sss", $nome, $email, $senha_hash);
            $stmt_u->execute();
            $id_novo_usuario = $conn->insert_id;

            // 2. Pacientes
            $stmt_p = $conn->prepare("INSERT INTO pacientes (id_usuario, cpf, telefone, endereco, id_posto_saude) VALUES (?, ?, ?, ?, ?)");
            $stmt_p->bind_param("isssi", $id_novo_usuario, $cpf, $telefone, $endereco, $id_posto);
            $stmt_p->execute();

            // 3. Doenças
            // 3. Doenças (Loop para inserir)
            if (!empty($doencas)) {
                // Prepara a query para inserção de doenças
                $stmt_d = $conn->prepare("INSERT INTO paciente_doencas (id_paciente, id_doenca) VALUES (?, ?)");
                
                foreach ($doencas as $id_doenca) {
                    $id_doenca_int = (int)$id_doenca;
                    
                    // Condição crucial: Só insere no banco se o ID da doença for válido (> 0).
                    // Isso IGNORA o ID 0 (que é a opção 'Nenhuma').
                    if ($id_doenca_int > 0) { 
                        $stmt_d->bind_param("ii", $id_usuario, $id_doenca_int);
                        $stmt_d->execute();
                    }
                }
                
                // Se a instrução foi preparada (para evitar erro se $doencas só tinha 0)
                if (isset($stmt_d)) {
                    $stmt_d->close();
                }
            }

            $conn->commit();

            // Auto-login
            $_SESSION['id_usuario'] = $id_novo_usuario;
            $_SESSION['nome_completo'] = $nome;
            $_SESSION['email'] = $email;
            $_SESSION['funcao'] = 'paciente';
            header('Location: Usuários/paciente.php');
            exit;

        } catch (mysqli_sql_exception $e) {
            // CÓDIGO 1062 = DUPLICATE ENTRY (E-mail já existe)
            if ($e->getCode() == 1062) {
                // Se o pré-cadastro já existe, redireciona para a página de registro com um status de aviso.
                header('Location: register.php?status=ja_cadastrado&email=' . urlencode($email));
                exit;
            } else {
                // Outro erro de banco de dados
                error_log("Erro no DB ao cadastrar enfermeiro: " . $e->getMessage());
                header('Location: register.php?erro=falha_db');
                exit;
            }
        }

    } 
    // --- ENFERMEIRO ---
    elseif ($funcao === 'enfermeiro') {
        try {
            // Tenta inserir no pré-cadastro
            $stmt_pre = $conn->prepare("INSERT INTO usuarioprecadastro (nome_completo, email, senha_hash, cpf, telefone, endereco, id_posto) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_pre->bind_param("ssssssi", $nome, $email, $senha_hash, $cpf, $telefone, $endereco, $id_posto);
            $stmt_pre->execute();

            // Sucesso: vai para verificação
            header('Location: verificacao_enfermeiro.php?status=cadastro_sucesso&email=' . urlencode($email));
            exit;

        } catch (mysqli_sql_exception $e) {
            // CÓDIGO 1062 = DUPLICATE ENTRY (E-mail já existe)
            if ($e->getCode() == 1062) {
                // Aqui está o pulo do gato: Redireciona de volta com status "ja_cadastrado"
                header('Location: register.php?status=ja_cadastrado&email=' . urlencode($email));
                exit;
            } else {
                header('Location: register.php?erro=falha_pre_cadastro');
                exit;
            }
        }
    }
}
$conn->close();
?>