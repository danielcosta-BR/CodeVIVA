<?php
// Usuarios/enf/registrar_vacina.php
header('Content-Type: application/json');
session_start();

// Verifica permissão
if (!isset($_SESSION['id_usuario']) || $_SESSION['funcao'] !== 'enfermeiro') {
    echo json_encode(['success' => false, 'msg' => 'Acesso negado']);
    exit;
}

include '../conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_paciente = $_POST['id_paciente'];
    $id_vacina_modelo = $_POST['id_vacina_modelo'];
    $id_enfermeiro = $_SESSION['id_usuario']; 

    // 1. Registrar a vacina atual como TOMADA
    // Verifica se já existe registro (caderneta) para atualizar ou insere novo
    $sql_check = "SELECT id_caderneta FROM caderneta WHERE id_paciente = ? AND id_vacina_modelo = ?";
    $stmt_c = $conn->prepare($sql_check);
    $stmt_c->bind_param("ii", $id_paciente, $id_vacina_modelo);
    $stmt_c->execute();
    $res = $stmt_c->get_result();
    
    $sucesso_atual = false;

    if ($res->num_rows > 0) {
        // Atualiza registro existente (ex: estava agendada, agora foi tomada)
        $row = $res->fetch_assoc();
        $id_caderneta = $row['id_caderneta'];
        $sql_up = "UPDATE caderneta SET data_tomada = CURDATE(), id_enfermeiro_aplicador = ? WHERE id_caderneta = ?";
        $stmt_up = $conn->prepare($sql_up);
        $stmt_up->bind_param("ii", $id_enfermeiro, $id_caderneta);
        $sucesso_atual = $stmt_up->execute();
        $stmt_up->close();
    } else {
        // Insere novo registro
        $sql_ins = "INSERT INTO caderneta (id_paciente, id_vacina_modelo, data_tomada, id_enfermeiro_aplicador) VALUES (?, ?, CURDATE(), ?)";
        $stmt_ins = $conn->prepare($sql_ins);
        $stmt_ins->bind_param("iii", $id_paciente, $id_vacina_modelo, $id_enfermeiro);
        $sucesso_atual = $stmt_ins->execute();
        $stmt_ins->close();
    }
    $stmt_c->close();

    // 2. Lógica de Encadeamento (Agendar a Próxima Dose)
    if ($sucesso_atual) {
        // Buscar configurações desta vacina (se tem próxima dose e qual o intervalo)
        $sql_config = "SELECT intervalo_dias, id_proxima_vacina FROM vacinamodelo WHERE id_vacina_modelo = ?";
        $stmt_cfg = $conn->prepare($sql_config);
        $stmt_cfg->bind_param("i", $id_vacina_modelo);
        $stmt_cfg->execute();
        $res_cfg = $stmt_cfg->get_result();
        
        if ($res_cfg->num_rows > 0) {
            $config = $res_cfg->fetch_assoc();
            $dias = $config['intervalo_dias'];
            $prox_id = $config['id_proxima_vacina'];

            // Se houver uma próxima vacina definida e um intervalo válido
            if (!empty($prox_id) && !empty($dias)) {
                
                // Calcula data prevista: Hoje + Intervalo
                $data_prevista = date('Y-m-d', strtotime("+$dias days"));

                // Verifica se a próxima vacina JÁ existe na caderneta do paciente
                $sql_check_prox = "SELECT id_caderneta FROM caderneta WHERE id_paciente = ? AND id_vacina_modelo = ?";
                $stmt_cp = $conn->prepare($sql_check_prox);
                $stmt_cp->bind_param("ii", $id_paciente, $prox_id);
                $stmt_cp->execute();
                $res_cp = $stmt_cp->get_result();

                if ($res_cp->num_rows == 0) {
                    // Se NÃO existe, cria o agendamento
                    // Nota: data_tomada é NULL
                    $sql_agendar = "INSERT INTO caderneta (id_paciente, id_vacina_modelo, data_prevista) VALUES (?, ?, ?)";
                    $stmt_ag = $conn->prepare($sql_agendar);
                    $stmt_ag->bind_param("iis", $id_paciente, $prox_id, $data_prevista);
                    $stmt_ag->execute();
                    $stmt_ag->close();
                }
                // Se já existe, não fazemos nada para não sobrescrever histórico ou duplicar
                $stmt_cp->close();
            }
        }
        $stmt_cfg->close();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Erro ao registrar vacina no banco.']);
    }
    
    $conn->close();

} else {
    echo json_encode(['success' => false, 'msg' => 'Método inválido']);
}
?>