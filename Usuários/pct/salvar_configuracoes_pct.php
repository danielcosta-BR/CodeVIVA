<?php
// Usuários/pct/salvar_configuracoes_pct.php
$funcao_permitida = 'paciente';
// 1. Incluir arquivos essenciais
include '../verificar_acesso.php'; // Garante que o usuário está logado e é paciente
include '../conexao.php';         // Conexão com o banco

// Verifica se a requisição é um POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    $id_posto_saude = $_POST['posto_saude'] ?? null;
    $cpf = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    
    // Limpar e validar dados (apenas sanitização básica por enquanto)
    $id_posto_saude = filter_var($id_posto_saude, FILTER_VALIDATE_INT);
    $cpf = preg_replace('/[^0-9]/', '', $cpf); // Remove máscara
    $telefone = preg_replace('/[^0-9]/', '', $telefone); // Remove máscara
    $endereco = trim($endereco);
    
    // O Posto de Saúde é obrigatório para sair do loop inicial
    if (empty($id_posto_saude) || $id_posto_saude === false) {
        // Redireciona de volta com erro
        header("Location: configuracoes_pct.php?status=erro&msg=Posto de saúde é obrigatório.");
        exit;
    }

    if ($id_usuario) {
        // SQL para ATUALIZAR ou INSERIR os dados na tabela 'pacientes'
        // Presume que 'id_usuario' é uma chave única na tabela 'pacientes' (como definido no DDL)
        
        $sql = "
            INSERT INTO pacientes (id_usuario, id_posto_saude, cpf, telefone, endereco)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                id_posto_saude = VALUES(id_posto_saude),
                cpf = VALUES(cpf),
                telefone = VALUES(telefone),
                endereco = VALUES(endereco)
        ";
        
        $stmt = $conn->prepare($sql);
        
        // s = string, i = integer
        // Bind parameters: 1 integer (id_usuario), 1 integer (id_posto_saude), 3 strings (cpf, tel, end)
        $stmt->bind_param("iisss", $id_usuario, $id_posto_saude, $cpf, $telefone, $endereco);

        if ($stmt->execute()) {
            // Sucesso! Redireciona para o Dashboard completo
            header("Location: ../paciente.php?status=config_sucesso");
            exit;
        } else {
            // Erro na execução do SQL
            error_log("Erro ao salvar config paciente: " . $stmt->error);
            header("Location: configuracoes_pct.php?status=erro");
            exit;
        }

        $stmt->close();
    } else {
        // ID de usuário não encontrado (erro de sessão)
        header("Location: ../logout.php");
        exit;
    }
    
    $conn->close();

} else {
    // Acesso direto ao script sem POST
    header("Location: configuracoes_pct.php");
    exit;
}
?>