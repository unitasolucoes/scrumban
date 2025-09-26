<?php

include ('../../../inc/includes.php');

// Verificar quadros disponíveis
$boards = PluginScrumbanBoard::getActiveBoards();
if (empty($boards)) {
   Html::header(__('Scrumban - Cronograma', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "cronograma");
   echo "<div class='center'>";
   echo "<h3>Nenhum quadro encontrado</h3>";
   echo "<p>Você precisa criar um quadro antes de visualizar o cronograma.</p>";
   echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/front/board.form.php' class='btn btn-primary'>";
   echo "<i class='fas fa-plus me-2'></i>Criar Quadro";
   echo "</a>";
   echo "</div>";
   Html::footer();
   exit;
}

$requested_board_id = isset($_GET['board_id']) ? intval($_GET['board_id']) : null;
$accessible_board = null;

foreach ($boards as $board_data) {
   if (!PluginScrumbanTeam::canUserAccessBoard($_SESSION['glpiID'], $board_data['id'])) {
      continue;
   }

   if ($requested_board_id !== null && $board_data['id'] == $requested_board_id) {
      $accessible_board = $board_data;
      break;
   }

   if ($accessible_board === null) {
      $accessible_board = $board_data;
   }
}

if ($accessible_board === null) {
   Html::header(__('Scrumban - Cronograma', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "cronograma");
   echo "<div class='container mt-3'>";
   echo "<div class='alert alert-danger' role='alert'>";
   echo __('Você não tem permissão para acessar nenhum quadro ativo.', 'scrumban');
   echo "</div>";
   echo "</div>";
   Html::footer();
   exit;
}

$board_id = (int)$accessible_board['id'];

if ($requested_board_id !== null && $requested_board_id !== $board_id) {
   Html::redirect($CFG_GLPI['root_doc'] . "/plugins/scrumban/front/timeline.php?board_id=" . $board_id);
   exit;
}

Html::header(__('Scrumban - Cronograma', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "cronograma");

// Incluir CSS e JS específicos
echo "<link rel='stylesheet' type='text/css' href='" . Plugin::getWebDir('scrumban') . "/css/scrumban.css'>";
echo "<script src='" . Plugin::getWebDir('scrumban') . "/js/scrumban.js'></script>";

// Renderizar header do sistema
$board = new PluginScrumbanBoard();
$board->getFromDB($board_id);

echo "<div class='agilepm-container'>";

// Header
echo "<div class='agilepm-header'>";
echo "<div class='container-fluid'>";
echo "<div class='d-flex justify-content-between align-items-center'>";
echo "<div>";
echo "<h1><i class='fas fa-project-diagram me-3'></i>" . $board->fields['name'] . "</h1>";
echo "<p class='mb-0 opacity-75'>" . __('Sistema de Gestão de Projetos Ágeis', 'scrumban') . "</p>";
echo "</div>";
echo "<div class='d-flex gap-2'>";
echo "<button class='btn btn-light btn-sm'><i class='fas fa-cog'></i> " . __('Configurações', 'scrumban') . "</button>";
echo "<button class='btn btn-outline-light btn-sm'><i class='fas fa-user'></i> " . $_SESSION['glpiname'] . "</button>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Navegação
$board->showNavigationTabs('timeline');

// Mostrar página do cronograma
echo "<div class='timeline-container fade-in'>";
echo "<div class='timeline-header'>";
echo "<div class='d-flex justify-content-between align-items-center'>";
echo "<div>";
echo "<h2>" . __('Cronograma do Projeto', 'scrumban') . "</h2>";
echo "<p class='text-muted mb-0'>" . __('Visualização temporal das sprints e marcos', 'scrumban') . "</p>";
echo "</div>";
echo "<div class='d-flex gap-3 align-items-center'>";
echo "<div class='btn-group' role='group'>";
echo "<button type='button' class='btn btn-outline-primary active'>" . __('Timeline', 'scrumban') . "</button>";
echo "<button type='button' class='btn btn-outline-primary'>" . __('Semana', 'scrumban') . "</button>";
echo "</div>";
echo "<select class='form-select' style='max-width: 200px;'>";
echo "<option>" . __('Próximos 3 meses', 'scrumban') . "</option>";
echo "<option>" . __('Próximos 6 meses', 'scrumban') . "</option>";
echo "<option>" . __('Próximo ano', 'scrumban') . "</option>";
echo "</select>";
echo "</div>";
echo "</div>";
echo "</div>";

// Buscar sprints do quadro
global $DB;
$sprints_iterator = $DB->request([
   'FROM' => 'glpi_plugin_scrumban_sprints',
   'WHERE' => [
      'boards_id' => $board_id,
      'is_active' => 1
   ],
   'ORDER' => ['start_date', 'name']
]);

$sprints = [];
foreach ($sprints_iterator as $sprint_data) {
   $sprint = new PluginScrumbanSprint();
   $sprint->fields = $sprint_data;
   $metrics = $sprint->calculateMetrics();
   
   $sprints[] = [
      'id' => $sprint_data['id'],
      'name' => $sprint_data['name'],
      'status' => $sprint_data['status'],
      'start_date' => $sprint_data['start_date'],
      'end_date' => $sprint_data['end_date'],
      'metrics' => $metrics
   ];
}

echo "<div class='timeline-view'>";

// Cabeçalho com datas
echo "<div class='timeline-grid'>";
echo "<div class='timeline-labels'>";
echo "<div style='height: 40px; display: flex; align-items: center; padding: 0 15px; font-weight: 600; background: #e9ecef;'>";
echo __('Sprints', 'scrumban');
echo "</div>";
echo "</div>";
echo "<div class='timeline-content'>";
echo "<div class='timeline-dates'>";

// Gerar datas do timeline (próximos 2 meses)
$start_date = new DateTime();
$end_date = new DateTime();
$end_date->add(new DateInterval('P2M'));

$current = clone $start_date;
while ($current <= $end_date) {
   echo "<div class='timeline-date'>" . $current->format('M d') . "</div>";
   $current->add(new DateInterval('P1W'));
}
echo "</div>";
echo "</div>";
echo "</div>";

// Renderizar cada sprint
foreach ($sprints as $sprint) {
   $status_class = 'sprint-' . $sprint['status'];
   
   echo "<div class='timeline-grid'>";
   echo "<div class='timeline-labels'>";
   echo "<div class='timeline-row'>";
   echo "<div class='timeline-label'>";
   echo "<div>";
   echo "<div style='font-weight: 600;'>" . htmlspecialchars($sprint['name']) . "</div>";
   echo "<div style='font-size: 0.8rem; color: #6c757d;'>";
   
   $status_labels = [
      'planejado' => __('Planejado', 'scrumban'),
      'ativo' => __('Ativo', 'scrumban'),
      'concluido' => __('Concluído', 'scrumban')
   ];
   echo $status_labels[$sprint['status']];
   echo "</div>";
   echo "</div>";
   echo "</div>";
   echo "</div>";
   echo "</div>";
   
   echo "<div class='timeline-content'>";
   echo "<div class='timeline-row'>";
   echo "<div class='timeline-bar-container'>";
   
   // Calcular posição e largura da barra
   $bar_left = 16; // posição inicial em %
   $bar_width = 25; // largura em %
   
   echo "<div class='timeline-bar $status_class' style='left: {$bar_left}%; width: {$bar_width}%;' onclick='showSprintDetails({$sprint['id']})'>";
   echo $sprint['metrics']['progress_percentage'] . "% | " . $sprint['metrics']['completed_points'] . "/" . $sprint['metrics']['planned_points'] . " pts";
   echo "</div>";
   echo "</div>";
   echo "</div>";
   echo "</div>";
   echo "</div>";
}

// Resumo do projeto
$total_sprints = count($sprints);
$completed_sprints = count(array_filter($sprints, function($s) { return $s['status'] == 'concluido'; }));
$active_sprints = count(array_filter($sprints, function($s) { return $s['status'] == 'ativo'; }));

$total_points = array_sum(array_map(function($s) { return $s['metrics']['planned_points']; }, $sprints));
$velocity = $completed_sprints > 0 ? round($total_points / $completed_sprints, 1) : 0;

echo "<div class='mt-4 p-3 bg-light rounded'>";
echo "<h5>" . __('Resumo do Projeto', 'scrumban') . "</h5>";
echo "<div class='row'>";
echo "<div class='col-md-2 text-center'>";
echo "<div style='font-size: 1.5rem; font-weight: 700; color: #007bff;'>$total_sprints</div>";
echo "<div style='font-size: 0.8rem; color: #6c757d;'>" . __('Total Sprints', 'scrumban') . "</div>";
echo "</div>";
echo "<div class='col-md-2 text-center'>";
echo "<div style='font-size: 1.5rem; font-weight: 700; color: #28a745;'>$completed_sprints</div>";
echo "<div style='font-size: 0.8rem; color: #6c757d;'>" . __('Concluídas', 'scrumban') . "</div>";
echo "</div>";
echo "<div class='col-md-2 text-center'>";
echo "<div style='font-size: 1.5rem; font-weight: 700; color: #ffc107;'>$active_sprints</div>";
echo "<div style='font-size: 0.8rem; color: #6c757d;'>" . __('Ativas', 'scrumban') . "</div>";
echo "</div>";
echo "<div class='col-md-2 text-center'>";
echo "<div style='font-size: 1.5rem; font-weight: 700; color: #17a2b8;'>$total_points</div>";
echo "<div style='font-size: 0.8rem; color: #6c757d;'>" . __('Story Points', 'scrumban') . "</div>";
echo "</div>";
echo "<div class='col-md-2 text-center'>";
echo "<div style='font-size: 1.5rem; font-weight: 700; color: #6f42c1;'>$velocity</div>";
echo "<div style='font-size: 0.8rem; color: #6c757d;'>" . __('Velocity Média', 'scrumban') . "</div>";
echo "</div>";
echo "<div class='col-md-2 text-center'>";
echo "<div style='font-size: 1.5rem; font-weight: 700; color: #fd7e14;'>" . __('Mar', 'scrumban') . "</div>";
echo "<div style='font-size: 0.8rem; color: #6c757d;'>" . __('Conclusão Est.', 'scrumban') . "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>"; // timeline-view
echo "</div>"; // timeline-container
echo "</div>"; // agilepm-container

?>

<script>
function showSprintDetails(sprintId) {
   // Implementar modal com detalhes da sprint
   alert('Detalhes da Sprint ' + sprintId + ' - Funcionalidade em desenvolvimento');
}
</script>

<?php
Html::footer();
?>