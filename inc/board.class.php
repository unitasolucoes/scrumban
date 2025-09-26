<?php

if (!defined('GLPI_ROOT')) {
   die('Sorry. You cannot access directly to this file');
}

class PluginScrumbanBoard extends CommonDBTM {

   public $dohistory = true;
   public static $rightname = PluginScrumbanProfile::RIGHT_MANAGE;

   static function getTypeName($nb = 0) {
      return _n('Quadro', 'Quadros', $nb, 'scrumban');
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      return $ong;
   }

   function showForm($ID, $options = []) {
      if (!Session::haveRight(PluginScrumbanProfile::RIGHT_MANAGE, READ)) {
         return false;
      }

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Nome', 'scrumban') . " *</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'name');
      echo "</td>";
      echo "<td>" . __('Equipe', 'scrumban') . "</td>";
      echo "<td>";
      $teams = PluginScrumbanTeam::getTeamsForUser(Session::getLoginUserID(false));
      $optionsTeam = [];
      foreach ($teams as $team) {
         $optionsTeam[$team['id']] = $team['name'];
      }
      Dropdown::showFromArray('teams_id', $optionsTeam, [
         'value' => $this->fields['teams_id'],
         'display_emptychoice' => true,
         'emptylabel' => __('Selecione uma equipe', 'scrumban')
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Visibilidade', 'scrumban') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('visibility', [
         'public' => __('Público', 'scrumban'),
         'team' => __('Equipe', 'scrumban'),
         'private' => __('Privado', 'scrumban')
      ], ['value' => $this->fields['visibility'] ?: 'team']);
      echo "</td>";
      echo "<td>" . __('Ativo', 'scrumban') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Descrição', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea class='form-control' name='description' rows='4'>" . $this->fields['description'] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }

   function prepareInputForAdd($input) {
      $input['users_id_created'] = Session::getLoginUserID(false);
      $input['entities_id'] = $_SESSION['glpiactive_entity'];
      $input['is_recursive'] = 1;
      $input['date_creation'] = date('Y-m-d H:i:s');
      $input['date_mod'] = $input['date_creation'];
      return $input;
   }

   function prepareInputForUpdate($input) {
      $input['date_mod'] = date('Y-m-d H:i:s');
      return $input;
   }

   function post_addItem() {
      if ($this->fields['teams_id']) {
         PluginScrumbanTeamBoard::setPermissions($this->fields['teams_id'], $this->getID(), 1, 1);
      }
   }

   static function getBoardsForUser($user_id, $team_id = null) {
      global $DB;

      $criteria = [
         'glpi_plugin_scrumban_boards.is_active' => 1
      ];

      if ($team_id) {
         $criteria['glpi_plugin_scrumban_team_boards.teams_id'] = $team_id;
      }

      $iterator = $DB->request([
         'SELECT' => 'DISTINCT glpi_plugin_scrumban_boards.*',
         'FROM' => 'glpi_plugin_scrumban_boards',
         'LEFT JOIN' => [
            'glpi_plugin_scrumban_team_boards' => [
               'ON' => [
                  'glpi_plugin_scrumban_boards' => 'id',
                  'glpi_plugin_scrumban_team_boards' => 'boards_id'
               ]
            ],
            'glpi_plugin_scrumban_team_members' => [
               'ON' => [
                  'glpi_plugin_scrumban_team_boards' => 'teams_id',
                  'glpi_plugin_scrumban_team_members' => 'teams_id'
               ]
            ]
         ],
         'WHERE' => [
            'glpi_plugin_scrumban_team_members.users_id' => $user_id,
            'glpi_plugin_scrumban_boards.is_active' => 1
         ] + ($team_id ? ['glpi_plugin_scrumban_team_boards.teams_id' => $team_id] : []),
         'ORDER' => 'glpi_plugin_scrumban_boards.name'
      ]);

      $boards = [];
      foreach ($iterator as $row) {
         $boards[$row['id']] = $row;
      }

      if (!$team_id) {
         $public = $DB->request([
            'FROM' => 'glpi_plugin_scrumban_boards',
            'WHERE' => [
               'visibility' => 'public',
               'is_active' => 1
            ]
         ]);
         foreach ($public as $row) {
            $boards[$row['id']] = $row;
         }
      }

      return array_values($boards);
   }

   static function getBoardWithAccessCheck($board_id) {
      $board = new self();
      if (!$board->getFromDB($board_id)) {
         return null;
      }

      $user_id = Session::getLoginUserID(false);
      if (!$user_id) {
         return null;
      }

      if ($board->fields['visibility'] === 'public') {
         return $board;
      }

      if ($board->fields['users_id_created'] == $user_id) {
         return $board;
      }

      if (PluginScrumbanTeam::canUserAccessBoard($user_id, $board_id)) {
         return $board;
      }

      return null;
   }

   function getBoardStats() {
      global $DB;

      $stats = [
         'cards' => 0,
         'story_points' => 0,
         'by_status' => []
      ];

      $iterator = $DB->request([
         'SELECT' => [
            'status',
            'COUNT(*) AS total',
            'SUM(story_points) AS points'
         ],
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'boards_id' => $this->getID()
         ],
         'GROUPBY' => 'status'
      ]);

      foreach ($iterator as $row) {
         $stats['cards'] += (int)$row['total'];
         $stats['story_points'] += (int)$row['points'];
         $stats['by_status'][$row['status']] = (int)$row['total'];
      }

      return $stats;
   }
}
