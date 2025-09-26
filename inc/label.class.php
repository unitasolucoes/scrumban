<?php

if (!defined('GLPI_ROOT')) {
   die("Desculpe. Você não pode acessar diretamente este arquivo");
}

class PluginScrumbanLabel extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return _n('Label', 'Labels', $nb, 'scrumban');
   }

   function prepareInputForAdd($input) {
      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      return $input;
   }

   /**
    * Obter labels de um card
    */
   static function getLabelsByCard($card_id) {
      global $DB;

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_labels',
         'WHERE' => ['cards_id' => $card_id],
         'ORDER' => 'name'
      ]);

      $labels = [];
      foreach ($iterator as $data) {
         $labels[] = $data;
      }

      return $labels;
   }

   /**
    * Adicionar label a um card
    */
   static function addLabelToCard($card_id, $name, $color = '#007bff') {
      global $DB;

      // Verificar se já existe
      $existing = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_labels',
         'WHERE' => [
            'cards_id' => $card_id,
            'name' => $name
         ]
      ]);

      if (count($existing) == 0) {
         $label = new self();
         return $label->add([
            'cards_id' => $card_id,
            'name' => $name,
            'color' => $color
         ]);
      }

      return false;
   }

   /**
    * Remover label de um card
    */
   static function removeLabelFromCard($card_id, $label_name) {
      global $DB;

      return $DB->delete('glpi_plugin_scrumban_labels', [
         'cards_id' => $card_id,
         'name' => $label_name
      ]);
   }
}