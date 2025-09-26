<?php

define('PLUGIN_SCRUMBAN_VERSION', '1.0.0');

/**
 * Inicialização do plugin
 */
function plugin_init_scrumban() {
   global $PLUGIN_HOOKS;

   // Incluir arquivo de inicialização
   include_once(GLPI_ROOT . '/plugins/scrumban/init.php');

   $PLUGIN_HOOKS['csrf_compliant']['scrumban'] = true;
   
   // Menu principal
   $PLUGIN_HOOKS['menu_toadd']['scrumban'] = [
      'tools' => 'PluginScrumbanMenu'
   ];

   // CSS e JS
   $PLUGIN_HOOKS['add_css']['scrumban'] = [
      'css/scrumban.css'
   ];
   
   $PLUGIN_HOOKS['add_javascript']['scrumban'] = [
      'js/scrumban.js'
   ];

   // Hooks de item
   $PLUGIN_HOOKS['item_add']['scrumban'] = [
      'Ticket' => 'plugin_scrumban_item_add_ticket'
   ];

   $PLUGIN_HOOKS['item_update']['scrumban'] = [
      'Ticket' => 'plugin_scrumban_item_update_ticket'
   ];

   // Registrar classes
   Plugin::registerClass('PluginScrumbanBoard');
   Plugin::registerClass('PluginScrumbanCard');
   Plugin::registerClass('PluginScrumbanSprint');
   Plugin::registerClass('PluginScrumbanBacklog');
   Plugin::registerClass('PluginScrumbanColumn');
   Plugin::registerClass('PluginScrumbanComment');
   Plugin::registerClass('PluginScrumbanLabel');
   Plugin::registerClass('PluginScrumbanAttachment');
   Plugin::registerClass('PluginScrumbanMenu');
}

/**
 * Informações da versão do plugin
 */
function plugin_version_scrumban() {
   return [
      'name'           => 'Scrumban - Gestão Ágil',
      'version'        => PLUGIN_SCRUMBAN_VERSION,
      'author'         => 'Unitá Soluções Digitais',
      'license'        => 'GPL v2+',
      'homepage'       => '',
      'minGlpiVersion' => '10.0.0',
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

/**
 * Verificar se o plugin pode ser instalado
 */
function plugin_scrumban_check_config($verbose = false) {
   if (version_compare(GLPI_VERSION, '10.0.0', '<')) {
      if ($verbose) {
         echo "Este plugin requer GLPI versão 10.0.0 ou superior.";
      }
      return false;
   }
   
   return true;
}

/**
 * Verificar se os pré-requisitos estão atendidos
 */
function plugin_scrumban_check_prerequisites($verbose = false) {
   return plugin_scrumban_check_config($verbose);
}

/**
 * Função chamada quando um ticket é adicionado
 */
function plugin_scrumban_item_add_ticket($item) {
   // Integrar com cards do Scrumban se necessário
   return true;
}

/**
 * Função chamada quando um ticket é atualizado
 */
function plugin_scrumban_item_update_ticket($item) {
   // Sincronizar mudanças com cards do Scrumban se necessário
   return true;
}