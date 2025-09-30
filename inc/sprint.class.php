<?php

if (!defined('GLPI_ROOT')) {
   die("Desculpe. Você não pode acessar diretamente este arquivo");
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
      echo "<td>" . __('Status', 'scrumban') . " *</td>";
      echo "<td>";
      $status_options = [
         'planejado' => __('Planejado', 'scrumban'),
         'ativo' => __('Ativo', 'scrumban'),
         'concluido' => __('Concluído', 'scrumban')
      ];
      Dropdown::showFromArray('status', $status_options, [
         'value' => $this->fields['status']
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Nome', 'scrumban') . " *</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", ['size' => 50]);
      echo "</td>";
      echo "<td>" . __('Capacidade (Story Points)', 'scrumban') . "</td>";
      echo "<td>";
      echo "<input type='number' name='capacity' value='" . $this->fields['capacity'] . "' min='0' max='999'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Data de Início', 'scrumban') . "</td>";
      echo "<td>";
      Html::showDateField("start_date", ['value' => $this->fields["start_date"]]);
      echo "</td>";
      echo "<td>" . __('Data de Fim', 'scrumban') . "</td>";
      echo "<td>";
      Html::showDateField("end_date", ['value' => $this->fields["end_date"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Objetivo', 'scrumban') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='objective' rows='4' cols='80'>" . $this->fields["objective"] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Velocidade Atual', 'scrumban') . "</td>";
      echo "<td>";
      echo "<input type='number' name='velocity' value='" . $this->fields['velocity'] . "' min='0' max='999' readonly>";
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
      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   function prepareInputForUpdate($input) {
      $input['date_mod'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   /**
    * Obter todas as sprints ativas
    */
   static function getActiveSprints($board_id = null) {
      global $DB;

      $criteria = [
         'is_active' => 1,
         'status' => ['ativo', 'planejado']
      ];

      if ($board_id) {
         $criteria['boards_id'] = $board_id;
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
    * Calcular métricas da sprint
    */
   function calculateMetrics() {
      // Usar função utilitária simples
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

      // Calcular velocidade
      if ($this->fields['status'] == 'concluido') {
         $metrics['velocity'] = $metrics['completed_points'];
      } else {
         $metrics['velocity'] = intval($this->fields['velocity']);
      }

      return $metrics;
   }

   /**
    * Gerar dados do burndown chart
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
      
      // Ideal burndown (linha reta)
      $days_total = $start_date->diff($end_date)->days;
      $points_per_day = $days_total > 0 ? $total_points / $days_total : 0;

      $day_count = 0;
      while ($current_date <= $end_date) {
         $date_str = $current_date->format('Y-m-d');
         
         // Pontos ideais
         $ideal_remaining = $total_points - ($day_count * $points_per_day);
         
         // Pontos reais (simulado - em implementação real buscar histórico)
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
    * Obter pontos reais restantes em uma data específica
    */
   private function getActualRemainingPoints($date) {
      // Simular dados reais - em implementação real buscar histórico
      $metrics = $this->calculateMetrics();
      return $metrics['remaining_points'];
   }

   /**
    * Iniciar sprint
    */
   function startSprint() {
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
    * Finalizar sprint
    */
   function finishSprint() {
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
    * Adicionar card à sprint
    */
   function addCard($card_id) {
      $card = new PluginScrumbanCard();
      if ($card->getFromDB($card_id)) {
         return $card->assignToSprint($this->getID());
      }
      return false;
   }

   /**
    * Remover card da sprint
    */
   function removeCard($card_id) {
      $card = new PluginScrumbanCard();
      if ($card->getFromDB($card_id)) {
         return $card->assignToSprint(0);
      }
      return false;
   }

   /**
    * Obter cards da sprint
    */
   function getCards() {
      return PluginScrumbanCard::getCardsBySprint($this->getID());
   }

   /**
    * Calcular duração da sprint em dias
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
    * Verificar se sprint está atrasada
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
    * Obter dias restantes
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
    * Renderizar card da sprint
    */
   function showSprintCard() {
      $metrics = $this->calculateMetrics();
      $status_class = 'status-' . $this->fields['status'];
      
      echo "<div class='sprint-card slide-up'>";
      echo "<div class='sprint-card-header'>";
      echo "<h3 class='sprint-title'>" . htmlspecialchars($this->fields['name']) . "</h3>";
      
      $status_labels = [
         'planejado' => __('Planejado', 'scrumban'),
         'ativo' => __('Ativo', 'scrumban'),
         'concluido' => __('Concluído', 'scrumban')
      ];
      
      echo "<div class='d-flex align-items-center gap-2'>";
      echo "<span class='sprint-status $status_class'>" . $status_labels[$this->fields['status']] . "</span>";
      echo "<button class='btn btn-outline-primary btn-sm' onclick='openEditSprintModal(" . $this->getID() . ")'>";
      echo "<i class='fas fa-edit'></i> Editar";
      echo "</button>";
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
      
      // Barra de progresso
      echo "<div class='sprint-progress'>";
      echo "<div class='progress-label'>";
      echo "<span>Progresso</span>";
      echo "<span>" . $metrics['progress_percentage'] . "%</span>";
      echo "</div>";
      echo "<div class='progress-bar-custom'>";
      echo "<div class='progress-fill' style='width: " . $metrics['progress_percentage'] . "%'></div>";
      echo "</div>";
      echo "</div>";

      // Métricas
      echo "<div class='sprint-metrics'>";
      echo "<div class='metric'>";
      echo "<div class='metric-value'>" . $metrics['planned_points'] . "</div>";
      echo "<div class='metric-label'>Story Points Planejados</div>";
      echo "</div>";
      echo "<div class='metric'>";
      echo "<div class='metric-value'>" . $metrics['completed_points'] . "</div>";
      echo "<div class='metric-label'>Story Points Concluídos</div>";
      echo "</div>";
      echo "<div class='metric'>";
      echo "<div class='metric-value'>" . ($metrics['total_cards'] - $metrics['completed_cards']) . "</div>";
      echo "<div class='metric-label'>Issues Restantes</div>";
      echo "</div>";
      echo "</div>";
      
      echo "</div>";
   }

   /**
    * Mostrar página de sprints
    */
   static function showSprintsPage($board_id = 1) {
      global $DB;

      echo "<div class='sprint-container fade-in'>";
      
      // Header
      echo "<div class='sprint-header'>";
      echo "<div>";
      echo "<h2>Gestão de Sprints</h2>";
      echo "<p class='text-muted mb-0'>Acompanhe o progresso das suas sprints</p>";
      echo "</div>";
      echo "<button class='btn btn-primary' onclick='openCreateSprintModal()'>";
      echo "<i class='fas fa-plus me-2'></i>Nova Sprint";
      echo "</button>";
      echo "</div>";

      // Grid de sprints
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
    * Exportar dados da sprint
    */
   function exportData() {
      $data = $this->fields;
      $data['metrics'] = $this->calculateMetrics();
      $data['burndown'] = $this->getBurndownData();
      $data['cards'] = $this->getCards();
      return $data;
   }
}