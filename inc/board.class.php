<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You cannot access directly to this file");
}

class PluginScrumbanBoard extends CommonDBTM {
   
   static function getTypeName($nb = 0) {
      return _n('Board', 'Boards', $nb, 'scrumban');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      return self::getTypeName();
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $board = new self();
      $board->showForm($item->getID(), ['target' => $item->getFormURL()]);
      return true;
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginScrumbanColumn', $ong, $options);
      $this->addStandardTab('PluginScrumbanCard', $ong, $options);
      $this->addStandardTab('PluginScrumbanSprint', $ong, $options);
      return $ong;
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name', 'scrumban') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>" . __('Active', 'scrumban') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='description' rows='4' cols='80'>" . $this->fields["description"] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      // Team selection for new boards
      if ($ID == 0) {
         $user_teams = PluginScrumbanTeam::getTeamsForUser($_SESSION['glpiID']);
         if (!empty($user_teams)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Team', 'scrumban') . "</td>";
            echo "<td>";
            $team_options = [];
            foreach ($user_teams as $team) {
               // Only show teams where user can manage
               $user_role = PluginScrumbanTeam::getUserRoleInTeam($_SESSION['glpiID'], $team['id']);
               if (in_array($user_role, ['admin', 'lead'])) {
                  $team_options[$team['id']] = $team['name'];
               }
            }
            Dropdown::showFromArray('teams_id', $team_options, [
               'value' => $this->fields['teams_id'],
               'display_emptychoice' => true,
               'emptylabel' => __('No team', 'scrumban')
            ]);
            echo "</td>";
            echo "<td>" . __('Visibility', 'scrumban') . "</td>";
            echo "<td>";
            $visibility_options = [
               'public' => __('Public', 'scrumban'),
               'team' => __('Team only', 'scrumban'),
               'private' => __('Private', 'scrumban')
            ];
            Dropdown::showFromArray('visibility', $visibility_options, [
               'value' => $this->fields['visibility'] ?: 'team'
            ]);
            echo "</td>";
            echo "</tr>";
         }
      }

      $this->showFormButtons($options);
      return true;
   }

   function prepareInputForAdd($input) {
      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      $input['users_id_created'] = $_SESSION['glpiID'];
      
      // Set default visibility
      if (!isset($input['visibility'])) {
         $input['visibility'] = 'team';
      }
      
      return $input;
   }

   function prepareInputForUpdate($input) {
      $input['date_mod'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   function post_addItem() {
      // If board is assigned to a team, create the association
      if ($this->fields['teams_id'] > 0) {
         $team_board = new PluginScrumbanTeamBoard();
         $team_board->add([
            'teams_id' => $this->fields['teams_id'],
            'boards_id' => $this->getID(),
            'can_edit' => 1,
            'can_manage' => 1
         ]);
      }
   }

   /**
    * Get all active boards (with team filtering)
    */
   static function getActiveBoards() {
      global $DB;

      // Get user's accessible boards through teams
      $user_teams = PluginScrumbanTeam::getTeamsForUser($_SESSION['glpiID']);
      
      if (empty($user_teams)) {
         // User not in any team - only show public boards
         $iterator = $DB->request([
            'FROM' => 'glpi_plugin_scrumban_boards',
            'WHERE' => [
               'is_active' => 1,
               'visibility' => 'public'
            ],
            'ORDER' => 'name'
         ]);
      } else {
         $team_ids = array_column($user_teams, 'id');
         
         // Show boards user has access to through teams + public boards
         $iterator = $DB->request([
            'SELECT' => 'DISTINCT glpi_plugin_scrumban_boards.*',
            'FROM' => 'glpi_plugin_scrumban_boards',
            'LEFT JOIN' => [
               'glpi_plugin_scrumban_team_boards' => [
                  'ON' => [
                     'glpi_plugin_scrumban_boards' => 'id',
                     'glpi_plugin_scrumban_team_boards' => 'boards_id'
                  ]
               ]
            ],
            'WHERE' => [
               'glpi_plugin_scrumban_boards.is_active' => 1,
               'OR' => [
                  'glpi_plugin_scrumban_boards.visibility' => 'public',
                  [
                     'glpi_plugin_scrumban_team_boards.teams_id' => $team_ids,
                     'glpi_plugin_scrumban_boards.visibility' => ['team', 'private']
                  ]
               ]
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
    * Check if user can edit this board
    */
   function canUserEdit($user_id = null) {
      if ($user_id === null) {
         $user_id = $_SESSION['glpiID'];
      }
      
      return PluginScrumbanTeam::canUserEditBoard($user_id, $this->getID());
   }

   /**
    * Check if user can manage this board
    */
   function canUserManage($user_id = null) {
      if ($user_id === null) {
         $user_id = $_SESSION['glpiID'];
      }
      
      return PluginScrumbanTeam::canUserManageBoard($user_id, $this->getID());
   }

   /**
    * Get board statistics
    */
   function getBoardStats() {
      // Use utility functions
      $basic_stats = PluginScrumbanUtils::getBoardBasicStats($this->getID());
      
      $stats = [
         'total_issues' => $basic_stats['total_cards'],
         'total_story_points' => $basic_stats['total_story_points'],
         'active_sprints' => $basic_stats['active_sprints'],
         'columns' => []
      ];

      // Stats per column
      global $DB;
      $columns_iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_columns',
         'WHERE' => [
            'boards_id' => $this->getID(),
            'is_active' => 1
         ],
         'ORDER' => 'position'
      ]);

      foreach ($columns_iterator as $column) {
         $card_count = PluginScrumbanUtils::getColumnStats($column['id']);
         
         $stats['columns'][] = [
            'id' => $column['id'],
            'name' => $column['name'],
            'count' => $card_count
         ];
      }

      return $stats;
   }

   /**
    * Get Kanban data with team access control
    */
   function getKanbanData() {
      global $DB;

      // Check if user can access this board
      if (!PluginScrumbanTeam::canUserAccessBoard($_SESSION['glpiID'], $this->getID())) {
         return [
            'error' => __('You do not have access to this board', 'scrumban'),
            'board' => [],
            'columns' => [],
            'cards' => []
         ];
      }

      $kanban_data = [
         'board' => $this->fields,
         'columns' => [],
         'cards' => []
      ];

      // Get columns
      $columns_iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_columns',
         'WHERE' => [
            'boards_id' => $this->getID(),
            'is_active' => 1
         ],
         'ORDER' => 'position'
      ]);

      foreach ($columns_iterator as $column_data) {
         $column_id = $column_data['id'];
         $kanban_data['columns'][$column_id] = $column_data;
         $kanban_data['cards'][$column_id] = [];
      }

      // Get cards
      $cards_iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'boards_id' => $this->getID(),
            'is_active' => 1
         ],
         'ORDER' => ['columns_id', 'position']
      ]);

      foreach ($cards_iterator as $card_data) {
         $column_id = $card_data['columns_id'];
         
         // Get card labels
         $labels_iterator = $DB->request([
            'FROM' => 'glpi_plugin_scrumban_labels',
            'WHERE' => ['cards_id' => $card_data['id']]
         ]);
         
         $labels = [];
         foreach ($labels_iterator as $label) {
            $labels[] = $label;
         }
         
         $card_data['labels'] = $labels;
         $kanban_data['cards'][$column_id][] = $card_data;
      }

      return $kanban_data;
   }

