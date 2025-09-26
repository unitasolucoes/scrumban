<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You cannot access directly to this file");
}

class PluginScrumbanCard extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return _n('Card', 'Cards', $nb, 'scrumban');
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginScrumbanComment', $ong, $options);
      $this->addStandardTab('PluginScrumbanAttachment', $ong, $options);
      return $ong;
   }

   function showForm($ID, $options = []) {
      global $DB;
      
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
      
      // Filter boards based on selected board_id
      if ($this->fields['boards_id']) {
         $board_accessible = false;
         foreach ($accessible_boards as $board) {
            if ($board['id'] == $this->fields['boards_id']) {
               $board_accessible = true;
               break;
            }
         }
         
         if (!$board_accessible) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4'>";
            echo "<div class='alert alert-danger'>";
            echo __('You do not have access to this board.', 'scrumban');
            echo "</div>";
            echo "</td>";
            echo "</tr>";
            $this->showFormButtons($options);
            return true;
         }
      }

      // Get columns for selected board
      $columns = [];
      if ($this->fields['boards_id']) {
         $columns_iterator = $DB->request([
            'FROM' => 'glpi_plugin_scrumban_columns',
            'WHERE' => [
               'boards_id' => $this->fields['boards_id'],
               'is_active' => 1
            ],
            'ORDER' => 'position'
         ]);
         foreach ($columns_iterator as $column) {
            $columns[$column['id']] = $column['name'];
         }
      }

      // Get sprints for selected board
      $sprints = [];
      if ($this->fields['boards_id']) {
         $sprints_iterator = $DB->request([
            'FROM' => 'glpi_plugin_scrumban_sprints',
            'WHERE' => [
               'boards_id' => $this->fields['boards_id'],
               'is_active' => 1
            ],
            'ORDER' => 'name'
         ]);
         foreach ($sprints_iterator as $sprint) {
            $sprints[$sprint['id']] = $sprint['name'];
         }
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Board', 'scrumban') . " *</td>";
      echo "<td>";
      $board_options = [];
      foreach ($accessible_boards as $board) {
         $board_options[$board['id']] = $board['name'];
      }
      Dropdown::showFromArray('boards_id', $board_options, [
         'value' => $this->fields['boards_id'],
         'on_change' => 'updateColumns(this.value)'
      ]);
      echo "</td>";
      echo "<td>" . __('Column', 'scrumban') . " *</td>";
      echo "<td>";
      Dropdown::showFromArray('columns_id', $columns, [
         'value' => $this->fields['columns_id']
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Title', 'scrumban') . " *</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "title", ['size' => 50]);
      echo "</td>";
      echo "<td>" . __('Type', 'scrumban') . " *</td>";
      echo "<td>";
      $type_options = [
         'story' => __('Story', 'scrumban'),
         'task' => __('Task', 'scrumban'),
         'bug' => __('Bug', 'scrumban'),
         'epic' => __('Epic', 'scrumban')
      ];
      Dropdown::showFromArray('type', $type_options, [
         'value' => $this->fields['type']
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Priority', 'scrumban') . "</td>";
      echo "<td>";
      $priority_options = [
         'trivial' => __('Trivial', 'scrumban'),
         'menor' => __('Minor', 'scrumban'),
         'normal' => __('Normal', 'scrumban'),
         'maior' => __('Major', 'scrumban'),
         'critico' => __('Critical', 'scrumban')
      ];
      Dropdown::showFromArray('priority', $priority_options, [
         'value' => $this->fields['priority']
      ]);
      echo "</td>";
      echo "<td>" . __('Story Points', 'scrumban') . "</td>";
      echo "<td>";
      $sp_options = [
         0 => __('Not estimated', 'scrumban'),
         1 => '1', 2 => '2', 3 => '3', 5 => '5', 
         8 => '8', 13 => '13', 21 => '21', 34 => '34'
      ];
      Dropdown::showFromArray('story_points', $sp_options, [
         'value' => $this->fields['story_points']
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Assignee', 'scrumban') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "assignee", ['size' => 30]);
      echo "</td>";
      echo "<td>" . __('Reporter', 'scrumban') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "reporter", ['size' => 30]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Sprint', 'scrumban') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('sprints_id', $sprints, [
         'value' => $this->fields['sprints_id'],
         'display_emptychoice' => true,
         'emptylabel' => __('Backlog', 'scrumban')
      ]);
      echo "</td>";
      echo "<td>" . __('Due Date', 'scrumban') . "</td>";
      echo "<td>";
      Html::showDateField("due_date", ['value' => $this->fields["due_date"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='description' rows='6' cols='80'>" . $this->fields["description"] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('GLPI Ticket', 'scrumban') . "</td>";
      echo "<td>";
      Ticket::dropdown(['name' => 'tickets_id', 'value' => $this->fields['tickets_id']]);
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
      global $DB;

      // Check if user can create cards on this board
      if (isset($input['boards_id']) && !PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $input['boards_id'])) {
         Session::addMessageAfterRedirect(__('You do not have permission to create cards on this board', 'scrumban'), false, ERROR);
         return false;
      }

      // Set position if not provided
      if (!isset($input['position']) && isset($input['columns_id'])) {
         $result = $DB->request([
            'SELECT' => 'MAX(position) as max_pos',
            'FROM' => $this->getTable(),
            'WHERE' => ['columns_id' => $input['columns_id']]
         ]);
         
         foreach ($result as $row) {
            $input['position'] = ($row['max_pos'] ?? -1) + 1;
         }
      }

      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   function prepareInputForUpdate($input) {
      // Check if user can edit cards on this board
      if (isset($input['id']) && $this->getFromDB($input['id'])) {
         if (!PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
            Session::addMessageAfterRedirect(__('You do not have permission to edit this card', 'scrumban'), false, ERROR);
            return false;
         }
      }

      $input['date_mod'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   /**
    * Move card to another column
    */
   function moveToColumn($column_id, $position = null) {
      global $DB;

      // Check if user can edit this board
      if (!PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
         return false;
      }

      if ($position === null) {
         // Find next available position
         $result = $DB->request([
            'SELECT' => 'MAX(position) as max_pos',
            'FROM' => $this->getTable(),
            'WHERE' => ['columns_id' => $column_id]
         ]);
         
         foreach ($result as $row) {
            $position = ($row['max_pos'] ?? -1) + 1;
         }
      }

      return $this->update([
         'id' => $this->getID(),
         'columns_id' => $column_id,
         'position' => $position
      ]);
   }

   /**
    * Assign to a sprint
    */
   function assignToSprint($sprint_id) {
      // Check if user can edit this board
      if (!PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
         return false;
      }

      return $this->update([
         'id' => $this->getID(),
         'sprints_id' => $sprint_id
      ]);
   }

   /**
    * Get cards by criteria with team filtering
    */
   static function getCards($criteria = []) {
      global $DB;

      $default_criteria = [
         'is_active' => 1
      ];

      $criteria = array_merge($default_criteria, $criteria);

      // Filter by accessible boards if board_id not specified
      if (!isset($criteria['boards_id'])) {
         $accessible_boards = PluginScrumbanTeam::getBoardsForUser($_SESSION['glpiID']);
         
         if (empty($accessible_boards)) {
            return [];
         }

         $board_ids = array_column($accessible_boards, 'id');
         $criteria['boards_id'] = $board_ids;
      } else {
         // Check if user can access the specific board
         if (!PluginScrumbanTeam::canUserAccessBoard($_SESSION['glpiID'], $criteria['boards_id'])) {
            return [];
         }
      }

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => $criteria,
         'ORDER' => ['columns_id', 'position']
      ]);

      $cards = [];
      foreach ($iterator as $data) {
         $cards[] = $data;
      }

      return $cards;
   }

   /**
    * Get cards by column with access control
    */
   static function getCardsByColumn($column_id) {
      global $DB;

      // Get board_id from column
      $column_result = $DB->request([
         'SELECT' => 'boards_id',
         'FROM' => 'glpi_plugin_scrumban_columns',
         'WHERE' => ['id' => $column_id],
         'LIMIT' => 1
      ]);

      foreach ($column_result as $column) {
         return self::getCards([
            'columns_id' => $column_id,
            'boards_id' => $column['boards_id']
         ]);
      }

      return [];
   }

   /**
    * Get cards by sprint with access control
    */
   static function getCardsBySprint($sprint_id) {
      global $DB;

      // Get board_id from sprint
      $sprint_result = $DB->request([
         'SELECT' => 'boards_id',
         'FROM' => 'glpi_plugin_scrumban_sprints',
         'WHERE' => ['id' => $sprint_id],
         'LIMIT' => 1
      ]);

      foreach ($sprint_result as $sprint) {
         return self::getCards([
            'sprints_id' => $sprint_id,
            'boards_id' => $sprint['boards_id']
         ]);
      }

      return [];
   }

   /**
    * Get backlog cards with access control
    */
   static function getBacklogCards($board_id) {
      return self::getCards([
         'boards_id' => $board_id,
         'sprints_id' => 0
      ]);
   }

   /**
    * Get card labels
    */
   function getLabels() {
      global $DB;

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_labels',
         'WHERE' => ['cards_id' => $this->getID()],
         'ORDER' => 'name'
      ]);

      $labels = [];
      foreach ($iterator as $data) {
         $labels[] = $data;
      }

      return $labels;
   }

   /**
    * Add label to card
    */
   function addLabel($name, $color = '#007bff') {
      global $DB;

      // Check if user can edit this board
      if (!PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
         return false;
      }

      // Check if already exists
      $existing = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_labels',
         'WHERE' => [
            'cards_id' => $this->getID(),
            'name' => $name
         ]
      ]);

      if (count($existing) == 0) {
         $DB->insert('glpi_plugin_scrumban_labels', [
            'cards_id' => $this->getID(),
            'name' => $name,
            'color' => $color,
            'date_creation' => $_SESSION['glpi_currenttime']
         ]);
         return true;
      }

      return false;
   }

   /**
    * Remove label from card
    */
   function removeLabel($label_id) {
      global $DB;

      // Check if user can edit this board
      if (!PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $this->fields['boards_id'])) {
         return false;
      }

      return $DB->delete('glpi_plugin_scrumban_labels', [
         'id' => $label_id,
         'cards_id' => $this->getID()
      ]);
   }

   /**
    * Get activity history
    */
   function getActivityHistory($limit = 10) {
      global $DB;

      $activities = [];

      // Comments
      $comments = $DB->request([
         'SELECT' => [
            'glpi_plugin_scrumban_comments.*',
            'glpi_users.firstname',
            'glpi_users.realname'
         ],
         'FROM' => 'glpi_plugin_scrumban_comments',
         'LEFT JOIN' => [
            'glpi_users' => [
               'ON' => [
                  'glpi_plugin_scrumban_comments' => 'users_id',
                  'glpi_users' => 'id'
               ]
            ]
         ],
         'WHERE' => ['cards_id' => $this->getID()],
         'ORDER' => 'date_creation DESC',
         'LIMIT' => $limit
      ]);

      foreach ($comments as $comment) {
         $activities[] = [
            'type' => 'comment',
            'date' => $comment['date_creation'],
            'user' => $comment['firstname'] . ' ' . $comment['realname'],
            'content' => $comment['content']
         ];
      }

      // Sort by date
      usort($activities, function($a, $b) {
         return strtotime($b['date']) - strtotime($a['date']);
      });

      return array_slice($activities, 0, $limit);
   }

   /**
    * Calculate cycle time
    */
   function getCycleTime() {
      $created = strtotime($this->fields['date_creation']);
      
      if ($this->isCompleted()) {
         $completed = strtotime($this->fields['date_mod']);
         return $completed - $created;
      }
      
      return time() - $created;
   }

   /**
    * Check if card is completed
    */
   function isCompleted() {
      global $DB;

      // Check if current column is completion column
      $result = $DB->request([
         'SELECT' => 'name',
         'FROM' => 'glpi_plugin_scrumban_columns',
         'WHERE' => ['id' => $this->fields['columns_id']]
      ]);

      foreach ($result as $row) {
         return (stripos($row['name'], 'finaliz') !== false || 
                 stripos($row['name'], 'done') !== false ||
                 stripos($row['name'], 'concluí') !== false);
      }

      return false;
   }

   /**
    * Export card data
    */
   function exportData() {
      $data = $this->fields;
      $data['labels'] = $this->getLabels();
      $data['activity'] = $this->getActivityHistory();
      return $data;
   }

   /**
    * Check if user can edit this specific card
    */
   function canUserEdit($user_id = null) {
      if ($user_id === null) {
         $user_id = $_SESSION['glpiID'];
      }
      
      return PluginScrumbanTeam::canUserEditBoard($user_id, $this->fields['boards_id']);
   }

   /**
    * Check if user can delete this specific card
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
         Session::addMessageAfterRedirect(__('You do not have permission to delete this card', 'scrumban'), false, ERROR);
         return false;
      }

      return true;
   }

   /**
    * Clean up after deletion
    */
   function post_deleteFromDB() {
      global $DB;

      // Remove labels
      $DB->delete('glpi_plugin_scrumban_labels', [
         'cards_id' => $this->getID()
      ]);

      // Remove comments
      $DB->delete('glpi_plugin_scrumban_comments', [
         'cards_id' => $this->getID()
      ]);

      // Remove attachments
      $DB->delete('glpi_plugin_scrumban_attachments', [
         'cards_id' => $this->getID()
      ]);

      return true;
   }
}