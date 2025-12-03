<?php
// Usuarios/pct/configuracoes_enf.php
$funcao_permitida = 'enfermeiro';
include '../verificar_acesso.php'; 
include '../conexao.php'; 

$id_usuario = $_SESSION['id_usuario'] ?? null;
$nome_completo = $_SESSION['nome_completo'] ?? 'Enfermeiro';
$erro = '';
$sucesso = '';

// Feedback via GET
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'sucesso') $sucesso = "Dados atualizados com sucesso!";
    elseif ($_GET['status'] == 'erro') $erro = "Erro ao salvar alterações.";
}

// 1. Buscar Dados Básicos
$dados_enfermeiro = ['cpf'=>'','telefone'=>'','endereco'=>'','id_posto_saude'=>null];
if ($id_usuario) {
    $stmt = $conn->prepare("SELECT cpf, telefone, endereco, id_posto_saude FROM enfermeiros WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) $dados_enfermeiro = $res->fetch_assoc();
    $stmt->close();
}

// 2. Buscar Postos para o Select
$postos_saude = [];
$res_p = $conn->query("SELECT id_posto, nome_posto FROM postosaude ORDER BY nome_posto ASC");
while ($row = $res_p->fetch_assoc()) $postos_saude[] = $row;

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Configurações</title>
    <link rel='stylesheet' href='../modal.css'> 
    <link rel='stylesheet' href='../enfermeiro.css'>
    <link rel='stylesheet' href='../../styleenf.css'>
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>
</head>
<body>

    <?php include 'header_enf.php'; ?>

    <main>
        <section class="form-section">
            <div class="form-container">
                <h2><i class="fas fa-cog"></i> Configurações</h2>
                <p>Mantenha seus dados e condições de saúde atualizados para um melhor acompanhamento.</p>

                <?php if ($erro): ?>
                    <p class="feedback-erro"><?php echo htmlspecialchars($erro); ?></p>
                <?php endif; ?>
                <?php if ($sucesso): ?>
                    <p class="feedback-sucesso"><?php echo htmlspecialchars($sucesso); ?></p>
                <?php endif; ?>

                <form action="salvar_configuracoes_enf.php" method="POST">
 
                    <div class="input-group">
                        <label for="posto_saude">Posto de Saúde</label>
                        <select id="posto_saude" name="posto_saude" required>
                            <option value="" disabled>Selecione...</option>
                            <?php foreach ($postos_saude as $posto): ?>
                                <option value="<?php echo $posto['id_posto']; ?>" <?php echo ($dados_enfermeiro['id_posto_saude'] == $posto['id_posto']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($posto['nome_posto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h4 >Dados de Contato</h4>
                    <div class="input-group">
                        <label>CPF</label>
                        <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($dados_enfermeiro['cpf']); ?>" oninput="maskCPF(this)">
                    </div>
                    <div class="input-group">
                        <label>Telefone</label>
                        <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($dados_enfermeiro['telefone']); ?>" oninput="maskTelefone(this)">
                    </div>
                    <div class="input-group">
                        <label>Endereço</label>
                        <input type="text" name="endereco" value="<?php echo htmlspecialchars($dados_enfermeiro['endereco']); ?>">
                    </div>

                    <button type="submit" class="submit-btn">Salvar Tudo</button>
                </form>
            </div>
        </section>
    </main>

    <?php include '../modal_logout.html'; ?>
    <script src='../modal.js'></script>

    <script>
        // =========================================================================
        // MÁSCARAS
        // =========================================================================

        function maskCPF(field) {
            let v = field.value;
            v = v.replace(/\D/g, ""); // Remove tudo que não for dígito
            v = v.replace(/(\d{3})(\d)/, "$1.$2"); // Coloca um ponto entre o terceiro e o quarto dígitos
            v = v.replace(/(\d{3})(\d)/, "$1.$2"); // Coloca um ponto entre o sexto e o sétimo dígitos
            v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2"); // Coloca um hífen entre o nono e o décimo dígitos
            field.value = v;
        }

        function maskTelefone(field) {
            let v = field.value;
            v = v.replace(/\D/g, ""); // Remove tudo que não for dígito
            v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); // Coloca parênteses em volta dos dois primeiros dígitos
            v = v.replace(/(\d)(\d{4})$/, "$1-$2"); // Coloca hífen antes dos últimos 4 dígitos
            field.value = v;
        }

        // Adicionando listeners de máscara para os campos
        document.addEventListener('DOMContentLoaded', function() {
            const cpfField = document.getElementById('cpf');
            const telefoneField = document.getElementById('telefone');

            if (cpfField) {
                cpfField.addEventListener('input', function(e) { maskCPF(e.target); });
                // Aplica a máscara ao carregar, caso o campo já tenha um valor
                maskCPF(cpfField); 
            }
            if (telefoneField) {
                telefoneField.addEventListener('input', function(e) { maskTelefone(e.target); });
                // Aplica a máscara ao carregar, caso o campo já tenha um valor
                maskTelefone(telefoneField);
            }

                // 3. REMOVIDA: INTERCEPTAÇÃO DO ENVIO DO FORMULÁRIO PRINCIPAL (Lógica incorreta do Paciente)

            // 4. FEEDBACK DE ATUALIZAÇÃO
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            if (status === 'sucesso' || status === 'erro') {
                // Limpa a URL para que a mensagem não apareça novamente ao recarregar
                history.replaceState(null, '', window.location.pathname); 
            }

        });
    </script>
</body>
</html>