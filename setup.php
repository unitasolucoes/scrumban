<?php

define('PLUGIN_SCRUMBAN_VERSION', '1.0.0');

define('PLUGIN_SCRUMBAN_ROOT', __DIR__);

define('PLUGIN_SCRUMBAN_CLASS_PATH', PLUGIN_SCRUMBAN_ROOT . '/inc');

define('PLUGIN_SCRUMBAN_FRONT_PATH', '/plugins/scrumban/front');

function plugin_init_scrumban() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['scrumban'] = true;

   include_once __DIR__ . '/init.php';

   $PLUGIN_HOOKS['menu_toadd']['scrumban'] = [
      'tools' => 'PluginScrumbanMenu'
   ];

   $PLUGIN_HOOKS['post_init']['scrumban'] = 'plugin_scrumban_post_init';

   $PLUGIN_HOOKS['add_css']['scrumban'] = [
      'css/scrumban.css'
   ];

   $PLUGIN_HOOKS['add_javascript']['scrumban'] = [
      'js/scrumban.js'
   ];
}

function plugin_scrumban_post_init() {
   PluginScrumbanProfile::registerRights();
}

function plugin_version_scrumban() {
   return [
      'name'           => __('Scrumban', 'scrumban'),
      'version'        => PLUGIN_SCRUMBAN_VERSION,
      'author'         => 'OpenAI Assistant',
      'license'        => 'GPLv2+',
      'homepage'       => 'https://glpi-project.org',
      'requirements'   => [
         'glpi' => [
            'min' => '10.0.0'
         ],
         'php' => [
            'min' => '7.4'
         ]
      ]
   ];
}

function plugin_scrumban_check_prerequisites($verbose = false) {
   if (version_compare(GLPI_VERSION, '10.0.0', '<')) {
      if ($verbose) {
         echo __('Scrumban plugin requer GLPI 10.0.0 ou superior', 'scrumban');
      }
      return false;
   }

   return true;
}

function plugin_scrumban_check_config($verbose = false) {
   return true;
}
