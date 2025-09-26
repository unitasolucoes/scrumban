<?php

if (!defined('GLPI_ROOT')) {
   die('Sorry. You cannot access directly to this file');
}

class PluginScrumbanCard extends CommonDBTM {

   public $dohistory = true;
   public static $rightname = PluginScrumbanProfile::RIGHT_EDIT;

   static function getTypeName($nb = 0) {
      return _n('Card', 'Cards', $nb, 'scrumban');
   }

   function canViewItem() {
      return Session::haveRight(PluginScrumbanProfile::RIGHT_VIEW, READ);
   }

   function canUpdateItem() {
      return Session::haveRight(PluginScrumbanProfile::RIGHT_EDIT, UPDATE);
   }

   function canDeleteItem() {
      return Session::haveRight(PluginScrumbanProfile::RIGHT_EDIT, DELETE);
   }

   function canCreateItem() {
      return Session::haveRight(PluginScrumbanProfile::RIGHT_EDIT, CREATE);
   }

   function prepareInputForAdd($input) {
      $input['users_id_created'] = Session::getLoginUserID(false);
      $input['entities_id'] = $_SESSION['glpiactive_entity'];
      $input['is_recursive'] = 1;
      $input['date_creation'] = date('Y-m-d H:i:s');
      $input['date_mod'] = $input['date_creation'];
      $input['labels'] = PluginScrumbanUtils::sanitizeLabels($input['labels'] ?? '');
      return $input;
   }

   function prepareInputForUpdate($input) {
      $input['date_mod'] = date('Y-m-d H:i:s');
      $input['labels'] = PluginScrumbanUtils::sanitizeLabels($input['labels'] ?? '');
      return $input;
   }

   function post_addItem() {
      PluginScrumbanUtils::recordHistory($this, sprintf(__('Card criado por %s', 'scrumban'), PluginScrumbanUtils::formatUserName($this->fields['users_id_created'])));
   }

   function post_updateItem($history = true) {
      PluginScrumbanUtils::recordHistory($this, __('Card atualizado', 'scrumban'));
   }

   function post_deleteItem() {
      PluginScrumbanUtils::recordHistory($this, __('Card removido', 'scrumban'));
   }

   static function getCardsForBoard($board_id, array $filters = []) {
      global $DB;

      $criteria = [
         'glpi_plugin_scrumban_cards.boards_id' => $board_id
      ];

      if (!empty($filters['status'])) {
         $criteria['glpi_plugin_scrumban_cards.status'] = $filters['status'];
      }
      if (!empty($filters['assignee'])) {
         $criteria['glpi_plugin_scrumban_cards.users_id_assigned'] = $filters['assignee'];
      }
      if (!empty($filters['type'])) {
         $criteria['glpi_plugin_scrumban_cards.type'] = $filters['type'];
      }
      if (!empty($filters['priority'])) {
         $criteria['glpi_plugin_scrumban_cards.priority'] = $filters['priority'];
      }

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_plugin_scrumban_cards.*',
            'glpi_users.firstname AS assignee_firstname',
            'glpi_users.realname AS assignee_realname',
            'glpi_users.name AS assignee_login'
         ],
         'FROM' => 'glpi_plugin_scrumban_cards',
         'LEFT JOIN' => [
            'glpi_users' => [
               'ON' => [
                  'glpi_users' => 'id',
                  'glpi_plugin_scrumban_cards' => 'users_id_assigned'
               ]
            ]
         ],
         'WHERE' => $criteria,
         'ORDER' => 'date_creation DESC'
      ]);

      $cards = [];
      foreach ($iterator as $row) {
         $row['labels'] = PluginScrumbanUtils::parseLabels($row['labels']);
         $row['assignee_name'] = $row['assignee_firstname'] || $row['assignee_realname']
            ? trim(($row['assignee_firstname'] ?? '') . ' ' . ($row['assignee_realname'] ?? ''))
            : ($row['assignee_login'] ?: '');
         $cards[] = $row;
      }

      return $cards;
   }

   static function renderCardModal($card_id = 0) {
      $card = new self();
      if ($card_id) {
         $card->getFromDB($card_id);
      }

      include __DIR__ . '/../front/card_modal.php';
   }

   function getHistory() {
      global $DB;

      if (!$this->getID()) {
         return [];
      }

      $iterator = $DB->request([
         'FROM' => 'glpi_logs',
         'WHERE' => [
            'itemtype' => $this->getType(),
            'items_id' => $this->getID(),
            'linked_action' => 'plugin-scrumban'
         ],
         'ORDER' => ['date_mod DESC']
      ]);

      $history = [];
      foreach ($iterator as $row) {
         $history[] = [
            'content' => $row['changes'],
            'date_mod' => $row['date_mod'],
            'user_name' => $row['user_name']
         ];
      }

      return $history;
   }

   function addComment($content) {
      if (!$this->getID()) {
         return false;
      }

      $user_id = Session::getLoginUserID(false);
      if (!$user_id) {
         return false;
      }

      $message = sprintf(__('%1$s comentou: %2$s', 'scrumban'), PluginScrumbanUtils::formatUserName($user_id), $content);
      PluginScrumbanUtils::recordHistory($this, $message);
      return true;
   }
}
