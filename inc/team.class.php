<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You cannot access directly to this file");
}

class PluginScrumbanTeam extends CommonDBTM {
   
   static function getTypeName($nb = 0) {
      return _n('Team', 'Teams', $nb, 'scrumban');
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginScrumbanTeamMember', $ong, $options);
      $this->addStandardTab('PluginScrumbanTeamBoard', $ong, $options);
      return $ong;
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name', 'scrumban') . " *</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>" . __('Manager', 'scrumban') . "</td>";
      echo "<td>";
      User::dropdown(['name' => 'manager_id', 'value' => $this->fields['manager_id']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='description' rows='4' cols='80'>" . $this->fields["description"] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Active', 'scrumban') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td>";
      echo "<td colspan='2'></td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }

   function prepareInputForAdd($input) {
      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      $input['entities_id'] = $_SESSION['glpiactive_entity'];
      return $input;
   }

   function prepareInputForUpdate($input) {
      $input['date_mod'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   function post_addItem() {
      // Add creator as administrator automatically
      $team_member = new PluginScrumbanTeamMember();
      $team_member->add([
         'teams_id' => $this->getID(),
         'users_id' => $_SESSION['glpiID'],
         'role' => 'admin'
      ]);
   }

   /**
    * Get teams for a user
    */
   static function getTeamsForUser($user_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_plugin_scrumban_teams.*',
            'glpi_plugin_scrumban_team_members.role'
         ],
         'FROM' => 'glpi_plugin_scrumban_teams',
         'INNER JOIN' => [
            'glpi_plugin_scrumban_team_members' => [
               'ON' => [
                  'glpi_plugin_scrumban_teams' => 'id',
                  'glpi_plugin_scrumban_team_members' => 'teams_id'
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
      foreach ($iterator as $data) {
         $teams[] = $data;
      }

      return $teams;
   }

   /**
    * Check if user can access board through team membership
    */
   static function canUserAccessBoard($user_id, $board_id) {
      global $DB;

      // Get user teams
      $user_teams = self::getTeamsForUser($user_id);
      
      if (empty($user_teams)) {
         return false;
      }

      $team_ids = array_column($user_teams, 'id');

      // Check if any team has access to this board
      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_team_boards',
         'WHERE' => [
            'teams_id' => $team_ids,
            'boards_id' => $board_id
         ],
         'LIMIT' => 1
      ]);

      return count($iterator) > 0;
   }

   /**
    * Check if user can edit board
    */
   static function canUserEditBoard($user_id, $board_id) {
      global $DB;

      $user_teams = self::getTeamsForUser($user_id);
      
      if (empty($user_teams)) {
         return false;
      }

      $team_ids = array_column($user_teams, 'id');

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_team_boards',
         'WHERE' => [
            'teams_id' => $team_ids,
            'boards_id' => $board_id,
            'can_edit' => 1
         ],
         'LIMIT' => 1
      ]);

      return count($iterator) > 0;
   }

   /**
    * Check if user can manage board
    */
   static function canUserManageBoard($user_id, $board_id) {
      global $DB;

      $user_teams = self::getTeamsForUser($user_id);
      
      if (empty($user_teams)) {
         return false;
      }

      $team_ids = array_column($user_teams, 'id');

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_team_boards',
         'WHERE' => [
            'teams_id' => $team_ids,
            'boards_id' => $board_id,
            'can_manage' => 1
         ],
         'LIMIT' => 1
      ]);

      return count($iterator) > 0;
   }

   /**
    * Get user role in team
    */
   static function getUserRoleInTeam($user_id, $team_id) {
      global $DB;

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'WHERE' => [
            'users_id' => $user_id,
            'teams_id' => $team_id
         ],
         'LIMIT' => 1
      ]);

      foreach ($iterator as $data) {
         return $data['role'];
      }

