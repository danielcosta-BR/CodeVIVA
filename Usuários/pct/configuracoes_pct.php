<?php
// Define a função permitida para esta página
$funcao_permitida = 'paciente';
// Inclui o script de verificação de acesso (necessário para iniciar a sessão e verificar login)
include '../verificar_acesso.php'; 
// Inclui a conexão com o banco de dados
include '../conexao.php'; 

$id_usuario = $_SESSION['id_usuario'] ?? null;
$nome_completo = $_SESSION['nome_completo'] ?? 'Paciente';
$erro = '';
$sucesso = '';

// Arrays para armazenar dados e opções
$dados_paciente = [
    'cpf' => '',
    'telefone' => '',
    'endereco' => '',
    'id_posto_saude' => null
];
$postos_saude = [];

// =========================================================================
// 1. BUSCAR POSTOS DE SAÚDE (USANDO O NOME CORRETO DA TABELA: postodesaude)
// =========================================================================
$sql_postos = "SELECT id_posto, nome_posto FROM postosaude ORDER BY nome_posto ASC";
$result_postos = $conn->query($sql_postos);

if ($result_postos->num_rows > 0) {
    while ($row = $result_postos->fetch_assoc()) {
        $postos_saude[] = $row;
    }
} else {
    $erro = "Não foi possível carregar a lista de postos de saúde. Entre em contato com o administrador.";
}

// =========================================================================
// 2. BUSCAR DADOS ATUAIS DO PACIENTE (NA TABELA 'pacientes')
// =========================================================================
if ($id_usuario) {
    $sql_dados = "SELECT cpf, telefone, endereco, id_posto_saude FROM pacientes WHERE id_usuario = ?";
    $stmt_dados = $conn->prepare($sql_dados);
    
    if ($stmt_dados) {
        $stmt_dados->bind_param("i", $id_usuario);
        $stmt_dados->execute();
        $result_dados = $stmt_dados->get_result();

        if ($result_dados->num_rows > 0) {
            $dados_paciente = $result_dados->fetch_assoc();
        } 
        // Se não encontrou, a array $dados_paciente já tem valores vazios (NULL) e o formulário será preenchido com vazios.
        $stmt_dados->close();
    } else {
        $erro = "Erro ao preparar a busca de dados: " . $conn->error;
    }
}


// Processar mensagens de feedback (Sucesso ou Erro) do redirecionamento
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'sucesso') {
        $sucesso = "Configurações salvas com sucesso!";
    } elseif ($_GET['status'] == 'erro') {
        $erro = "Erro ao salvar as configurações. Tente novamente.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>VIVA+ | Configurações do Paciente</title>
    <!-- Usamos os estilos de paciente e perfil existentes -->
    <link rel='stylesheet' type='text/css' media='screen' href='../administrador.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../styleprofile.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='modal.css'>
    <link rel='stylesheet' type='text/css' media='screen' href='../../stylepct.css'>
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>

    <!-- Script de Máscaras (para CPF, Telefone) -->
    <script>
        function maskCPF(value) {
            return value
                .replace(/\D/g, '') // Remove tudo o que não é dígito
                .replace(/(\d{3})(\d)/, '$1.$2') // Coloca um ponto entre o terceiro e o quarto dígitos
                .replace(/(\d{3})(\d)/, '$1.$2') // Coloca um ponto entre o sexto e o sétimo dígitos
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2') // Coloca um hífen entre o nono e o décimo dígitos
                .substring(0, 14); // Limita ao tamanho do CPF (14 caracteres)
        }

        function maskTelefone(value) {
            value = value.replace(/\D/g, ''); // Remove tudo o que não é dígito
            if (value.length > 10) {
                // Formato (XX) XXXXX-XXXX (com nono dígito)
                return value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else {
                // Formato (XX) XXXX-XXXX
                return value.replace(/^(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const cpfInput = document.getElementById('cpf');
            const telInput = document.getElementById('telefone');

            if (cpfInput) {
                cpfInput.addEventListener('input', (e) => {
                    e.target.value = maskCPF(e.target.value);
                });
            }

            if (telInput) {
                telInput.addEventListener('input', (e) => {
                    e.target.value = maskTelefone(e.target.value);
                });
            }
        });
    </script>
</head>
<body>

    <?php 
        // Ajuste no caminho para o header.php, que está na pasta Usuários/
        include 'header.php'; 
    ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2><i class="fas fa-cog"></i> Configurações Essenciais</h2>
                <p>Olá, <?php echo htmlspecialchars($nome_completo); ?>. Para garantir que sua caderneta de vacinação seja gerenciada pelo enfermeiro correto, por favor, configure seu <b>Posto de Saúde</b> e adicione seus dados complementares.</p>
                
                <?php if ($erro): ?>
                    <p class="feedback-erro"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro); ?></p>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <p class="feedback-sucesso"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($sucesso); ?></p>
                <?php endif; ?>

                <!-- O FORMULÁRIO ENVIARÁ OS DADOS PARA O SCRIPT DE PROCESSAMENTO -->
                <form action="salvar_configuracoes_pct.php" method="POST">
                    
                    <!-- 1. POSTO DE SAÚDE (CAMPO CRÍTICO) -->
                    <div class="input-group">
                        <label for="posto_saude">Posto de Saúde <span>*</span></label>
                        <select id="posto_saude" name="posto_saude" required>
                            <option value="" disabled <?php echo is_null($dados_paciente['id_posto_saude']) ? 'selected' : ''; ?>>Selecione seu Posto de Saúde</option>
                            <?php foreach ($postos_saude as $posto): ?>
                                <option 
                                    value="<?php echo htmlspecialchars($posto['id_posto']); ?>"
                                    <?php echo ($dados_paciente['id_posto_saude'] == $posto['id_posto']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($posto['nome_posto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="hint">Selecione o posto de saúde que você costuma frequentar.</p>
                    </div>

                    <!-- 2. CPF -->
                    <div class="input-group">
                        <label for="cpf">CPF</label>
                        <input 
                            autocomplete="off" 
                            type="text" 
                            id="cpf" 
                            name="cpf" 
                            maxlength="14" 
                            placeholder="000.000.000-00"
                            value="<?php echo htmlspecialchars($dados_paciente['cpf'] ?? ''); ?>"
                        >
                    </div>

                    <!-- 3. TELEFONE -->
                    <div class="input-group">
                        <label for="telefone">Telefone</label>
                        <input 
                            autocomplete="off" 
                            type="text" 
                            id="telefone" 
                            name="telefone" 
                            maxlength="15" 
                            placeholder="(XX) XXXXX-XXXX"
                            value="<?php echo htmlspecialchars($dados_paciente['telefone'] ?? ''); ?>"
                        >
                    </div>
                    
                    <!-- 4. ENDEREÇO -->
                    <div class="input-group">
                        <label for="endereco">Endereço Completo</label>
                        <input 
                            autocomplete="off" 
                            type="text" 
                            id="endereco" 
                            name="endereco" 
                            maxlength="255" 
                            placeholder="Rua, Número, Bairro, Cidade, Estado"
                            value="<?php echo htmlspecialchars($dados_paciente['endereco'] ?? ''); ?>"
                        >
                    </div>
                    
                    <button type="submit" class="submit-btn">Salvar Configurações e Continuar</button>
                </form>

            </div>
        </section>
    </main>

    <?php include 'modal_logout.html'; ?>
    <script src='../modal.js'></script>
</body>
</html>