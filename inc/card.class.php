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

      $acceptance_criteria = $this->getAcceptanceCriteria();
      if (empty($acceptance_criteria)) {
         $acceptance_criteria[] = [
            'title' => '',
            'description' => '',
            'is_completed' => 0
         ];
      }

      $test_scenarios = $this->getTestScenarios();
      if (empty($test_scenarios)) {
         $test_scenarios[] = [
            'title' => '',
            'description' => '',
            'expected_result' => '',
            'status' => 'pending'
         ];
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
      echo "<td>" . __('External Reference', 'scrumban') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'external_reference', ['size' => 30]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Planned Delivery Date', 'scrumban') . "</td>";
      echo "<td>";
      Html::showDateField('planned_delivery_date', ['value' => $this->fields['planned_delivery_date']]);
      echo "</td>";
      echo "<td>" . __('Completion Date', 'scrumban') . "</td>";
      echo "<td>";
      Html::showDateTimeField('completed_at', ['value' => $this->fields['completed_at']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Definition of Ready (%)', 'scrumban') . "</td>";
      echo "<td>";
      echo "<input type='number' name='dor_percent' class='form-control' min='0' max='100' value='" . (int)$this->fields['dor_percent'] . "'>";
      echo "</td>";
      echo "<td>" . __('Definition of Done (%)', 'scrumban') . "</td>";
      echo "<td>";
      echo "<input type='number' name='dod_percent' class='form-control' min='0' max='100' value='" . (int)$this->fields['dod_percent'] . "'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Development Branch', 'scrumban') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'development_branch', ['size' => 30]);
      echo "</td>";
      echo "<td>" . __('Pull Request', 'scrumban') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'development_pull_request', ['size' => 30]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Commits', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='development_commits' rows='4' class='form-control'>" . htmlspecialchars((string)$this->fields['development_commits']) . "</textarea>";
      echo "<p class='small text-muted mb-0'>" . __('One commit per line. You can include SHA or summary.', 'scrumban') . "</p>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4'>";
      echo "<div class='mb-4'>";
      echo "<div class='d-flex justify-content-between align-items-center mb-2'>";
      echo "<h4 class='mb-0'>" . __('Acceptance Criteria', 'scrumban') . "</h4>";
      echo "<button type='button' class='btn btn-outline-primary btn-sm' onclick='ScrumbanCardForm.addCriteriaRow()'>";
      echo "<i class='fas fa-plus me-1'></i>" . __('Add Criterion', 'scrumban');
      echo "</button>";
      echo "</div>";
      $criteria_count = count($acceptance_criteria);
      echo "<div id='acceptance-criteria-list' class='scrumban-repeatable-list' data-next-index='" . $criteria_count . "'>";
      foreach ($acceptance_criteria as $index => $criterion) {
         echo "<div class='card mb-3 scrumban-repeatable-item'>";
         echo "<div class='card-body'>";
         echo "<div class='row g-3 align-items-end'>";
         echo "<div class='col-md-6'>";
         echo "<label class='form-label'>" . __('Title', 'scrumban') . "</label>";
         echo "<input type='text' class='form-control' name='acceptance_criteria[$index][title]' value='" . htmlspecialchars((string)($criterion['title'] ?? '')) . "'>";
         echo "</div>";
         echo "<div class='col-md-4'>";
         echo "<label class='form-label'>" . __('Status', 'scrumban') . "</label>";
         echo "<select class='form-select' name='acceptance_criteria[$index][is_completed]'>";
         $is_completed = !empty($criterion['is_completed']);
         echo "<option value='0'" . (!$is_completed ? " selected" : "") . ">" . __('Pending', 'scrumban') . "</option>";
         echo "<option value='1'" . ($is_completed ? " selected" : "") . ">" . __('Completed', 'scrumban') . "</option>";
         echo "</select>";
         echo "</div>";
         echo "<div class='col-md-2 text-end'>";
         echo "<label class='form-label d-block'>&nbsp;</label>";
         echo "<button type='button' class='btn btn-outline-danger btn-sm' onclick='ScrumbanCardForm.removeRow(this)'><i class='fas fa-trash'></i></button>";
         echo "</div>";
         echo "<div class='col-12'>";
         echo "<label class='form-label'>" . __('Description', 'scrumban') . "</label>";
         echo "<textarea class='form-control' rows='2' name='acceptance_criteria[$index][description]'>" . htmlspecialchars((string)($criterion['description'] ?? '')) . "</textarea>";
         echo "</div>";
         echo "</div>";
         echo "</div>";
         echo "</div>";
      }
      echo "</div>";
      echo "</div>";

      echo "<div class='mb-4'>";
      echo "<div class='d-flex justify-content-between align-items-center mb-2'>";
      echo "<h4 class='mb-0'>" . __('Test Scenarios', 'scrumban') . "</h4>";
      echo "<button type='button' class='btn btn-outline-primary btn-sm' onclick='ScrumbanCardForm.addTestScenarioRow()'>";
      echo "<i class='fas fa-plus me-1'></i>" . __('Add Scenario', 'scrumban');
      echo "</button>";
      echo "</div>";
      $scenario_count = count($test_scenarios);
      echo "<div id='test-scenarios-list' class='scrumban-repeatable-list' data-next-index='" . $scenario_count . "'>";
      foreach ($test_scenarios as $index => $scenario) {
         echo "<div class='card mb-3 scrumban-repeatable-item'>";
         echo "<div class='card-body'>";
         echo "<div class='row g-3 align-items-end'>";
         echo "<div class='col-md-6'>";
         echo "<label class='form-label'>" . __('Title', 'scrumban') . "</label>";
         echo "<input type='text' class='form-control' name='test_scenarios[$index][title]' value='" . htmlspecialchars((string)($scenario['title'] ?? '')) . "'>";
         echo "</div>";
         echo "<div class='col-md-4'>";
         echo "<label class='form-label'>" . __('Status', 'scrumban') . "</label>";
         echo "<select class='form-select' name='test_scenarios[$index][status]'>";
         $status = $scenario['status'] ?? 'pending';
         $status_options = [
            'pending' => __('Pending', 'scrumban'),
            'passed' => __('Passed', 'scrumban'),
            'failed' => __('Failed', 'scrumban')
         ];
         foreach ($status_options as $value => $label) {
            $selected = ($status === $value) ? " selected" : "";
            echo "<option value='" . $value . "'" . $selected . ">" . $label . "</option>";
         }
         echo "</select>";
         echo "</div>";
         echo "<div class='col-md-2 text-end'>";
         echo "<label class='form-label d-block'>&nbsp;</label>";
         echo "<button type='button' class='btn btn-outline-danger btn-sm' onclick='ScrumbanCardForm.removeRow(this)'><i class='fas fa-trash'></i></button>";
         echo "</div>";
         echo "<div class='col-md-6'>";
         echo "<label class='form-label'>" . __('Description', 'scrumban') . "</label>";
         echo "<textarea class='form-control' rows='2' name='test_scenarios[$index][description]'>" . htmlspecialchars((string)($scenario['description'] ?? '')) . "</textarea>";
         echo "</div>";
         echo "<div class='col-md-6'>";
         echo "<label class='form-label'>" . __('Expected Result', 'scrumban') . "</label>";
         echo "<textarea class='form-control' rows='2' name='test_scenarios[$index][expected_result]'>" . htmlspecialchars((string)($scenario['expected_result'] ?? '')) . "</textarea>";
         echo "</div>";
         echo "</div>";
         echo "</div>";
         echo "</div>";
      }
      echo "</div>";
      echo "</div>";

      echo "<template id='acceptance-criteria-template'>";
      echo "<div class='card mb-3 scrumban-repeatable-item'>";
      echo "<div class='card-body'>";
      echo "<div class='row g-3 align-items-end'>";
      echo "<div class='col-md-6'>";
      echo "<label class='form-label'>" . __('Title', 'scrumban') . "</label>";
      echo "<input type='text' class='form-control' data-field='title'>";
      echo "</div>";
      echo "<div class='col-md-4'>";
      echo "<label class='form-label'>" . __('Status', 'scrumban') . "</label>";
      echo "<select class='form-select' data-field='is_completed'>";
      echo "<option value='0'>" . __('Pending', 'scrumban') . "</option>";
      echo "<option value='1'>" . __('Completed', 'scrumban') . "</option>";
      echo "</select>";
      echo "</div>";
      echo "<div class='col-md-2 text-end'>";
      echo "<label class='form-label d-block'>&nbsp;</label>";
      echo "<button type='button' class='btn btn-outline-danger btn-sm' onclick='ScrumbanCardForm.removeRow(this)'><i class='fas fa-trash'></i></button>";
      echo "</div>";
      echo "<div class='col-12'>";
      echo "<label class='form-label'>" . __('Description', 'scrumban') . "</label>";
      echo "<textarea class='form-control' rows='2' data-field='description'></textarea>";
      echo "</div>";
      echo "</div>";
      echo "</div>";
      echo "</div>";
      echo "</template>";

      echo "<template id='test-scenario-template'>";
      echo "<div class='card mb-3 scrumban-repeatable-item'>";
      echo "<div class='card-body'>";
      echo "<div class='row g-3 align-items-end'>";
      echo "<div class='col-md-6'>";
      echo "<label class='form-label'>" . __('Title', 'scrumban') . "</label>";
      echo "<input type='text' class='form-control' data-field='title'>";
      echo "</div>";
      echo "<div class='col-md-4'>";
      echo "<label class='form-label'>" . __('Status', 'scrumban') . "</label>";
      echo "<select class='form-select' data-field='status'>";
      echo "<option value='pending'>" . __('Pending', 'scrumban') . "</option>";
      echo "<option value='passed'>" . __('Passed', 'scrumban') . "</option>";
      echo "<option value='failed'>" . __('Failed', 'scrumban') . "</option>";
      echo "</select>";
      echo "</div>";
      echo "<div class='col-md-2 text-end'>";
      echo "<label class='form-label d-block'>&nbsp;</label>";
      echo "<button type='button' class='btn btn-outline-danger btn-sm' onclick='ScrumbanCardForm.removeRow(this)'><i class='fas fa-trash'></i></button>";
      echo "</div>";
      echo "<div class='col-md-6'>";
      echo "<label class='form-label'>" . __('Description', 'scrumban') . "</label>";
      echo "<textarea class='form-control' rows='2' data-field='description'></textarea>";
      echo "</div>";
      echo "<div class='col-md-6'>";
      echo "<label class='form-label'>" . __('Expected Result', 'scrumban') . "</label>";
      echo "<textarea class='form-control' rows='2' data-field='expected_result'></textarea>";
      echo "</div>";
      echo "</div>";
      echo "</div>";
      echo "</div>";
      echo "</template>";

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Active', 'scrumban') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td>";
      echo "<td colspan='2'></td>";
      echo "</tr>";

      static $card_form_script = false;
      if (!$card_form_script) {
         $card_form_script = true;
         $script = <<<'JS'
(function () {
   function assignNames(root, prefix, index) {
      root.querySelectorAll('[data-field]').forEach(function (element) {
         var field = element.getAttribute('data-field');
         element.setAttribute('name', prefix + '[' + index + '][' + field + ']');
         if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
            if (element.type === 'checkbox' || element.type === 'radio') {
               element.checked = false;
            } else {
               element.value = '';
            }
         }
         if (element.tagName === 'SELECT') {
            element.selectedIndex = 0;
         }
      });
   }

   window.ScrumbanCardForm = window.ScrumbanCardForm || {};

   window.ScrumbanCardForm.removeRow = function (button) {
      var item = button.closest('.scrumban-repeatable-item');
      if (item) {
         item.remove();
      }
   };

   window.ScrumbanCardForm.addCriteriaRow = function () {
      var container = document.getElementById('acceptance-criteria-list');
      if (!container) {
         return;
      }
      var template = document.getElementById('acceptance-criteria-template');
      if (!template) {
         return;
      }
      var index = parseInt(container.dataset.nextIndex || container.children.length, 10);
      var node = template.content.firstElementChild.cloneNode(true);
      assignNames(node, 'acceptance_criteria', index);
      container.appendChild(node);
      container.dataset.nextIndex = index + 1;
   };

   window.ScrumbanCardForm.addTestScenarioRow = function () {
      var container = document.getElementById('test-scenarios-list');
      if (!container) {
         return;
      }
      var template = document.getElementById('test-scenario-template');
      if (!template) {
         return;
      }
      var index = parseInt(container.dataset.nextIndex || container.children.length, 10);
      var node = template.content.firstElementChild.cloneNode(true);
      assignNames(node, 'test_scenarios', index);
      container.appendChild(node);
      container.dataset.nextIndex = index + 1;
   };
})();
JS;
         echo Html::scriptBlock($script);
      }

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

      $input = $this->normalizeStructuredFields($input);

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

      $input = $this->normalizeStructuredFields($input);

      $input['date_mod'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   private function normalizeStructuredFields(array $input) {
      if (isset($input['dor_percent'])) {
         $input['dor_percent'] = max(0, min(100, (int)$input['dor_percent']));
      }

      if (isset($input['dod_percent'])) {
         $input['dod_percent'] = max(0, min(100, (int)$input['dod_percent']));
      }

      if (isset($input['development_commits'])) {
         $lines = preg_split("/(\\r\\n|\\r|\\n)/", (string)$input['development_commits']);
         $lines = array_filter(array_map('trim', $lines));
         $input['development_commits'] = implode(PHP_EOL, $lines);
      }

      $criteria_source = $input['acceptance_criteria'] ?? ($_POST['acceptance_criteria'] ?? null);
      if (is_array($criteria_source)) {
         $normalized = [];
         foreach ($criteria_source as $item) {
            if (!is_array($item)) {
               continue;
            }
            $title = trim((string)($item['title'] ?? ''));
            $description = trim((string)($item['description'] ?? ''));
            $is_completed = !empty($item['is_completed']) && $item['is_completed'] !== '0';

            if ($title === '' && $description === '') {
               continue;
            }

            $normalized[] = [
               'title' => $title,
               'description' => $description,
               'is_completed' => $is_completed ? 1 : 0
            ];
         }

         $input['acceptance_criteria'] = !empty($normalized)
            ? json_encode($normalized, JSON_UNESCAPED_UNICODE)
            : null;
      }

      $scenario_source = $input['test_scenarios'] ?? ($_POST['test_scenarios'] ?? null);
      if (is_array($scenario_source)) {
         $normalized = [];
         foreach ($scenario_source as $item) {
            if (!is_array($item)) {
               continue;
            }

            $title = trim((string)($item['title'] ?? ''));
            $description = trim((string)($item['description'] ?? ''));
            $expected = trim((string)($item['expected_result'] ?? ''));
            $status = $item['status'] ?? 'pending';
            if (!in_array($status, ['pending', 'passed', 'failed'], true)) {
               $status = 'pending';
            }

            if ($title === '' && $description === '' && $expected === '') {
               continue;
            }

            $normalized[] = [
               'title' => $title,
               'description' => $description,
               'expected_result' => $expected,
               'status' => $status
            ];
         }

         $input['test_scenarios'] = !empty($normalized)
            ? json_encode($normalized, JSON_UNESCAPED_UNICODE)
            : null;
      }

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

   function getAcceptanceCriteria() {
      if (empty($this->fields['acceptance_criteria'])) {
         return [];
      }

      $data = json_decode($this->fields['acceptance_criteria'], true);
      if (!is_array($data)) {
         return [];
      }

      $normalized = [];
      foreach ($data as $item) {
         if (!is_array($item)) {
            continue;
         }

         $normalized[] = [
            'title' => trim((string)($item['title'] ?? '')),
            'description' => trim((string)($item['description'] ?? '')),
            'is_completed' => !empty($item['is_completed']) ? 1 : 0
         ];
      }

      return $normalized;
   }

   function getTestScenarios() {
      if (empty($this->fields['test_scenarios'])) {
         return [];
      }

      $data = json_decode($this->fields['test_scenarios'], true);
      if (!is_array($data)) {
         return [];
      }

      $normalized = [];
      foreach ($data as $item) {
         if (!is_array($item)) {
            continue;
         }

         $status = $item['status'] ?? 'pending';
         if (!in_array($status, ['pending', 'passed', 'failed'], true)) {
            $status = 'pending';
         }

         $normalized[] = [
            'title' => trim((string)($item['title'] ?? '')),
            'description' => trim((string)($item['description'] ?? '')),
            'expected_result' => trim((string)($item['expected_result'] ?? '')),
            'status' => $status
         ];
      }

      return $normalized;
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
      $data['acceptance_criteria'] = $this->getAcceptanceCriteria();
      $data['test_scenarios'] = $this->getTestScenarios();
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