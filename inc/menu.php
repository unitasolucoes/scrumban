<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You cannot access directly to this file");
}

class PluginScrumbanMenu extends CommonGLPI {
   
   static function getMenuName() {
      return __('Scrumban - Agile Management', 'scrumban');
   }

   static function getMenuContent() {
      
      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page'] = Plugin::getWebDir('scrumban').'/front/board.php';
      $menu['icon'] = 'fas fa-project-diagram';

      $menu['options']['board'] = [
         'title' => __('Kanban Board', 'scrumban'),
         'page'  => Plugin::getWebDir('scrumban').'/front/board.php',
         'icon'  => 'fas fa-columns'
      ];

      $menu['options']['sprints'] = [
         'title' => __('Sprints', 'scrumban'),
         'page'  => Plugin::getWebDir('scrumban').'/front/sprint.php',
         'icon'  => 'fas fa-sync-alt'
      ];

      $menu['options']['backlog'] = [
         'title' => __('Backlog', 'scrumban'),
         'page'  => Plugin::getWebDir('scrumban').'/front/backlog.php',
         'icon'  => 'fas fa-list-ul'
      ];

      $menu['options']['timeline'] = [
         'title' => __('Timeline', 'scrumban'),
         'page'  => Plugin::getWebDir('scrumban').'/front/timeline.php',
         'icon'  => 'fas fa-calendar-alt'
      ];

      // Add teams menu - check if classes exist and user has access
      if (class_exists('PluginScrumbanTeam') && isset($_SESSION['glpiID'])) {
         $user_teams = PluginScrumbanTeam::getTeamsForUser($_SESSION['glpiID']);
         $can_manage = PluginScrumbanTeam::canUserManageTeams($_SESSION['glpiID']);
         
         if ($can_manage || !empty($user_teams)) {
            $menu['options']['teams'] = [
               'title' => __('Teams', 'scrumban'),
               'page'  => Plugin::getWebDir('scrumban').'/front/team.php',
               'icon'  => 'fas fa-users'
            ];
         }
      }

      return $menu;
   }
}