      return null;
   }

   /**
    * Check if user is admin or lead of any team
    */
   static function canUserManageTeams($user_id) {
      global $DB;

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_team_members',
         'WHERE' => [
            'users_id' => $user_id,
            'role' => ['admin', 'lead']
         ],
         'LIMIT' => 1
      ]);

      return count($iterator) > 0;
   }

   /**
    * Get team members
    */
   function getMembers() {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_plugin_scrumban_team_members.*',
            'glpi_users.firstname',
            'glpi_users.realname',
            'glpi_users.name as username'
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
         'WHERE' => ['teams_id' => $this->getID()],
         'ORDER' => 'glpi_plugin_scrumban_team_members.role DESC'
      ]);

      $members = [];
      foreach ($iterator as $data) {
         $members[] = $data;
      }

      return $members;
   }

   /**
    * Get team boards
    */
   function getBoards() {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_plugin_scrumban_boards.*',
            'glpi_plugin_scrumban_team_boards.can_edit',
            'glpi_plugin_scrumban_team_boards.can_manage'
         ],
         'FROM' => 'glpi_plugin_scrumban_team_boards',
         'LEFT JOIN' => [
            'glpi_plugin_scrumban_boards' => [
               'ON' => [
                  'glpi_plugin_scrumban_team_boards' => 'boards_id',
                  'glpi_plugin_scrumban_boards' => 'id'
               ]
            ]
         ],
         'WHERE' => ['teams_id' => $this->getID()],
         'ORDER' => 'glpi_plugin_scrumban_boards.name'
      ]);

      $boards = [];
      foreach ($iterator as $data) {
         $boards[] = $data;
      }

      return $boards;
   }

   /**
    * Get team statistics
    */
   function getTeamStats() {
      global $DB;

      $stats = [
         'total_members' => 0,
         'total_boards' => 0,
         'total_cards' => 0,
         'total_story_points' => 0,
         'active_sprints' => 0
      ];

      // Count members
      $stats['total_members'] = count($this->getMembers());

      // Count boards
      $boards = $this->getBoards();
      $stats['total_boards'] = count($boards);

      if (!empty($boards)) {
         $board_ids = array_column($boards, 'id');
         
         // Count cards
         $cards_result = $DB->request([
            'SELECT' => ['COUNT(*) as total', 'SUM(story_points) as points'],
            'FROM' => 'glpi_plugin_scrumban_cards',
            'WHERE' => [
               'boards_id' => $board_ids,
               'is_active' => 1
            ]
         ]);

         foreach ($cards_result as $row) {
            $stats['total_cards'] = $row['total'];
            $stats['total_story_points'] = $row['points'] ?? 0;
         }

         // Count active sprints
         $sprints_result = $DB->request([
            'SELECT' => ['COUNT(*) as total'],
            'FROM' => 'glpi_plugin_scrumban_sprints',
            'WHERE' => [
               'boards_id' => $board_ids,
               'status' => 'ativo',
               'is_active' => 1
            ]
         ]);

         foreach ($sprints_result as $row) {
            $stats['active_sprints'] = $row['total'];
         }
      }

      return $stats;
   }

   /**
    * Show team management page
    */
   static function showTeamManagementPage() {
      global $DB;

      echo "<div class='team-container fade-in'>";
      
      // Header
      echo "<div class='team-header'>";
      echo "<div class='d-flex justify-content-between align-items-center'>";
      echo "<div>";
      echo "<h2>" . __('Team Management', 'scrumban') . "</h2>";
      echo "<p class='text-muted mb-0'>" . __('Manage teams and access control', 'scrumban') . "</p>";
      echo "</div>";
      echo "<button class='btn btn-primary' onclick='openCreateTeamModal()'>";
      echo "<i class='fas fa-plus me-2'></i>" . __('New Team', 'scrumban');
      echo "</button>";
      echo "</div>";
      echo "</div>";

      // Team cards grid
      echo "<div class='teams-grid'>";
      
      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_teams',
         'WHERE' => ['is_active' => 1],
         'ORDER' => 'name'
      ]);

      foreach ($iterator as $data) {
         $team = new self();
         $team->fields = $data;
         $team->showTeamCard();
      }
      
      echo "</div>";
      echo "</div>";
   }

   /**
    * Show team card
    */
   function showTeamCard() {
      $stats = $this->getTeamStats();
      $members = $this->getMembers();
      
      echo "<div class='team-card slide-up'>";
      echo "<div class='team-card-header'>";
      echo "<h3 class='team-title'>" . htmlspecialchars($this->fields['name']) . "</h3>";
      echo "<div class='team-actions'>";
      echo "<button class='btn btn-outline-primary btn-sm' onclick='manageTeam(" . $this->getID() . ")'>";
      echo "<i class='fas fa-cogs'></i> " . __('Manage', 'scrumban');
      echo "</button>";
      echo "</div>";
      echo "</div>";
      
      if (!empty($this->fields['description'])) {
         echo "<p class='text-muted mb-3'>" . htmlspecialchars(substr($this->fields['description'], 0, 100));
         if (strlen($this->fields['description']) > 100) echo "...";
         echo "</p>";
      }
      
      // Team stats
      echo "<div class='team-stats'>";
      echo "<div class='stat'>";
      echo "<div class='stat-value'>" . $stats['total_members'] . "</div>";
      echo "<div class='stat-label'>" . __('Members', 'scrumban') . "</div>";
      echo "</div>";
      echo "<div class='stat'>";
      echo "<div class='stat-value'>" . $stats['total_boards'] . "</div>";
      echo "<div class='stat-label'>" . __('Boards', 'scrumban') . "</div>";
      echo "</div>";
      echo "<div class='stat'>";
      echo "<div class='stat-value'>" . $stats['total_cards'] . "</div>";
      echo "<div class='stat-label'>" . __('Cards', 'scrumban') . "</div>";
      echo "</div>";
      echo "<div class='stat'>";
      echo "<div class='stat-value'>" . $stats['active_sprints'] . "</div>";
      echo "<div class='stat-label'>" . __('Active Sprints', 'scrumban') . "</div>";
      echo "</div>";
      echo "</div>";
      
      // Team members preview
      if (!empty($members)) {
         echo "<div class='team-members-preview'>";
         echo "<div class='members-avatars'>";
         $shown = 0;
         foreach ($members as $member) {
            if ($shown >= 5) break;
            $initials = $this->getInitials($member['firstname'] . ' ' . $member['realname']);
            $role_class = 'role-' . $member['role'];
            echo "<div class='member-avatar $role_class' title='" . htmlspecialchars($member['firstname'] . ' ' . $member['realname']) . " (" . ucfirst($member['role']) . ")'>";
            echo $initials;
            echo "</div>";
            $shown++;
         }
         if (count($members) > 5) {
            echo "<div class='member-avatar more'>+" . (count($members) - 5) . "</div>";
         }
         echo "</div>";
         echo "</div>";
      }
      
      echo "</div>";
   }

   /**
    * Get initials from name
    */
   function getInitials($name) {
      $words = explode(' ', trim($name));
      $initials = '';
      foreach ($words as $word) {
         if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
            if (strlen($initials) >= 2) break;
         }
      }
      return $initials ?: '--';
   }

   /**
    * Get boards available for team
    */
   static function getBoardsForUser($user_id, $team_id = null) {
      global $DB;

      $where = ['glpi_plugin_scrumban_boards.is_active' => 1];

      if ($team_id) {
         // Get boards for specific team
         $iterator = $DB->request([
            'SELECT' => 'glpi_plugin_scrumban_boards.*',
            'FROM' => 'glpi_plugin_scrumban_boards',
            'INNER JOIN' => [
               'glpi_plugin_scrumban_team_boards' => [
                  'ON' => [
                     'glpi_plugin_scrumban_boards' => 'id',
                     'glpi_plugin_scrumban_team_boards' => 'boards_id'
                  ]
               ]
            ],
            'WHERE' => [
               'glpi_plugin_scrumban_team_boards.teams_id' => $team_id,
               'glpi_plugin_scrumban_boards.is_active' => 1
            ],
            'ORDER' => 'glpi_plugin_scrumban_boards.name'
         ]);
      } else {
         // Get all boards user has access to through any team
         $user_teams = self::getTeamsForUser($user_id);
         
         if (empty($user_teams)) {
            return [];
         }

         $team_ids = array_column($user_teams, 'id');

         $iterator = $DB->request([
            'SELECT' => 'DISTINCT glpi_plugin_scrumban_boards.*',
            'FROM' => 'glpi_plugin_scrumban_boards',
            'INNER JOIN' => [
               'glpi_plugin_scrumban_team_boards' => [
                  'ON' => [
                     'glpi_plugin_scrumban_boards' => 'id',
                     'glpi_plugin_scrumban_team_boards' => 'boards_id'
                  ]
               ]
            ],
            'WHERE' => [
               'glpi_plugin_scrumban_team_boards.teams_id' => $team_ids,
               'glpi_plugin_scrumban_boards.is_active' => 1
            ],
            'ORDER' => 'glpi_plugin_scrumban_boards.name'
         ]);
      }

      $boards = [];
      foreach ($iterator as $data) {
         $boards[] = $data;
      }

      return $boards;
   }

   /**
    * Validate team deletion
    */
   function pre_deleteItem() {
      // Check if user has permission
      $user_role = self::getUserRoleInTeam($_SESSION['glpiID'], $this->getID());
      
      if ($user_role !== 'admin') {
         Session::addMessageAfterRedirect(__('You need administrator role to delete this team', 'scrumban'), false, ERROR);
         return false;
      }

      return true;
   }

   /**
    * Clean up after team deletion
    */
   function post_deleteFromDB() {
      global $DB;

      // Remove team members
      $DB->delete('glpi_plugin_scrumban_team_members', [
         'teams_id' => $this->getID()
      ]);

      // Remove team board associations
      $DB->delete('glpi_plugin_scrumban_team_boards', [
         'teams_id' => $this->getID()
      ]);

      return true;
   }
}