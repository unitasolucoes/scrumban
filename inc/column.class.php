<?php

if (!defined('GLPI_ROOT')) {
   die("Desculpe. Você não pode acessar diretamente este arquivo");
}

class PluginScrumbanColumn extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return _n('Coluna', 'Colunas', $nb, 'scrumban');
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      return $ong;
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      // Buscar quadros disponíveis
      $boards = PluginScrumbanBoard::getActiveBoards();

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Quadro', 'scrumban') . " *</td>";
      echo "<td>";
      $board_options = [];
      foreach ($boards as $board) {
         $board_options[$board['id']] = $board['name'];
      }
      Dropdown::showFromArray('boards_id', $board_options, [
         'value' => $this->fields['boards_id']
      ]);
      echo "</td>";
      echo "<td>" . __('Posição', 'scrumban') . "</td>";
      echo "<td>";
      echo "<input type='number' name='position' value='" . $this->fields['position'] . "' min='0' max='99'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Nome', 'scrumban') . " *</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", ['size' => 30]);
      echo "</td>";
      echo "<td>" . __('Limite WIP', 'scrumban') . "</td>";
      echo "<td>";
      echo "<input type='number' name='wip_limit' value='" . $this->fields['wip_limit'] . "' min='0' max='99'>";
      echo "<small class='form-text text-muted'>0 = sem limite</small>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Cor', 'scrumban') . "</td>";
      echo "<td>";
      echo "<input type='color' name='color' value='" . ($this->fields['color'] ?: '#6c757d') . "'>";
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

      // Definir posição se não informada
      if (!isset($input['position']) && isset($input['boards_id'])) {
         $result = $DB->request([
            'SELECT' => 'MAX(position) as max_pos',
            'FROM' => $this->getTable(),
            'WHERE' => ['boards_id' => $input['boards_id']]
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
    * Obter colunas de um quadro
    */
   static function getColumnsByBoard($board_id) {
      global $DB;

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_columns',
         'WHERE' => [
            'boards_id' => $board_id,
            'is_active' => 1
         ],
         'ORDER' => 'position'
      ]);

      $columns = [];
      foreach ($iterator as $data) {
         $columns[] = $data;
      }

      return $columns;
   }

   /**
    * Verificar limite WIP
    */
   function checkWipLimit() {
      global $DB;

      if ($this->fields['wip_limit'] <= 0) {
         return true; // Sem limite
      }

      $result = $DB->request([
         'SELECT' => 'COUNT(*) as count',
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'columns_id' => $this->getID(),
            'is_active' => 1
         ]
      ]);

      foreach ($result as $row) {
         return $row['count'] < $this->fields['wip_limit'];
      }

      return false;
   }

   /**
    * Obter contagem atual de cards
    */
   function getCardCount() {
      global $DB;

      $result = $DB->request([
         'SELECT' => 'COUNT(*) as count',
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'columns_id' => $this->getID(),
            'is_active' => 1
         ]
      ]);

      foreach ($result as $row) {
         return $row['count'];
      }

      return 0;
   }

   /**
    * Mover coluna para nova posição
    */
   function moveToPosition($new_position) {
      global $DB;

      $old_position = $this->fields['position'];
      $board_id = $this->fields['boards_id'];

      // Ajustar posições das outras colunas
      if ($new_position > $old_position) {
         // Movendo para frente
         $DB->update(
            $this->getTable(),
            ['position' => new QueryExpression($DB->quoteName('position') . ' - 1')],
            [
               'boards_id' => $board_id,
               'position' => ['>', $old_position],
               'position' => ['<=', $new_position],
               'id' => ['!=', $this->getID()]
            ]
         );
      } else {
         // Movendo para trás
         $DB->update(
            $this->getTable(),
            ['position' => new QueryExpression($DB->quoteName('position') . ' + 1')],
            [
               'boards_id' => $board_id,
               'position' => ['>=', $new_position],
               'position' => ['<', $old_position],
               'id' => ['!=', $this->getID()]
            ]
         );
      }

      // Atualizar posição desta coluna
      return $this->update([
         'id' => $this->getID(),
         'position' => $new_position
      ]);
   }

   /**
    * Obter cards da coluna
    */
   function getCards() {
      return PluginScrumbanCard::getCardsByColumn($this->getID());
   }

   /**
    * Verificar se é coluna de finalização
    */
   function isCompletedColumn() {
      $name = strtolower($this->fields['name']);
      return (stripos($name, 'finaliz') !== false || 
              stripos($name, 'done') !== false ||
              stripos($name, 'concluí') !== false ||
              stripos($name, 'pronto') !== false);
   }

   /**
    * Obter estatísticas da coluna
    */
   function getColumnStats() {
      global $DB;

      $stats = [
         'total_cards' => 0,
         'story_points' => 0,
         'avg_story_points' => 0,
         'types' => [],
         'priorities' => []
      ];

      // Cards e story points
      $cards_result = $DB->request([
         'SELECT' => [
            'COUNT(*) as total',
            'SUM(story_points) as total_sp',
            'AVG(story_points) as avg_sp',
            'type',
            'priority'
         ],
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'columns_id' => $this->getID(),
            'is_active' => 1
         ],
         'GROUP' => ['type', 'priority']
      ]);

      $total_cards = 0;
      $total_sp = 0;

      foreach ($cards_result as $row) {
         $total_cards += $row['total'];
         $total_sp += $row['total_sp'] ?: 0;
         
         if (!isset($stats['types'][$row['type']])) {
            $stats['types'][$row['type']] = 0;
         }
         $stats['types'][$row['type']] += $row['total'];
         
         if (!isset($stats['priorities'][$row['priority']])) {
            $stats['priorities'][$row['priority']] = 0;
         }
         $stats['priorities'][$row['priority']] += $row['total'];
      }

      $stats['total_cards'] = $total_cards;
      $stats['story_points'] = $total_sp;
      $stats['avg_story_points'] = $total_cards > 0 ? round($total_sp / $total_cards, 1) : 0;

      return $stats;
   }

   /**
    * Deletar coluna e reposicionar outras
    */
   function post_deleteFromDB() {
      global $DB;

      // Mover cards para primeira coluna do quadro
      $first_column = $DB->request([
         'SELECT' => 'id',
         'FROM' => $this->getTable(),
         'WHERE' => [
            'boards_id' => $this->fields['boards_id'],
            'is_active' => 1,
            'id' => ['!=', $this->getID()]
         ],
         'ORDER' => 'position',
         'LIMIT' => 1
      ]);

      foreach ($first_column as $column) {
         $DB->update('glpi_plugin_scrumban_cards', [
            'columns_id' => $column['id']
         ], [
            'columns_id' => $this->getID()
         ]);
      }

      // Reposicionar colunas
      $DB->update(
         $this->getTable(),
         ['position' => new QueryExpression($DB->quoteName('position') . ' - 1')],
         [
            'boards_id' => $this->fields['boards_id'],
            'position' => ['>', $this->fields['position']]
         ]
      );

      return true;
   }
}