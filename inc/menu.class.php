<?php

if (!defined('GLPI_ROOT')) {
   die('Sorry. You cannot access directly to this file');
}

class PluginScrumbanMenu extends CommonGLPI {

   static function getTypeName($nb = 0) {
      return __('Scrumban', 'scrumban');
   }

   static function getMenuName() {
      return __('Scrumban', 'scrumban');
   }

   static function getMenuContent() {
      $menu = [];

      if (Session::haveRight(PluginScrumbanProfile::RIGHT_VIEW, READ)) {
         $menu['title'] = self::getTypeName();
         $menu['page'] = PLUGIN_SCRUMBAN_FRONT_PATH . '/board.php';
         $menu['icon'] = 'ti ti-layout-kanban';
         $menu['links'] = [
            'search' => PLUGIN_SCRUMBAN_FRONT_PATH . '/board.php',
            'add'    => PLUGIN_SCRUMBAN_FRONT_PATH . '/card.form.php',
            'summary'=> PLUGIN_SCRUMBAN_FRONT_PATH . '/dashboard.php'
         ];
      }

      if (Session::haveRight(PluginScrumbanProfile::RIGHT_TEAM, READ)) {
         $menu['links']['config'] = PLUGIN_SCRUMBAN_FRONT_PATH . '/team.php';
      }

      return $menu;
   }
}
