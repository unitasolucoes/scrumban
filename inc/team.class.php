<?php

if (!defined('GLPI_ROOT')) {
   die('Sorry. You cannot access directly to this file');
}

class PluginScrumbanTeam extends CommonDBTM {

   public $dohistory = true;
   public static $rightname = PluginScrumbanProfile::RIGHT_TEAM;

   static function getTypeName($nb = 0) {
      return _n('Equipe', 'Equipes', $nb, 'scrumban');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() === __CLASS__) {
         return __('Membros', 'scrumban');
      }
      return parent::getTabNameForItem($item, $withtemplate);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginScrumbanTeamMember', $ong, $options);
      $this->addStandardTab('PluginScrumbanTeamBoard', $ong, $options);
      return $ong;
   }

   function showForm($ID, $options = []) {
      if (!Session::haveRight(self::$rightname, READ)) {
         return false;
      }

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Nome', 'scrumban') . " *</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'name');
      echo "</td>";
      echo "<td>" . __('Ativa', 'scrumban') . "</td>";
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

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Gerente', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      User::dropdown([
         'name'  => 'manager_id',
         'value' => $this->fields['manager_id'],
         'right' => 'all'
      ]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }

   function prepareInputForAdd($input) {
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
      $user_id = Session::getLoginUserID(false);
      if ($user_id) {
         $member = new PluginScrumbanTeamMember();
         $member->add([
            'teams_id' => $this->getID(),
            'users_id' => $user_id,
            'role' => 'admin'
         ]);
      }
   }

   function userHasRole($user_id, array $roles) {
      $member = new PluginScrumbanTeamMember();
      return $member->userHasRole($user_id, $this->getID(), $roles);
   }

   static function getTeamsForUser($user_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_plugin_scrumban_teams.*',
            'glpi_plugin_scrumban_team_members.role'
         ],
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'INNER JOIN' => [
            'glpi_plugin_scrumban_teams' => [
               'ON' => [
                  'glpi_plugin_scrumban_team_members' => 'teams_id',
                  'glpi_plugin_scrumban_teams' => 'id'
               ]
            ]
         ],
         'WHERE' => [
            'glpi_plugin_scrumban_team_members.users_id' => $user_id,
            'glpi_plugin_scrumban_teams.is_active' => 1
         ],
         'ORDER' => 'glpi_plugin_scrumban_teams.name'
      ]);

      $teams = [];
      foreach ($iterator as $row) {
         $teams[] = $row;
      }
      return $teams;
   }

   static function getUserRole($user_id, $team_id) {
      $member = new PluginScrumbanTeamMember();
      return $member->getRole($user_id, $team_id);
   }

   static function canUserAccessBoard($user_id, $board_id) {
      return PluginScrumbanTeamBoard::userHasAccess($user_id, $board_id);
   }

   static function canUserManageBoard($user_id, $board_id) {
      return PluginScrumbanTeamBoard::userCanManage($user_id, $board_id);
   }

   static function canUserEditBoard($user_id, $board_id) {
      return PluginScrumbanTeamBoard::userCanEdit($user_id, $board_id);
   }

   static function getMembersData($team_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_plugin_scrumban_team_members.id',
            'glpi_plugin_scrumban_team_members.users_id',
            'glpi_plugin_scrumban_team_members.role',
            'glpi_users.firstname',
            'glpi_users.realname',
            'glpi_users.name AS login'
         ],
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'LEFT JOIN' => [
            'glpi_users' => [
               'ON' => [
                  'glpi_plugin_scrumban_team_members' => 'users_id',
                  'glpi_users' => 'id'
               ]
            ]
         ],
         'WHERE' => [
            'glpi_plugin_scrumban_team_members.teams_id' => $team_id
         ],
         'ORDER' => ['glpi_plugin_scrumban_team_members.role DESC', 'glpi_users.realname']
      ]);

      $members = [];
      foreach ($iterator as $row) {
         $members[] = [
            'id' => (int)$row['id'],
            'users_id' => (int)$row['users_id'],
            'role' => $row['role'],
            'name' => trim(($row['firstname'] ?? '') . ' ' . ($row['realname'] ?? '')) ?: $row['login']
         ];
      }

      return $members;
   }
}
