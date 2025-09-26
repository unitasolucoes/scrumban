<?php
include ('../../../inc/includes.php');

PluginScrumbanUtils::requireAjax();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
   case 'list':
      $team_id = (int)($_GET['team_id'] ?? 0);
      $boards = PluginScrumbanBoard::getBoardsForUser(Session::getLoginUserID(false), $team_id ?: null);
      PluginScrumbanUtils::jsonResponse(['boards' => $boards]);
      break;

   case 'stats':
      $board_id = (int)($_GET['board_id'] ?? 0);
      $board = PluginScrumbanBoard::getBoardWithAccessCheck($board_id);
      if (!$board) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Quadro não encontrado', 'scrumban')], 'error', 404);
      }
      PluginScrumbanUtils::jsonResponse(['stats' => $board->getBoardStats()]);
      break;

   default:
      PluginScrumbanUtils::jsonResponse(['message' => __('Ação desconhecida', 'scrumban')], 'error', 400);
}
