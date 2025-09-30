<?php

if (!defined('GLPI_ROOT')) {
   die("Desculpe. Você não pode acessar diretamente este arquivo");
}

class PluginScrumbanBoard extends CommonDBTM {
   
   static function getTypeName($nb = 0) {
      return _n('Quadro', 'Quadros', $nb, 'scrumban');
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
      echo "<td>" . __('Nome', 'scrumban') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>" . __('Ativo', 'scrumban') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Descrição', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='description' rows='4' cols='80'>" . $this->fields["description"] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }

   function prepareInputForAdd($input) {
      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   function prepareInputForUpdate($input) {
      $input['date_mod'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   /**
    * Obtém todos os quadros ativos
    */
   static function getActiveBoards() {
      global $DB;

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_boards',
         'WHERE' => ['is_active' => 1],
         'ORDER' => 'name'
      ]);

      $boards = [];
      foreach ($iterator as $data) {
         $boards[] = $data;
      }

      return $boards;
   }

   /**
    * Obtém estatísticas do quadro
    */
   function getBoardStats() {
      // Usar funções utilitárias simples
      $basic_stats = PluginScrumbanUtils::getBoardBasicStats($this->getID());
      
      $stats = [
         'total_issues' => $basic_stats['total_cards'],
         'total_story_points' => $basic_stats['total_story_points'],
         'active_sprints' => $basic_stats['active_sprints'],
         'columns' => []
      ];

      // Stats por coluna usando queries simples
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
    * Obtém dados do quadro Kanban
    */
   function getKanbanData() {
      global $DB;

      $kanban_data = [
         'board' => $this->fields,
         'columns' => [],
         'cards' => []
      ];

      // Buscar colunas
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

      // Buscar cards
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
         
         // Buscar labels do card
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
    * Renderiza o quadro Kanban em HTML
    */
   function showKanbanBoard() {
      $kanban_data = $this->getKanbanData();
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
      echo "<button class='btn btn-light btn-sm'><i class='fas fa-cog'></i> Configurações</button>";
      echo "<button class='btn btn-outline-light btn-sm'><i class='fas fa-user'></i> " . $_SESSION['glpiname'] . "</button>";
      echo "</div>";
      echo "</div>";
      echo "</div>";
      echo "</div>";

      // Tabs de navegação
      $this->showNavigationTabs('kanban');

      // Container do Kanban
      echo "<div class='kanban-container fade-in'>";
      
      // Board header com estatísticas
      echo "<div class='board-header'>";
      echo "<div class='board-info'>";
      echo "<h2>" . $this->fields['name'] . "</h2>";
      echo "<p class='text-muted mb-0'>" . $this->fields['description'] . "</p>";
      echo "</div>";
      echo "<div class='board-stats'>";
      echo "<div class='stat-item'>";
      echo "<div class='stat-number'>" . $stats['total_issues'] . "</div>";
      echo "<div class='stat-label'>Issues Totais</div>";
      echo "</div>";
      echo "<div class='stat-item'>";
      echo "<div class='stat-number'>" . $stats['total_story_points'] . "</div>";
      echo "<div class='stat-label'>Story Points</div>";
      echo "</div>";
      echo "<div class='stat-item'>";
      echo "<div class='stat-number'>" . $stats['active_sprints'] . "</div>";
      echo "<div class='stat-label'>Sprints Ativos</div>";
      echo "</div>";
      echo "</div>";
      echo "<div>";
      echo "<button class='btn btn-primary' onclick='openCreateIssueModal()'>";
      echo "<i class='fas fa-plus me-2'></i>Criar Issue";
      echo "</button>";
      echo "</div>";
      echo "</div>";

      // Board Kanban
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
    * Renderiza um card individual
    */
   function renderCard($card) {
      $priority_class = 'priority-' . $card['priority'];
      $type_class = 'issue-' . $card['type'];
      
      echo "<div class='kanban-card $priority_class $type_class' data-card-id='" . $card['id'] . "'>";
      
      // Header do card
      echo "<div class='card-header'>";
      echo "<span class='issue-type $type_class'>" . strtoupper($card['type']) . "</span>";
      echo "<span class='card-id'>#" . str_pad($card['id'], 3, '0', STR_PAD_LEFT) . "</span>";
      echo "</div>";
      
      // Título e descrição
      echo "<div class='card-title'>" . htmlspecialchars($card['title']) . "</div>";
      if (!empty($card['description'])) {
         echo "<div class='card-description'>" . htmlspecialchars(substr($card['description'], 0, 100)) . "...</div>";
      }
      
      // Footer do card
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
      
      // Indicador de prioridade
      echo "<div class='priority-indicator'></div>";
      
      echo "</div>";
   }

   /**
    * Obtém iniciais de um nome
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
    * Mostra as abas de navegação
    */
   function showNavigationTabs($active_tab = 'kanban') {
      echo "<nav class='agilepm-nav'>";
      echo "<div class='container-fluid'>";
      echo "<ul class='nav nav-tabs' role='tablist'>";
      
      $tabs = [
         'kanban' => ['Quadro Kanban', 'fas fa-columns', 'board.php'],
         'sprints' => ['Sprints', 'fas fa-sync-alt', 'sprint.php'],
         'backlog' => ['Backlog', 'fas fa-list-ul', 'backlog.php'],
         'timeline' => ['Cronograma', 'fas fa-calendar-alt', 'timeline.php']
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
}