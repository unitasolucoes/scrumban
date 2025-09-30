<?php

if (!defined('GLPI_ROOT')) {
   die("Desculpe. Você não pode acessar diretamente este arquivo");
}

class PluginScrumbanAttachment extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return _n('Anexo', 'Anexos', $nb, 'scrumban');
   }

   function prepareInputForAdd($input) {
      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      $input['users_id'] = $_SESSION['glpiID'];
      return $input;
   }

   /**
    * Obter anexos de um card
    */
   static function getAttachmentsByCard($card_id) {
      global $DB;

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_scrumban_attachments',
         'WHERE' => ['cards_id' => $card_id],
         'ORDER' => 'date_creation DESC'
      ]);

      $attachments = [];
      foreach ($iterator as $data) {
         $attachments[] = $data;
      }

      return $attachments;
   }

   /**
    * Fazer upload de arquivo
    */
   function uploadFile($card_id, $file_data) {
      // Implementar lógica de upload
      // Por segurança, apenas estrutura básica
      return false;
   }
}