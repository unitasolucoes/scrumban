<?php

if (!defined('GLPI_ROOT')) {
   die('Sorry. You cannot access directly to this file');
}

class PluginScrumbanSprint extends CommonDBTM {

   public $dohistory = true;
   public static $rightname = PluginScrumbanProfile::RIGHT_MANAGE;

   static function getTypeName($nb = 0) {
      return _n('Sprint', 'Sprints', $nb, 'scrumban');
   }

   function prepareInputForAdd($input) {
      $input['entities_id'] = $_SESSION['glpiactive_entity'];
      $input['is_recursive'] = 1;
      $input['date_creation'] = date('Y-m-d H:i:s');
      $input['date_mod'] = $input['date_creation'];
      return $input;
   }

   function prepareInputForUpdate($input) {
      $input['date_mod'] = date('Y-m-d H:i:s');
      return $input;
   }

   static function getActiveSprintForBoard($board_id) {
      $sprint = new self();
      $items = $sprint->find([
         'boards_id' => $board_id,
         'is_active' => 1
      ], ['date_start DESC'], 1);

      if ($items) {
         $data = reset($items);
         $sprint->fields = $data;
         return $sprint;
      }

      return null;
   }
}
