<?php

if (!defined('GLPI_ROOT')) {
   die("Desculpe. Você não pode acessar diretamente este arquivo");
}

class PluginScrumbanBacklog extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return _n('Backlog', 'Backlogs', $nb, 'scrumban');
   }

   /**
    * Exibir página do backlog
    */
   static function showBacklogPage($board_id = 1) {
      global $DB;

      echo "<div class='backlog-container fade-in'>";
      
      // Controles do backlog
      echo "<div class='backlog-controls'>";
      echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
      echo "<h2>Backlog do Produto</h2>";
      echo "<button class='btn btn-primary' onclick='openCreateIssueModal()'>";
      echo "<i class='fas fa-plus me-2'></i>Nova Issue";
      echo "</button>";
      echo "</div>";
      
      // Filtros
      echo "<div class='backlog-filters'>";
      echo "<div class='search-box'>";
      echo "<input type='text' class='form-control' id='backlog-search' placeholder='Buscar por título ou descrição...'>";
      echo "<i class='fas fa-search'></i>";
      echo "</div>";
      
      // Filtro de prioridade
      echo "<select class='form-select' id='priority-filter' style='max-width: 200px;'>";
      echo "<option value=''>Todas as Prioridades</option>";
      echo "<option value='critico'>Crítico</option>";
      echo "<option value='maior'>Maior</option>";
      echo "<option value='normal'>Normal</option>";
      echo "<option value='menor'>Menor</option>";
      echo "<option value='trivial'>Trivial</option>";
      echo "</select>";
      
      // Filtro de tipo
      echo "<select class='form-select' id='type-filter' style='max-width: 200px;'>";
      echo "<option value=''>Todos os Tipos</option>";
      echo "<option value='story'>História</option>";
      echo "<option value='task'>Tarefa</option>";
      echo "<option value='bug'>Bug</option>";
      echo "<option value='epic'>Épico</option>";
      echo "</select>";
      echo "</div>";
      echo "</div>";

      // Itens do backlog
      echo "<div class='backlog-items' id='backlog-items-container'>";
      
      // Buscar cards do backlog (sem sprint)
      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'boards_id' => $board_id,
            'sprints_id' => 0,
            'is_active' => 1
         ],
         'ORDER' => 'position'
      ]);

      foreach ($iterator as $card_data) {
         self::renderBacklogItem($card_data);
      }
      
      echo "</div>";
      echo "</div>";
   }

   /**
    * Renderizar item do backlog
    */
   static function renderBacklogItem($card) {
      // Buscar labels
      global $DB;
      $labels_iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_labels',
         'WHERE' => ['cards_id' => $card['id']]
      ]);
      
      $labels = [];
      foreach ($labels_iterator as $label) {
         $labels[] = $label;
      }

      $type_class = 'issue-' . $card['type'];
      $priority_colors = [
         'trivial' => '#6c757d',
         'menor' => '#17a2b8', 
         'normal' => '#28a745',
         'maior' => '#ffc107',
         'critico' => '#dc3545'
      ];

      echo "<div class='backlog-item slide-up' data-card-id='" . $card['id'] . "' ";
      echo "data-priority='" . $card['priority'] . "' data-type='" . $card['type'] . "'>";
      
      echo "<div class='drag-handle'>";
      echo "<i class='fas fa-grip-vertical'></i>";
      echo "</div>";
      
      echo "<div class='item-content'>";
      echo "<div class='item-header'>";
      echo "<span class='issue-type $type_class'>" . strtoupper($card['type']) . "</span>";
      echo "<span class='card-id'>#" . str_pad($card['id'], 3, '0', STR_PAD_LEFT) . "</span>";
      echo "<div class='priority-indicator' style='background-color: " . $priority_colors[$card['priority']] . ";'></div>";
      echo "</div>";
      
      echo "<div class='item-title'>" . htmlspecialchars($card['title']) . "</div>";
      
      if (!empty($card['description'])) {
         echo "<div class='item-description'>" . htmlspecialchars(substr($card['description'], 0, 150)) . "...</div>";
      }
      
      echo "<div class='item-meta'>";
      
      if ($card['story_points'] > 0) {
         echo "<span class='story-points'>" . $card['story_points'] . "</span>";
      } else {
         echo "<span style='color: #dc3545; font-style: italic;'>Não estimado</span>";
      }
      
      if (!empty($card['assignee'])) {
         $initials = self::getInitials($card['assignee']);
         echo "<div class='assignee'>";
         echo "<div class='assignee-avatar'>$initials</div>";
         echo "<span>" . htmlspecialchars($card['assignee']) . "</span>";
         echo "</div>";
      } else {
         echo "<div class='assignee' style='color: #dc3545; font-style: italic;'>";
         echo "<div class='assignee-avatar'>--</div>";
         echo "<span>Não atribuído</span>";
         echo "</div>";
      }
      
      // Mostrar labels
      foreach ($labels as $label) {
         $badge_class = 'bg-' . self::getLabelBadgeClass($label['name']);
         echo "<span class='badge $badge_class'>" . htmlspecialchars($label['name']) . "</span>";
      }
      
      echo "</div>";
      echo "</div>";
      
      echo "<div class='item-actions'>";
      echo "<button class='btn btn-outline-primary btn-sm' onclick='openCardDetails(" . $card['id'] . ")'>";
      echo "<i class='fas fa-eye'></i>";
      echo "</button>";
      echo "<button class='btn btn-outline-secondary btn-sm' onclick='editCard(" . $card['id'] . ")'>";
      echo "<i class='fas fa-edit'></i>";
      echo "</button>";
      echo "</div>";
      
      echo "</div>";
   }

   /**
    * Obter iniciais de um nome
    */
   static function getInitials($name) {
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
    * Obter classe CSS para badges de labels
    */
   static function getLabelBadgeClass($label_name) {
      $label_lower = strtolower($label_name);
      
      $class_map = [
         'enhancement' => 'info',
         'bug' => 'danger',
         'feature' => 'success',
         'task' => 'warning',
         'high-priority' => 'warning',
         'low-priority' => 'secondary',
         'urgent' => 'danger',
         'api' => 'primary',
         'ui' => 'secondary',
         'database' => 'info',
         'documentation' => 'info',
         'security' => 'danger',
         'performance' => 'success',
         'ready' => 'success'
      ];
      
      foreach ($class_map as $keyword => $class) {
         if (strpos($label_lower, $keyword) !== false) {
            return $class;
         }
      }
      
      return 'primary';
   }

   /**
    * Reordenar itens do backlog
    */
   static function reorderBacklog($board_id, $card_orders) {
      global $DB;

      foreach ($card_orders as $order) {
         $card_id = $order['card_id'];
         $position = $order['position'];
         
         $DB->update('glpi_plugin_scrumban_cards', [
            'position' => $position
         ], [
            'id' => $card_id,
            'boards_id' => $board_id
         ]);
      }

      return true;
   }

   /**
    * Adicionar múltiplos cards à sprint
    */
   static function addCardsToSprint($card_ids, $sprint_id) {
      global $DB;

      foreach ($card_ids as $card_id) {
         $DB->update('glpi_plugin_scrumban_cards', [
            'sprints_id' => $sprint_id
         ], [
            'id' => $card_id
         ]);
      }

      return true;
   }

   /**
    * Obter estatísticas do backlog
    */
   static function getBacklogStats($board_id) {
      // Usar função utilitária simples
      return PluginScrumbanUtils::getBacklogBasicStats($board_id);
   }

   /**
    * Buscar sugestões para planejamento de sprint
    */
   static function getSuggestions($board_id, $sprint_capacity = 0) {
      global $DB;

      $suggestions = [
         'recommended_cards' => [],
         'total_points' => 0,
         'fit_percentage' => 0
      ];

      if ($sprint_capacity <= 0) {
         return $suggestions;
      }

      $priority_order = ['critico', 'maior', 'normal', 'menor', 'trivial'];
      $cards = [];
      
      foreach ($priority_order as $priority) {
         $iterator = $DB->request([
            'FROM' => 'glpi_plugin_scrumban_cards',
            'WHERE' => [
               'boards_id' => $board_id,
               'sprints_id' => 0,
               'is_active' => 1,
               'priority' => $priority,
               'story_points' => ['>', 0]
            ],
            'ORDER' => ['story_points', 'position']
         ]);

         foreach ($iterator as $card) {
            $cards[] = $card;
         }
      }

      $current_points = 0;
      $selected_cards = [];

      foreach ($cards as $card) {
         if ($current_points + $card['story_points'] <= $sprint_capacity) {
            $selected_cards[] = $card;
            $current_points += $card['story_points'];
         }
      }

      $suggestions['recommended_cards'] = $selected_cards;
      $suggestions['total_points'] = $current_points;
      $suggestions['fit_percentage'] = $sprint_capacity > 0 ? 
         round(($current_points / $sprint_capacity) * 100, 1) : 0;

      return $suggestions;
   }

   /**
    * Exportar backlog para CSV
    */
   static function exportToCsv($board_id) {
      global $DB;

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'boards_id' => $board_id,
            'sprints_id' => 0,
            'is_active' => 1
         ],
         'ORDER' => 'position'
      ]);

      $filename = 'backlog_' . date('Y-m-d') . '.csv';
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment; filename="' . $filename . '"');

      $output = fopen('php://output', 'w');
      
      fputcsv($output, [
         'ID', 'Título', 'Descrição', 'Tipo', 'Prioridade', 
         'Story Points', 'Responsável', 'Reporter', 'Posição'
      ]);

      foreach ($iterator as $card) {
         fputcsv($output, [
            $card['id'],
            $card['title'],
            $card['description'],
            $card['type'],
            $card['priority'],
            $card['story_points'],
            $card['assignee'],
            $card['reporter'],
            $card['position']
         ]);
      }

      fclose($output);
      exit;
   }

   /**
    * Importar backlog de CSV
    */
   static function importFromCsv($board_id, $file_path) {
      global $DB;

      if (!file_exists($file_path)) {
         return false;
      }

      $handle = fopen($file_path, 'r');
      if (!$handle) {
         return false;
      }

      fgetcsv($handle);
      
      $imported = 0;
      while (($data = fgetcsv($handle)) !== FALSE) {
         if (count($data) >= 8) {
            $column_result = $DB->request([
               'SELECT' => 'id',
               'FROM' => 'glpi_plugin_scrumban_columns',
               'WHERE' => [
                  'boards_id' => $board_id,
                  'is_active' => 1
               ],
               'ORDER' => 'position',
               'LIMIT' => 1
            ]);

            $column_id = 0;
            foreach ($column_result as $col) {
               $column_id = $col['id'];
            }

            if ($column_id > 0) {
               $DB->insert('glpi_plugin_scrumban_cards', [
                  'boards_id' => $board_id,
                  'columns_id' => $column_id,
                  'title' => $data[1],
                  'description' => $data[2],
                  'type' => $data[3],
                  'priority' => $data[4],
                  'story_points' => intval($data[5]),
                  'assignee' => $data[6],
                  'reporter' => $data[7],
                  'position' => $imported,
                  'date_creation' => $_SESSION['glpi_currenttime']
               ]);
               $imported++;
            }
         }
      }

      fclose($handle);
      return $imported;
   }
}