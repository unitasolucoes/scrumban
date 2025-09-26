<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You cannot access directly to this file");
}

class PluginScrumbanCard extends CommonDBTM {

   private $pendingAcceptanceCriteria = null;
   private $pendingTestScenarios = null;

   const ACCEPTANCE_STATUSES = ['pending', 'in_progress', 'done'];
   const TEST_SCENARIO_STATUSES = ['not_run', 'in_progress', 'passed', 'failed', 'blocked'];

   static function getAcceptanceStatusOptions() {
      return [
         'pending' => __('Pendente', 'scrumban'),
         'in_progress' => __('Em andamento', 'scrumban'),
         'done' => __('Concluído', 'scrumban')
      ];
   }

   static function getTestScenarioStatusOptions() {
      return [
         'not_run' => __('Não executado', 'scrumban'),
         'in_progress' => __('Em execução', 'scrumban'),
         'passed' => __('Aprovado', 'scrumban'),
         'failed' => __('Reprovado', 'scrumban'),
         'blocked' => __('Bloqueado', 'scrumban')
      ];
   }

   private static function renderAcceptanceCriterionRow($index, $criterion) {
      $id = intval($criterion['id'] ?? 0);
      $description = htmlspecialchars($criterion['description'] ?? '', ENT_QUOTES, 'UTF-8');
      $status = $criterion['status'] ?? 'pending';

      echo "<tr data-index='" . $index . "'>";
      echo "<td>";
      echo "<input type='hidden' name='acceptance_criteria[" . $index . "][id]' value='" . $id . "' />";
      echo "<textarea name='acceptance_criteria[" . $index . "][description]' rows='2' class='form-control'>" . $description . "</textarea>";
      echo "</td>";
      echo "<td>";
      echo "<select name='acceptance_criteria[" . $index . "][status]' class='form-control'>";
      foreach (self::getAcceptanceStatusOptions() as $value => $label) {
         $selected = ($status === $value) ? " selected" : '';
         echo "<option value='" . $value . "'" . $selected . ">" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</option>";
      }
      echo "</select>";
      echo "</td>";
      echo "<td class='text-center'>";
      echo "<button type='button' class='btn btn-outline-danger btn-sm' onclick='PluginScrumbanCardForm.removeRow(this)'>&times;</button>";
      echo "</td>";
      echo "</tr>";
   }

   private static function renderTestScenarioRow($index, $scenario) {
      $id = intval($scenario['id'] ?? 0);
      $name = htmlspecialchars($scenario['name'] ?? '', ENT_QUOTES, 'UTF-8');
      $status = $scenario['status'] ?? 'not_run';
      $notes = htmlspecialchars($scenario['notes'] ?? '', ENT_QUOTES, 'UTF-8');

      echo "<tr data-index='" . $index . "'>";
      echo "<td>";
      echo "<input type='hidden' name='test_scenarios[" . $index . "][id]' value='" . $id . "' />";
      echo "<input type='text' name='test_scenarios[" . $index . "][name]' value='" . $name . "' class='form-control' />";
      echo "</td>";
      echo "<td>";
      echo "<select name='test_scenarios[" . $index . "][status]' class='form-control'>";
      foreach (self::getTestScenarioStatusOptions() as $value => $label) {
         $selected = ($status === $value) ? " selected" : '';
         echo "<option value='" . $value . "'" . $selected . ">" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</option>";
      }
      echo "</select>";
      echo "</td>";
      echo "<td>";
      echo "<textarea name='test_scenarios[" . $index . "][notes]' rows='2' class='form-control'>" . $notes . "</textarea>";
      echo "</td>";
      echo "<td class='text-center'>";
      echo "<button type='button' class='btn btn-outline-danger btn-sm' onclick='PluginScrumbanCardForm.removeRow(this)'>&times;</button>";
      echo "</td>";
      echo "</tr>";
   }

