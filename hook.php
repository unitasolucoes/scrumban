<?php

/**
 * Instalar o plugin
 */
function plugin_scrumban_install() {
   global $DB;

   // Tabela de quadros (boards)
   if (!$DB->tableExists("glpi_plugin_scrumban_boards")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_boards` (
         `id` int unsigned NOT NULL auto_increment,
         `name` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `is_active` tinyint NOT NULL default '1',
         `entities_id` int unsigned NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_active` (`is_active`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Tabela de colunas
   if (!$DB->tableExists("glpi_plugin_scrumban_columns")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_columns` (
         `id` int unsigned NOT NULL auto_increment,
         `boards_id` int unsigned NOT NULL,
         `name` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `position` int NOT NULL default '0',
         `wip_limit` int NOT NULL default '0',
         `color` varchar(7) collate utf8mb4_unicode_ci NOT NULL default '#6c757d',
         `is_active` tinyint NOT NULL default '1',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `boards_id` (`boards_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Tabela de cards
   if (!$DB->tableExists("glpi_plugin_scrumban_cards")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_cards` (
         `id` int unsigned NOT NULL auto_increment,
         `boards_id` int unsigned NOT NULL,
         `columns_id` int unsigned NOT NULL,
         `sprints_id` int unsigned NOT NULL default '0',
         `title` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `type` enum('story','task','bug','epic') NOT NULL default 'story',
         `priority` enum('trivial','menor','normal','maior','critico') NOT NULL default 'normal',
         `story_points` int NOT NULL default '0',
         `assignee` varchar(255) collate utf8mb4_unicode_ci,
         `reporter` varchar(255) collate utf8mb4_unicode_ci,
         `position` int NOT NULL default '0',
         `due_date` date NULL default NULL,
         `is_active` tinyint NOT NULL default '1',
         `tickets_id` int unsigned NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `boards_id` (`boards_id`),
         KEY `columns_id` (`columns_id`),
         KEY `sprints_id` (`sprints_id`),
         KEY `tickets_id` (`tickets_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Tabela de sprints
   if (!$DB->tableExists("glpi_plugin_scrumban_sprints")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_sprints` (
         `id` int unsigned NOT NULL auto_increment,
         `boards_id` int unsigned NOT NULL,
         `name` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `objective` text collate utf8mb4_unicode_ci,
         `start_date` date NULL default NULL,
         `end_date` date NULL default NULL,
         `status` enum('planejado','ativo','concluido') NOT NULL default 'planejado',
         `capacity` int NOT NULL default '0',
         `velocity` int NOT NULL default '0',
         `is_active` tinyint NOT NULL default '1',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `boards_id` (`boards_id`),
         KEY `status` (`status`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Tabela de labels
   if (!$DB->tableExists("glpi_plugin_scrumban_labels")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_labels` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `name` varchar(100) collate utf8mb4_unicode_ci NOT NULL default '',
         `color` varchar(7) collate utf8mb4_unicode_ci NOT NULL default '#007bff',
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Tabela de comentários
   if (!$DB->tableExists("glpi_plugin_scrumban_comments")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_comments` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `users_id` int unsigned NOT NULL,
         `content` text collate utf8mb4_unicode_ci NOT NULL,
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Tabela de anexos
   if (!$DB->tableExists("glpi_plugin_scrumban_attachments")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_attachments` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `filename` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `filepath` varchar(500) collate utf8mb4_unicode_ci NOT NULL default '',
         `filesize` int unsigned NOT NULL default '0',
         `mime_type` varchar(100) collate utf8mb4_unicode_ci,
         `users_id` int unsigned NOT NULL,
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Inserir colunas padrão para o primeiro quadro
   insertDefaultData($DB);

   return true;
}

/**
 * Desinstalar o plugin
 */
function plugin_scrumban_uninstall() {
   global $DB;

   $tables = [
      'glpi_plugin_scrumban_attachments',
      'glpi_plugin_scrumban_comments',
      'glpi_plugin_scrumban_labels',
      'glpi_plugin_scrumban_cards',
      'glpi_plugin_scrumban_sprints',
      'glpi_plugin_scrumban_columns',
      'glpi_plugin_scrumban_boards'
   ];

   foreach ($tables as $table) {
      if ($DB->tableExists($table)) {
         $DB->queryOrDie("DROP TABLE `$table`", $DB->error());
      }
   }

   return true;
}

/**
 * Inserir dados padrão
 */
function insertDefaultData($DB) {
   // Inserir quadro padrão
   $query = "INSERT INTO `glpi_plugin_scrumban_boards` 
             (`name`, `description`, `is_active`, `entities_id`, `date_creation`) 
             VALUES ('Sistema de E-commerce', 'Desenvolvimento da plataforma de vendas online', 1, 0, NOW())";
   $DB->queryOrDie($query, $DB->error());
   
   $board_id = $DB->insertId();

   // Inserir colunas padrão
   $columns = [
      ['Backlog', 0, '#6c757d'],
      ['Planejado', 1, '#17a2b8'],
      ['Em Execução', 2, '#ffc107'],
      ['Em Teste', 3, '#fd7e14'],
      ['Finalizado', 4, '#28a745']
   ];

   foreach ($columns as $index => $col) {
      $query = "INSERT INTO `glpi_plugin_scrumban_columns` 
                (`boards_id`, `name`, `position`, `color`, `wip_limit`, `date_creation`) 
                VALUES ($board_id, '{$col[0]}', {$col[1]}, '{$col[2]}', 0, NOW())";
      $DB->queryOrDie($query, $DB->error());
   }

   // Inserir sprint padrão
   $query = "INSERT INTO `glpi_plugin_scrumban_sprints` 
             (`boards_id`, `name`, `objective`, `start_date`, `end_date`, `status`, `capacity`, `date_creation`) 
             VALUES ($board_id, 'Sprint 1 - Funcionalidades Básicas', 'Implementar funcionalidades core do sistema: login, catálogo e carrinho.', '2024-01-14', '2024-01-28', 'ativo', 34, NOW())";
   $DB->queryOrDie($query, $DB->error());
   
   $sprint_id = $DB->insertId();

   // Inserir cards de exemplo
   $cards_data = [
      [
         'title' => 'Implementar sistema de login',
         'description' => 'Criar sistema de autenticação com email e senha para usuários da plataforma',
         'type' => 'story',
         'priority' => 'maior',
         'story_points' => 8,
         'assignee' => 'Maria Santos',
         'reporter' => 'João Silva',
         'column_pos' => 0
      ],
      [
         'title' => 'Configurar banco de dados',
         'description' => 'Setup inicial do MySQL com as tabelas principais',
         'type' => 'task',
         'priority' => 'normal',
         'story_points' => 3,
         'assignee' => 'Pedro Lima',
         'reporter' => 'João Silva',
         'column_pos' => 0
      ],
      [
         'title' => 'Sistema de pagamento',
         'description' => 'Integração com gateway de pagamento Stripe',
         'type' => 'story',
         'priority' => 'maior',
         'story_points' => 21,
         'assignee' => 'Roberto Franco',
         'reporter' => 'Ana Costa',
         'column_pos' => 2
      ]
   ];

   // Buscar IDs das colunas
   $result = $DB->request([
      'SELECT' => ['id', 'position'],
      'FROM' => 'glpi_plugin_scrumban_columns',
      'WHERE' => ['boards_id' => $board_id],
      'ORDER' => 'position'
   ]);
   
   $column_ids = [];
   foreach ($result as $row) {
      $column_ids[$row['position']] = $row['id'];
   }

   foreach ($cards_data as $index => $card) {
      $column_id = $column_ids[$card['column_pos']];
      $query = "INSERT INTO `glpi_plugin_scrumban_cards` 
                (`boards_id`, `columns_id`, `sprints_id`, `title`, `description`, `type`, `priority`, `story_points`, `assignee`, `reporter`, `position`, `date_creation`) 
                VALUES ($board_id, $column_id, $sprint_id, '{$card['title']}', '{$card['description']}', '{$card['type']}', '{$card['priority']}', {$card['story_points']}, '{$card['assignee']}', '{$card['reporter']}', $index, NOW())";
      $DB->queryOrDie($query, $DB->error());
   }
}