<?php

/**
 * Install the plugin
 */
function plugin_scrumban_install() {
   global $DB;

   // Create teams table
   if (!$DB->tableExists("glpi_plugin_scrumban_teams")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_teams` (
         `id` int unsigned NOT NULL auto_increment,
         `name` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `manager_id` int unsigned NOT NULL default '0',
         `is_active` tinyint NOT NULL default '1',
         `entities_id` int unsigned NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `manager_id` (`manager_id`),
         KEY `is_active` (`is_active`),
         KEY `name` (`name`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Create team members table
   if (!$DB->tableExists("glpi_plugin_scrumban_team_members")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_team_members` (
         `id` int unsigned NOT NULL auto_increment,
         `teams_id` int unsigned NOT NULL,
         `users_id` int unsigned NOT NULL,
         `role` enum('member','lead','admin') NOT NULL default 'member',
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `teams_users` (`teams_id`, `users_id`),
         KEY `teams_id` (`teams_id`),
         KEY `users_id` (`users_id`),
         KEY `role` (`role`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Create team boards association table
   if (!$DB->tableExists("glpi_plugin_scrumban_team_boards")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_team_boards` (
         `id` int unsigned NOT NULL auto_increment,
         `teams_id` int unsigned NOT NULL,
         `boards_id` int unsigned NOT NULL,
         `can_edit` tinyint NOT NULL default '1',
         `can_manage` tinyint NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `teams_boards` (`teams_id`, `boards_id`),
         KEY `teams_id` (`teams_id`),
         KEY `boards_id` (`boards_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Update boards table to support teams (if not already updated)
   $boards_fields = $DB->listFields("glpi_plugin_scrumban_boards");
   
   if (!isset($boards_fields['teams_id'])) {
      $query = "ALTER TABLE `glpi_plugin_scrumban_boards` 
                ADD COLUMN `teams_id` int unsigned NOT NULL default '0' AFTER `entities_id`,
                ADD COLUMN `visibility` enum('public','team','private') NOT NULL default 'public' AFTER `teams_id`,
                ADD COLUMN `users_id_created` int unsigned NOT NULL default '0' AFTER `visibility`,
                ADD KEY `teams_id` (`teams_id`),
                ADD KEY `visibility` (`visibility`),
                ADD KEY `users_id_created` (`users_id_created`)";
      $DB->queryOrDie($query, $DB->error());
   }

   // Create boards table if it doesn't exist
   if (!$DB->tableExists("glpi_plugin_scrumban_boards")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_boards` (
         `id` int unsigned NOT NULL auto_increment,
         `name` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `entities_id` int unsigned NOT NULL default '0',
         `teams_id` int unsigned NOT NULL default '0',
         `visibility` enum('public','team','private') NOT NULL default 'public',
         `users_id_created` int unsigned NOT NULL default '0',
         `is_active` tinyint NOT NULL default '1',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `teams_id` (`teams_id`),
         KEY `visibility` (`visibility`),
         KEY `users_id_created` (`users_id_created`),
         KEY `is_active` (`is_active`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Create columns table
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
         KEY `boards_id` (`boards_id`),
         KEY `position` (`position`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Create cards table
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
         `external_reference` varchar(255) collate utf8mb4_unicode_ci,
         `position` int NOT NULL default '0',
         `due_date` date NULL default NULL,
         `planned_delivery_date` date NULL default NULL,
         `completed_at` datetime NULL default NULL,
         `is_active` tinyint NOT NULL default '1',
         `tickets_id` int unsigned NOT NULL default '0',
         `development_branch` varchar(255) collate utf8mb4_unicode_ci,
         `development_pull_request` varchar(255) collate utf8mb4_unicode_ci,
         `development_commits` text collate utf8mb4_unicode_ci,
         `dor_percent` tinyint unsigned NOT NULL default '0',
         `dod_percent` tinyint unsigned NOT NULL default '0',
         `acceptance_criteria` longtext collate utf8mb4_unicode_ci,
         `test_scenarios` longtext collate utf8mb4_unicode_ci,
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `boards_id` (`boards_id`),
         KEY `columns_id` (`columns_id`),
         KEY `sprints_id` (`sprints_id`),
         KEY `tickets_id` (`tickets_id`),
         KEY `assignee` (`assignee`),
         KEY `type` (`type`),
         KEY `priority` (`priority`),
         KEY `planned_delivery_date` (`planned_delivery_date`),
         KEY `completed_at` (`completed_at`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";

      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists("glpi_plugin_scrumban_card_criteria")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_card_criteria` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `title` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `is_completed` tinyint NOT NULL default '0',
         `position` int NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `is_completed` (`is_completed`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";

      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists("glpi_plugin_scrumban_card_test_scenarios")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_card_test_scenarios` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `title` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `expected_result` text collate utf8mb4_unicode_ci,
         `status` enum('pending','passed','failed') NOT NULL default 'pending',
         `position` int NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `status` (`status`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";

      $DB->queryOrDie($query, $DB->error());
   }

   // Create sprints table
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
         KEY `status` (`status`),
         KEY `dates` (`start_date`, `end_date`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Create labels table
   if (!$DB->tableExists("glpi_plugin_scrumban_labels")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_labels` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `name` varchar(100) collate utf8mb4_unicode_ci NOT NULL default '',
         `color` varchar(7) collate utf8mb4_unicode_ci NOT NULL default '#007bff',
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `name` (`name`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }

   // Create comments table
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

   // Create attachments table
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

   // Check if this is an upgrade from v1.0 or a fresh install
   $needs_migration = checkIfNeedsMigration($DB);
   
   if ($needs_migration) {
      performMigrationToTeams($DB);
   } else {
      insertDefaultData($DB);
   }

   return true;
}

/**
 * Uninstall the plugin
 */
function plugin_scrumban_uninstall() {
   global $DB;

   $tables = [
      'glpi_plugin_scrumban_team_boards',
      'glpi_plugin_scrumban_team_members',
      'glpi_plugin_scrumban_teams',
      'glpi_plugin_scrumban_attachments',
      'glpi_plugin_scrumban_comments',
      'glpi_plugin_scrumban_labels',
      'glpi_plugin_scrumban_card_test_scenarios',
      'glpi_plugin_scrumban_card_criteria',
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
 * Check if we need to migrate from v1.0 to v2.0
 */
function checkIfNeedsMigration($DB) {
   // If teams table doesn't exist but boards table does, it's v1.0
   return $DB->tableExists("glpi_plugin_scrumban_boards") && 
          !$DB->tableExists("glpi_plugin_scrumban_teams");
}

/**
 * Perform migration from v1.0 to v2.0 with teams
 */
function performMigrationToTeams($DB) {
   // Create default team
   $team_query = "INSERT INTO `glpi_plugin_scrumban_teams` 
                 (`name`, `description`, `manager_id`, `is_active`, `entities_id`, `date_creation`) 
                 VALUES ('Default Team', 'Default team for existing boards', 0, 1, 0, NOW())";
   $DB->queryOrDie($team_query, $DB->error());
   
   $default_team_id = $DB->insertId();

   // Get all existing boards and associate with default team
   $boards_iterator = $DB->request([
      'FROM' => 'glpi_plugin_scrumban_boards'
   ]);

   foreach ($boards_iterator as $board) {
      // Update board with default team
      $DB->update('glpi_plugin_scrumban_boards', [
         'teams_id' => $default_team_id,
         'visibility' => 'team',
         'users_id_created' => $board['entities_id'] // Use entity as fallback
      ], [
         'id' => $board['id']
      ]);

      // Associate board with default team
      $DB->insert('glpi_plugin_scrumban_team_boards', [
         'teams_id' => $default_team_id,
         'boards_id' => $board['id'],
         'can_edit' => 1,
         'can_manage' => 1,
         'date_creation' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
      ]);
   }

   // Add all active users as members of default team
   $users_iterator = $DB->request([
      'SELECT' => 'id',
      'FROM' => 'glpi_users',
      'WHERE' => ['is_active' => 1],
      'LIMIT' => 50 // Limit to avoid too many members
   ]);

   $first_user = true;
   foreach ($users_iterator as $user) {
      $role = $first_user ? 'admin' : 'member';
      
      $DB->insert('glpi_plugin_scrumban_team_members', [
         'teams_id' => $default_team_id,
         'users_id' => $user['id'],
         'role' => $role,
         'date_creation' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
      ]);
      
      $first_user = false;
   }
}

/**
 * Insert default data for fresh installation
 */
function insertDefaultData($DB) {
   // Create development team
   $team_query = "INSERT INTO `glpi_plugin_scrumban_teams` 
                 (`name`, `description`, `manager_id`, `is_active`, `entities_id`, `date_creation`) 
                 VALUES ('Development Team', 'Main development team for e-commerce project', 0, 1, 0, NOW())";
   $DB->queryOrDie($team_query, $DB->error());
   
   $team_id = $DB->insertId();

   // Insert default board
   $board_query = "INSERT INTO `glpi_plugin_scrumban_boards` 
                  (`name`, `description`, `teams_id`, `visibility`, `users_id_created`, `is_active`, `entities_id`, `date_creation`) 
                  VALUES ('E-commerce System', 'Development of online sales platform', $team_id, 'team', 0, 1, 0, NOW())";
   $DB->queryOrDie($board_query, $DB->error());
   
   $board_id = $DB->insertId();

   // Associate team with board
   $DB->insert('glpi_plugin_scrumban_team_boards', [
      'teams_id' => $team_id,
      'boards_id' => $board_id,
      'can_edit' => 1,
      'can_manage' => 1,
      'date_creation' => date('Y-m-d H:i:s')
   ]);

   // Insert default columns
   $columns = [
      ['Backlog', 0, '#6c757d'],
      ['Planned', 1, '#17a2b8'],
      ['In Progress', 2, '#ffc107'],
      ['Testing', 3, '#fd7e14'],
      ['Done', 4, '#28a745']
   ];

   foreach ($columns as $index => $col) {
      $column_query = "INSERT INTO `glpi_plugin_scrumban_columns` 
                      (`boards_id`, `name`, `position`, `color`, `wip_limit`, `date_creation`) 
                      VALUES ($board_id, '{$col[0]}', {$col[1]}, '{$col[2]}', 0, NOW())";
      $DB->queryOrDie($column_query, $DB->error());
   }

   // Insert default sprint
   $sprint_query = "INSERT INTO `glpi_plugin_scrumban_sprints` 
                   (`boards_id`, `name`, `objective`, `start_date`, `end_date`, `status`, `capacity`, `date_creation`) 
                   VALUES ($board_id, 'Sprint 1 - Core Features', 'Implement core system functionality: authentication, catalog, and cart.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'ativo', 34, NOW())";
   $DB->queryOrDie($sprint_query, $DB->error());
   
   $sprint_id = $DB->insertId();

   // Get column IDs for card insertion
   $columns_result = $DB->request([
      'SELECT' => ['id', 'position'],
      'FROM' => 'glpi_plugin_scrumban_columns',
      'WHERE' => ['boards_id' => $board_id],
      'ORDER' => 'position'
   ]);
   
   $column_ids = [];
   foreach ($columns_result as $row) {
      $column_ids[$row['position']] = $row['id'];
   }

   // Insert sample cards
   $cards_data = [
      [
         'title' => 'Implement user authentication system',
         'description' => 'Create secure login system with email and password for platform users',
         'type' => 'story',
         'priority' => 'maior',
         'story_points' => 8,
         'assignee' => 'Maria Santos',
         'reporter' => 'João Silva',
         'column_pos' => 0
      ],
      [
         'title' => 'Setup database schema',
         'description' => 'Initial MySQL setup with core tables',
         'type' => 'task',
         'priority' => 'normal',
         'story_points' => 3,
         'assignee' => 'Pedro Lima',
         'reporter' => 'João Silva',
         'column_pos' => 1
      ],
      [
         'title' => 'Payment gateway integration',
         'description' => 'Integrate with Stripe payment gateway',
         'type' => 'story',
         'priority' => 'maior',
         'story_points' => 21,
         'assignee' => 'Roberto Franco',
         'reporter' => 'Ana Costa',
         'column_pos' => 2
      ],
      [
         'title' => 'Fix login validation bug',
         'description' => 'Users can bypass email validation on login form',
         'type' => 'bug',
         'priority' => 'critico',
         'story_points' => 2,
         'assignee' => 'Maria Santos',
         'reporter' => 'QA Team',
         'column_pos' => 2
      ]
   ];

   foreach ($cards_data as $index => $card) {
      $column_id = $column_ids[$card['column_pos']];
      $card_query = "INSERT INTO `glpi_plugin_scrumban_cards` 
                    (`boards_id`, `columns_id`, `sprints_id`, `title`, `description`, `type`, `priority`, `story_points`, `assignee`, `reporter`, `position`, `date_creation`) 
                    VALUES ($board_id, $column_id, $sprint_id, '" . addslashes($card['title']) . "', '" . addslashes($card['description']) . "', '{$card['type']}', '{$card['priority']}', {$card['story_points']}, '" . addslashes($card['assignee']) . "', '" . addslashes($card['reporter']) . "', $index, NOW())";
      $DB->queryOrDie($card_query, $DB->error());
      
      // Add some sample labels to cards
      $card_id = $DB->insertId();
      
      if ($card['type'] == 'bug') {
         $DB->insert('glpi_plugin_scrumban_labels', [
            'cards_id' => $card_id,
            'name' => 'critical',
            'color' => '#dc3545',
            'date_creation' => date('Y-m-d H:i:s')
         ]);
      } elseif ($card['type'] == 'story') {
         $DB->insert('glpi_plugin_scrumban_labels', [
            'cards_id' => $card_id,
            'name' => 'feature',
            'color' => '#28a745',
            'date_creation' => date('Y-m-d H:i:s')
         ]);
      }
   }

   // Create QA team
   $qa_team_query = "INSERT INTO `glpi_plugin_scrumban_teams` 
                    (`name`, `description`, `manager_id`, `is_active`, `entities_id`, `date_creation`) 
                    VALUES ('QA Team', 'Quality assurance and testing team', 0, 1, 0, NOW())";
   $DB->queryOrDie($qa_team_query, $DB->error());
   
   $qa_team_id = $DB->insertId();

   // Associate QA team with board (read-only access)
   $DB->insert('glpi_plugin_scrumban_team_boards', [
      'teams_id' => $qa_team_id,
      'boards_id' => $board_id,
      'can_edit' => 0,
      'can_manage' => 0,
      'date_creation' => date('Y-m-d H:i:s')
   ]);

   // Add current user as admin of development team (if available)
   if (isset($_SESSION['glpiID']) && $_SESSION['glpiID'] > 0) {
      $DB->insert('glpi_plugin_scrumban_team_members', [
         'teams_id' => $team_id,
         'users_id' => $_SESSION['glpiID'],
         'role' => 'admin',
         'date_creation' => date('Y-m-d H:i:s')
      ]);
   }
}

/**
 * Update database schema for version 2.0
 */
function plugin_scrumban_update_200() {
   global $DB;

   $migration = new Migration(200);
   
   // Teams table
   if (!$DB->tableExists("glpi_plugin_scrumban_teams")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_teams` (
         `id` int unsigned NOT NULL auto_increment,
         `name` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `manager_id` int unsigned NOT NULL default '0',
         `is_active` tinyint NOT NULL default '1',
         `entities_id` int unsigned NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `manager_id` (`manager_id`),
         KEY `is_active` (`is_active`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }
   
   // Team members table
   if (!$DB->tableExists("glpi_plugin_scrumban_team_members")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_team_members` (
         `id` int unsigned NOT NULL auto_increment,
         `teams_id` int unsigned NOT NULL,
         `users_id` int unsigned NOT NULL,
         `role` enum('member','lead','admin') NOT NULL default 'member',
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `teams_users` (`teams_id`, `users_id`),
         KEY `teams_id` (`teams_id`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }
   
   // Team boards table
   if (!$DB->tableExists("glpi_plugin_scrumban_team_boards")) {
      $query = "CREATE TABLE `glpi_plugin_scrumban_team_boards` (
         `id` int unsigned NOT NULL auto_increment,
         `teams_id` int unsigned NOT NULL,
         `boards_id` int unsigned NOT NULL,
         `can_edit` tinyint NOT NULL default '1',
         `can_manage` tinyint NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `teams_boards` (`teams_id`, `boards_id`),
         KEY `teams_id` (`teams_id`),
         KEY `boards_id` (`boards_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }
   
   // Update boards table
   $boards_fields = $DB->listFields("glpi_plugin_scrumban_boards");
   
   if (!isset($boards_fields['teams_id'])) {
      $migration->addField("glpi_plugin_scrumban_boards", "teams_id", "int unsigned NOT NULL default '0'");
      $migration->addField("glpi_plugin_scrumban_boards", "visibility", "enum('public','team','private') NOT NULL default 'public'");
      $migration->addField("glpi_plugin_scrumban_boards", "users_id_created", "int unsigned NOT NULL default '0'");
      
      $migration->addKey("glpi_plugin_scrumban_boards", "teams_id");
      $migration->addKey("glpi_plugin_scrumban_boards", "visibility");
      $migration->addKey("glpi_plugin_scrumban_boards", "users_id_created");
   }
   
   $migration->executeMigration();
   
   // Perform data migration
   performMigrationToTeams($DB);
   
   return true;
}

function plugin_scrumban_update_201() {
   global $DB;

   $migration = new Migration(201);

   if ($DB->tableExists('glpi_plugin_scrumban_cards')) {
      $cards_fields = $DB->listFields('glpi_plugin_scrumban_cards');

      if (!isset($cards_fields['external_reference'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'external_reference', "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
      }

      if (!isset($cards_fields['planned_delivery_date'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'planned_delivery_date', 'date DEFAULT NULL');
      }

      if (!isset($cards_fields['completed_at'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'completed_at', 'datetime DEFAULT NULL');
      }

      if (!isset($cards_fields['development_branch'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'development_branch', "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
      }

      if (!isset($cards_fields['development_pull_request'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'development_pull_request', "varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
      }

      if (!isset($cards_fields['development_commits'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'development_commits', "text COLLATE utf8mb4_unicode_ci");
      }

      if (!isset($cards_fields['dor_percent'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'dor_percent', "tinyint unsigned NOT NULL DEFAULT '0'");
      }

      if (!isset($cards_fields['dod_percent'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'dod_percent', "tinyint unsigned NOT NULL DEFAULT '0'");
      }

      if (!isset($cards_fields['acceptance_criteria'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'acceptance_criteria', "longtext COLLATE utf8mb4_unicode_ci");
      }

      if (!isset($cards_fields['test_scenarios'])) {
         $migration->addField('glpi_plugin_scrumban_cards', 'test_scenarios', "longtext COLLATE utf8mb4_unicode_ci");
      }

      $migration->addKey('glpi_plugin_scrumban_cards', ['planned_delivery_date']);
      $migration->addKey('glpi_plugin_scrumban_cards', ['completed_at']);
   }

   $migration->executeMigration();

   if (!$DB->tableExists('glpi_plugin_scrumban_card_criteria')) {
      $DB->queryOrDie("CREATE TABLE `glpi_plugin_scrumban_card_criteria` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `title` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `is_completed` tinyint NOT NULL default '0',
         `position` int NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `is_completed` (`is_completed`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;", $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_scrumban_card_test_scenarios')) {
      $DB->queryOrDie("CREATE TABLE `glpi_plugin_scrumban_card_test_scenarios` (
         `id` int unsigned NOT NULL auto_increment,
         `cards_id` int unsigned NOT NULL,
         `title` varchar(255) collate utf8mb4_unicode_ci NOT NULL default '',
         `description` text collate utf8mb4_unicode_ci,
         `expected_result` text collate utf8mb4_unicode_ci,
         `status` enum('pending','passed','failed') NOT NULL default 'pending',
         `position` int NOT NULL default '0',
         `date_creation` timestamp NULL default NULL,
         `date_mod` timestamp NULL default NULL,
         PRIMARY KEY (`id`),
         KEY `cards_id` (`cards_id`),
         KEY `status` (`status`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;", $DB->error());
   }

   plugin_scrumban_create_indexes();

   return true;
}

/**
 * Create database indexes for performance
 */
function plugin_scrumban_create_indexes() {
   global $DB;
   
   $indexes = [
      'glpi_plugin_scrumban_teams' => [
         'name' => 'name',
         'manager_active' => ['manager_id', 'is_active']
      ],
      'glpi_plugin_scrumban_team_members' => [
         'user_role' => ['users_id', 'role'],
         'team_role' => ['teams_id', 'role']
      ],
      'glpi_plugin_scrumban_team_boards' => [
         'permissions' => ['can_edit', 'can_manage'],
         'team_permissions' => ['teams_id', 'can_edit', 'can_manage']
      ],
      'glpi_plugin_scrumban_boards' => [
         'team_visibility' => ['teams_id', 'visibility'],
         'creator_active' => ['users_id_created', 'is_active']
      ],
      'glpi_plugin_scrumban_cards' => [
         'board_sprint' => ['boards_id', 'sprints_id'],
         'assignee_active' => ['assignee', 'is_active'],
         'type_priority' => ['type', 'priority'],
         'plan_dates' => ['planned_delivery_date', 'completed_at']
      ],
      'glpi_plugin_scrumban_card_criteria' => [
         'card_state' => ['cards_id', 'is_completed']
      ],
      'glpi_plugin_scrumban_card_test_scenarios' => [
         'card_status' => ['cards_id', 'status']
      ]
   ];
   
   foreach ($indexes as $table => $table_indexes) {
      if ($DB->tableExists($table)) {
         foreach ($table_indexes as $index_name => $columns) {
            $columns_str = is_array($columns) ? implode(', ', $columns) : $columns;
            
            // Check if index exists
            $existing_indexes = $DB->query("SHOW INDEX FROM $table WHERE Key_name = '$index_name'");
            
            if ($existing_indexes->num_rows == 0) {
               $DB->query("ALTER TABLE $table ADD INDEX $index_name ($columns_str)");
            }
         }
      }
   }
}