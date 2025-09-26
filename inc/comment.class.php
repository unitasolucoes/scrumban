<?php

if (!defined('GLPI_ROOT')) {
   die("Desculpe. Você não pode acessar diretamente este arquivo");
}

class PluginScrumbanComment extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return _n('Comentário', 'Comentários', $nb, 'scrumban');
   }

   function prepareInputForAdd($input) {
      $input['date_creation'] = $_SESSION['glpi_currenttime'];
      $input['users_id'] = $_SESSION['glpiID'];
      return $input;
   }

   /**
    * Obter comentários de um card
    */
   static function getCommentsByCard($card_id) {
      global $DB;

      $iterator = $DB->request([
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
         'WHERE' => ['cards_id' => $card_id],
         'ORDER' => 'date_creation DESC'
      ]);

      $comments = [];
      foreach ($iterator as $data) {
         $comments[] = $data;
      }

      return $comments;
   }
}