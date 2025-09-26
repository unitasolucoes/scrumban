<?php
include ('../../../inc/includes.php');

Session::checkLoginUser();
Session::haveRight(PluginScrumbanProfile::RIGHT_VIEW, READ);

$team_id = (int)($_GET['team_id'] ?? 0);

$teams = PluginScrumbanTeam::getTeamsForUser(Session::getLoginUserID(false));
$boards = [];
if ($team_id) {
   $boards = PluginScrumbanBoard::getBoardsForUser(Session::getLoginUserID(false), $team_id);
}

Html::header(__('Dashboard Scrumban', 'scrumban'), '', 'tools', 'pluginScrumbanMenu', 'dashboard');

echo "<div class='scrumban-dashboard container-fluid'>";

echo "<div class='scrumban-page__header'>";
echo "<div>";
echo "<h2><i class='ti ti-chart-donut me-2'></i>" . __('Dashboard', 'scrumban') . "</h2>";
echo "<p class='text-muted'>" . __('Métricas de equipe e andamento das entregas.', 'scrumban') . "</p>";
echo "</div>";

echo "<div class='scrumban-selector'>";
$team_options = ['' => __('Todas as equipes', 'scrumban')];
foreach ($teams as $team) {
   $team_options[$team['id']] = $team['name'];
}
Dropdown::showFromArray('scrumban-dashboard-team', $team_options, [
   'value' => $team_id,
   'display' => false,
   'rand' => 'scrumban-dashboard-team'
]);
echo Dropdown::getDropdownId('scrumban-dashboard-team');
echo "</div>";
echo "</div>";

if (!$team_id) {
   echo "<div class='alert alert-info'>" . __('Selecione uma equipe para visualizar estatísticas.', 'scrumban') . "</div>";
} else {
   echo "<div class='row g-4'>";

   $total_cards = $total_points = 0;
   foreach ($boards as $board_data) {
      $board = new PluginScrumbanBoard();
      $board->fields = $board_data;
      $stats = $board->getBoardStats();
      $total_cards += $stats['cards'];
      $total_points += $stats['story_points'];

      echo "<div class='col-lg-6'>";
      echo "<div class='scrumban-card card h-100'>";
      echo "<div class='card-body'>";
      echo "<div class='d-flex justify-content-between align-items-start mb-3'>";
      echo "<div>";
      echo "<h5 class='card-title mb-1'>" . htmlspecialchars($board_data['name']) . "</h5>";
      echo "<p class='text-muted mb-0'>" . htmlspecialchars($board_data['description']) . "</p>";
      echo "</div>";
      echo "<span class='badge bg-primary'>" . __('Quadro', 'scrumban') . "</span>";
      echo "</div>";

      echo "<div class='row g-3'>";
      echo "<div class='col-6'>";
      echo "<div class='scrumban-stat-box'>";
      echo "<div class='scrumban-stat-box__label'>" . __('Cards', 'scrumban') . "</div>";
      echo "<div class='scrumban-stat-box__value'>" . $stats['cards'] . "</div>";
      echo "</div>";
      echo "</div>";
      echo "<div class='col-6'>";
      echo "<div class='scrumban-stat-box'>";
      echo "<div class='scrumban-stat-box__label'>" . __('Story Points', 'scrumban') . "</div>";
      echo "<div class='scrumban-stat-box__value'>" . $stats['story_points'] . "</div>";
      echo "</div>";
      echo "</div>";
      echo "</div>";

      echo "<div class='scrumban-status-summary mt-3'>";
      foreach (PluginScrumbanUtils::STATUSES as $key => $label) {
         $count = $stats['by_status'][$key] ?? 0;
         echo "<div class='scrumban-status-summary__item'>";
         echo "<span class='scrumban-status-summary__label'>" . __($label, 'scrumban') . "</span>";
         echo "<span class='scrumban-status-summary__badge'>" . $count . "</span>";
         echo "</div>";
      }
      echo "</div>";

      echo "</div>";
      echo "</div>";
      echo "</div>";
   }

   echo "<div class='col-12'>";
   echo "<div class='scrumban-highlight card'>";
   echo "<div class='card-body d-flex flex-wrap justify-content-between align-items-center'>";
   echo "<div>";
   echo "<h5 class='mb-1'>" . __('Resumo da equipe', 'scrumban') . "</h5>";
   echo "<p class='text-muted mb-0'>" . __('Total consolidado considerando todos os quadros da equipe selecionada.', 'scrumban') . "</p>";
   echo "</div>";
   echo "<div class='d-flex gap-4 flex-wrap'>";
   echo "<div class='scrumban-stat-box'>";
   echo "<div class='scrumban-stat-box__label'>" . __('Total de cards', 'scrumban') . "</div>";
   echo "<div class='scrumban-stat-box__value'>" . $total_cards . "</div>";
   echo "</div>";
   echo "<div class='scrumban-stat-box'>";
   echo "<div class='scrumban-stat-box__label'>" . __('Story Points', 'scrumban') . "</div>";
   echo "<div class='scrumban-stat-box__value'>" . $total_points . "</div>";
   echo "</div>";
   echo "</div>";
   echo "</div>";
   echo "</div>";
   echo "</div>";

   echo "</div>";
}

echo "</div>";

Html::footer();