   /**
    * Render Kanban board with team access control
    */
   function showKanbanBoard() {
      // Check team access first
      $user_teams = PluginScrumbanTeam::getTeamsForUser($_SESSION['glpiID']);
      
      if (empty($user_teams) && $this->fields['visibility'] != 'public') {
         echo "<div class='alert alert-warning'>";
         echo "<h4>" . __('No Team Access', 'scrumban') . "</h4>";
         echo "<p>" . __('You are not a member of any team. Contact your administrator to be added to a team.', 'scrumban') . "</p>";
         echo "</div>";
         return;
      }

      // Check specific board access
      if (!PluginScrumbanTeam::canUserAccessBoard($_SESSION['glpiID'], $this->getID())) {
         echo "<div class='alert alert-danger'>";
         echo "<h4>" . __('Access Denied', 'scrumban') . "</h4>";
         echo "<p>" . __('You do not have permission to access this board.', 'scrumban') . "</p>";
         echo "</div>";
         return;
      }

      $kanban_data = $this->getKanbanData();
      
      if (isset($kanban_data['error'])) {
         echo "<div class='alert alert-danger'>" . $kanban_data['error'] . "</div>";
         return;
      }
      
      $stats = $this->getBoardStats();
      
      echo "<div class='agilepm-container'>";
      
      // Header
      echo "<div class='agilepm-header'>";
      echo "<div class='container-fluid'>";
      echo "<div class='d-flex justify-content-between align-items-center'>";
      echo "<div>";
      echo "<h1><i class='fas fa-project-diagram me-3'></i>" . $this->fields['name'] . "</h1>";
      echo "<p class='mb-0 opacity-75'>" . $this->fields['description'] . "</p>";
      echo "</div>";
      echo "<div class='d-flex gap-2'>";
      
      // Team selector
      if (!empty($user_teams) && count($user_teams) > 1) {
         echo plugin_scrumban_get_team_selector();
      }
      
      echo "<button class='btn btn-light btn-sm'><i class='fas fa-cog'></i> " . __('Settings', 'scrumban') . "</button>";
      echo "<button class='btn btn-outline-light btn-sm'><i class='fas fa-user'></i> " . $_SESSION['glpiname'] . "</button>";
      echo "</div>";
      echo "</div>";
      echo "</div>";
      echo "</div>";

      // Navigation tabs
      $this->showNavigationTabs('kanban');

      // Kanban container
      echo "<div class='kanban-container fade-in'>";
      
      // Board header with statistics
      echo "<div class='board-header'>";
      echo "<div class='board-info'>";
      echo "<h2>" . $this->fields['name'] . "</h2>";
      echo "<p class='text-muted mb-0'>" . $this->fields['description'] . "</p>";
      echo "</div>";
      echo "<div class='board-stats'>";
      echo "<div class='stat-item'>";
      echo "<div class='stat-number'>" . $stats['total_issues'] . "</div>";
      echo "<div class='stat-label'>" . __('Total Issues', 'scrumban') . "</div>";
      echo "</div>";
      echo "<div class='stat-item'>";
      echo "<div class='stat-number'>" . $stats['total_story_points'] . "</div>";
      echo "<div class='stat-label'>" . __('Story Points', 'scrumban') . "</div>";
      echo "</div>";
      echo "<div class='stat-item'>";
      echo "<div class='stat-number'>" . $stats['active_sprints'] . "</div>";
      echo "<div class='stat-label'>" . __('Active Sprints', 'scrumban') . "</div>";
      echo "</div>";
      echo "</div>";
      echo "<div>";
      
      // Only show create button if user can edit
      if ($this->canUserEdit()) {
         echo "<button class='btn btn-primary' onclick='openCreateIssueModal()'>";
         echo "<i class='fas fa-plus me-2'></i>" . __('Create Issue', 'scrumban');
         echo "</button>";
      }
      
      echo "</div>";
      echo "</div>";

      // Kanban board
      echo "<div class='kanban-board'>";
      
      foreach ($kanban_data['columns'] as $column_id => $column) {
         echo "<div class='kanban-column' data-column-id='" . $column_id . "'>";
         echo "<div class='column-header'>";
         echo "<h3 class='column-title'>" . $column['name'] . "</h3>";
         
         $card_count = count($kanban_data['cards'][$column_id]);
         echo "<span class='column-count'>" . $card_count . "</span>";
         echo "</div>";
         
         echo "<div class='cards-container' id='column-" . $column_id . "'>";
         
         foreach ($kanban_data['cards'][$column_id] as $card) {
            $this->renderCard($card);
         }
         
         echo "</div>";
         echo "</div>";
      }
      
      echo "</div>";
      echo "</div>";
      echo "</div>";
   }

