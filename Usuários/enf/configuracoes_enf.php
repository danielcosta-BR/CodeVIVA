<?php
// Usuarios/enf/configuracoes_enf.php
$funcao_permitida = 'enfermeiro';
include '../verificar_acesso.php'; 
include '../conexao.php'; 

$id_usuario = $_SESSION['id_usuario'];
$nome_completo = $_SESSION['nome_completo'];
$erro = '';
$sucesso = '';

// Buscar Postos de Saúde para o Select
$postos_saude = [];
$sql_postos = "SELECT id_posto, nome_posto FROM postosaude ORDER BY nome_posto ASC";
$result_postos = $conn->query($sql_postos);
if ($result_postos->num_rows > 0) {
    while ($row = $result_postos->fetch_assoc()) {
        $postos_saude[] = $row;
    }
}

// Variáveis iniciais
$dados_enf = [
    'cpf' => '',
    'telefone' => '',
    'endereco' => '',
    'id_posto_saude' => ''
];

// Buscar dados atuais se já existirem
$sql_busca = "SELECT * FROM enfermeiros WHERE id_usuario = ?";
$stmt = $conn->prepare($sql_busca);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows > 0) {
    $dados_enf = $res->fetch_assoc();
}
$stmt->close();

// Feedback visual via GET
if (isset($_GET['msg']) && $_GET['status'] == 'erro') {
    $erro = htmlspecialchars($_GET['msg']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Configurações Enfermeiro</title>
    <link rel='stylesheet' type='text/css' href='../modal.css'>
    <link rel='stylesheet' type='text/css' href='../enfermeiro.css'>
    <link rel='stylesheet' type='text/css' href='../../styleenf.css'>
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>
    <!-- Script de Máscaras -->
    <script>
        function maskCPF(value) {
            return value.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2').substring(0, 14);
        }
        function maskTelefone(value) {
            value = value.replace(/\D/g, '');
            if (value.length > 10) return value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            return value.replace(/^(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('cpf').addEventListener('input', (e) => e.target.value = maskCPF(e.target.value));
            document.getElementById('telefone').addEventListener('input', (e) => e.target.value = maskTelefone(e.target.value));
        });
    </script>
</head>
<body>
    <?php include 'header_enf.php'; ?> <!-- Reutiliza o header_enf que está na pasta pai -->

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2><i class="fas fa-user-nurse"></i> Dados Profissionais</h2>
                <p>Complete seu cadastro para ter acesso à gestão de pacientes da sua unidade.</p>
                
                <?php if ($erro): ?>
                    <p style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px;"><?php echo $erro; ?></p>
                <?php endif; ?>

                <form action="salvar_configuracoes_enf.php" method="POST">
                    
                    <div class="input-group">
                        <label for="posto_saude">Unidade de Atuação (Posto de Saúde) <span>*</span></label>
                        <select id="posto_saude" name="posto_saude" required>
                            <option value="" disabled <?php echo empty($dados_enf['id_posto_saude']) ? 'selected' : ''; ?>>Selecione...</option>
                            <?php foreach ($postos_saude as $posto): ?>
                                <option value="<?php echo $posto['id_posto']; ?>" <?php echo ($dados_enf['id_posto_saude'] == $posto['id_posto']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($posto['nome_posto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($dados_enf['cpf']); ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="telefone">Telefone Profissional/Celular</label>
                        <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($dados_enf['telefone']); ?>">
                    </div>

                    <div class="input-group">
                        <label for="endereco">Endereço Residencial</label>
                        <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($dados_enf['endereco']); ?>">
                    </div>

                    <button type="submit" class="submit-btn">Salvar Dados</button>
                </form>
            </div>
        </section>
    </main>
    
    <?php include '../modal_logout.html'; ?>
    <script src="../modal.js"></script>
</body>
</html>