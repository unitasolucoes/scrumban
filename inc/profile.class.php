<?php

if (!defined('GLPI_ROOT')) {
   die('Sorry. You cannot access directly to this file');
}

class PluginScrumbanProfile extends CommonDBTM {

   const RIGHT_VIEW   = 'plugin_scrumban_view';
   const RIGHT_EDIT   = 'plugin_scrumban_edit';
   const RIGHT_MANAGE = 'plugin_scrumban_manage';
   const RIGHT_TEAM   = 'plugin_scrumban_team';

   static function registerRights() {
      ProfileRight::addProfileRights([self::RIGHT_VIEW, self::RIGHT_EDIT, self::RIGHT_MANAGE, self::RIGHT_TEAM]);
   }

   static function getRightsGeneral() {
      return [
         self::RIGHT_VIEW   => __('Visualizar quadros Scrumban', 'scrumban'),
         self::RIGHT_EDIT   => __('Editar cards Scrumban', 'scrumban'),
         self::RIGHT_MANAGE => __('Gerenciar quadros Scrumban', 'scrumban'),
         self::RIGHT_TEAM   => __('Administrar equipes Scrumban', 'scrumban')
      ];
   }
}
