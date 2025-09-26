<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You cannot access directly to this file");
}

class PluginScrumbanTeamBoard extends CommonDBTM {
   
   static function getTypeName($nb = 0) {
      return _n('Team Board', 'Team Boards', $nb, 'scrumban');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'PluginScrumbanTeam') {
         return __('Boards', 'scrumban');
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'PluginScrumbanTeam') {
         self::showForTeam($item);
      }
      return true;
   }

   function prepareInputForAdd($input) {
      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      
      // Set default permissions
      if (!isset($input['can_edit'])) {
         $input['can_edit'] = 1;
      }
      if (!isset($input['can_manage'])) {
         $input['can_manage'] = 0;
      }
      
      return $input;
   }

   /**
    * Show boards tab for team
    */
   static function showForTeam(PluginScrumbanTeam $team) {
      global $DB;

      $team_id = $team->getID();
      $can_manage = PluginScrumbanTeamMember::canManageTeamMembers($_SESSION['glpiID'], $team_id);

      echo "<div class='center'>";

      if ($can_manage) {
         echo "<div class='team-board-actions mb-3'>";
         echo "<button class='btn btn-primary' onclick='openAddBoardModal($team_id)'>";
         echo "<i class='fas fa-plus me-2'></i>" . __('Add Board', 'scrumban');
         echo "</button>";
         echo "</div>";
      }

      // Get team boards
      $boards = $team->getBoards();

      if (empty($boards)) {
         echo "<div class='alert alert-info'>";
         echo __('No boards associated with this team yet.', 'scrumban');
         echo "</div>";
         echo "</div>";
         return;
      }

      echo "<div class='row'>";

      foreach ($boards as $board) {
         echo "<div class='col-md-6 col-lg-4 mb-3'>";
         echo "<div class='board-card'>";
         
         echo "<div class='board-card-header'>";
         echo "<h5>" . htmlspecialchars($board['name']) . "</h5>";
         if ($can_manage) {
            echo "<div class='board-actions'>";
            echo "<button class='btn btn-outline-secondary btn-sm' onclick='editBoardPermissions($team_id, {$board['id']})'>";
            echo "<i class='fas fa-cog'></i>";
            echo "</button>";
            echo "<button class='btn btn-outline-danger btn-sm' onclick='removeBoardFromTeam($team_id, {$board['id']})'>";
            echo "<i class='fas fa-unlink'></i>";
            echo "</button>";
            echo "</div>";
         }
         echo "</div>";
         
         if (!empty($board['description'])) {
            echo "<p class='board-description text-muted'>";
            echo htmlspecialchars(substr($board['description'], 0, 100));
            if (strlen($board['description']) > 100) echo "...";
            echo "</p>";
         }
         
         // Permissions
         echo "<div class='board-permissions'>";
         echo "<div class='permissions-grid'>";
         
         echo "<div class='permission-item'>";
         echo "<i class='fas fa-eye text-primary'></i>";
         echo "<span>" . __('View', 'scrumban') . "</span>";
         echo "<i class='fas fa-check text-success'></i>";
         echo "</div>";
         
         echo "<div class='permission-item'>";
         echo "<i class='fas fa-edit text-warning'></i>";
         echo "<span>" . __('Edit', 'scrumban') . "</span>";
         if ($board['can_edit']) {
            echo "<i class='fas fa-check text-success'></i>";
         } else {
            echo "<i class='fas fa-times text-danger'></i>";
         }
         echo "</div>";
         
         echo "<div class='permission-item'>";
         echo "<i class='fas fa-cogs text-info'></i>";
         echo "<span>" . __('Manage', 'scrumban') . "</span>";
         if ($board['can_manage']) {
            echo "<i class='fas fa-check text-success'></i>";
         } else {
            echo "<i class='fas fa-times text-danger'></i>";
         }
         echo "</div>";
         
         echo "</div>";
         echo "</div>";
         
         // Board stats
         $stats = self::getBoardStats($board['id']);
         echo "<div class='board-stats'>";
         echo "<div class='stats-row'>";
         echo "<div class='stat'>";
         echo "<span class='stat-value'>" . $stats['total_cards'] . "</span>";
         echo "<span class='stat-label'>" . __('Cards', 'scrumban') . "</span>";
         echo "</div>";
         echo "<div class='stat'>";
         echo "<span class='stat-value'>" . $stats['active_sprints'] . "</span>";
         echo "<span class='stat-label'>" . __('Sprints', 'scrumban') . "</span>";
         echo "</div>";
         echo "</div>";
         echo "</div>";
         
         // Quick access
         echo "<div class='board-card-footer'>";
         echo "<a href='" . Plugin::getWebDir('scrumban') . "/front/board.php?board_id={$board['id']}' class='btn btn-outline-primary btn-sm'>";
         echo "<i class='fas fa-external-link-alt me-1'></i>" . __('Open Board', 'scrumban');
         echo "</a>";
         echo "</div>";
         
         echo "</div>";
         echo "</div>";
      }

      echo "</div>";
      echo "</div>";
   }

   /**
    * Get available boards to add to team
    */
   static function getAvailableBoards($team_id) {
      global $DB;

      // Get boards already associated with team
      $associated_boards = $DB->request([
         'SELECT' => 'boards_id',
         'FROM' => 'glpi_plugin_scrumban_team_boards',
         'WHERE' => ['teams_id' => $team_id]
      ]);

      $excluded_boards = [];
      foreach ($associated_boards as $board) {
         $excluded_boards[] = $board['boards_id'];
      }

      $where = ['is_active' => 1];
      if (!empty($excluded_boards)) {
         $where['id'] = ['NOT' => $excluded_boards];
      }

      // Get available boards
      $iterator = $DB->request([
         'SELECT' => ['id', 'name', 'description'],
         'FROM' => 'glpi_plugin_scrumban_boards',
         'WHERE' => $where,
         'ORDER' => 'name'
      ]);

      $boards = [];
      foreach ($iterator as $data) {
         $boards[] = $data;
      }

      return $boards;
   }

   /**
    * Add board to team
    */
   static function addBoardToTeam($team_id, $board_id, $can_edit = 1, $can_manage = 0) {
      global $DB;

      // Check if already associated
      $existing = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_team_boards',
         'WHERE' => [
            'teams_id' => $team_id,
            'boards_id' => $board_id
         ],
         'LIMIT' => 1
      ]);

      if (count($existing) > 0) {
         return false; // Already associated
      }

      $team_board = new self();
      return $team_board->add([
         'teams_id' => $team_id,
         'boards_id' => $board_id,
         'can_edit' => $can_edit ? 1 : 0,
         'can_manage' => $can_manage ? 1 : 0
      ]);
   }

   /**
    * Remove board from team
    */
   static function removeBoardFromTeam($team_id, $board_id) {
      global $DB;

      return $DB->delete('glpi_plugin_scrumban_team_boards', [
         'teams_id' => $team_id,
         'boards_id' => $board_id
      ]);
   }

   /**
    * Update board permissions for team
    */
   static function updateBoardPermissions($team_id, $board_id, $can_edit, $can_manage) {
      global $DB;

      return $DB->update('glpi_plugin_scrumban_team_boards', [
         'can_edit' => $can_edit ? 1 : 0,
         'can_manage' => $can_manage ? 1 : 0
      ], [
         'teams_id' => $team_id,
         'boards_id' => $board_id
      ]);
   }

   /**
    * Get board statistics
    */
   static function getBoardStats($board_id) {
      global $DB;

      $stats = [
         'total_cards' => 0,
         'active_sprints' => 0,
         'total_columns' => 0
      ];

      // Count cards
      $cards_result = $DB->request([
         'SELECT' => 'COUNT(*) as total',
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'boards_id' => $board_id,
            'is_active' => 1
         ]
      ]);

      foreach ($cards_result as $row) {
         $stats['total_cards'] = $row['total'];
      }

      // Count active sprints
      $sprints_result = $DB->request([
         'SELECT' => 'COUNT(*) as total',
         'FROM' => 'glpi_plugin_scrumban_sprints',
         'WHERE' => [
            'boards_id' => $board_id,
            'status' => 'ativo',
            'is_active' => 1
         ]
      ]);

      foreach ($sprints_result as $row) {
         $stats['active_sprints'] = $row['total'];
      }

      // Count columns
      $columns_result = $DB->request([
         'SELECT' => 'COUNT(*) as total',
         'FROM' => 'glpi_plugin_scrumban_columns',
         'WHERE' => [
            'boards_id' => $board_id,
            'is_active' => 1
         ]
      ]);

      foreach ($columns_result as $row) {
         $stats['total_columns'] = $row['total'];
      }

      return $stats;
   }

   /**
    * Get teams associated with a board
    */
   static function getTeamsForBoard($board_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_plugin_scrumban_teams.*',
            'glpi_plugin_scrumban_team_boards.can_edit',
            'glpi_plugin_scrumban_team_boards.can_manage'
         ],
         'FROM' => 'glpi_plugin_scrumban_team_boards',
         'LEFT JOIN' => [
            'glpi_plugin_scrumban_teams' => [
               'ON' => [
                  'glpi_plugin_scrumban_team_boards' => 'teams_id',
                  'glpi_plugin_scrumban_teams' => 'id'
               ]
            ]
         ],
         'WHERE' => [
            'glpi_plugin_scrumban_team_boards.boards_id' => $board_id,
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
    * Check if team has permission on board
    */
   static function hasTeamPermission($team_id, $board_id, $permission = 'view') {
      global $DB;

      $where = [
         'teams_id' => $team_id,
         'boards_id' => $board_id
      ];

      switch ($permission) {
         case 'edit':
            $where['can_edit'] = 1;
            break;
         case 'manage':
            $where['can_manage'] = 1;
            break;
         case 'view':
         default:
            // Just check if association exists
            break;
      }

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_team_boards',
         'WHERE' => $where,
         'LIMIT' => 1
      ]);

      return count($iterator) > 0;
   }

   /**
    * Get permission matrix for board
    */
   static function getBoardPermissionMatrix($board_id) {
      global $DB;

      $teams = self::getTeamsForBoard($board_id);
      $matrix = [];

      foreach ($teams as $team) {
         $permissions = [];
         $permissions['view'] = true; // Always true if team is associated
         $permissions['edit'] = (bool)$team['can_edit'];
         $permissions['manage'] = (bool)$team['can_manage'];
         
         $matrix[$team['id']] = [
            'team_name' => $team['name'],
            'permissions' => $permissions
         ];
      }

      return $matrix;
   }

   /**
    * Show board access summary
    */
   static function showBoardAccessSummary($board_id) {
      $teams = self::getTeamsForBoard($board_id);
      
      if (empty($teams)) {
         echo "<div class='alert alert-warning'>";
         echo __('This board is not associated with any team.', 'scrumban');
         echo "</div>";
         return;
      }

      echo "<div class='board-access-summary'>";
      echo "<h5>" . __('Team Access', 'scrumban') . "</h5>";
      echo "<div class='teams-access-list'>";

      foreach ($teams as $team) {
         echo "<div class='team-access-item'>";
         echo "<div class='team-info'>";
         echo "<strong>" . htmlspecialchars($team['name']) . "</strong>";
         echo "</div>";
         echo "<div class='team-permissions'>";
         
         if ($team['can_manage']) {
            echo "<span class='badge bg-success'>" . __('Full Access', 'scrumban') . "</span>";
         } elseif ($team['can_edit']) {
            echo "<span class='badge bg-warning text-dark'>" . __('Edit Access', 'scrumban') . "</span>";
         } else {
            echo "<span class='badge bg-secondary'>" . __('View Only', 'scrumban') . "</span>";
         }
         
         echo "</div>";
         echo "</div>";
      }

      echo "</div>";
      echo "</div>";
   }

   /**
    * Bulk update permissions
    */
   static function bulkUpdatePermissions($updates) {
      global $DB;

      $success_count = 0;

      foreach ($updates as $update) {
         if (!isset($update['team_id'], $update['board_id'])) {
            continue;
         }

         $result = $DB->update('glpi_plugin_scrumban_team_boards', [
            'can_edit' => isset($update['can_edit']) ? ($update['can_edit'] ? 1 : 0) : 0,
            'can_manage' => isset($update['can_manage']) ? ($update['can_manage'] ? 1 : 0) : 0
         ], [
            'teams_id' => $update['team_id'],
            'boards_id' => $update['board_id']
         ]);

         if ($result) {
            $success_count++;
         }
      }

      return $success_count;
   }

   /**
    * Validate before deletion
    */
   function pre_deleteItem() {
      // Check if user has permission to manage this association
      $user_role = PluginScrumbanTeam::getUserRoleInTeam($_SESSION['glpiID'], $this->fields['teams_id']);
      
      if (!in_array($user_role, ['admin', 'lead'])) {
         Session::addMessageAfterRedirect(__('You need administrator or lead role to remove boards', 'scrumban'), false, ERROR);
         return false;
      }

      return true;
   }
}