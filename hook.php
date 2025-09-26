<?php

if (!defined('PLUGIN_SCRUMBAN_VERSION')) {
   require_once __DIR__ . '/setup.php';
}

function plugin_scrumban_install() {
   global $DB;

   include_once GLPI_ROOT . '/inc/migration.class.php';

   $migration = new Migration(PLUGIN_SCRUMBAN_VERSION);
   plugin_scrumban_define_schema($migration, $DB);
   $migration->executeMigration();

   plugin_scrumban_seed_defaults();

   return true;
}

function plugin_scrumban_uninstall() {
   global $DB;

   foreach ([
      'glpi_plugin_scrumban_team_boards',
      'glpi_plugin_scrumban_team_members',
      'glpi_plugin_scrumban_cards',
      'glpi_plugin_scrumban_sprints',
      'glpi_plugin_scrumban_boards',
      'glpi_plugin_scrumban_teams'
   ] as $table) {
      if ($DB->tableExists($table)) {
         $DB->queryOrDie("DROP TABLE `$table`", $DB->error());
      }
   }

   return true;
}

function plugin_scrumban_define_schema(Migration $migration, DBmysql $DB) {
   if (!$DB->tableExists('glpi_plugin_scrumban_teams')) {
      $migration->addSql("CREATE TABLE `glpi_plugin_scrumban_teams` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `name` varchar(255) NOT NULL,
         `description` text,
         `manager_id` int(11) DEFAULT NULL,
         `is_active` tinyint(1) DEFAULT 1,
         `entities_id` int(11) DEFAULT NULL,
         `is_recursive` tinyint(1) DEFAULT 0,
         `date_creation` datetime DEFAULT NULL,
         `date_mod` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `manager_id` (`manager_id`),
         KEY `entities_id` (`entities_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
   }

   if (!$DB->tableExists('glpi_plugin_scrumban_team_members')) {
      $migration->addSql("CREATE TABLE `glpi_plugin_scrumban_team_members` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `teams_id` int(11) NOT NULL,
         `users_id` int(11) NOT NULL,
         `role` enum('member','lead','admin') DEFAULT 'member',
         `date_creation` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `unique_team_user` (`teams_id`, `users_id`),
         KEY `teams_id` (`teams_id`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
   }

   if (!$DB->tableExists('glpi_plugin_scrumban_boards')) {
      $migration->addSql("CREATE TABLE `glpi_plugin_scrumban_boards` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `name` varchar(255) NOT NULL,
         `description` text,
         `teams_id` int(11) DEFAULT NULL,
         `visibility` enum('public','team','private') DEFAULT 'team',
         `users_id_created` int(11) DEFAULT NULL,
         `entities_id` int(11) DEFAULT NULL,
         `is_recursive` tinyint(1) DEFAULT 0,
         `is_active` tinyint(1) DEFAULT 1,
         `date_creation` datetime DEFAULT NULL,
         `date_mod` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `teams_id` (`teams_id`),
         KEY `users_id_created` (`users_id_created`),
         KEY `entities_id` (`entities_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
   }

   if (!$DB->tableExists('glpi_plugin_scrumban_team_boards')) {
      $migration->addSql("CREATE TABLE `glpi_plugin_scrumban_team_boards` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `teams_id` int(11) NOT NULL,
         `boards_id` int(11) NOT NULL,
         `can_edit` tinyint(1) DEFAULT 1,
         `can_manage` tinyint(1) DEFAULT 0,
         `date_creation` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `unique_team_board` (`teams_id`, `boards_id`),
         KEY `teams_id` (`teams_id`),
         KEY `boards_id` (`boards_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
   }

   if (!$DB->tableExists('glpi_plugin_scrumban_cards')) {
      $migration->addSql("CREATE TABLE `glpi_plugin_scrumban_cards` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `boards_id` int(11) NOT NULL,
         `name` varchar(255) NOT NULL,
         `description` text,
         `status` enum('backlog','todo','em-execucao','review','done') DEFAULT 'backlog',
         `type` enum('feature','bug','task','story') DEFAULT 'task',
         `priority` enum('LOW','NORMAL','HIGH','CRITICAL') DEFAULT 'NORMAL',
         `story_points` int(11) DEFAULT NULL,
         `users_id_assigned` int(11) DEFAULT NULL,
         `users_id_requester` int(11) DEFAULT NULL,
         `users_id_created` int(11) DEFAULT NULL,
         `date_planned` datetime DEFAULT NULL,
         `date_completion` datetime DEFAULT NULL,
         `sprint_id` int(11) DEFAULT NULL,
         `labels` text,
         `acceptance_criteria` text,
         `test_scenarios` text,
         `branch` varchar(255) DEFAULT NULL,
         `pull_request` varchar(255) DEFAULT NULL,
         `commits` text,
         `dor_percentage` int(11) DEFAULT 0,
         `dod_percentage` int(11) DEFAULT 0,
         `entities_id` int(11) DEFAULT NULL,
         `is_recursive` tinyint(1) DEFAULT 0,
         `date_creation` datetime DEFAULT NULL,
         `date_mod` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `boards_id` (`boards_id`),
         KEY `users_id_assigned` (`users_id_assigned`),
         KEY `users_id_requester` (`users_id_requester`),
         KEY `users_id_created` (`users_id_created`),
         KEY `sprint_id` (`sprint_id`),
         KEY `entities_id` (`entities_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
   }

   if (!$DB->tableExists('glpi_plugin_scrumban_sprints')) {
      $migration->addSql("CREATE TABLE `glpi_plugin_scrumban_sprints` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `boards_id` int(11) NOT NULL,
         `name` varchar(255) NOT NULL,
         `description` text,
         `date_start` datetime DEFAULT NULL,
         `date_end` datetime DEFAULT NULL,
         `is_active` tinyint(1) DEFAULT 0,
         `entities_id` int(11) DEFAULT NULL,
         `is_recursive` tinyint(1) DEFAULT 0,
         `date_creation` datetime DEFAULT NULL,
         `date_mod` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `boards_id` (`boards_id`),
         KEY `entities_id` (`entities_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
   }
}

function plugin_scrumban_seed_defaults() {
   global $DB;

   if (!$DB->tableExists('glpi_plugin_scrumban_teams')) {
      return;
   }

   $count = (int)$DB->query("SELECT COUNT(*) AS c FROM glpi_plugin_scrumban_teams")->fetch_assoc()['c'];
   if ($count > 0) {
      return;
   }

   $now = date('Y-m-d H:i:s');
   $user_id = Session::getLoginUserID(false) ?: 0;

   $DB->insert('glpi_plugin_scrumban_teams', [
      'name' => __('Equipe Padrão', 'scrumban'),
      'description' => __('Equipe criada automaticamente durante a instalação do plugin.', 'scrumban'),
      'manager_id' => $user_id,
      'is_active' => 1,
      'entities_id' => 0,
      'is_recursive' => 1,
      'date_creation' => $now,
      'date_mod' => $now
   ]);

   $team_id = (int)$DB->insertId();

   if ($team_id) {
      $DB->insert('glpi_plugin_scrumban_team_members', [
         'teams_id' => $team_id,
         'users_id' => $user_id,
         'role' => 'admin',
         'date_creation' => $now
      ]);

      $DB->insert('glpi_plugin_scrumban_boards', [
         'name' => __('Projeto Exemplo', 'scrumban'),
         'description' => __('Quadro inicial com colunas padrão backlog, todo, em-execucao, review e done.', 'scrumban'),
         'teams_id' => $team_id,
         'visibility' => 'team',
         'users_id_created' => $user_id,
         'entities_id' => 0,
         'is_recursive' => 1,
         'is_active' => 1,
         'date_creation' => $now,
         'date_mod' => $now
      ]);

      $board_id = (int)$DB->insertId();

      if ($board_id) {
         $DB->insert('glpi_plugin_scrumban_team_boards', [
            'teams_id' => $team_id,
            'boards_id' => $board_id,
            'can_edit' => 1,
            'can_manage' => 1,
            'date_creation' => $now
         ]);
      }
   }
}
