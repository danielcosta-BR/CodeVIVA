<?php
// Usuarios/pct/configuracoes_pct.php
$funcao_permitida = 'paciente';
include '../verificar_acesso.php'; 
include '../conexao.php'; 

$id_usuario = $_SESSION['id_usuario'] ?? null;
$nome_completo = $_SESSION['nome_completo'] ?? 'Paciente';
$erro = '';
$sucesso = '';

// Feedback via GET
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'sucesso') $sucesso = "Dados atualizados com sucesso!";
    elseif ($_GET['status'] == 'erro') $erro = "Erro ao salvar alterações.";
}

// 1. Buscar Dados Básicos
$dados_paciente = ['cpf'=>'','telefone'=>'','endereco'=>'','id_posto_saude'=>null];
if ($id_usuario) {
    $stmt = $conn->prepare("SELECT cpf, telefone, endereco, id_posto_saude FROM pacientes WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) $dados_paciente = $res->fetch_assoc();
    $stmt->close();
}

// 2. Buscar Postos para o Select
$postos_saude = [];
$res_p = $conn->query("SELECT id_posto, nome_posto FROM postosaude ORDER BY nome_posto ASC");
while ($row = $res_p->fetch_assoc()) $postos_saude[] = $row;

// 3. Buscar Doenças Atuais do Paciente
$doencas_paciente_ids = [];
if ($id_usuario) {
    // Busca os IDs de todas as doenças ligadas ao paciente na tabela 'paciente_doencas'
    $stmt_d = $conn->prepare("SELECT id_doenca FROM paciente_doencas WHERE id_paciente = ?");
    $stmt_d->bind_param("i", $id_usuario);
    $stmt_d->execute();
    $res_d = $stmt_d->get_result();
    while ($row_d = $res_d->fetch_assoc()) {
        $doencas_paciente_ids[] = $row_d['id_doenca'];
    }
    $stmt_d->close();
}

// 4. Buscar TODAS as Doenças para o Modal
$todas_doencas = [];
// Para que o checkbox 'Nenhuma' não se perca, é crucial que id_doenca > 0
$res_d_all = $conn->query("SELECT id_doenca, nome_doenca FROM doencas WHERE id_doenca > 0 ORDER BY nome_doenca ASC"); 
while ($row = $res_d_all->fetch_assoc()) {
    $todas_doencas[] = $row;
}
$res_d_all->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIVA+ | Configurações</title>
    <link rel='stylesheet' href='modal.css'> 
    <link rel='stylesheet' href='../paciente.css'>
    <link rel='stylesheet' href='../../stylepct.css'>
    <script src="https://kit.fontawesome.com/e878368812.js" crossorigin="anonymous"></script>
</head>
<body>

    <?php include 'header.php'; ?>

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

                <form action="salvar_configuracoes_pct.php" method="POST">
                    
                    <div class="input-group">
                        <label for="posto_saude">Posto de Saúde</label>
                        <select id="posto_saude" name="posto_saude" required>
                            <option value="" disabled>Selecione...</option>
                            <?php foreach ($postos_saude as $posto): ?>
                                <option value="<?php echo $posto['id_posto']; ?>" <?php echo ($dados_paciente['id_posto_saude'] == $posto['id_posto']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($posto['nome_posto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group" style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px;">
                        <label>Condições de Saúde</label>
                        <p>
                            Selecione as doenças que você possui para receber alertas personalizados.
                        </p>
                        <button type="button" class="btn-doencas" onclick="document.getElementById('modal-doencas').style.display='block'">
                            <i class="fas fa-heartbeat"></i> Gerenciar Minhas Condições
                        </button>
                        <p style="font-size: 0.8em; color: #888; margin-top: 5px;">
                            Atualmente marcado: <strong><?php echo count($doencas_paciente_ids) > 0 ? count($doencas_paciente_ids) . ' item(ns)' : 'Nenhum'; ?></strong>
                        </p>
                    </div>

                    <h4 >Dados de Contato</h4>
                    <div class="input-group">
                        <label>CPF</label>
                        <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($dados_paciente['cpf']); ?>" oninput="maskCPF(this)">
                    </div>
                    <div class="input-group">
                        <label>Telefone</label>
                        <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($dados_paciente['telefone']); ?>" oninput="maskTelefone(this)">
                    </div>
                    <div class="input-group">
                        <label>Endereço</label>
                        <input type="text" name="endereco" value="<?php echo htmlspecialchars($dados_paciente['endereco']); ?>">
                    </div>
                    
                    <button type="submit" class="submit-btn">Salvar Tudo</button>
                </form>
            </div>
        </section>
    </main>

    <div id="modal-doencas" class="modal-logout2">
        <div class="modal-content-logout4">
            <h3 class="msg-title">Gerenciar Condições</h3>
            <p class="msg-info">Marque o que se aplica a você. Se não tiver condições, marque "Nenhuma".</p>
            
            <div class="doencas-list checkbox-grid">
                <?php 
                // Se o array de doenças do paciente estiver vazio, 'Nenhuma' deve estar marcada
                $is_nenhuma_checked = empty($doencas_paciente_ids) ? 'checked' : ''; 
                ?>
                <label class="checkbox-item">
                    Nenhuma
                    <input type="checkbox" class="chk-doenca" id="chk-nenhuma" value="0" data-nome="Nenhuma" style="margin-left: 15px;" <?php echo $is_nenhuma_checked; ?>>
                    <span class="checkmark" style="margin-left: 50px;"></span>
                </label>

                <?php foreach ($todas_doencas as $doenca): 
                    // Verifica se a doença está na lista de IDs salvos para o paciente
                    $is_checked = in_array($doenca['id_doenca'], $doencas_paciente_ids) ? 'checked' : ''; 
                ?>
                    <label class="checkbox-item">
                        <?php echo htmlspecialchars($doenca['nome_doenca']); ?>
                        <input type="checkbox" class="chk-doenca" value="<?php echo $doenca['id_doenca']; ?>" data-nome="<?php echo htmlspecialchars($doenca['nome_doenca']); ?>" <?php echo $is_checked; ?> style="margin-left: 15px;">
                        <span class="checkmark"></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div style="text-align: right; margin-top: 10px;">
                <button type="button" class="submit-btn" onclick="document.getElementById('modal-doencas').style.display='none'">
                    Confirmar e Fechar
                </button>
            </div>
        </div>
    </div>

    <?php include 'modal_logout.html'; ?>
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
        
        // Adicionando listeners de máscara para os campos (pois no HTML só está o oninput)
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
        });

        // =========================================================================
        // LÓGICA DE CONSISTÊNCIA E ENVIO DE DOENÇAS (CORRIGIDO)
        // =========================================================================
        document.addEventListener('DOMContentLoaded', function() {
            
            // Referências do DOM
            const modalDoencas = document.getElementById('modal-doencas');
            const chkNenhuma = document.getElementById('chk-nenhuma');
            // Busca os checkboxes que não são o "Nenhuma"
            const checksOutras = document.querySelectorAll('.chk-doenca:not(#chk-nenhuma)');
            
            // FUNÇÃO PRINCIPAL DE CONSISTÊNCIA
            // Garante que 'Nenhuma' e outras doenças não estejam marcadas ao mesmo tempo
            function checkDoencaConsistency() {
                const outrasMarcadas = document.querySelectorAll('.chk-doenca:not(#chk-nenhuma):checked').length;
                
                // 1. Se outras doenças foram marcadas, 'Nenhuma' deve ser desmarcada.
                if (outrasMarcadas > 0 && chkNenhuma.checked) {
                    chkNenhuma.checked = false;
                } 
                
                // 2. Se NADA foi marcado (outrasMarcadas é zero) e 'Nenhuma' não está marcada, 
                // forçamos 'Nenhuma' a ser marcada.
                if (outrasMarcadas === 0 && !chkNenhuma.checked) {
                    chkNenhuma.checked = true;
                }
            }
            
            // Inicializa o estado correto das checkboxes ao carregar a página
            checkDoencaConsistency();

            // 1. Fechar o modal clicando fora
            window.addEventListener('click', function(event) {
                if (event.target == modalDoencas) {
                    modalDoencas.style.display = 'none';
                }
            });

            // 2. Listeners para os Checkboxes de Doenças
            document.querySelectorAll('.chk-doenca').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    
                    // Lógica de "Nenhuma"
                    if (this.id === 'chk-nenhuma' && this.checked) {
                        // Se marcou 'Nenhuma', desmarca todas as outras.
                        checksOutras.forEach(c => c.checked = false);
                    }
                    
                    // Lógica de "Outras"
                    if (this.id !== 'chk-nenhuma' && this.checked) {
                        // Se marcou uma doença, desmarca 'Nenhuma'.
                        if (chkNenhuma && chkNenhuma.checked) {
                            chkNenhuma.checked = false;
                        }
                    }
                    
                    // Finaliza com a função de consistência para garantir o estado final
                    checkDoencaConsistency();
                });
            });


            // 3. INTERCEPTAÇÃO DO ENVIO DO FORMULÁRIO PRINCIPAL (SALVAR TUDO)
            document.querySelector('form').addEventListener('submit', function(e) {
                
                // Executa a consistência final antes de preparar o envio (IMPORTANTE!)
                checkDoencaConsistency(); 

                // Remove inputs hidden antigos de doenças se houver (para evitar duplicatas)
                document.querySelectorAll('input[name="doencas[]"]').forEach(el => el.remove());

                // Pega *todos* os checkboxes marcados (incluindo 'Nenhuma' se for o caso)
                const checks = document.querySelectorAll('.chk-doenca:checked');
                
                if (checks.length === 0) {
                    e.preventDefault();
                    alert("Selecione ao menos uma condição ou 'Nenhuma'.");
                    return;
                }

                // Cria inputs hidden para enviar os IDs selecionados via POST para o PHP
                checks.forEach(c => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'doencas[]';
                    input.value = c.value; // O valor é o ID da doença (ou '0' para Nenhuma)
                    this.appendChild(input);
                });
                
                // O formulário prossegue com o envio para salvar_configuracoes_pct.php
            });
            
            // 4. FEEDBACK DE ATUALIZAÇÃO
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            if (status === 'sucesso') {
                // A lógica de alerta foi removida porque o seu PHP já trata o sucesso/erro, mas
                // caso você queira o alerta (como estava no seu JS anterior), descomente a linha abaixo.
                // alert('Dados atualizados com sucesso!');
                // Limpa a URL para que a mensagem não apareça novamente ao recarregar
                history.replaceState(null, '', window.location.pathname); 
            } else if (status === 'erro') {
                // alert('Erro ao salvar alterações.');
                history.replaceState(null, '', window.location.pathname);
            }

        });
    </script>
</body>
</html>