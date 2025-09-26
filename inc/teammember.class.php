<?php

if (!defined('GLPI_ROOT')) {
   die('Sorry. You cannot access directly to this file');
}

class PluginScrumbanTeamMember extends CommonDBTM {

   public $dohistory = true;
   public static $rightname = PluginScrumbanProfile::RIGHT_TEAM;

   static function getTypeName($nb = 0) {
      return _n('Membro', 'Membros', $nb, 'scrumban');
   }

   function getSearchOptionsNew() {
      $tab = [];
      $tab[] = [
         'id' => 'common',
         'name' => self::getTypeName(2)
      ];
      return $tab;
   }

   function showForm($ID, $options = []) {
      return false;
   }

   function canViewItem() {
      return Session::haveRight(self::$rightname, READ);
   }

   function canUpdateItem() {
      return Session::haveRight(self::$rightname, UPDATE);
   }

   function canCreateItem() {
      return Session::haveRight(self::$rightname, CREATE);
   }

   function canDeleteItem() {
      return Session::haveRight(self::$rightname, DELETE);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() === 'PluginScrumbanTeam') {
         return __('Membros', 'scrumban');
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() !== 'PluginScrumbanTeam') {
         return false;
      }

      $member = new self();
      $member->showMembers($item->getID());
      return true;
   }

   function showMembers($team_id) {
      global $DB;

      $members = $DB->request([
         'SELECT' => [
            'glpi_plugin_scrumban_team_members.*',
            'glpi_users.name AS username',
            'glpi_users.realname',
            'glpi_users.firstname'
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
         'ORDER' => 'glpi_plugin_scrumban_team_members.role DESC'
      ]);

      echo "<div class='scrumban-table-wrapper'>";
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th>" . __('Usuário', 'scrumban') . "</th><th>" . __('Função', 'scrumban') . "</th><th></th></tr>";

      foreach ($members as $data) {
         echo "<tr>";
         $displayName = $data['firstname'] || $data['realname']
            ? trim($data['firstname'] . ' ' . $data['realname'])
            : ($data['username'] ?: PluginScrumbanUtils::formatUserName($data['users_id']));
         echo "<td>" . htmlspecialchars($displayName) . "</td>";
         echo "<td>" . ucfirst($data['role']) . "</td>";
         echo "<td class='text-end'>";
         echo "<button class='btn btn-sm btn-outline-danger' data-action='remove-member' data-id='" . $data['id'] . "'>";
         echo "<i class='ti ti-user-minus'></i>";
         echo "</button>";
         echo "</td>";
         echo "</tr>";
      }

      echo "</table>";
      echo "</div>";
   }

   function userHasRole($user_id, $team_id, array $roles) {
      global $DB;

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'WHERE' => [
            'users_id' => $user_id,
            'teams_id' => $team_id,
            'role' => $roles
         ],
         'LIMIT' => 1
      ]);

      return count($iterator) > 0;
   }

   function getRole($user_id, $team_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'role',
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'WHERE' => [
            'users_id' => $user_id,
            'teams_id' => $team_id
         ],
         'LIMIT' => 1
      ]);

      foreach ($iterator as $row) {
         return $row['role'];
      }

      return null;
   }
}