   private static function renderCardFormScripts() {
      $acceptance_options = json_encode(self::getAcceptanceStatusOptions(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
      $test_options = json_encode(self::getTestScenarioStatusOptions(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

      ob_start();
      ?>
<script>
(function() {
   if (!window.PluginScrumbanCardForm) {
      window.PluginScrumbanCardForm = {
         acceptanceOptions: {},
         testOptions: {},
         acceptanceIndex: 0,
         testIndex: 0,
         init: function(acceptanceOptions, testOptions) {
            this.acceptanceOptions = acceptanceOptions || {};
            this.testOptions = testOptions || {};
            var acceptanceRows = document.querySelectorAll('#acceptance-criteria-table tbody tr');
            var scenarioRows = document.querySelectorAll('#test-scenarios-table tbody tr');
            this.acceptanceIndex = acceptanceRows ? acceptanceRows.length : 0;
            this.testIndex = scenarioRows ? scenarioRows.length : 0;
         },
         removeRow: function(button) {
            var row = button.closest('tr');
            if (row && row.parentNode) {
               row.parentNode.removeChild(row);
            }
         },
         addAcceptanceCriterion: function() {
            var tbody = document.querySelector('#acceptance-criteria-table tbody');
            if (!tbody) { return; }
            var index = this.acceptanceIndex++;
            var row = document.createElement('tr');
            row.setAttribute('data-index', index);
            row.innerHTML = "<td><input type='hidden' name='acceptance_criteria[" + index + "][id]' value='0' />" +
               "<textarea name='acceptance_criteria[" + index + "][description]' rows='2' class='form-control'></textarea></td>" +
               "<td>" + this.buildSelect('acceptance_criteria[' + index + '][status]', this.acceptanceOptions, 'pending') + "</td>" +
               "<td class='text-center'><button type='button' class='btn btn-outline-danger btn-sm' onclick='PluginScrumbanCardForm.removeRow(this)'>&times;</button></td>";
            tbody.appendChild(row);
         },
         addTestScenario: function() {
            var tbody = document.querySelector('#test-scenarios-table tbody');
            if (!tbody) { return; }
            var index = this.testIndex++;
            var row = document.createElement('tr');
            row.setAttribute('data-index', index);
            row.innerHTML = "<td><input type='hidden' name='test_scenarios[" + index + "][id]' value='0' />" +
               "<input type='text' name='test_scenarios[" + index + "][name]' class='form-control' value='' /></td>" +
               "<td>" + this.buildSelect('test_scenarios[' + index + '][status]', this.testOptions, 'not_run') + "</td>" +
               "<td><textarea name='test_scenarios[" + index + "][notes]' rows='2' class='form-control'></textarea></td>" +
               "<td class='text-center'><button type='button' class='btn btn-outline-danger btn-sm' onclick='PluginScrumbanCardForm.removeRow(this)'>&times;</button></td>";
            tbody.appendChild(row);
         },
         buildSelect: function(name, options, selected) {
            var html = "<select name='" + name + "' class='form-control'>";
            for (var value in options) {
               if (!Object.prototype.hasOwnProperty.call(options, value)) {
                  continue;
               }
               var isSelected = value === selected ? " selected" : "";
               html += "<option value='" + value + "'" + isSelected + ">" + options[value] + "</option>";
            }
            html += "</select>";
            return html;
         }
      };
   }
   window.PluginScrumbanCardForm.init(<?php echo $acceptance_options; ?>, <?php echo $test_options; ?>);
})();
</script>
<?php
      echo ob_get_clean();
   }

   private function captureCollectionsFromInput(array &$input) {
      if (array_key_exists('acceptance_criteria', $input)) {
         $this->pendingAcceptanceCriteria = self::sanitizeAcceptanceCriteriaInput($input['acceptance_criteria']);
         unset($input['acceptance_criteria']);
      }

      if (array_key_exists('test_scenarios', $input)) {
         $this->pendingTestScenarios = self::sanitizeTestScenariosInput($input['test_scenarios']);
         unset($input['test_scenarios']);
      }
   }

   private static function sanitizeAcceptanceCriteriaInput($criteria) {
      $sanitized = [];
      if (!is_array($criteria)) {
         return $sanitized;
      }

      $position = 0;
      foreach ($criteria as $criterion) {
         $description = isset($criterion['description']) ? trim($criterion['description']) : '';
         $id = isset($criterion['id']) ? intval($criterion['id']) : 0;

         if ($description === '') {
            continue;
         }

         $status = isset($criterion['status']) && in_array($criterion['status'], self::ACCEPTANCE_STATUSES, true) ? $criterion['status'] : 'pending';

         $sanitized[] = [
            'id' => $id,
            'description' => $description,
            'status' => $status,
            'position' => $position++
         ];
      }

      return $sanitized;
   }

   private static function sanitizeTestScenariosInput($scenarios) {
      $sanitized = [];
      if (!is_array($scenarios)) {
         return $sanitized;
      }

      $position = 0;
      foreach ($scenarios as $scenario) {
         $name = isset($scenario['name']) ? trim($scenario['name']) : '';
         $notes = isset($scenario['notes']) ? trim($scenario['notes']) : '';
         $id = isset($scenario['id']) ? intval($scenario['id']) : 0;

         if ($name === '' && $notes === '') {
            continue;
         }

         $status = isset($scenario['status']) && in_array($scenario['status'], self::TEST_SCENARIO_STATUSES, true) ? $scenario['status'] : 'not_run';

         $sanitized[] = [
            'id' => $id,
            'name' => $name,
            'status' => $status,
            'notes' => $notes,
            'position' => $position++
         ];
      }

      return $sanitized;
   }

   static function getAcceptanceCriteriaForCard($card_id) {
      global $DB;

      if (!$card_id) {
         return [];
      }

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_card_acceptance_criteria',
         'WHERE' => ['cards_id' => $card_id],
         'ORDER' => ['position', 'id']
      ]);

      $criteria = [];
      foreach ($iterator as $row) {
         $criteria[] = $row;
      }

      return $criteria;
   }

   static function getTestScenariosForCard($card_id) {
      global $DB;

      if (!$card_id) {
         return [];
      }

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_card_test_scenarios',
         'WHERE' => ['cards_id' => $card_id],
         'ORDER' => ['position', 'id']
      ]);

      $scenarios = [];
      foreach ($iterator as $row) {
         $scenarios[] = $row;
      }

      return $scenarios;
   }

