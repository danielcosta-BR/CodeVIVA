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

// 1. Buscar todas as vacinas modelo
$vacinas_modelo = [];
$sql_modelo = "SELECT * FROM vacinamodelo ORDER BY nome_vacina ASC";
$res_modelo = $conn->query($sql_modelo);
while($row = $res_modelo->fetch_assoc()) {
    $vacinas_modelo[] = $row;
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

// Montar Tabela HTML
echo '<table class="data-table" style="width:100%">';
echo '<thead><tr><th>Vacina</th><th>Recomendação</th><th>Situação Atual</th><th>Ação</th></tr></thead>';
echo '<tbody>';

foreach ($vacinas_modelo as $vacina) {
    $id_mod = $vacina['id_vacina_modelo'];
    $status_texto = "Pendente";
    $classe_status = "status-pendente"; // Você pode definir estilos CSS para isso
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
             $status_texto = "Agendada para $data_prev";
        }
    }

    echo "<tr>";
    echo "<td>" . htmlspecialchars($vacina['nome_vacina']) . "</td>";
    echo "<td>" . htmlspecialchars($vacina['recomendacao_idade']) . "</td>";
    echo "<td class='status-cell'>" . $status_texto . "</td>";
    echo "<td>";
    // O botão chama a função JS aplicarVacina definida no arquivo pai
    echo "<button class='action-btn' style='$botao_style' $botao_disabled onclick='aplicarVacina($id_paciente, $id_mod, this)'>$botao_texto</button>";
    echo "</td>";
    echo "</tr>";
}

echo '</tbody></table>';
?>