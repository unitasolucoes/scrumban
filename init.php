<?php

if (!defined('GLPI_ROOT')) {
   die('Sorry. You cannot access directly to this file');
}

require_once __DIR__ . '/setup.php';

foreach ([
   'utils',
   'profile',
   'menu',
   'team',
   'teammember',
   'teamboard',
   'board',
   'card',
   'sprint'
] as $file) {
   $path = __DIR__ . '/inc/' . $file . '.class.php';
   if (file_exists($path)) {
      require_once $path;
   }
}

function plugin_scrumban_registerClasses() {
   Plugin::registerClass('PluginScrumbanTeam');
   Plugin::registerClass('PluginScrumbanTeamMember');
   Plugin::registerClass('PluginScrumbanTeamBoard');
   Plugin::registerClass('PluginScrumbanBoard');
   Plugin::registerClass('PluginScrumbanCard');
   Plugin::registerClass('PluginScrumbanSprint');
}

plugin_scrumban_registerClasses();

function plugin_scrumban_haveRight($right) {
   return Session::haveRight($right, READ);
}
