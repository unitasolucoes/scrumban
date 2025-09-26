<?php

include ('../../../inc/includes.php');

Html::header(__('Scrumban - Backlog', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "backlog");

// Verificar quadros disponíveis
$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : null;
$user_teams = PluginScrumbanTeam::getTeamsForUser($_SESSION['glpiID']);

if ($team_id) {
   $allowed_team_ids = array_map('intval', array_column($user_teams, 'id'));
   if (!in_array($team_id, $allowed_team_ids, true)) {
      $team_id = null;
   }
}

$boards = PluginScrumbanTeam::getBoardsForUser($_SESSION['glpiID'], $team_id);
if (empty($boards)) {
   echo "<div class='center'>";
   if ($team_id) {
      echo "<h3>" . __('Nenhum quadro disponível para esta equipe', 'scrumban') . "</h3>";
      echo "<p>" . __('Associe um quadro à equipe selecionada ou escolha outra equipe para continuar.', 'scrumban') . "</p>";
   } else {
      echo "<h3>" . __('Nenhum quadro encontrado', 'scrumban') . "</h3>";
      echo "<p>" . __('Você precisa criar um quadro antes de gerenciar o backlog.', 'scrumban') . "</p>";
   }
   echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/front/board.form.php' class='btn btn-primary'>";
   echo "<i class='fas fa-plus me-2'></i>Criar Quadro";
   echo "</a>";
   echo "</div>";
   Html::footer();
   exit;
}

// Pegar ID do quadro
$board_id = isset($_GET['board_id']) ? (int)$_GET['board_id'] : (int)$boards[0]['id'];
$board_ids = array_map('intval', array_column($boards, 'id'));

if (!in_array($board_id, $board_ids, true)) {
   $board_id = (int)$boards[0]['id'];
}

// Incluir CSS e JS específicos
echo "<link rel='stylesheet' type='text/css' href='" . Plugin::getWebDir('scrumban') . "/css/scrumban.css'>";
echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js'></script>";
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
echo "<div class='d-flex gap-2 flex-wrap justify-content-end align-items-center'>";

$board_selector = plugin_scrumban_render_board_selector($board_id, [
   'boards' => $boards,
   'team_id' => $team_id,
   'team_selector_id' => 'scrumban-team-selector',
   'always_show' => true,
   'label' => __('Quadro', 'scrumban'),
   'label_class' => 'form-label text-white-50 mb-1 small',
   'wrapper_class' => 'scrumban-selector text-start d-flex flex-column'
]);

if (!empty($board_selector)) {
   echo $board_selector;
}

$team_selector = plugin_scrumban_render_team_selector($team_id, [
   'teams' => $user_teams,
   'board_selector_id' => 'scrumban-board-selector',
   'label' => __('Equipe', 'scrumban'),
   'placeholder' => __('Todas as equipes', 'scrumban'),
   'label_class' => 'form-label text-white-50 mb-1 small',
   'wrapper_class' => 'scrumban-selector text-start d-flex flex-column'
]);

if (!empty($team_selector)) {
   echo $team_selector;
}

echo "<button class='btn btn-light btn-sm'><i class='fas fa-cog'></i> " . __('Configurações', 'scrumban') . "</button>";
echo "<button class='btn btn-outline-light btn-sm'><i class='fas fa-user'></i> " . $_SESSION['glpiname'] . "</button>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Navegação
$board->showNavigationTabs('backlog', ['team_id' => $team_id, 'board_id' => $board_id]);

// Mostrar página do backlog
PluginScrumbanBacklog::showBacklogPage($board_id);

echo "</div>";

Html::footer();
?>