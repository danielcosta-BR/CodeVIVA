<?php
// Usuarios/enf/buscar_vacinas_paciente.php
// Este arquivo retorna apenas o HTML da tabela de vacinas para ser inserido no modal

$funcao_permitida = 'enfermeiro';
include '../verificar_acesso.php';
include '../conexao.php';

$id_paciente = $_GET['id_paciente'] ?? null;

if (!$id_paciente) {
    echo "ID do paciente não fornecido.";
    exit;
}

// 1. Buscar todas as vacinas modelo (INCLUINDO SEÇÃO E ORDENANDO)
$vacinas_modelo = [];
// Alteração aqui: Ordenar por secao para facilitar o agrupamento
$sql_modelo = "SELECT * FROM vacinamodelo ORDER BY secao, nome_vacina ASC"; 
$res_modelo = $conn->query($sql_modelo);

// Alteração aqui: Agrupar por seção
$vacinas_agrupadas_por_secao = [];
while($row = $res_modelo->fetch_assoc()) {
    $secao_atual = htmlspecialchars($row['secao'] ?? 'Geral'); // Usa 'Geral' como fallback
    if (!isset($vacinas_agrupadas_por_secao[$secao_atual])) {
        $vacinas_agrupadas_por_secao[$secao_atual] = [];
    }
    $vacinas_agrupadas_por_secao[$secao_atual][] = $row;
}

// 2. Buscar vacinas já tomadas ou agendadas na caderneta deste paciente
$caderneta = [];
$sql_caderneta = "SELECT id_vacina_modelo, data_tomada, data_prevista FROM caderneta WHERE id_paciente = ?";
$stmt = $conn->prepare($sql_caderneta);
$stmt->bind_param("i", $id_paciente);
$stmt->execute();
$res_cad = $stmt->get_result();
while($row = $res_cad->fetch_assoc()) {
    $caderneta[$row['id_vacina_modelo']] = $row; // Indexa pelo ID do modelo para fácil acesso
}
$stmt->close();

$conn->close();

// Define a ordem canônica das seções para exibição
$ordem_secoes_base = ['Gestante', 'Criança', 'Adolescente e Jovem', 'Adulto', 'Idoso', 'Geral'];
$secoes_ordenadas = [];

// Garante que as seções existentes no banco apareçam na ordem desejada
foreach ($ordem_secoes_base as $secao) {
    if (isset($vacinas_agrupadas_por_secao[$secao])) {
        $secoes_ordenadas[$secao] = $vacinas_agrupadas_por_secao[$secao];
        unset($vacinas_agrupadas_por_secao[$secao]);
    }
}
// Adiciona qualquer seção não listada na base (se houver) ao final
$secoes_ordenadas = array_merge($secoes_ordenadas, $vacinas_agrupadas_por_secao);


// =========================================================================
// 3. Montar Tabelas HTML (Agrupadas por Seção)
// =========================================================================

if (empty($secoes_ordenadas)) {
    echo "<p style='text-align: center; color: #666; margin-top: 20px;'>Nenhum modelo de vacina encontrado ou cadastrado.</p>";
    exit;
}

$i = 0;
// Itera sobre as seções ordenadas
foreach ($secoes_ordenadas as $secao_nome => $vacinas_lista) {
    // Adiciona a classe 'hidden-section-modal' para as seções não ativas.
    // O JS em enfermeiro.php precisa desta classe para controlar a navegação.
    $is_active = $i === 0 ? '' : 'hidden-section-modal'; 
    $i++;

    // Div principal da seção que o JS em enfermeiro.php busca
    echo "<div class='tabela-vacinas-secao {$is_active}'>"; 
    // Título H4 que o JS em enfermeiro.php usa para criar a aba
    echo "<h4>" . htmlspecialchars($secao_nome) . "</h4>";

    echo '<div class="table-responsive">'; 
    echo '<table class="data-table" style="width:100%">';
    echo '<thead><tr><th>Vacina</th><th>Recomendação</th><th>Situação Atual</th><th>Ação</th></tr></thead>';
    echo '<tbody>';

    foreach ($vacinas_lista as $vacina) {
        $id_mod = $vacina['id_vacina_modelo'];
        $status_texto = "Pendente";
        $botao_disabled = "";
        $botao_texto = "Aplicar Vacina";
        $botao_style = "";

        if (isset($caderneta[$id_mod])) {
            $registro = $caderneta[$id_mod];
            
            if (!empty($registro['data_tomada'])) {
                $data_br = date('d/m/Y', strtotime($registro['data_tomada']));
                $status_texto = "<span class='status-check'><i class='fas fa-check-circle'></i> Tomada em $data_br</span>";
                $botao_disabled = "disabled";
                $botao_texto = "Concluído";
                $botao_style = "background-color: #ccc; cursor: default;";
            } elseif (!empty($registro['data_prevista'])) {
                 $data_prev = date('d/m/Y', strtotime($registro['data_prevista']));
                 $data_prev_ts = strtotime($registro['data_prevista']);
                 $hoje_ts = strtotime(date('Y-m-d'));

                 if ($data_prev_ts < $hoje_ts) {
                    $status_texto = "<span style='color: red; font-weight: bold;'>ATRASADA! Agendada para $data_prev</span>";
                 } else {
                    $status_texto = "Agendada para $data_prev";
                 }
            }
        }

        echo "<tr>";
        echo "<td>" . htmlspecialchars($vacina['nome_vacina']) . "</td>";
        echo "<td>" . htmlspecialchars($vacina['recomendacao_idade']) . "</td>";
        echo "<td class='status-cell'>" . $status_texto . "</td>";
        echo "<td>";
        // O botão chama a função JS aplicarVacina definida no arquivo pai (enfermeiro.php)
        echo "<button class='action-btn' style='$botao_style' $botao_disabled onclick='aplicarVacina($id_paciente, $id_mod, this)'>$botao_texto</button>";
        echo "</td>";
        echo "</tr>";
    }

    echo '</tbody></table>';
    echo '</div>'; // Fecha .table-responsive
    echo '</div>'; // Fecha .tabela-vacinas-secao
}
?>