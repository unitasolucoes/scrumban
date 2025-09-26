<?php

include ('../../../inc/includes.php');

Html::header(__('Scrumban - Backlog', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "PluginScrumbanMenu", "backlog");

// Verificar quadros disponíveis
$boards = PluginScrumbanBoard::getActiveBoards();
if (empty($boards)) {
   echo "<div class='center'>";
   echo "<h3>Nenhum quadro encontrado</h3>";
   echo "<p>Você precisa criar um quadro antes de gerenciar o backlog.</p>";
   echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/front/board.form.php' class='btn btn-primary'>";
   echo "<i class='fas fa-plus me-2'></i>Criar Quadro";
   echo "</a>";
   echo "</div>";
   Html::footer();
   exit;
}

// Pegar ID do quadro
$board_id = isset($_GET['board_id']) ? intval($_GET['board_id']) : $boards[0]['id'];

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
echo "<div class='d-flex gap-2'>";
echo "<button class='btn btn-light btn-sm'><i class='fas fa-cog'></i> " . __('Configurações', 'scrumban') . "</button>";
echo "<button class='btn btn-outline-light btn-sm'><i class='fas fa-user'></i> " . $_SESSION['glpiname'] . "</button>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Navegação
$board->showNavigationTabs('backlog');

// Mostrar página do backlog
PluginScrumbanBacklog::showBacklogPage($board_id);

echo "</div>";

Html::footer();
?>