<?php
include ('../../../inc/includes.php');

Session::checkLoginUser();
Session::haveRight(PluginScrumbanProfile::RIGHT_VIEW, READ);

$board_id = (int)($_GET['boards_id'] ?? 0);
$team_id = (int)($_GET['team_id'] ?? 0);
$assignee_filter = $_GET['assignee'] ?? '';
$type_filter = $_GET['type'] ?? '';
$priority_filter = $_GET['priority'] ?? '';

$available_teams = PluginScrumbanTeam::getTeamsForUser(Session::getLoginUserID(false));

if (!$board_id && !empty($available_teams)) {
   $default_boards = PluginScrumbanBoard::getBoardsForUser(Session::getLoginUserID(false), $team_id ?: null);
   if (!empty($default_boards)) {
      $board_id = $default_boards[0]['id'];
   }
}

$board = $board_id ? PluginScrumbanBoard::getBoardWithAccessCheck($board_id) : null;
$team_members = [];
if ($board && !empty($board->fields['teams_id'])) {
   $team_members = PluginScrumbanTeam::getMembersData($board->fields['teams_id']);
}

Html::header(__('Quadros Kanban', 'scrumban'), '', 'tools', 'pluginScrumbanMenu', 'board');

echo "<div class='scrumban-board-page container-fluid'>";

echo "<div class='scrumban-board-toolbar d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4'>";

echo "<div>";
if ($board) {
   echo "<h2 class='mb-0'>" . htmlspecialchars($board->fields['name']) . "</h2>";
   echo "<p class='text-muted mb-0'>" . htmlspecialchars($board->fields['description']) . "</p>";
} else {
   echo "<h2 class='mb-0'>" . __('Selecione um quadro', 'scrumban') . "</h2>";
}
echo "</div>";

echo "<div class='d-flex gap-3 flex-wrap'>";

echo "<div class='scrumban-selector'>";
echo "<label class='form-label'>" . __('Equipe', 'scrumban') . "</label>";
echo "<select class='form-select' id='scrumban-team-select'>";
echo "<option value=''>" . __('Todas as equipes', 'scrumban') . "</option>";
foreach ($available_teams as $team) {
   $selected = $team_id == $team['id'] ? 'selected' : '';
   echo "<option value='" . $team['id'] . "' $selected>" . htmlspecialchars($team['name']) . "</option>";
}
echo "</select>";
echo "</div>";

$boards = PluginScrumbanBoard::getBoardsForUser(Session::getLoginUserID(false), $team_id ?: null);
$board_options = [];
foreach ($boards as $item) {
   $board_options[$item['id']] = $item['name'];
}
echo "<div class='scrumban-selector'>";
echo "<label class='form-label'>" . __('Quadro', 'scrumban') . "</label>";
echo "<select class='form-select' id='scrumban-board-select'>";
foreach ($board_options as $value => $label) {
   $selected = $board_id == $value ? 'selected' : '';
   echo "<option value='" . $value . "' $selected>" . htmlspecialchars($label) . "</option>";
}
echo "</select>";
echo "</div>";

$filters_disabled = $board ? '' : 'disabled';

echo "<div class='scrumban-selector'>";
echo "<label class='form-label'>" . __('Responsável', 'scrumban') . "</label>";
echo "<select class='form-select' id='scrumban-filter-assignee' $filters_disabled>";
echo "<option value=''>" . __('Todos os responsáveis', 'scrumban') . "</option>";
foreach ($team_members as $member) {
   $value = (int)$member['users_id'];
   $selected = ((string)$assignee_filter === (string)$value) ? 'selected' : '';
   echo "<option value='" . $value . "' $selected>" . htmlspecialchars($member['name']) . "</option>";
}
echo "</select>";
echo "</div>";

echo "<div class='scrumban-selector'>";
echo "<label class='form-label'>" . __('Tipo de Issue', 'scrumban') . "</label>";
echo "<select class='form-select' id='scrumban-filter-type' $filters_disabled>";
echo "<option value=''>" . __('Todos os tipos', 'scrumban') . "</option>";
foreach (PluginScrumbanUtils::TYPES as $key => $label) {
   $selected = ((string)$type_filter === (string)$key) ? 'selected' : '';
   echo "<option value='" . htmlspecialchars($key) . "' $selected>" . __($label, 'scrumban') . "</option>";
}
echo "</select>";
echo "</div>";

echo "<div class='scrumban-selector'>";
echo "<label class='form-label'>" . __('Prioridade', 'scrumban') . "</label>";
echo "<select class='form-select' id='scrumban-filter-priority' $filters_disabled>";
echo "<option value=''>" . __('Todas as prioridades', 'scrumban') . "</option>";
foreach (PluginScrumbanUtils::PRIORITIES as $key => $label) {
   $selected = ((string)$priority_filter === (string)$key) ? 'selected' : '';
   echo "<option value='" . htmlspecialchars($key) . "' $selected>" . __($label, 'scrumban') . "</option>";
}
echo "</select>";
echo "</div>";

if (Session::haveRight(PluginScrumbanProfile::RIGHT_EDIT, CREATE)) {
   echo "<button class='btn btn-primary' id='scrumban-add-card'><i class='ti ti-plus'></i> " . __('Novo card', 'scrumban') . "</button>";
}

echo "</div>";
echo "</div>";

if (!$board) {
   echo "<div class='alert alert-info'>" . __('Você ainda não possui acesso a nenhum quadro. Solicite acesso a uma equipe ou crie um novo quadro.', 'scrumban') . "</div>";
} else {
   echo "<div id='scrumban-kanban' data-board='" . $board->getID() . "'>";
   foreach (PluginScrumbanUtils::STATUSES as $status_key => $status_label) {
      echo "<div class='scrumban-column' data-status='" . $status_key . "'>";
      echo "<div class='scrumban-column__header'>";
      echo "<span class='scrumban-column__title'>" . __($status_label, 'scrumban') . "</span>";
      echo "<span class='badge bg-secondary' data-count='0'>0</span>";
      echo "</div>";
      echo "<div class='scrumban-column__body' data-status-list='" . $status_key . "'></div>";
      echo "</div>";
   }
   echo "</div>";
}

echo "</div>";

include __DIR__ . '/card.form.php';
include __DIR__ . '/templates/card_modal_static.php';

Html::footer();
