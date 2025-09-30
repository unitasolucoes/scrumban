<?php

if (!defined('GLPI_ROOT')) {
   die("Desculpe. Você não pode acessar diretamente este arquivo");
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

      // Buscar quadros disponíveis
      $boards = PluginScrumbanBoard::getActiveBoards();
      
      // Buscar colunas do quadro selecionado
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

      // Buscar sprints disponíveis
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
      echo "<td>" . __('Quadro', 'scrumban') . " *</td>";
      echo "<td>";
      $board_options = [];
      foreach ($boards as $board) {
         $board_options[$board['id']] = $board['name'];
      }
      Dropdown::showFromArray('boards_id', $board_options, [
         'value' => $this->fields['boards_id'],
         'on_change' => 'updateColumns(this.value)'
      ]);
      echo "</td>";
      echo "<td>" . __('Coluna', 'scrumban') . " *</td>";
      echo "<td>";
      Dropdown::showFromArray('columns_id', $columns, [
         'value' => $this->fields['columns_id']
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Título', 'scrumban') . " *</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "title", ['size' => 50]);
      echo "</td>";
      echo "<td>" . __('Tipo', 'scrumban') . " *</td>";
      echo "<td>";
      $type_options = [
         'story' => __('História', 'scrumban'),
         'task' => __('Tarefa', 'scrumban'),
         'bug' => __('Bug', 'scrumban'),
         'epic' => __('Épico', 'scrumban')
      ];
      Dropdown::showFromArray('type', $type_options, [
         'value' => $this->fields['type']
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Prioridade', 'scrumban') . "</td>";
      echo "<td>";
      $priority_options = [
         'trivial' => __('Trivial', 'scrumban'),
         'menor' => __('Menor', 'scrumban'),
         'normal' => __('Normal', 'scrumban'),
         'maior' => __('Maior', 'scrumban'),
         'critico' => __('Crítico', 'scrumban')
      ];
      Dropdown::showFromArray('priority', $priority_options, [
         'value' => $this->fields['priority']
      ]);
      echo "</td>";
      echo "<td>" . __('Story Points', 'scrumban') . "</td>";
      echo "<td>";
      $sp_options = [
         0 => __('Não estimado', 'scrumban'),
         1 => '1', 2 => '2', 3 => '3', 5 => '5', 
         8 => '8', 13 => '13', 21 => '21', 34 => '34'
      ];
      Dropdown::showFromArray('story_points', $sp_options, [
         'value' => $this->fields['story_points']
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Responsável', 'scrumban') . "</td>";
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
      echo "<td>" . __('Data Limite', 'scrumban') . "</td>";
      echo "<td>";
      Html::showDateField("due_date", ['value' => $this->fields["due_date"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Descrição', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='description' rows='6' cols='80'>" . $this->fields["description"] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Ticket GLPI', 'scrumban') . "</td>";
      echo "<td>";
      Ticket::dropdown(['name' => 'tickets_id', 'value' => $this->fields['tickets_id']]);
      echo "</td>";
      echo "<td>" . __('Ativo', 'scrumban') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }

   function prepareInputForAdd($input) {
      global $DB;

      // Definir posição
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
      $input['date_mod'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   /**
    * Mover card para outra coluna
    */
   function moveToColumn($column_id, $position = null) {
      global $DB;

      if ($position === null) {
         // Buscar próxima posição disponível
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
    * Atribuir a uma sprint
    */
   function assignToSprint($sprint_id) {
      return $this->update([
         'id' => $this->getID(),
         'sprints_id' => $sprint_id
      ]);
   }

   /**
    * Buscar cards por critério
    */
   static function getCards($criteria = []) {
      global $DB;

      $default_criteria = [
         'is_active' => 1
      ];

      $criteria = array_merge($default_criteria, $criteria);

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
    * Buscar cards de uma coluna
    */
   static function getCardsByColumn($column_id) {
      return self::getCards(['columns_id' => $column_id]);
   }

   /**
    * Buscar cards de uma sprint
    */
   static function getCardsBySprint($sprint_id) {
      return self::getCards(['sprints_id' => $sprint_id]);
   }

   /**
    * Buscar cards do backlog (sem sprint)
    */
   static function getBacklogCards($board_id) {
      return self::getCards([
         'boards_id' => $board_id,
         'sprints_id' => 0
      ]);
   }

   /**
    * Obter labels do card
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
    * Adicionar label ao card
    */
   function addLabel($name, $color = '#007bff') {
      global $DB;

      // Verificar se já existe
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
      }
   }

   /**
    * Remover label do card
    */
   function removeLabel($label_id) {
      global $DB;

      return $DB->delete('glpi_plugin_scrumban_labels', [
         'id' => $label_id,
         'cards_id' => $this->getID()
      ]);
   }

   /**
    * Obter histórico de atividades
    */
   function getActivityHistory($limit = 10) {
      global $DB;

      $activities = [];

      // Comentários
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

      // Ordenar por data
      usort($activities, function($a, $b) {
         return strtotime($b['date']) - strtotime($a['date']);
      });

      return array_slice($activities, 0, $limit);
   }

   /**
    * Calcular tempo de ciclo
    */
   function getCycleTime() {
      // Implementar lógica para calcular tempo entre criação e finalização
      $created = strtotime($this->fields['date_creation']);
      
      if ($this->isCompleted()) {
         $completed = strtotime($this->fields['date_mod']);
         return $completed - $created;
      }
      
      return time() - $created;
   }

   /**
    * Verificar se o card está finalizado
    */
   function isCompleted() {
      global $DB;

      // Buscar se a coluna atual é de finalização
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
    * Exportar dados do card
    */
   function exportData() {
      $data = $this->fields;
      $data['labels'] = $this->getLabels();
      $data['activity'] = $this->getActivityHistory();
      return $data;
   }
}