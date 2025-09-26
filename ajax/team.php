<?php
include ('../../../inc/includes.php');

PluginScrumbanUtils::requireAjax();

$DB = $GLOBALS['DB'];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
   case 'meta':
      Session::checkRight(PluginScrumbanProfile::RIGHT_TEAM, READ);
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
      PluginScrumbanUtils::jsonResponse(['users' => $users]);
      break;

   case 'get':
      Session::checkRight(PluginScrumbanProfile::RIGHT_TEAM, READ);
      $team_id = (int)($_GET['id'] ?? 0);
      $team = new PluginScrumbanTeam();
      if (!$team->getFromDB($team_id)) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Equipe não encontrada', 'scrumban')], 'error', 404);
      }
      $data = [
         'id' => $team->getID(),
         'name' => $team->fields['name'],
         'description' => $team->fields['description'],
         'manager_id' => $team->fields['manager_id'],
         'is_active' => $team->fields['is_active']
      ];
      $members = PluginScrumbanTeam::getMembersData($team_id);
      PluginScrumbanUtils::jsonResponse(['team' => $data, 'members' => $members]);
      break;

   case 'save':
      Session::checkRight(PluginScrumbanProfile::RIGHT_TEAM, UPDATE);
      $team = new PluginScrumbanTeam();
      $input = [
         'id' => $_POST['id'] ?? 0,
         'name' => $_POST['name'] ?? '',
         'description' => $_POST['description'] ?? '',
         'manager_id' => $_POST['manager_id'] ?? 0,
         'is_active' => $_POST['is_active'] ?? 1
      ];

      if (empty($input['name'])) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Nome é obrigatório', 'scrumban')], 'error', 400);
      }

      if (!empty($input['id'])) {
         $team->update($input);
         $team_id = (int)$input['id'];
      } else {
         $team_id = (int)$team->add($input);
      }

      PluginScrumbanUtils::jsonResponse(['team_id' => $team_id]);
      break;

   case 'members':
      Session::checkRight(PluginScrumbanProfile::RIGHT_TEAM, READ);
      $team_id = (int)($_GET['team_id'] ?? 0);
      $members = PluginScrumbanTeam::getMembersData($team_id);
      PluginScrumbanUtils::jsonResponse(['members' => $members]);
      break;

   case 'addMember':
      Session::checkRight(PluginScrumbanProfile::RIGHT_TEAM, UPDATE);
      $team_id = (int)($_POST['teams_id'] ?? 0);
      $user_id = (int)($_POST['users_id'] ?? 0);
      $role = $_POST['role'] ?? 'member';
      if (!$team_id || !$user_id) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Dados inválidos', 'scrumban')], 'error', 400);
      }
      $member = new PluginScrumbanTeamMember();
      $member->add([
         'teams_id' => $team_id,
         'users_id' => $user_id,
         'role' => $role
      ]);
      PluginScrumbanUtils::jsonResponse(['id' => $member->getID()]);
      break;

   case 'removeMember':
      Session::checkRight(PluginScrumbanProfile::RIGHT_TEAM, DELETE);
      $member_id = (int)($_POST['id'] ?? 0);
      if (!$member_id) {
         PluginScrumbanUtils::jsonResponse(['message' => __('Registro inválido', 'scrumban')], 'error', 400);
      }
      $member = new PluginScrumbanTeamMember();
      $member->delete(['id' => $member_id]);
      PluginScrumbanUtils::jsonResponse();
      break;

   default:
      PluginScrumbanUtils::jsonResponse(['message' => __('Ação desconhecida', 'scrumban')], 'error', 400);
}
