<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You cannot access directly to this file");
}

class PluginScrumbanSprint extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return _n('Sprint', 'Sprints', $nb, 'scrumban');
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginScrumbanCard', $ong, $options);
      return $ong;
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      // Get accessible boards for user
      $accessible_boards = PluginScrumbanTeam::getBoardsForUser($_SESSION['glpiID']);
      
      if (empty($accessible_boards)) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4'>";
         echo "<div class='alert alert-warning'>";
         echo __('You do not have access to any boards. Contact your administrator to be added to a team.', 'scrumban');
         echo "</div>";
         echo "</td>";
         echo "</tr>";
         $this->showFormButtons($options);
         return true;
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Board', 'scrumban') . " *</td>";
      echo "<td>";
      $board_options = [];
      foreach ($accessible_boards as $board) {
         $board_options[$board['id']] = $board['name'];
      }
      Dropdown::showFromArray('boards_id', $board_options, [
         'value' => $this->fields['boards_id']
      ]);
      echo "</td>";
      echo "<td>" . __('Status', 'scrumban') . " *</td>";
      echo "<td>";
      $status_options = [
         'planejado' => __('Planned', 'scrumban'),
         'ativo' => __('Active', 'scrumban'),
         'concluido' => __('Completed', 'scrumban')
      ];
      Dropdown::showFromArray('status', $status_options, [
         'value' => $this->fields['status']
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name', 'scrumban') . " *</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", ['size' => 50]);
      echo "</td>";
      echo "<td>" . __('Capacity (Story Points)', 'scrumban') . "</td>";
      echo "<td>";
      echo "<input type='number' name='capacity' value='" . $this->fields['capacity'] . "' min='0' max='999'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Start Date', 'scrumban') . "</td>";
      echo "<td>";
      Html::showDateField("start_date", ['value' => $this->fields["start_date"]]);
      echo "</td>";
      echo "<td>" . __('End Date', 'scrumban') . "</td>";
      echo "<td>";
      Html::showDateField("end_date", ['value' => $this->fields["end_date"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Objective', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='objective' rows='4' cols='80'>" . $this->fields["objective"] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Current Velocity', 'scrumban') . "</td>";
      echo "<td>";
      echo "<input type='number' name='velocity' value='" . $this->fields['velocity'] . "' min='0' max='999' readonly>";
      echo "</td>";
      echo "<td>" . __('Active', 'scrumban') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }

   function prepareInputForAdd($input) {
      // Check if user can create sprints on this board
      if (isset($input['boards_id']) && !PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $input['boards_id'])) {
         Session::addMessageAfterRedirect(__('You do not have permission to create sprints on this board', 'scrumban'), false, ERROR);
         return false;
      }

      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   function prepareInputForUpdate($input) {
      // Check if user can edit sprints on this board
      if (isset($input['id']) && $this->getFromDB($input['id'])) {
         if (!PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
            Session::addMessageAfterRedirect(__('You do not have permission to edit this sprint', 'scrumban'), false, ERROR);
            return false;
         }
      }

      $input['date_mod'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   /**
    * Get all active sprints with team access control
    */
   static function getActiveSprints($board_id = null) {
      global $DB;

      $criteria = [
         'is_active' => 1,
         'status' => ['ativo', 'planejado']
      ];

      if ($board_id) {
         // Check if user can access this specific board
         if (!PluginScrumbanTeam::canUserAccessBoard($_SESSION['glpiID'], $board_id)) {
            return [];
         }
         $criteria['boards_id'] = $board_id;
      } else {
         // Filter by accessible boards
         $accessible_boards = PluginScrumbanTeam::getBoardsForUser($_SESSION['glpiID']);
         
         if (empty($accessible_boards)) {
            return [];
         }

         $board_ids = array_column($accessible_boards, 'id');
         $criteria['boards_id'] = $board_ids;
      }

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_sprints',
         'WHERE' => $criteria,
         'ORDER' => 'start_date'
      ]);

      $sprints = [];
      foreach ($iterator as $data) {
         $sprints[] = $data;
      }

      return $sprints;
   }

   /**
    * Calculate sprint metrics
    */
   function calculateMetrics() {
      // Use utility functions
      $basic_metrics = PluginScrumbanUtils::getSprintBasicMetrics($this->getID());
      
      $metrics = [
         'planned_points' => $basic_metrics['total_points'],
         'completed_points' => $basic_metrics['completed_points'],
         'remaining_points' => $basic_metrics['total_points'] - $basic_metrics['completed_points'],
         'total_cards' => $basic_metrics['total_cards'],
         'completed_cards' => $basic_metrics['completed_cards'],
         'progress_percentage' => $basic_metrics['progress_percentage'],
         'burndown_data' => [],
         'velocity' => 0
      ];

      // Calculate velocity
      if ($this->fields['status'] == 'concluido') {
         $metrics['velocity'] = $metrics['completed_points'];
      } else {
         $metrics['velocity'] = intval($this->fields['velocity']);
      }

      return $metrics;
   }

   /**
    * Generate burndown chart data
    */
   function getBurndownData() {
      global $DB;

      $burndown = [];
      
      if (empty($this->fields['start_date']) || empty($this->fields['end_date'])) {
         return $burndown;
      }

      $start_date = new DateTime($this->fields['start_date']);
      $end_date = new DateTime($this->fields['end_date']);
      $current_date = clone $start_date;

      $metrics = $this->calculateMetrics();
      $total_points = $metrics['planned_points'];
      
      // Ideal burndown (straight line)
      $days_total = $start_date->diff($end_date)->days;
      $points_per_day = $days_total > 0 ? $total_points / $days_total : 0;

      $day_count = 0;
      while ($current_date <= $end_date) {
         $date_str = $current_date->format('Y-m-d');
         
         // Ideal points
         $ideal_remaining = $total_points - ($day_count * $points_per_day);
         
         // Actual points (simulated - in real implementation get from history)
         $actual_remaining = $this->getActualRemainingPoints($date_str);
         
         $burndown[] = [
            'date' => $date_str,
            'ideal' => max(0, $ideal_remaining),
            'actual' => $actual_remaining,
            'day' => $day_count
         ];
         
         $current_date->add(new DateInterval('P1D'));
         $day_count++;
      }

      return $burndown;
   }

   /**
    * Get actual remaining points for specific date
    */
   private function getActualRemainingPoints($date) {
      // Simulate actual data - in real implementation get from history
      $metrics = $this->calculateMetrics();
      return $metrics['remaining_points'];
   }

   /**
    * Start sprint
    */
   function startSprint() {
      // Check permissions
      if (!PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
         return false;
      }

      if ($this->fields['status'] == 'planejado') {
         return $this->update([
            'id' => $this->getID(),
            'status' => 'ativo',
            'start_date' => date('Y-m-d')
         ]);
      }
      return false;
   }

   /**
    * Finish sprint
    */
   function finishSprint() {
      // Check permissions
      if (!PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
         return false;
      }

      if ($this->fields['status'] == 'ativo') {
         $metrics = $this->calculateMetrics();
         
         return $this->update([
            'id' => $this->getID(),
            'status' => 'concluido',
            'end_date' => date('Y-m-d'),
            'velocity' => $metrics['completed_points']
         ]);
      }
      return false;
   }

   /**
    * Add card to sprint
    */
   function addCard($card_id) {
      // Check permissions
      if (!PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
         return false;
      }

      $card = new PluginScrumbanCard();
      if ($card->getFromDB($card_id)) {
         return $card->assignToSprint($this->getID());
      }
      return false;
   }

   /**
    * Remove card from sprint
    */
   function removeCard($card_id) {
      // Check permissions
      if (!PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
         return false;
      }

      $card = new PluginScrumbanCard();
      if ($card->getFromDB($card_id)) {
         return $card->assignToSprint(0);
      }
      return false;
   }

   /**
    * Get sprint cards with access control
    */
   function getCards() {
      return PluginScrumbanCard::getCardsBySprint($this->getID());
   }

   /**
    * Calculate sprint duration in days
    */
   function getDuration() {
      if (empty($this->fields['start_date']) || empty($this->fields['end_date'])) {
         return 0;
      }

      $start = new DateTime($this->fields['start_date']);
      $end = new DateTime($this->fields['end_date']);
      return $start->diff($end)->days;
   }

   /**
    * Check if sprint is overdue
    */
   function isOverdue() {
      if ($this->fields['status'] != 'ativo' || empty($this->fields['end_date'])) {
         return false;
      }

      $today = new DateTime();
      $end_date = new DateTime($this->fields['end_date']);
      return $today > $end_date;
   }

   /**
    * Get remaining days
    */
   function getDaysRemaining() {
      if ($this->fields['status'] != 'ativo' || empty($this->fields['end_date'])) {
         return 0;
      }

      $today = new DateTime();
      $end_date = new DateTime($this->fields['end_date']);
      
      if ($today > $end_date) {
         return 0;
      }
      
      return $today->diff($end_date)->days;
   }

   /**
    * Render sprint card
    */
   function showSprintCard() {
      $metrics = $this->calculateMetrics();
      $status_class = 'status-' . $this->fields['status'];
      
      echo "<div class='sprint-card slide-up'>";
      echo "<div class='sprint-card-header'>";
      echo "<h3 class='sprint-title'>" . htmlspecialchars($this->fields['name']) . "</h3>";
      
      $status_labels = [
         'planejado' => __('Planned', 'scrumban'),
         'ativo' => __('Active', 'scrumban'),
         'concluido' => __('Completed', 'scrumban')
      ];
      
      echo "<div class='d-flex align-items-center gap-2'>";
      echo "<span class='sprint-status $status_class'>" . $status_labels[$this->fields['status']] . "</span>";
      
      // Only show edit button if user can edit
      if (PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
         echo "<button class='btn btn-outline-primary btn-sm' onclick='openEditSprintModal(" . $this->getID() . ")'>";
         echo "<i class='fas fa-edit'></i> " . __('Edit', 'scrumban');
         echo "</button>";
      }
      
      echo "</div>";
      echo "</div>";
      
      if (!empty($this->fields['start_date']) && !empty($this->fields['end_date'])) {
         echo "<div class='sprint-dates'>";
         echo "<i class='fas fa-calendar me-2'></i>";
         echo "<span>" . Html::convDate($this->fields['start_date']) . " - " . Html::convDate($this->fields['end_date']) . "</span>";
         echo "</div>";
      }
      
      if (!empty($this->fields['objective'])) {
         echo "<p class='text-muted mb-3'>" . htmlspecialchars(substr($this->fields['objective'], 0, 100)) . "...</p>";
      }
      
      // Progress bar
      echo "<div class='sprint-progress'>";
      echo "<div class='progress-label'>";
      echo "<span>" . __('Progress', 'scrumban') . "</span>";
      echo "<span>" . $metrics['progress_percentage'] . "%</span>";
      echo "</div>";
      echo "<div class='progress-bar-custom'>";
      echo "<div class='progress-fill' style='width: " . $metrics['progress_percentage'] . "%'></div>";
      echo "</div>";
      echo "</div>";

      // Metrics
      echo "<div class='sprint-metrics'>";
      echo "<div class='metric'>";
      echo "<div class='metric-value'>" . $metrics['planned_points'] . "</div>";
      echo "<div class='metric-label'>" . __('Planned Story Points', 'scrumban') . "</div>";
      echo "</div>";
      echo "<div class='metric'>";
      echo "<div class='metric-value'>" . $metrics['completed_points'] . "</div>";
      echo "<div class='metric-label'>" . __('Completed Story Points', 'scrumban') . "</div>";
      echo "</div>";
      echo "<div class='metric'>";
      echo "<div class='metric-value'>" . ($metrics['total_cards'] - $metrics['completed_cards']) . "</div>";
      echo "<div class='metric-label'>" . __('Remaining Issues', 'scrumban') . "</div>";
      echo "</div>";
      echo "</div>";
      
      echo "</div>";
   }

   /**
    * Show sprints page with team filtering
    */
   static function showSprintsPage($board_id = 1) {
      global $DB;

      // Check if user can access this board
      if (!PluginScrumbanTeam::canUserAccessBoard($_SESSION['glpiID'], $board_id)) {
         echo "<div class='alert alert-danger'>";
         echo __('You do not have access to this board.', 'scrumban');
         echo "</div>";
         return;
      }

      echo "<div class='sprint-container fade-in'>";
      
      // Header
      echo "<div class='sprint-header'>";
      echo "<div>";
      echo "<h2>" . __('Sprint Management', 'scrumban') . "</h2>";
      echo "<p class='text-muted mb-0'>" . __('Track your sprint progress', 'scrumban') . "</p>";
      echo "</div>";
      
      // Only show create button if user can edit
      if (PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $board_id)) {
         echo "<button class='btn btn-primary' onclick='openCreateSprintModal()'>";
         echo "<i class='fas fa-plus me-2'></i>" . __('New Sprint', 'scrumban');
         echo "</button>";
      }
      
      echo "</div>";

      // Sprint grid
      echo "<div class='sprints-grid'>";
      
      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_sprints',
         'WHERE' => [
            'boards_id' => $board_id,
            'is_active' => 1
         ],
         'ORDER' => ['status', 'start_date']
      ]);

      foreach ($iterator as $data) {
         $sprint = new self();
         $sprint->fields = $data;
         $sprint->showSprintCard();
      }
      
      echo "</div>";
      echo "</div>";
   }

   /**
    * Export sprint data
    */
   function exportData() {
      $data = $this->fields;
      $data['metrics'] = $this->calculateMetrics();
      $data['burndown'] = $this->getBurndownData();
      $data['cards'] = $this->getCards();
      return $data;
   }

   /**
    * Check if user can edit this specific sprint
    */
   function canUserEdit($user_id = null) {
      if ($user_id === null) {
         $user_id = $_SESSION['glpiID'];
      }
      
      return PluginScrumbanTeam::canUserEditBoard($user_id, $this->fields['boards_id']);
   }

   /**
    * Check if user can delete this specific sprint
    */
   function canUserDelete($user_id = null) {
      if ($user_id === null) {
         $user_id = $_SESSION['glpiID'];
      }
      
      return PluginScrumbanTeam::canUserManageBoard($user_id, $this->fields['boards_id']);
   }

   /**
    * Validate before deletion
    */
   function pre_deleteItem() {
      // Check if user has permission to delete
      if (!$this->canUserDelete()) {
         Session::addMessageAfterRedirect(__('You do not have permission to delete this sprint', 'scrumban'), false, ERROR);
         return false;
      }

      return true;
   }

   /**
    * Clean up after deletion
    */
   function post_deleteFromDB() {
      global $DB;

      // Move cards back to backlog
      $DB->update('glpi_plugin_scrumban_cards', [
         'sprints_id' => 0
      ], [
         'sprints_id' => $this->getID()
      ]);

      return true;
   }

   /**
    * Get sprints accessible to user
    */
   static function getAccessibleSprints($user_id = null) {
      if ($user_id === null) {
         $user_id = $_SESSION['glpiID'];
      }

      global $DB;

      $accessible_boards = PluginScrumbanTeam::getBoardsForUser($user_id);
      
      if (empty($accessible_boards)) {
         return [];
      }

      $board_ids = array_column($accessible_boards, 'id');

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_sprints',
         'WHERE' => [
            'boards_id' => $board_ids,
            'is_active' => 1
         ],
         'ORDER' => ['start_date', 'name']
      ]);

      $sprints = [];
      foreach ($iterator as $data) {
         $sprints[] = $data;
      }

      return $sprints;
   }
}