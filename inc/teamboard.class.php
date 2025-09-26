<?php

if (!defined('GLPI_ROOT')) {
   die('Sorry. You cannot access directly to this file');
}

class PluginScrumbanTeamBoard extends CommonDBTM {

   public $dohistory = true;
   public static $rightname = PluginScrumbanProfile::RIGHT_TEAM;

   static function getTypeName($nb = 0) {
      return _n('Permissão de Quadro', 'Permissões de Quadros', $nb, 'scrumban');
   }

   function showForm($ID, $options = []) {
      return false;
   }

   static function userHasAccess($user_id, $board_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => '1',
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'INNER JOIN' => [
            'glpi_plugin_scrumban_team_boards' => [
               'ON' => [
                  'glpi_plugin_scrumban_team_members' => 'teams_id',
                  'glpi_plugin_scrumban_team_boards' => 'teams_id'
               ]
            ]
         ],
         'WHERE' => [
            'glpi_plugin_scrumban_team_members.users_id' => $user_id,
            'glpi_plugin_scrumban_team_boards.boards_id' => $board_id
         ],
         'LIMIT' => 1
      ]);

      return count($iterator) > 0;
   }

   static function userCanEdit($user_id, $board_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => '1',
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'INNER JOIN' => [
            'glpi_plugin_scrumban_team_boards' => [
               'ON' => [
                  'glpi_plugin_scrumban_team_members' => 'teams_id',
                  'glpi_plugin_scrumban_team_boards' => 'teams_id'
               ]
            ]
         ],
         'WHERE' => [
            'glpi_plugin_scrumban_team_members.users_id' => $user_id,
            'glpi_plugin_scrumban_team_boards.boards_id' => $board_id,
            'glpi_plugin_scrumban_team_boards.can_edit' => 1
         ],
         'LIMIT' => 1
      ]);

      return count($iterator) > 0;
   }

   static function userCanManage($user_id, $board_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => '1',
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'INNER JOIN' => [
            'glpi_plugin_scrumban_team_boards' => [
               'ON' => [
                  'glpi_plugin_scrumban_team_members' => 'teams_id',
                  'glpi_plugin_scrumban_team_boards' => 'teams_id'
               ]
            ]
         ],
         'WHERE' => [
            'glpi_plugin_scrumban_team_members.users_id' => $user_id,
            'glpi_plugin_scrumban_team_boards.boards_id' => $board_id,
            'glpi_plugin_scrumban_team_boards.can_manage' => 1
         ],
         'LIMIT' => 1
      ]);

      return count($iterator) > 0;
   }

   static function setPermissions($team_id, $board_id, $can_edit, $can_manage) {
      $link = new self();
      $existing = $link->find([
         'teams_id' => $team_id,
         'boards_id' => $board_id
      ], [], 1);

      if ($existing) {
         $data = reset($existing);
         $link->update([
            'id' => $data['id'],
            'can_edit' => (int)$can_edit,
            'can_manage' => (int)$can_manage
         ]);
      } else {
         $link->add([
            'teams_id' => $team_id,
            'boards_id' => $board_id,
            'can_edit' => (int)$can_edit,
            'can_manage' => (int)$can_manage,
            'date_creation' => date('Y-m-d H:i:s')
         ]);
      }
   }
}