   private function sanitizeCardInput(array &$input) {
      foreach (['dor_percent', 'dod_percent'] as $field) {
         if (isset($input[$field])) {
            $value = intval($input[$field]);
            $input[$field] = max(0, min(100, $value));
         }
      }

      foreach (['due_date', 'planned_due_date', 'done_date'] as $date_field) {
         if (isset($input[$date_field]) && $input[$date_field] === '') {
            $input[$date_field] = null;
         }
      }

      foreach (['branch', 'pull_request'] as $text_field) {
         if (isset($input[$text_field])) {
            $input[$text_field] = trim($input[$text_field]);
         }
      }
   }

   private function persistAcceptanceCriteria() {
      global $DB;

      if ($this->pendingAcceptanceCriteria === null) {
         return;
      }

      $card_id = $this->getID();
      if (!$card_id) {
         return;
      }

      $existing_ids = [];
      $result = $DB->request([
         'SELECT' => 'id',
         'FROM' => 'glpi_plugin_scrumban_card_acceptance_criteria',
         'WHERE' => ['cards_id' => $card_id]
      ]);
      foreach ($result as $row) {
         $existing_ids[] = intval($row['id']);
      }

      $kept_ids = [];
      foreach ($this->pendingAcceptanceCriteria as $criterion) {
         $data = [
            'description' => $criterion['description'],
            'status' => $criterion['status'],
            'position' => $criterion['position'],
            'date_mod' => $_SESSION['glpi_currenttime']
         ];

         if ($criterion['id'] > 0) {
            $DB->update('glpi_plugin_scrumban_card_acceptance_criteria', $data, ['id' => $criterion['id']]);
            $kept_ids[] = $criterion['id'];
         } else {
            $data['cards_id'] = $card_id;
            $data['date_creation'] = $_SESSION['glpi_currenttime'];
            $DB->insert('glpi_plugin_scrumban_card_acceptance_criteria', $data);
            $kept_ids[] = $DB->insertId();
         }
      }

      $to_delete = array_diff($existing_ids, $kept_ids);
      if (!empty($to_delete)) {
         $DB->delete('glpi_plugin_scrumban_card_acceptance_criteria', ['id' => $to_delete]);
      }

      $this->pendingAcceptanceCriteria = null;
   }

   private function persistTestScenarios() {
      global $DB;

      if ($this->pendingTestScenarios === null) {
         return;
      }

      $card_id = $this->getID();
      if (!$card_id) {
         return;
      }

      $existing_ids = [];
      $result = $DB->request([
         'SELECT' => 'id',
         'FROM' => 'glpi_plugin_scrumban_card_test_scenarios',
         'WHERE' => ['cards_id' => $card_id]
      ]);
      foreach ($result as $row) {
         $existing_ids[] = intval($row['id']);
      }

      $kept_ids = [];
      foreach ($this->pendingTestScenarios as $scenario) {
         $data = [
            'name' => $scenario['name'],
            'status' => $scenario['status'],
            'notes' => $scenario['notes'],
            'position' => $scenario['position'],
            'date_mod' => $_SESSION['glpi_currenttime']
         ];

         if ($scenario['id'] > 0) {
            $DB->update('glpi_plugin_scrumban_card_test_scenarios', $data, ['id' => $scenario['id']]);
            $kept_ids[] = $scenario['id'];
         } else {
            $data['cards_id'] = $card_id;
            $data['date_creation'] = $_SESSION['glpi_currenttime'];
            $DB->insert('glpi_plugin_scrumban_card_test_scenarios', $data);
            $kept_ids[] = $DB->insertId();
         }
      }

      $to_delete = array_diff($existing_ids, $kept_ids);
      if (!empty($to_delete)) {
         $DB->delete('glpi_plugin_scrumban_card_test_scenarios', ['id' => $to_delete]);
      }

      $this->pendingTestScenarios = null;
   }

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

