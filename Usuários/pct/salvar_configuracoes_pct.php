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
        header("Location: configuracoes_pct.php?status=erro&msg=Posto de Saúde é obrigatório.");
        exit;
    }

    if ($id_usuario) {
        // PARTE 1: Salvar Dados Básicos (Seção Existente)
        // ... (Seu código SQL ON DUPLICATE KEY UPDATE)
        
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
        $stmt->bind_param("iisss", $id_usuario, $id_posto_saude, $cpf, $telefone, $endereco);

        if ($stmt->execute()) {
            // Sucesso nos dados básicos. Agora, PARTE 2: Salvar as Doenças.

            // As doenças vêm do formulário via JS (Input Hidden) como um array de IDs: doencas[]
            $doencas_selecionadas = $_POST['doencas'] ?? [];
            
            // 2.1. Excluir TODAS as doenças existentes do paciente (prepara para inserir as novas)
            // Isso garante que se ele desmarcou uma doença, ela será removida do banco.
            $stmt_del = $conn->prepare("DELETE FROM paciente_doencas WHERE id_paciente = ?");
            $stmt_del->bind_param("i", $id_usuario);
            $stmt_del->execute();
            $stmt_del->close();
            
            // 2.2. Inserir as novas doenças
            if (!empty($doencas_selecionadas)) {
                
                $sql_insert = "INSERT INTO paciente_doencas (id_paciente, id_doenca) VALUES (?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("ii", $id_usuario, $id_doenca);
                
                foreach ($doencas_selecionadas as $id_doenca_str) {
                    $id_doenca = (int) $id_doenca_str;
                    
                    // IMPORTANTE: O valor '0' no checkbox representa 'Nenhuma'. 
                    // Se o paciente selecionou 'Nenhuma', o array conterá apenas '0'.
                    // Se o paciente selecionou doenças reais, o array NÃO conterá '0'.
                    // A tabela `paciente_doencas` só deve receber IDs de doenças válidos (> 0).
                    if ($id_doenca > 0) { 
                        // Atribui o ID para o bind_param (que já foi preparado)
                        $id_doenca = $id_doenca; 
                        
                        if (!$stmt_insert->execute()) {
                            // Se houver erro aqui, registre e continue o loop
                            error_log("Erro ao salvar doença ID $id_doenca para paciente $id_usuario: " . $stmt_insert->error);
                        }
                    }
                }
                $stmt_insert->close();
            }
            
            // Sucesso na atualização dos dados básicos E das doenças
            header("Location: configuracoes_pct.php?status=sucesso");
            exit;

        } else {
            // Erro na execução do SQL de dados básicos
            error_log("Erro ao salvar config paciente: " . $stmt->error);
            header("Location: configuracoes_pct.php?status=erro");
            exit;
        }

        $stmt->close();
    } else {
        // ID de usuário não encontrado (erro de sessão)
        header("Location: ../login.html?erro=sessao_expirada");
        exit;
    }
} else {
    // Não é POST, redireciona de volta
    header("Location: configuracoes_pct.php");
    exit;
}