<?php
include ('../../../inc/includes.php');

PluginScrumbanUtils::requireAjax();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
   case 'load':
      $board_id = (int)($_GET['board_id'] ?? 0);
      $board = PluginScrumbanBoard::getBoardWithAccessCheck($board_id);
      if (!$board) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Quadro não encontrado', 'scrumban')], 'error', 404);
      }
      $filters = [
         'status' => $_GET['status'] ?? null,
         'assignee' => $_GET['assignee'] ?? null,
         'type' => $_GET['type'] ?? null,
         'priority' => $_GET['priority'] ?? null
      ];
      $cards = PluginScrumbanCard::getCardsForBoard($board_id, $filters);
      PluginScrumbanUtils::jsonResponse(['cards' => $cards]);
      break;

   case 'move':
      Session::checkRight(PluginScrumbanProfile::RIGHT_EDIT, UPDATE);
      $card_id = (int)($_POST['card_id'] ?? 0);
      $status = $_POST['status'] ?? '';
      if (!$card_id || !isset(PluginScrumbanUtils::STATUSES[$status])) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Movimentação inválida', 'scrumban')], 'error', 400);
      }
      $card = new PluginScrumbanCard();
      if (!$card->getFromDB($card_id)) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Card não encontrado', 'scrumban')], 'error', 404);
      }
      if (!PluginScrumbanTeam::canUserEditBoard(Session::getLoginUserID(false), $card->fields['boards_id'])) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Sem permissão para editar este quadro', 'scrumban')], 'error', 403);
      }
      $card->update([
         'id' => $card_id,
         'status' => $status,
         'date_mod' => date('Y-m-d H:i:s')
      ]);
      PluginScrumbanUtils::jsonResponse();
      break;

   default:
      PluginScrumbanUtils::jsonResponse(['message' => __('Ação desconhecida', 'scrumban')], 'error', 400);
}
