<?php

// Ensure classes are loaded
if (!class_exists('PluginScrumbanMenu')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/menu.class.php');
}

if (!class_exists('PluginScrumbanBoard')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/board.class.php');
}

if (!class_exists('PluginScrumbanCard')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/card.class.php');
}

if (!class_exists('PluginScrumbanSprint')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/sprint.class.php');
}

if (!class_exists('PluginScrumbanColumn')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/column.class.php');
}

if (!class_exists('PluginScrumbanBacklog')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/backlog.class.php');
}

if (!class_exists('PluginScrumbanComment')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/comment.class.php');
}

if (!class_exists('PluginScrumbanLabel')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/label.class.php');
}

if (!class_exists('PluginScrumbanAttachment')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/attachment.class.php');
}

if (!class_exists('PluginScrumbanUtils')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/utils.class.php');
}

// Load team classes
if (!class_exists('PluginScrumbanTeam')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/team.class.php');
}

if (!class_exists('PluginScrumbanTeamMember')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/teammember.class.php');
}

if (!class_exists('PluginScrumbanTeamBoard')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/teamboard.class.php');
}

if (!function_exists('plugin_scrumban_render_board_selector')) {
   require_once(GLPI_ROOT . '/plugins/scrumban/inc/helpers.php');
}
