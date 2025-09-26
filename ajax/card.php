<?php
include ('../../../inc/includes.php');

PluginScrumbanUtils::requireAjax();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
   case 'get':
      global $DB;

      $card_id = (int)($_GET['id'] ?? 0);
      $board_id = (int)($_GET['board_id'] ?? 0);
      $card = new PluginScrumbanCard();
      $data = [];

      if ($card_id > 0) {
         if (!$card->getFromDB($card_id)) {
            PluginScrumbanUtils::jsonResponse(['message' => __('Card não encontrado', 'scrumban')], 'error', 404);
         }
         $board_id = (int)$card->fields['boards_id'];
         if (!PluginScrumbanTeam::canUserAccessBoard(Session::getLoginUserID(false), $board_id)) {
            PluginScrumbanUtils::jsonResponse(['message' => __('Acesso negado', 'scrumban')], 'error', 403);
         }
         $data = $card->fields;
      } else {
         if (!$board_id) {
            PluginScrumbanUtils::jsonResponse(['message' => __('Quadro não encontrado', 'scrumban')], 'error', 404);
         }
         if (!PluginScrumbanTeam::canUserAccessBoard(Session::getLoginUserID(false), $board_id)) {
            PluginScrumbanUtils::jsonResponse(['message' => __('Acesso negado', 'scrumban')], 'error', 403);
         }
         $data = [
            'id' => 0,
            'boards_id' => $board_id,
            'name' => '',
            'description' => '',
            'status' => 'backlog',
            'type' => 'task',
            'priority' => 'NORMAL',
            'story_points' => null,
            'users_id_assigned' => null,
            'users_id_requester' => null,
            'date_planned' => null,
            'date_completion' => null,
            'sprint_id' => null,
            'labels' => '',
            'branch' => '',
            'pull_request' => '',
            'commits' => '',
            'dor_percentage' => 0,
            'dod_percentage' => 0,
            'acceptance_criteria' => '[]',
            'test_scenarios' => '[]'
         ];
      }

      $data['labels'] = PluginScrumbanUtils::parseLabels($data['labels']);
      $data['acceptance_criteria'] = json_decode($data['acceptance_criteria'] ?? '[]', true) ?: [];
      $data['test_scenarios'] = json_decode($data['test_scenarios'] ?? '[]', true) ?: [];
      $data['history'] = $card_id ? $card->getHistory() : [];

      $users = [];
      $iterator = $DB->request([
         'SELECT' => ['id', 'firstname', 'realname', 'name'],
         'FROM' => 'glpi_users',
         'WHERE' => ['is_active' => 1],
         'ORDER' => ['realname', 'firstname'],
         'LIMIT' => 200
      ]);
      foreach ($iterator as $row) {
        $users[] = [
           'id' => (int)$row['id'],
           'name' => trim(($row['firstname'] ?? '') . ' ' . ($row['realname'] ?? '')) ?: $row['name']
        ];
      }

      $sprints = [];
      if ($board_id) {
         $sprintObj = new PluginScrumbanSprint();
         $sprints = array_values($sprintObj->find(['boards_id' => $board_id], ['date_start DESC']));
      }

      PluginScrumbanUtils::jsonResponse([
         'card' => $data,
         'meta' => [
            'statuses' => PluginScrumbanUtils::STATUSES,
            'types' => PluginScrumbanUtils::TYPES,
            'priorities' => PluginScrumbanUtils::PRIORITIES,
            'scenario_statuses' => PluginScrumbanUtils::SCENARIO_STATUSES,
            'users' => $users,
            'sprints' => $sprints
         ]
      ]);
      break;

   case 'save':
      Session::checkRight(PluginScrumbanProfile::RIGHT_EDIT, UPDATE);
      $card = new PluginScrumbanCard();
      $input = $_POST;
      $card_id = (int)($input['id'] ?? 0);
      $board_id = (int)($input['boards_id'] ?? 0);

      if (!$board_id && $card_id) {
         if ($card->getFromDB($card_id)) {
            $board_id = (int)$card->fields['boards_id'];
         }
      }

      if (!$board_id) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Quadro não encontrado', 'scrumban')], 'error', 404);
      }

      if (!PluginScrumbanTeam::canUserEditBoard(Session::getLoginUserID(false), $board_id)) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Acesso negado', 'scrumban')], 'error', 403);
      }

      $input['boards_id'] = $board_id;
      $input['labels'] = PluginScrumbanUtils::sanitizeLabels($input['labels'] ?? []);
      $input['acceptance_criteria'] = json_encode($input['acceptance_criteria'] ?? []);
      $input['test_scenarios'] = json_encode($input['test_scenarios'] ?? []);
      $input['dor_percentage'] = max(0, min(100, (int)($input['dor_percentage'] ?? 0)));
      $input['dod_percentage'] = max(0, min(100, (int)($input['dod_percentage'] ?? 0)));

      if ($card_id) {
         $card->check($card_id, UPDATE);
         $card->update($input);
      } else {
         $card->check(-1, CREATE, $input);
         $card_id = $card->add($input);
      }
      PluginScrumbanUtils::jsonResponse(['id' => $card_id]);
      break;

   case 'delete':
      Session::checkRight(PluginScrumbanProfile::RIGHT_EDIT, DELETE);
      $card_id = (int)($_POST['id'] ?? 0);
      if (!$card_id) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Card inválido', 'scrumban')], 'error', 400);
      }
      $card = new PluginScrumbanCard();
      $card->check($card_id, DELETE);
      $card->delete(['id' => $card_id]);
      PluginScrumbanUtils::jsonResponse();
      break;

   case 'comment':
      $card_id = (int)($_POST['id'] ?? 0);
      $content = trim($_POST['content'] ?? '');
      if (!$card_id || $content === '') {
         PluginScrumbanUtils::jsonResponse(['message' => __('Comentário inválido', 'scrumban')], 'error', 400);
      }
      $card = new PluginScrumbanCard();
      if (!$card->getFromDB($card_id)) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Card não encontrado', 'scrumban')], 'error', 404);
      }
      $card->addComment($content);
      PluginScrumbanUtils::jsonResponse();
      break;

   default:
      PluginScrumbanUtils::jsonResponse(['message' => __('Ação desconhecida', 'scrumban')], 'error', 400);
}
