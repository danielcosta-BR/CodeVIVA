<?php
// Usuarios/processa_mensagem.php
session_start();
include 'conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'msg' => 'Usuário não logado']);
    exit;
}

$acao = $_POST['acao'] ?? '';
$id_remetente = $_SESSION['id_usuario'];

// --- ENVIAR MENSAGEM ---
if ($acao === 'enviar') {
    $assunto = trim($_POST['assunto']);
    $corpo = trim($_POST['corpo']);
    $tipo = $_POST['tipo']; // 'privada' (Enf->Pct) ou 'solicitacao' (Pct->Posto)

    if (empty($assunto) || empty($corpo)) {
        echo json_encode(['success' => false, 'msg' => 'Preencha todos os campos.']);
        exit;
    }

    if ($tipo === 'privada') {
        // Enfermeiro enviando para Paciente Específico
        $id_destinatario = (int)$_POST['id_destinatario'];
        $sql = "INSERT INTO mensagens (id_remetente, id_destinatario, assunto, corpo) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $id_remetente, $id_destinatario, $assunto, $corpo);
        
    } elseif ($tipo === 'solicitacao') {
        // Paciente enviando para o Posto (solicitação geral)
        // Precisamos descobrir qual o posto do paciente primeiro
        // (Ou enviamos via POST hidden, mas pegar do banco é mais seguro)
        
        // Verifica posto do paciente na tabela pacientes
        $sql_p = "SELECT id_posto_saude FROM pacientes WHERE id_usuario = ?";
        $stmt_p = $conn->prepare($sql_p);
        $stmt_p->bind_param("i", $id_remetente);
        $stmt_p->execute();
        $res_p = $stmt_p->get_result();
        
        if ($row = $res_p->fetch_assoc()) {
            $id_posto = $row['id_posto_saude'];
            if (!$id_posto) {
                echo json_encode(['success' => false, 'msg' => 'Você não está vinculado a um posto. Configure seu perfil.']);
                exit;
            }
            
            $sql = "INSERT INTO mensagens (id_remetente, id_posto_alvo, assunto, corpo, status) VALUES (?, ?, ?, ?, 'pendente')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $id_remetente, $id_posto, $assunto, $corpo);
        } else {
             echo json_encode(['success' => false, 'msg' => 'Erro ao identificar paciente.']);
             exit;
        }
    } else {
        echo json_encode(['success' => false, 'msg' => 'Tipo inválido.']);
        exit;
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Erro no banco: ' . $stmt->error]);
    }
    $stmt->close();
}

// --- MARCAR COMO ATENDIDA ---
elseif ($acao === 'atender') {
    if ($_SESSION['funcao'] !== 'enfermeiro') {
        echo json_encode(['success' => false, 'msg' => 'Apenas enfermeiros podem atender.']);
        exit;
    }

    $id_mensagem = (int)$_POST['id_mensagem'];
    
    // Verifica se já não foi atendida por outro (concorrência)
    $check = $conn->query("SELECT status FROM mensagens WHERE id_mensagem = $id_mensagem");
    $atual = $check->fetch_assoc();
    
    if ($atual['status'] === 'atendida') {
        echo json_encode(['success' => false, 'msg' => 'Esta mensagem já foi atendida por outro colega. Atualize a página.']);
        exit;
    }

    $sql = "UPDATE mensagens SET status = 'atendida', id_atendente = ?, data_atendimento = NOW() WHERE id_mensagem = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_remetente, $id_mensagem);
    
    if ($stmt->execute()) {
        // Opcional: Se o enfermeiro digitou uma réplica, podemos inserir uma NOVA mensagem de resposta aqui
        // Mas o requisito pedia apenas "Marcar como atendida".
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Erro ao atualizar.']);
    }
    $stmt->close();
}

$conn->close();
?>