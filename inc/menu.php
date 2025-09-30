<?php

if (!defined('GLPI_ROOT')) {
   die("Desculpe. Você não pode acessar diretamente este arquivo");
}

class PluginScrumbanMenu extends CommonGLPI {
   
   static function getMenuName() {
      return __('Scrumban - Gestão Ágil', 'scrumban');
   }

   static function getMenuContent() {
      
      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page'] = Plugin::getWebDir('scrumban').'/front/board.php';
      $menu['icon'] = 'fas fa-project-diagram';

      $menu['options']['quadro'] = [
         'title' => __('Quadro Kanban', 'scrumban'),
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

      $menu['options']['cronograma'] = [
         'title' => __('Cronograma', 'scrumban'),
         'page'  => Plugin::getWebDir('scrumban').'/front/timeline.php',
         'icon'  => 'fas fa-calendar-alt'
      ];

      return $menu;
   }
}