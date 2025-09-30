<?php

if (!defined('GLPI_ROOT')) {
   die("Desculpe. Você não pode acessar diretamente este arquivo");
}

class PluginScrumbanUtils {

   /**
    * Contar registros simples
    */
   static function countRecords($table, $where = []) {
      global $DB;
      
      $iterator = $DB->request([
         'FROM' => $table,
         'WHERE' => $where
      ]);
      
      $count = 0;
      foreach ($iterator as $row) {
         $count++;
      }
      
      return $count;
   }

   /**
    * Somar campo numérico
    */
   static function sumField($table, $field, $where = []) {
      global $DB;
      
      $iterator = $DB->request([
         'FROM' => $table,
         'WHERE' => $where
      ]);
      
      $sum = 0;
      foreach ($iterator as $row) {
         $sum += intval($row[$field]);
      }
      
      return $sum;
   }

   /**
    * Obter estatísticas básicas de um quadro
    */
   static function getBoardBasicStats($board_id) {
      $stats = [];
      
      // Total de cards
      $stats['total_cards'] = self::countRecords('glpi_plugin_scrumban_cards', [
         'boards_id' => $board_id,
         'is_active' => 1
      ]);
      
      // Total de story points
      $stats['total_story_points'] = self::sumField('glpi_plugin_scrumban_cards', 'story_points', [
         'boards_id' => $board_id,
         'is_active' => 1
      ]);
      
      // Sprints ativas
      $stats['active_sprints'] = self::countRecords('glpi_plugin_scrumban_sprints', [
         'boards_id' => $board_id,
         'status' => 'ativo',
         'is_active' => 1
      ]);
      
      return $stats;
   }

   /**
    * Obter estatísticas de coluna
    */
   static function getColumnStats($column_id) {
      return self::countRecords('glpi_plugin_scrumban_cards', [
         'columns_id' => $column_id,
         'is_active' => 1
      ]);
   }

   /**
    * Verificar se coluna é de finalização
    */
   static function isCompletedColumn($column_name) {
      $name = strtolower($column_name);
      return (strpos($name, 'finaliz') !== false || 
              strpos($name, 'done') !== false ||
              strpos($name, 'concluí') !== false ||
              strpos($name, 'pronto') !== false);
   }

   /**
    * Obter métricas básicas de sprint
    */
   static function getSprintBasicMetrics($sprint_id) {
      global $DB;
      
      $metrics = [
         'total_cards' => 0,
         'completed_cards' => 0,
         'total_points' => 0,
         'completed_points' => 0,
         'progress_percentage' => 0
      ];
      
      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'sprints_id' => $sprint_id,
            'is_active' => 1
         ]
      ]);
      
      foreach ($iterator as $card) {
         $metrics['total_cards']++;
         $metrics['total_points'] += intval($card['story_points']);
         
         // Verificar se está em coluna de finalização
         $column_iterator = $DB->request([
            'FROM' => 'glpi_plugin_scrumban_columns',
            'WHERE' => ['id' => $card['columns_id']]
         ]);
         
         foreach ($column_iterator as $column) {
            if (self::isCompletedColumn($column['name'])) {
               $metrics['completed_cards']++;
               $metrics['completed_points'] += intval($card['story_points']);
            }
         }
      }
      
      if ($metrics['total_points'] > 0) {
         $metrics['progress_percentage'] = round(($metrics['completed_points'] / $metrics['total_points']) * 100, 1);
      }
      
      return $metrics;
   }

   /**
    * Obter estatísticas do backlog
    */
   static function getBacklogBasicStats($board_id) {
      global $DB;
      
      $stats = [
         'total_items' => 0,
         'story_points' => 0,
         'unestimated' => 0
      ];
      
      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_cards',
         'WHERE' => [
            'boards_id' => $board_id,
            'sprints_id' => 0,
            'is_active' => 1
         ]
      ]);
      
      foreach ($iterator as $card) {
         $stats['total_items']++;
         $points = intval($card['story_points']);
         
         if ($points > 0) {
            $stats['story_points'] += $points;
         } else {
            $stats['unestimated']++;
         }
      }
      
      return $stats;
   }
}