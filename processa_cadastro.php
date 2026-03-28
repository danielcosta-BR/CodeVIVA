<?php
// processa_cadastro.php
session_start();
include 'Usuários/conexao.php'; 

// Habilita exceções para capturar erros de banco
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $funcao = $_POST['funcao_usuario'];

    // Validação de Segurança da Senha
    if (strlen($senha) < 8) {
        header('Location: register.php?erro=senha_curta');
        exit;
    }

    // Dados Passo 2
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $endereco = trim($_POST['endereco']);
    $id_posto = (int)$_POST['id_posto'];
    $doencas = $_POST['doencas'] ?? []; // Array de doenças

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // --- PACIENTE ---
    if ($funcao === 'paciente') {
        $conn->begin_transaction();
        try {
            // 1. Insere Usuario
            $stmt_u = $conn->prepare("INSERT INTO usuario (nome_completo, email, senha, funcao) VALUES (?, ?, ?, 'paciente')");
            $stmt_u->bind_param("sss", $nome, $email, $senha_hash);
            $stmt_u->execute();
            
            // ✅ PEGA O ID DO NOVO USUÁRIO
            $id_novo_usuario = $conn->insert_id; 

            // 2. Insere Dados de Paciente
            $stmt_p = $conn->prepare("INSERT INTO pacientes (id_usuario, cpf, telefone, endereco, id_posto_saude) VALUES (?, ?, ?, ?, ?)");
            $stmt_p->bind_param("isssi", $id_novo_usuario, $cpf, $telefone, $endereco, $id_posto);
            $stmt_p->execute();

            // 3. Doenças (Loop para inserir)
            if (!empty($doencas)) {
                $stmt_d = $conn->prepare("INSERT INTO paciente_doencas (id_paciente, id_doenca) VALUES (?, ?)");
                
                foreach ($doencas as $id_doenca) {
                    $id_doenca_int = (int)$id_doenca;
                    
                    // Insere apenas se for ID > 1.
                    // Isso assume que "Nenhuma" é ID 1 no banco ou 0 no JS.
                    // Se você tiver doenças reais com ID 1, mude para > 0.
                    if ($id_doenca_int > 1) { 
                        $stmt_d->bind_param("ii", $id_novo_usuario, $id_doenca_int); 
                        $stmt_d->execute();
                    }
                }
                $stmt_d->close();
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
            $conn->rollback();
            if ($e->getCode() == 1062) {
                header('Location: register.php?status=ja_cadastrado&email=' . urlencode($email));
                exit;
            } else {
                error_log("Erro no cadastro: " . $e->getMessage());
                header('Location: register.php?erro=falha_db');
                exit;
            }
        }

    } 
    // --- ENFERMEIRO ---
    elseif ($funcao === 'enfermeiro') {
        try {
            $stmt_pre = $conn->prepare("INSERT INTO usuarioprecadastro (nome_completo, email, senha_hash, cpf, telefone, endereco, id_posto) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_pre->bind_param("ssssssi", $nome, $email, $senha_hash, $cpf, $telefone, $endereco, $id_posto);
            $stmt_pre->execute();

            header('Location: verificacao_enfermeiro.php?status=cadastro_sucesso&email=' . urlencode($email));
            exit;

        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
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