      $acceptance_criteria = $this->getID() ? self::getAcceptanceCriteriaForCard($this->getID()) : [];
      $test_scenarios = $this->getID() ? self::getTestScenariosForCard($this->getID()) : [];

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
      echo "<td>" . __('Planned Due Date', 'scrumban') . "</td>";
      echo "<td>";
      Html::showDateField("planned_due_date", ['value' => $this->fields["planned_due_date"]]);
      echo "</td>";
      echo "<td>" . __('Done Date', 'scrumban') . "</td>";
      echo "<td>";
      Html::showDateField("done_date", ['value' => $this->fields["done_date"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Branch', 'scrumban') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "branch", ['size' => 40]);
      echo "</td>";
      echo "<td>" . __('Pull Request', 'scrumban') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "pull_request", ['size' => 40]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Definition of Ready', 'scrumban') . " (%)</td>";
      echo "<td>";
      echo "<input type='number' min='0' max='100' name='dor_percent' value='" . intval($this->fields['dor_percent']) . "' class='form-control' />";
      echo "</td>";
      echo "<td>" . __('Definition of Done', 'scrumban') . " (%)</td>";
      echo "<td>";
      echo "<input type='number' min='0' max='100' name='dod_percent' value='" . intval($this->fields['dod_percent']) . "' class='form-control' />";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='description' rows='6' cols='80'>" . $this->fields["description"] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4'>";
      echo "<h3>" . __('Critérios de Aceitação', 'scrumban') . "</h3>";
      echo "<table class='tab_cadre_fixe' id='acceptance-criteria-table'>";
      echo "<thead><tr><th>" . __('Descrição', 'scrumban') . "</th><th style='width:180px'>" . __('Status', 'scrumban') . "</th><th style='width:60px'></th></tr></thead>";
      echo "<tbody>";
      if (empty($acceptance_criteria)) {
         $acceptance_criteria[] = ['id' => 0, 'description' => '', 'status' => 'pending'];
      }
      foreach ($acceptance_criteria as $index => $criterion) {
         self::renderAcceptanceCriterionRow($index, $criterion);
      }
      echo "</tbody>";
      echo "</table>";
      echo "<button type='button' class='btn btn-secondary' onclick='PluginScrumbanCardForm.addAcceptanceCriterion()'>" . __('Adicionar critério', 'scrumban') . "</button>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4'>";
      echo "<h3>" . __('Cenários de Teste', 'scrumban') . "</h3>";
      echo "<table class='tab_cadre_fixe' id='test-scenarios-table'>";
      echo "<thead><tr><th>" . __('Cenário', 'scrumban') . "</th><th style='width:180px'>" . __('Status', 'scrumban') . "</th><th>" . __('Notas', 'scrumban') . "</th><th style='width:60px'></th></tr></thead>";
      echo "<tbody>";
      if (empty($test_scenarios)) {
         $test_scenarios[] = ['id' => 0, 'name' => '', 'status' => 'not_run', 'notes' => ''];
      }
      foreach ($test_scenarios as $index => $scenario) {
         self::renderTestScenarioRow($index, $scenario);
      }
      echo "</tbody>";
      echo "</table>";
      echo "<button type='button' class='btn btn-secondary' onclick='PluginScrumbanCardForm.addTestScenario()'>" . __('Adicionar cenário', 'scrumban') . "</button>";
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
      self::renderCardFormScripts();
      return true;
   }

   function prepareInputForAdd($input) {
      global $DB;

      // Check if user can create cards on this board
      if (isset($input['boards_id']) && !PluginScrumbanTeam::canUserEditBoard($_SESSION['glpiID'], $input['boards_id'])) {
         Session::addMessageAfterRedirect(__('You do not have permission to create cards on this board', 'scrumban'), false, ERROR);
         return false;
      }

      $this->captureCollectionsFromInput($input);
      $this->sanitizeCardInput($input);

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

      $this->captureCollectionsFromInput($input);
      $this->sanitizeCardInput($input);
      $input['date_mod'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   function post_addItem() {
      $this->persistAcceptanceCriteria();
      $this->persistTestScenarios();
   }

   function post_updateItem($history = 1) {
      $this->persistAcceptanceCriteria();
      $this->persistTestScenarios();
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