   /**
    * Render individual card
    */
   function renderCard($card) {
      $priority_class = 'priority-' . $card['priority'];
      $type_class = 'issue-' . $card['type'];
      
      echo "<div class='kanban-card $priority_class $type_class' data-card-id='" . $card['id'] . "'>";
      
      // Card header
      echo "<div class='card-header'>";
      echo "<span class='issue-type $type_class'>" . strtoupper($card['type']) . "</span>";
      echo "<span class='card-id'>#" . str_pad($card['id'], 3, '0', STR_PAD_LEFT) . "</span>";
      echo "</div>";
      
      // Title and description
      echo "<div class='card-title'>" . htmlspecialchars($card['title']) . "</div>";
      if (!empty($card['description'])) {
         echo "<div class='card-description'>" . htmlspecialchars(substr($card['description'], 0, 100)) . "...</div>";
      }
      
      // Card footer
      echo "<div class='card-footer'>";
      echo "<div class='card-meta'>";
      
      if ($card['story_points'] > 0) {
         echo "<span class='story-points'>" . $card['story_points'] . "</span>";
      }
      
      if (!empty($card['assignee'])) {
         $initials = $this->getInitials($card['assignee']);
         echo "<div class='assignee'>";
         echo "<div class='assignee-avatar'>$initials</div>";
         echo "<span>" . htmlspecialchars($card['assignee']) . "</span>";
         echo "</div>";
      }
      
      echo "</div>";
      echo "</div>";
      
      // Priority indicator
      echo "<div class='priority-indicator'></div>";
      
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
    * Show navigation tabs
    */
   function showNavigationTabs($active_tab = 'kanban') {
      echo "<nav class='agilepm-nav'>";
      echo "<div class='container-fluid'>";
      echo "<ul class='nav nav-tabs' role='tablist'>";
      
      $tabs = [
         'kanban' => ['Kanban Board', 'fas fa-columns', 'board.php'],
         'sprints' => ['Sprints', 'fas fa-sync-alt', 'sprint.php'],
         'backlog' => ['Backlog', 'fas fa-list-ul', 'backlog.php'],
         'timeline' => ['Timeline', 'fas fa-calendar-alt', 'timeline.php']
      ];
      
      foreach ($tabs as $key => $tab) {
         $active_class = ($active_tab == $key) ? 'active' : '';
         $url = Plugin::getWebDir('scrumban') . '/front/' . $tab[2];
         
         echo "<li class='nav-item'>";
         echo "<a class='nav-link $active_class' href='$url'>";
         echo "<i class='" . $tab[1] . " me-2'></i>" . $tab[0];
         echo "</a>";
         echo "</li>";
      }
      
      echo "</ul>";
      echo "</div>";
      echo "</nav>";
   }

   /**
    * Check if user can edit boards in general
    */
   static function canUserEditBoard($user_id, $board_id) {
      return PluginScrumbanTeam::canUserEditBoard($user_id, $board_id);
   }

   /**
    * Check if user can manage boards in general
    */
   static function canUserManageBoard($user_id, $board_id) {
      return PluginScrumbanTeam::canUserManageBoard($user_id, $board_id);
   }
}